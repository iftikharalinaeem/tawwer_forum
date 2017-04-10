<?php
/**
 *
 * Changes:
 *  1.0     Release
 *  1.2.3   Allow ReactionModel() to react from any source user.
 *  1.2.4   Allow some reactions to be protected so that users can't flag moderator posts.
 *  1.2.13  Added TagModel_Types_Handler.
 *  1.3     Add class permissions; fix GetReactionTypes attributes; fix descriptions.
 *  1.2.15  Add section 508 fixes.
 *  1.4.0   Add support for merging users' reactions.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Reactions'] = [
    'Name' => 'Reactions',
    'Description' => "Adds reaction options to discussions & comments.",
    'Version' => '1.4.4',
    'RequiredApplications' => ['Vanilla' => '2.1'],
    'RegisterPermissions' => [
        'Reactions.Positive.Add' => 'Garden.SignIn.Allow',
        'Reactions.Negative.Add' => 'Garden.SignIn.Allow',
        'Reactions.Flag.Add' => 'Garden.SignIn.Allow'
    ],
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'MobileFriendly' => true,
    'SettingsUrl' => '/reactions/settings',
    'Icon' => 'reactions.png'
];

/**
 * Class ReactionsPlugin
 */
class ReactionsPlugin extends Gdn_Plugin {

    const RECORD_REACTIONS_DEFAULT = 'popup';

    const BEST_OF_MAX_PAGES = 300;
    /** @var array */

    protected static $_CommentOrder;

    /** @var array Get the user's preference for comment sorting (if enabled). */
    protected static $_CommentSort;

    /**
     * Include ReactionsController for /reactions requests
     *
     * Manually detect and include reactions controller when a request comes in
     * that probably uses it.
     *
     * @param Gdn_Dispatcher $Sender
     */
    public function gdn_dispatcher_beforeDispatch_handler($Sender, $Args) {
        if (!isset($Args['Request'])) {
            return;
        }

        $Path = $Args['Request']->path();
        if (preg_match('`^/?reactions`i', $Path)) {
            require_once($this->getResource('controllers/class.reactionscontroller.php'));
        }
    }

    /**
     * Add content from a reaction to the promoted content module.
     *
     * @param PromotedContentModule $sender
     */
    public function promotedContentModule_selectByReaction_handler($sender) {
        $model = new ReactionModel();
        $reactionType = ReactionModel::reactionTypes($sender->Selection);

        if (!$reactionType) {
            return;
        }

        $data = $model->getRecordsWhere(
            array('TagID' => $reactionType['TagID'], 'RecordType' => array('Discussion-Total', 'Comment-Total'), 'Total >=' => 1),
            'DateInserted', 'desc',
            $sender->Limit, 0);

        // Massage the data for the promoted content module.
        foreach ($data as &$row) {
            $row['ItemType'] = $row['RecordType'];
            $row['Author'] = Gdn::userModel()->getID($row['InsertUserID']);
        }

        $sender->setData('Content', $data);
    }

    /**
     * Add mapper methods.
     *
     * @param SimpleApiPlugin $sender
     */
    public function simpleApiPlugin_mapper_handler($sender) {
        switch ($sender->Mapper->Version) {
            case '1.0':
                $sender->Mapper->addMap(array(
                    'reactions/list' => 'reactions',
                    'reactions/get' => 'reactions/get',
                    'reactions/add' => 'reactions/add',
                    'reactions/edit' => 'reactions/edit',
                    'reactions/toggle' => 'reactions/toggle'
                ));
                break;
        }
    }

    /**
     *
     *
     * @param $sender
     */
    private function addJs($sender) {
        $sender->AddJsFile('jquery-ui.js');
        $sender->AddJsFile('reactions.js', 'plugins/Reactions');
    }

    /**
     *
     *
     * @return array
     */
    public static function commentOrder() {
        if (!self::$_CommentOrder) {
            $SetPreference = false;

            if (!Gdn::session()->isValid()) {
                if (Gdn::controller() != null && strcasecmp(Gdn::controller()->RequestMethod, 'embed') == 0) {
                    $OrderColumn = c('Plugins.Reactions.DefaultEmbedOrderBy', 'Score');
                } else {
                    $OrderColumn = c('Plugins.Reactions.DefaultOrderBy', 'DateInserted');
                }
            } else {
                $DefaultOrderParts = array('DateInserted', 'asc');

                $OrderBy = Gdn::request()->get('orderby', '');
                if ($OrderBy) {
                    $SetPreference = true;
                } else {
                    $OrderBy = Gdn::session()->getPreference('Comments.OrderBy');
                }
                $OrderParts = explode(' ', $OrderBy);
                $OrderColumn = GetValue(0, $OrderParts, $DefaultOrderParts[0]);

                // Make sure the order is correct.
                if (!in_array($OrderColumn, array('DateInserted', 'Score')))
                    $OrderColumn = 'DateInserted';


                if ($SetPreference) {
                    Gdn::session()->setPreference('Comments.OrderBy', $OrderColumn);
                }
            }
            $OrderDirection = $OrderColumn == 'Score' ? 'desc' : 'asc';

            $CommentOrder = array('c.'.$OrderColumn.' '.$OrderDirection);

            // Add a unique order if we aren't ordering by a unique column.
            if (!in_array($OrderColumn, array('DateInserted', 'CommentID'))) {
                $CommentOrder[] = 'c.DateInserted asc';
            }

            self::$_CommentOrder = $CommentOrder;
        }

        return self::$_CommentOrder;
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Database updates.
     */
    public function structure() {
        include dirname(__FILE__).'/structure.php';
    }

    /**
     *
     *
     * @param $sender
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('reactions.css', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param ActivityController $sender
     */
    public function activityController_render_before($sender) {
        if ($sender->deliveryMethod() == DELIVERY_METHOD_XHTML || $sender->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->addJs($sender);
            include_once $sender->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
        }
    }

    /**
     * Adds items to Dashboard menu.
     *
     * @since 1.0.0
     * @param DashboardController $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $Menu = $sender->EventArguments['SideMenu'];
        $Menu->addLink('Forum', t('Reactions'), 'reactions', 'Garden.Community.Manage', array('class' => 'nav-reactions'));
    }

    /**
     * New Html method of adding to discussion filters.
     *
     * @param Gdn_Controller $sender
     */
    public function base_afterDiscussionFilters_handler($sender) {
        echo '<li class="Reactions-BestOf">'.anchor(sprite('SpBestOf').' '.t('Best Of...'), '/bestof/everything', '').'</li>';
    }

    /**
     *
     *
     * @param Gdn_Controller $Sender
     */
    public function discussionController_render_before($Sender) {
        $Sender->ReactionsVersion = 2;

        $OrderBy = self::commentOrder();
        list($OrderColumn, $OrderDirection) = explode(' ', val('0', $OrderBy));
        $OrderColumn = stringBeginsWith($OrderColumn, 'c.', true, true);

        // Send back comment order for non-api calls.
        if ($Sender->deliveryType() !== DELIVERY_TYPE_DATA) {
            $Sender->setData('CommentOrder', array('Column' => $OrderColumn, 'Direction' => $OrderDirection));
        }

        if ($Sender->ReactionsVersion != 1) {
            $this->addJs($Sender);
        }

        $ReactionModel = new ReactionModel();
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            $ReactionModel->JoinUserTags($Sender->Data['Discussion'], 'Discussion');
            $ReactionModel->JoinUserTags($Sender->Data['Comments'], 'Comment');

            if (isset($Sender->Data['Answers'])) {
                $ReactionModel->JoinUserTags($Sender->Data['Answers'], 'Comment');
            }
        }

        include_once $Sender->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function commentModel_beforeUpdateCommentCount_handler($Sender, $Args) {
        if (!isset($Args['Discussion'])) {
            return;
        }

        // A discussion with a low score counts as sunk.
        $Discussion =& $Args['Discussion'];
        if ((int)val('Score', $Discussion) <= -5) {
            Gdn::controller()->setData('Score', val('Score', $Discussion));
            setValue('Sink', $Discussion, true);
        }
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_beforeCommentRender_handler($Sender) {
        include_once $Sender->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
    }
    /**

     *
     *
     * @param $Sender
     * @param $Args
     * @throws Exception
     */
    public function base_afterUserInfo_handler($Sender, $Args) {
        // Fetch the view helper functions.
        include_once Gdn::controller()->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
        echo '<div class="ReactionsWrap">';
        echo '<h2 class="H">'.t('Reactions').'</h2>';
        writeProfileCounts();
        echo '</div>';
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function base_beforeCommentDisplay_handler($Sender, $Args) {
        $CssClass = ScoreCssClass($Args['Object']);
        if ($CssClass) {
            $Args['CssClass'] .= ' '.$CssClass;
            SetValue('_CssClass', $Args['Object'], $CssClass);
        }
    }

    /**
     * Show user's reacted-to content by reaction type.
     *
     * @param ProfileController $Sender Duh.
     * @param string|int $UserReference A username or userid.
     * @param string $Username
     * @param string $Reaction Which reaction is selected.
     * @param int $Page What page to show. Defaults to 1.
     */
    public function profileController_reactions_create($Sender, $UserReference, $Username = '', $Reaction = '', $Page = '') {
        $Sender->permission('Garden.Profiles.View');

        $ReactionType = ReactionModel::reactionTypes($Reaction);
        if (!$ReactionType) {
            throw NotFoundException();
        }

        $Sender->getUserInfo($UserReference, $Username);
        $UserID = val('UserID', $Sender->User);

        list($Offset, $Limit) = OffsetLimit($Page, 5);

        // If this value is less-than-or-equal-to _CurrentRecords, we'll get a "next" pagination link.
        $Sender->setData('_Limit', $Limit + 1);

        // Try to query five additional records to compensate for user permission and deleted record issues.
        $ReactionModel = new ReactionModel();
        $Data = $ReactionModel->getRecordsWhere(
            ['TagID' => $ReactionType['TagID'], 'RecordType' => ['Discussion-Total', 'Comment-Total'], 'UserID' => $UserID, 'Total >' => 0],
            'DateInserted', 'desc',
            $Limit + 5, $Offset);
        $Sender->setData('_CurrentRecords', count($Data));

        // If necessary, shave records off the end to get back down to the original size limit.
        while (count($Data) > $Limit) {
            array_pop($Data);
        }
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) === 'avatars') {
            $ReactionModel->joinUserTags($Data);
        }

        $Sender->setData('Data', $Data);
        $Sender->setData('EditMode', false, true);

        $Sender->_setBreadcrumbs(t($ReactionType['Name']), $Sender->canonicalUrl());
        $Sender->setTabView('Reactions', 'DataList', '', 'plugins/Reactions');
        $this->addJs($Sender);
        $Sender->addJsFile('jquery.expander.js');
        $Sender->addDefinition('ExpandText', T('(more)'));
        $Sender->addDefinition('CollapseText', T('(less)'));

        $Sender->render();
    }

    /**
     *
     *
     * @param $Sender
     */
    public function profileController_render_before($Sender) {
        if (!$Sender->data('Profile')) {
            return;
        }

        // Grab all of the counts for the user.
        $Data = Gdn::sql()
            ->getWhere('UserTag', ['RecordID' => $Sender->data('Profile.UserID'), 'RecordType' => 'User', 'UserID' => ReactionModel::USERID_OTHER])
            ->resultArray();
        $Data = Gdn_DataSet::index($Data, ['TagID']);

        $Counts = $Sender->data('Counts', []);
        foreach (ReactionModel::reactionTypes() as $Code => $Type) {
            if (!$Type['Active']) {
                continue;
            }

            $Row = [
                'Name' => $Type['Name'],
                'Url' => Url(UserUrl($Sender->data('Profile'), '', 'reactions').'?reaction='.urlencode($Code), true),
                'Total' => 0
            ];

            if (isset($Data[$Type['TagID']])) {
                $Row['Total'] = $Data[$Type['TagID']]['Total'];
            }
            $Counts[$Type['Name']] = $Row;
        }

        $Sender->setData('Counts', $Counts);
        $this->addJs($Sender);
    }

    /**
     * Handle user reactions.
     *
     * @param Gdn_Controller $Sender
     * @param string $RecordType Type of record we're reacting to. Discussion, comment or activity.
     * @param string $Reaction The url code of the reaction.
     * @param int $ID The ID of the record.
     * @param bool $selfReact Whether a user can react to their own post
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function rootController_react_create($Sender, $RecordType, $Reaction, $ID, $selfReact = false) {
        if (!Gdn::session()->isValid()) {
            throw new Gdn_UserException(t('You need to sign in before you can do this.'), 403);
        }

        include_once $Sender->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');

        if (!$Sender->Request->isAuthenticatedPostBack(true)) {
            throw PermissionException('Javascript');
        }

        $ReactionType = ReactionModel::reactionTypes($Reaction);
        $Sender->EventArguments['ReactionType'] = &$ReactionType;
        $Sender->EventArguments['RecordType'] = $RecordType;
        $Sender->EventArguments['RecordID'] = $ID;
        $Sender->fireAs('ReactionModel')->fireEvent('GetReaction');

        // Only allow enabled reactions
        if (!val('Active', $ReactionType)) {
            throw ForbiddenException("@You may not use that Reaction.");
        }

        // Permission
        if ($Permission = val('Permission', $ReactionType)) {
            // Check reaction's permission if a custom/specific one is applied
            $Sender->permission($Permission);
        } elseif ($PermissionClass = val('Class', $ReactionType)) {
            // Check reaction's permission based on class
            $Sender->permission('Reactions.'.$PermissionClass.'.Add');
        }

        $ReactionModel = new ReactionModel();
        $ReactionModel->react($RecordType, $ID, $Reaction, null, $selfReact);
        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Add the "Best Of..." link to the main menu.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        if (is_object($Menu = val('Menu', $Sender))) {
            $Menu->addLink('BestOf', t('Best Of...'), '/bestof/everything', false, array('class' => 'BestOf'));
        }
        if (!isMobile()) {
            $Sender->addDefinition('ShowUserReactions', c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT));
        }
    }


    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param type $Sender Controller firing the event.
     * @param string $ReactionType Type of reaction content to show
     * @param int $Page The current page of content
     */
    public function rootController_bestOfOld_create($Sender, $Reaction = 'everything') {
        // Load all of the reaction types.
        try {
            $ReactionTypes = ReactionModel::getReactionTypes(['Class' => 'Positive', 'Active' => 1]);
            $Sender->setData('ReactionTypes', $ReactionTypes);
        } catch (Exception $ex) {
            $Sender->setData('ReactionTypes', []);
        }
        if (!isset($ReactionTypes[$Reaction])) {
            $Reaction = 'everything';
        }
        $Sender->setData('CurrentReaction', $Reaction);

        // Define the query offset & limit.
        $Page = 'p'.getIncomingValue('Page', 1);
        $Limit = c('Plugins.Reactions.BestOfPerPage', 30);
        list($Offset, $Limit) = offsetLimit($Page, $Limit);
        $Sender->SetData('_Limit', $Limit + 1);

        $ReactionModel = new ReactionModel();
        if ($Reaction == 'everything') {
            $PromotedTagID = $ReactionModel->defineTag('Promoted', 'BestOf');
            $Data = $ReactionModel->detRecordsWhere(
                array('TagID' => $PromotedTagID, 'RecordType' => array('Discussion', 'Comment')),
                'DateInserted', 'desc',
                $Limit + 1, $Offset);
        } else {
            $ReactionType = $ReactionTypes[$Reaction];
            $Data = $ReactionModel->getRecordsWhere(
                array('TagID' => $ReactionType['TagID'], 'RecordType' => array('Discussion-Total', 'Comment-Total'), 'Total >=' => 1),
                'DateInserted', 'desc',
                $Limit + 1, $Offset);
        }

        $Sender->setData('_CurrentRecords', count($Data));
        if (count($Data) > $Limit) {
            array_pop($Data);
        }
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            $ReactionModel->joinUserTags($Data);
        }
        $Sender->setData('Data', $Data);

        // Set up head.
        $Sender->Head = new HeadModule($Sender);
        $Sender->addJsFile('jquery.js');
        $Sender->addJsFile('jquery.livequery.js');
        $Sender->addJsFile('global.js');
        $Sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions'); // I customized this to get proper callbacks.
        $Sender->addJsFile('library/jQuery-Wookmark/jquery.imagesloaded.js', 'plugins/Reactions');
        $Sender->addJsFile('library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js', 'plugins/Reactions');
        $Sender->addJsFile('tile.js', 'plugins/Reactions');
        $Sender->addCssFile('style.css');
        $Sender->addCssFile('vanillicon.css', 'static');

        // Set the title, breadcrumbs, canonical.
        $Sender->title(t('Best Of'));
        $Sender->setData('Breadcrumbs', [['Name' => t('Best Of'), 'Url' => '/bestof/everything']]);
        $Sender->canonicalUrl(
            url(concatSep('/', 'bestof/'.$Reaction, pageNumber($Offset, $Limit, true, Gdn::session()->UserID != 0)), true),
            Gdn::session()->UserID == 0
        );

        // Modules
        $Sender->addModule('GuestModule');
        $Sender->addModule('SignedInModule');
        $Sender->addModule('BestOfFilterModule');

        // Render the page.
        if (class_exists('LeaderBoardModule')) {
            $Sender->addModule('LeaderBoardModule');

            $Module = new LeaderBoardModule();
            $Module->SlotType = 'a';
            $Sender->addModule($Module);
        }

        // Render the page (or deliver the view)
        $Sender->render('bestof_old', '', 'plugins/Reactions');
    }

    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param type $Sender Controller firing the event.
     * @param string $ReactionType Type of reaction content to show
     * @param int $Page The current page of content
     */
    public function rootController_bestOf_create($Sender, $Reaction = 'everything') {
        Gdn_Theme::section('BestOf');
        // Load all of the reaction types.
        try {
            $ReactionTypes = ReactionModel::getReactionTypes(array('Class' => 'Positive', 'Active' => 1));

            $Sender->setData('ReactionTypes', $ReactionTypes);
        } catch (Exception $ex) {
            $Sender->setData('ReactionTypes', array());
        }

        if (!isset($ReactionTypes[$Reaction])) {
            $Reaction = 'everything';
        }
        $Sender->setData('CurrentReaction', $Reaction);

        // Define the query offset & limit.
        $Page = Gdn::request()->get('Page', 1);

        // Limit the number of pages.
        if (self::BEST_OF_MAX_PAGES && $Page > self::BEST_OF_MAX_PAGES) {
            $Page = self::BEST_OF_MAX_PAGES;
        }
        $Page = 'p'.$Page;

        $Limit = c('Plugins.Reactions.BestOfPerPage', 10);
        list($Offset, $Limit) = offsetLimit($Page, $Limit);

        $Sender->setData('_Limit', $Limit + 1);

        $ReactionModel = new ReactionModel();
        saveToConfig('Plugins.Reactions.ShowUserReactions', false, false);
        if ($Reaction == 'everything') {
            $PromotedTagID = $ReactionModel->defineTag('Promoted', 'BestOf');
            $Data = $ReactionModel->getRecordsWhere(
                ['TagID' => $PromotedTagID, 'RecordType' => ['Discussion', 'Comment']],
                'DateInserted', 'desc',
                $Limit + 1, $Offset);
        } else {
            $ReactionType = $ReactionTypes[$Reaction];
            $Data = $ReactionModel->getRecordsWhere(
                ['TagID' => $ReactionType['TagID'], 'RecordType' =>['Discussion-Total', 'Comment-Total'], 'Total >=' => 1],
                'DateInserted', 'desc',
                $Limit + 1, $Offset);
        }

        $Sender->setData('_CurrentRecords', count($Data));
        if (count($Data) > $Limit) {
            array_pop($Data);
        }
        $Sender->setData('Data', $Data);

        // Set up head
        $Sender->Head = new HeadModule($Sender);

        $Sender->addJsFile('jquery.js');
        $Sender->addJsFile('jquery.livequery.js');
        $Sender->addJsFile('global.js');
        $Sender->addJsFile('jquery.popup.js');

        if (c('Plugins.Reactions.BestOfStyle', 'Tiles') == 'Tiles') {
            $Sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions'); // I customized this to get proper callbacks.
            $Sender->addJsFile('library/jQuery-Wookmark/jquery.imagesloaded.js', 'plugins/Reactions');
            $Sender->addJsFile('library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js', 'plugins/Reactions');
            $Sender->addJsFile('tile.js', 'plugins/Reactions');
            $Sender->CssClass .= ' NoPanel';
            $View = $Sender->deliveryType() == DELIVERY_TYPE_VIEW ? 'tile_items' : 'tiles';
        } else {
            $View = 'BestOf';
            $Sender->addModule('GuestModule');
            $Sender->addModule('SignedInModule');
            $Sender->addModule('BestOfFilterModule');
        }

        $Sender->addCssFile('style.css');
        $Sender->addCssFile('vanillicon.css', 'static');

        // Set the title, breadcrumbs, canonical
        $Sender->title(t('Best Of'));
        $Sender->setData('Breadcrumbs', [['Name' => t('Best Of'), 'Url' => '/bestof/everything']]);
        $Sender->canonicalUrl(
            url(concatSep('/', 'bestof/'.$Reaction, pageNumber($Offset, $Limit, true, Gdn::session()->UserID != 0)), true),
            Gdn::session()->UserID == 0
        );

        // Render the page (or deliver the view)
        $Sender->render($View, '', 'plugins/Reactions');
    }

    /**
     * Recalculate all reaction data, including totals.
     *
     * @param utilityController $sender
     */
    public function utilityController_recalculateReactions_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $this->form = new Gdn_Form();

        if ($this->form->authenticatedPostback()) {
            $reactionModel = new ReactionModel();
            $reactionModel->recalculateTotals();
            $sender->setData('Recalculated', true);
        }

        $sender->addSideMenu();
        $sender->setData('Title', t('Recalculate Reactions'));
        $sender->render('Recalculate', '', 'plugins/Reactions');
    }

    /**
     * Sort the comments by score if necessary.
     *
     * @param CommentModel $CommentModel
     */
    public function commentModel_afterConstruct_handler($CommentModel) {
        if (!c('Plugins.Reactions.CommentSortEnabled')) {
            return;
        }

        $Sort = self::commentSort();
        switch (strtolower($Sort)) {
            case 'score':
                $CommentModel->orderBy(['coalesce(c.Score, 0) desc', 'c.CommentID']);
                break;
            case 'date':
            default:
                $CommentModel->orderBy('c.DateInserted');
                break;
        }
    }

    /**
     * Adds track points separately option to category options in edit/add category page.
     *
     * @param SettingsController $sender
     */
    public function vanillaSettingsController_afterCategorySettings_handler($sender) {
        $showCustomPoints = c('Plugins.Reactions.TrackPointsSeparately', false);
        if ($showCustomPoints) {
            $desc = 'This allows you to create separate leaderboards for this category. Tracking points for this '
                .'category separately will not be retroactive. To add a category-specific leaderboard module to your '
                .'theme template, add <code>{module name="LeaderboardModule" CategoryID="7"}</code>, replacing the '
                .'CategoryID value with the ID of the category with separate tracking enabled.';
            $label = 'Track leaderboard points for this category separately.';
            $toggle = $sender->Form->toggle('CustomPoints', $label, [], $desc);
            echo wrap($toggle, 'li', ['class' => 'form-group']);
        }
    }

    /**
     *
     *
     * @return array|bool|mixed|null|string|void
     */
    public static function commentSort() {
        if (!c('Plugins.Reactions.CommentSortEnabled')) {
            return;
        }

        if (self::$_CommentSort) {
            return self::$_CommentSort;
        }

        $Sort = getIncomingValue('Sort', '');
        if (Gdn::session()->isValid()) {
            if ($Sort == '') {
                // No sort was specified so grab it from the user's preferences.
                $Sort = Gdn::session()->getPreference('Plugins.Reactions.CommentSort', 'score');
            } else {
                // Save the sort to the user's preferences.
                Gdn::session()->setPreference('Plugins.Reactions.CommentSort', $Sort == 'score' ? 'score' : $Sort);
            }
        }

        if (!in_array($Sort, array('score', 'date'))) {
            $Sort = 'date';
        }

        self::$_CommentSort = $Sort;

        return $Sort;
    }

    /**
     * Allow comments to be sorted by score?
     *
     * @param discussionController $Sender
     */
    public function discussionController_beforeCommentDisplay_handler($Sender) {
        if (!c('Plugins.Reactions.CommentSortEnabled')) {
            return;
        }

        if (val('Type', $Sender->EventArguments, 'Comment') == 'Comment' && !val('VoteHeaderWritten', $this)) {
            ?>
            <li class="Item">
                <span class="NavLabel"><?php echo t('Sort by'); ?></span>
            <span class="DiscussionSort NavBar">
            <?php
                $Query = Gdn::request()->get();

                $Query['Sort'] = 'score';

                echo anchor('Points',
                    url('?'.http_build_query($Query), true),
                    'NoTop Button'.(self::commentSort() == 'score' ? ' Active' : ''),
                    ['rel' => 'nofollow', 'alt' => t('Sort by reaction points')]
                );

                $Query['Sort'] = 'date';

                echo anchor('Date Added',
                    url('?'.http_build_query($Query), true),
                    'NoTop Button'.(self::commentSort() == 'date' ? ' Active' : ''),
                    ['rel' => 'nofollow', 'alt' => t('Sort by date added')]
                );
            ?>
            </span>
            </li>
            <?php
            $this->VoteHeaderWritten = true;
        }
    }

    /**
     * Add Types to TagModel so that tabs in the dashboard will have more info to work with.
     *
     * @sender TagModel $sender
     */
    public function tagModel_types_handler($sender) {
        $sender->addType('BestOf', [
            'key' => 'BestOf',
            'name' => 'BestOf',
            'plural' => 'BestOf',
            'addtag' => false,
            'default' => false
        ]);

        $sender->addType('Reaction', [
            'key' => 'Reaction',
            'name' => 'Reaction',
            'plural' => 'Reactions',
            'addtag' => false,
            'default' => false
        ]);
    }

    /**
     * Merge reactions alongside a user-merge.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_merge_handler($sender, $args) {
        $oldUser = $args['OldUser'];
        $newUser = $args['NewUser'];

        $reactionModel = new ReactionModel();
        $reactionModel->mergeUsers(val('UserID', $oldUser), val('UserID', $newUser));
        Gdn::sql()->put('UserMerge', ['ReactionsMerged' => 1], ['MergeID' => $args['MergeID']]);
    }
}

if (!function_exists('writeReactions')) {
    /**
     *
     *
     * @param $Row
     * @throws Exception
     */
    function writeReactions($Row) {
        $Attributes = val('Attributes', $Row);
        if (is_string($Attributes)) {
            $Attributes = dbdecode($Attributes);
            setValue('Attributes', $Row, $Attributes);
        }

        static $Types = null;
        if ($Types === null) {
            $Types = ReactionModel::getReactionTypes(array('Class' => array('Positive', 'Negative'), 'Active' => 1));
        }
        Gdn::controller()->EventArguments['ReactionTypes'] = &$Types;

        if ($ID = val('CommentID', $Row)) {
            $RecordType = 'comment';
        } elseif ($ID = val('ActivityID', $Row)) {
            $RecordType = 'activity';
        } else {
            $RecordType = 'discussion';
            $ID = val('DiscussionID', $Row);
        }
        Gdn::controller()->EventArguments['RecordType'] = $RecordType;
        Gdn::controller()->EventArguments['RecordID'] = $ID;

        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            writeRecordReactions($Row);
        }

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        // Write the flags.
        static $Flags = null;
        if ($Flags === null && checkPermission('Reactions.Flag.Add')) {
            $Flags = ReactionModel::getReactionTypes(array('Class' => 'Flag', 'Active' => 1));
            $FlagCodes = array();
            foreach ($Flags as $Flag) {
                $FlagCodes[] = $Flag['UrlCode'];
            }
            Gdn::controller()->EventArguments['Flags'] = &$Flags;
            Gdn::controller()->fireEvent('Flags');
        }

        // Allow addons to work with flags
        Gdn::controller()->EventArguments['Flags'] = &$Flags;
        Gdn::controller()->fireEvent('BeforeFlag');

        if (!empty($Flags) && is_array($Flags)) {
            echo Gdn_Theme::bulletItem('Flags');

            echo ' <span class="FlagMenu ToggleFlyout">';
            // Write the handle.
            echo reactionButton($Row, 'Flag', array('LinkClass' => 'FlyoutButton', 'IsHeading' => true));
            echo '<ul class="Flyout MenuItems Flags" style="display: none;">';

            foreach ($Flags as $Flag) {
                if (is_callable($Flag)) {
                    echo '<li>'.call_user_func($Flag, $Row, $RecordType, $ID).'</li>';
                } else {
                    echo '<li>'.reactionButton($Row, $Flag['UrlCode']).'</li>';
                }
            }

            Gdn::controller()->fireEvent('AfterFlagOptions');
            echo '</ul>';
            echo '</span> ';
        }
        Gdn::controller()->fireEvent('AfterFlag');

        $Score = formatScore(val('Score', $Row));
        echo '<span class="Column-Score Hidden">'.$Score.'</span>';

        // Write the reactions.
        echo Gdn_Theme::bulletItem('Reactions');
        echo '<span class="ReactMenu">';
        echo '<span class="ReactButtons">';
        foreach ($Types as $Type) {
            if (isset($Type['RecordTypes']) && !in_array($RecordType, (array)$Type['RecordTypes'])) {
                continue;
            }
            echo ' '.ReactionButton($Row, $Type['UrlCode']).' ';
        }
        echo '</span>';
        echo '</span>';

        if (checkPermission(['Garden.Moderation.Manage', 'Moderation.Reactions.Edit'])) {
            echo Gdn_Theme::bulletItem('ReactionsMod').anchor(
                t('Log'),
                "/reactions/log/{$RecordType}/{$ID}",
                'Popup ReactButton ReactButton-Log'
            );
        }

        Gdn::controller()->fireEvent('AfterReactions');

        echo '</div>';
        Gdn::controller()->fireAs('DiscussionController')->fireEvent('Replies');
    }
}
