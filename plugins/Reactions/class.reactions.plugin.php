<?php
/**
 *
 * Changes:
 *  1.0     Release
 *  1.2.3   Allow reactionModel() to react from any source user.
 *  1.2.4   Allow some reactions to be protected so that users can't flag moderator posts.
 *  1.2.13  Added TagModel_Types_Handler.
 *  1.3     Add class permissions; fix GetReactionTypes attributes; fix descriptions.
 *  1.2.15  Add section 508 fixes.
 *  1.4.0   Add support for merging users' reactions.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

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
            ['TagID' => $reactionType['TagID'], 'RecordType' => ['Discussion-Total', 'Comment-Total'], 'Total >=' => 1],
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
                $sender->Mapper->addMap([
                    'reactions/list' => 'reactions',
                    'reactions/get' => 'reactions/get',
                    'reactions/add' => 'reactions/add',
                    'reactions/edit' => 'reactions/edit',
                    'reactions/toggle' => 'reactions/toggle'
                ]);
                break;
        }
    }

    /**
     *
     *
     * @param $sender
     */
    private function addJs($sender) {
        $sender->addJsFile('jquery-ui.min.js');
        $sender->addJsFile('reactions.js', 'plugins/Reactions');
    }

    /**
     *
     *
     * @return array
     */
    public static function commentOrder() {
        if (!self::$_CommentOrder) {
            $setPreference = false;

            if (!Gdn::session()->isValid()) {
                if (Gdn::controller() != null && strcasecmp(Gdn::controller()->RequestMethod, 'embed') == 0) {
                    $orderColumn = c('Plugins.Reactions.DefaultEmbedOrderBy', 'Score');
                } else {
                    $orderColumn = c('Plugins.Reactions.DefaultOrderBy', 'DateInserted');
                }
            } else {
                $defaultOrderParts = ['DateInserted', 'asc'];

                $orderBy = Gdn::request()->get('orderby', '');
                if ($orderBy) {
                    $setPreference = true;
                } else {
                    $orderBy = Gdn::session()->getPreference('Comments.OrderBy');
                }
                $orderParts = explode(' ', $orderBy);
                $orderColumn = getValue(0, $orderParts, $defaultOrderParts[0]);

                // Make sure the order is correct.
                if (!in_array($orderColumn, ['DateInserted', 'Score']))
                    $orderColumn = 'DateInserted';


                if ($setPreference) {
                    Gdn::session()->setPreference('Comments.OrderBy', $orderColumn);
                }
            }
            $orderDirection = $orderColumn == 'Score' ? 'desc' : 'asc';

            $commentOrder = ['c.'.$orderColumn.' '.$orderDirection];

            // Add a unique order if we aren't ordering by a unique column.
            if (!in_array($orderColumn, ['DateInserted', 'CommentID'])) {
                $commentOrder[] = 'c.DateInserted asc';
            }

            self::$_CommentOrder = $commentOrder;
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
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Forum', t('Reactions'), 'reactions', 'Garden.Community.Manage', ['class' => 'nav-reactions']);
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
            $Sender->setData('CommentOrder', ['Column' => $OrderColumn, 'Direction' => $OrderDirection]);
        }

        if ($Sender->ReactionsVersion != 1) {
            $this->addJs($Sender);
        }

        $ReactionModel = new ReactionModel();
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            $ReactionModel->joinUserTags($Sender->Data['Discussion'], 'Discussion');
            $ReactionModel->joinUserTags($Sender->Data['Comments'], 'Comment');

            if (isset($Sender->Data['Answers'])) {
                $ReactionModel->joinUserTags($Sender->Data['Answers'], 'Comment');
            }
        }

        include_once $Sender->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    public function commentModel_beforeUpdateCommentCount_handler($sender, $args) {
        if (!isset($args['Discussion'])) {
            return;
        }

        // A discussion with a low score counts as sunk.
        $discussion =& $args['Discussion'];
        if ((int)val('Score', $discussion) <= -5) {
            Gdn::controller()->setData('Score', val('Score', $discussion));
            setValue('Sink', $discussion, true);
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
     * @param $sender
     * @param $args
     */
    public function base_beforeCommentDisplay_handler($sender, $args) {
        $cssClass = scoreCssClass($args['Object']);
        if ($cssClass) {
            $args['CssClass'] .= ' '.$cssClass;
            setValue('_CssClass', $args['Object'], $cssClass);
        }
    }

    /**
     * Show user's reacted-to content by reaction type.
     *
     * @param ProfileController $sender Duh.
     * @param string|int $userReference A username or userid.
     * @param string $username
     * @param string $reaction Which reaction is selected.
     * @param int $page What page to show. Defaults to 1.
     */
    public function profileController_reactions_create($sender, $userReference, $username = '', $reaction = '', $page = '') {
        $sender->permission('Garden.Profiles.View');

        $reactionType = ReactionModel::reactionTypes($reaction);
        if (!$reactionType) {
            throw notFoundException();
        }

        $sender->getUserInfo($userReference, $username);
        $userID = val('UserID', $sender->User);

        list($offset, $limit) = offsetLimit($page, 5);

        // If this value is less-than-or-equal-to _CurrentRecords, we'll get a "next" pagination link.
        $sender->setData('_Limit', $limit + 1);

        // Try to query five additional records to compensate for user permission and deleted record issues.
        $reactionModel = new ReactionModel();
        $data = $reactionModel->getRecordsWhere(
            ['TagID' => $reactionType['TagID'], 'RecordType' => ['Discussion-Total', 'Comment-Total'], 'UserID' => $userID, 'Total >' => 0],
            'DateInserted', 'desc',
            $limit + 5, $offset);
        $sender->setData('_CurrentRecords', count($data));

        // If necessary, shave records off the end to get back down to the original size limit.
        while (count($data) > $limit) {
            array_pop($data);
        }
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) === 'avatars') {
            $reactionModel->joinUserTags($data);
        }

        $sender->setData('Data', $data);
        $sender->setData('EditMode', false, true);

        $sender->_setBreadcrumbs(t($reactionType['Name']), $sender->canonicalUrl());
        $sender->setTabView('Reactions', 'DataList', '', 'plugins/Reactions');
        $this->addJs($sender);
        $sender->addJsFile('jquery.expander.js');
        $sender->addDefinition('ExpandText', t('(more)'));
        $sender->addDefinition('CollapseText', t('(less)'));

        $sender->render();
    }

    /**
     *
     *
     * @param $sender
     */
    public function profileController_render_before($sender) {
        if (!$sender->data('Profile')) {
            return;
        }

        // Grab all of the counts for the user.
        $data = Gdn::sql()
            ->getWhere('UserTag', ['RecordID' => $sender->data('Profile.UserID'), 'RecordType' => 'User', 'UserID' => ReactionModel::USERID_OTHER])
            ->resultArray();
        $data = Gdn_DataSet::index($data, ['TagID']);

        $counts = $sender->data('Counts', []);
        foreach (ReactionModel::reactionTypes() as $code => $type) {
            if (!$type['Active']) {
                continue;
            }

            $row = [
                'Name' => $type['Name'],
                'Url' => url(userUrl($sender->data('Profile'), '', 'reactions').'?reaction='.urlencode($code), true),
                'Total' => 0
            ];

            if (isset($data[$type['TagID']])) {
                $row['Total'] = $data[$type['TagID']]['Total'];
            }
            $counts[$type['Name']] = $row;
        }

        $sender->setData('Counts', $counts);
        $this->addJs($sender);
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
            throw permissionException('Javascript');
        }

        $ReactionType = ReactionModel::reactionTypes($Reaction);
        $Sender->EventArguments['ReactionType'] = &$ReactionType;
        $Sender->EventArguments['RecordType'] = $RecordType;
        $Sender->EventArguments['RecordID'] = $ID;
        $Sender->fireAs('ReactionModel')->fireEvent('GetReaction');

        // Only allow enabled reactions
        if (!val('Active', $ReactionType)) {
            throw forbiddenException("@You may not use that Reaction.");
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
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        if (is_object($menu = val('Menu', $sender))) {
            $menu->addLink('BestOf', t('Best Of...'), '/bestof/everything', false, ['class' => 'BestOf']);
        }
        if (!isMobile()) {
            $sender->addDefinition('ShowUserReactions', c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT));
        }
    }


    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param type $sender Controller firing the event.
     * @param string $reactionType Type of reaction content to show
     * @param int $page The current page of content
     */
    public function rootController_bestOfOld_create($sender, $reaction = 'everything') {
        // Load all of the reaction types.
        try {
            $reactionTypes = ReactionModel::getReactionTypes(['Class' => 'Positive', 'Active' => 1]);
            $sender->setData('ReactionTypes', $reactionTypes);
        } catch (Exception $ex) {
            $sender->setData('ReactionTypes', []);
        }
        if (!isset($reactionTypes[$reaction])) {
            $reaction = 'everything';
        }
        $sender->setData('CurrentReaction', $reaction);

        // Define the query offset & limit.
        $page = 'p'.getIncomingValue('Page', 1);
        $limit = c('Plugins.Reactions.BestOfPerPage', 30);
        list($offset, $limit) = offsetLimit($page, $limit);
        $sender->setData('_Limit', $limit + 1);

        $reactionModel = new ReactionModel();
        if ($reaction == 'everything') {
            $promotedTagID = $reactionModel->defineTag('Promoted', 'BestOf');
            $data = $reactionModel->detRecordsWhere(
                ['TagID' => $promotedTagID, 'RecordType' => ['Discussion', 'Comment']],
                'DateInserted', 'desc',
                $limit + 1, $offset);
        } else {
            $reactionType = $reactionTypes[$reaction];
            $data = $reactionModel->getRecordsWhere(
                ['TagID' => $reactionType['TagID'], 'RecordType' => ['Discussion-Total', 'Comment-Total'], 'Total >=' => 1],
                'DateInserted', 'desc',
                $limit + 1, $offset);
        }

        $sender->setData('_CurrentRecords', count($data));
        if (count($data) > $limit) {
            array_pop($data);
        }
        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            $reactionModel->joinUserTags($data);
        }
        $sender->setData('Data', $data);

        // Set up head.
        $sender->Head = new HeadModule($sender);
        $sender->addJsFile('jquery.js');
        $sender->addJsFile('jquery.livequery.js');
        $sender->addJsFile('global.js');
        $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions'); // I customized this to get proper callbacks.
        $sender->addJsFile('library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js', 'plugins/Reactions');
        $sender->addJsFile('tile.js', 'plugins/Reactions');
        $sender->addCssFile('style.css');
        $sender->addCssFile('vanillicon.css', 'static');

        // Set the title, breadcrumbs, canonical.
        $sender->title(t('Best Of'));
        $sender->setData('Breadcrumbs', [['Name' => t('Best Of'), 'Url' => '/bestof/everything']]);
        $sender->canonicalUrl(
            url(concatSep('/', 'bestof/'.$reaction, pageNumber($offset, $limit, true, Gdn::session()->UserID != 0)), true),
            Gdn::session()->UserID == 0
        );

        // Modules
        $sender->addModule('GuestModule');
        $sender->addModule('SignedInModule');
        $sender->addModule('BestOfFilterModule');

        // Render the page.
        if (class_exists('LeaderBoardModule')) {
            $sender->addModule('LeaderBoardModule');

            $module = new LeaderBoardModule();
            $module->SlotType = 'a';
            $sender->addModule($module);
        }

        // Render the page (or deliver the view)
        $sender->render('bestof_old', '', 'plugins/Reactions');
    }

    /**
     * Add a "Best Of" view for reacted content.
     *
     * @param type $sender Controller firing the event.
     * @param string $reactionType Type of reaction content to show
     * @param int $page The current page of content
     */
    public function rootController_bestOf_create($sender, $reaction = 'everything') {
        Gdn_Theme::section('BestOf');
        // Load all of the reaction types.
        try {
            $reactionTypes = ReactionModel::getReactionTypes(['Class' => 'Positive', 'Active' => 1]);

            $sender->setData('ReactionTypes', $reactionTypes);
        } catch (Exception $ex) {
            $sender->setData('ReactionTypes', []);
        }

        if (!isset($reactionTypes[$reaction])) {
            $reaction = 'everything';
        }
        $sender->setData('CurrentReaction', $reaction);

        // Define the query offset & limit.
        $page = Gdn::request()->get('Page', 1);

        // Limit the number of pages.
        if (self::BEST_OF_MAX_PAGES && $page > self::BEST_OF_MAX_PAGES) {
            $page = self::BEST_OF_MAX_PAGES;
        }
        $page = 'p'.$page;

        $limit = c('Plugins.Reactions.BestOfPerPage', 10);
        list($offset, $limit) = offsetLimit($page, $limit);

        $sender->setData('_Limit', $limit + 1);

        $reactionModel = new ReactionModel();
        saveToConfig('Plugins.Reactions.ShowUserReactions', false, false);
        if ($reaction == 'everything') {
            $promotedTagID = $reactionModel->defineTag('Promoted', 'BestOf');
            $data = $reactionModel->getRecordsWhere(
                ['TagID' => $promotedTagID, 'RecordType' => ['Discussion', 'Comment']],
                'DateInserted', 'desc',
                $limit + 1, $offset);
        } else {
            $reactionType = $reactionTypes[$reaction];
            $data = $reactionModel->getRecordsWhere(
                ['TagID' => $reactionType['TagID'], 'RecordType' =>['Discussion-Total', 'Comment-Total'], 'Total >=' => 1],
                'DateInserted', 'desc',
                $limit + 1, $offset);
        }

        $sender->setData('_CurrentRecords', count($data));
        if (count($data) > $limit) {
            array_pop($data);
        }
        $sender->setData('Data', $data);

        // Set up head
        $sender->Head = new HeadModule($sender);

        $sender->addJsFile('jquery.js');
        $sender->addJsFile('jquery.livequery.js');
        $sender->addJsFile('global.js');
        $sender->addJsFile('jquery.form.js');
        $sender->addJsFile('jquery.popup.js');

        if (c('Plugins.Reactions.BestOfStyle', 'Tiles') == 'Tiles') {
            $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions'); // I customized this to get proper callbacks.
            $sender->addJsFile('library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js', 'plugins/Reactions');
            $sender->addJsFile('tile.js', 'plugins/Reactions');
            $sender->CssClass .= ' NoPanel';
            $view = $sender->deliveryType() == DELIVERY_TYPE_VIEW ? 'tile_items' : 'tiles';
        } else {
            $view = 'BestOf';
            $sender->addModule('GuestModule');
            $sender->addModule('SignedInModule');
            $sender->addModule('BestOfFilterModule');
        }

        $sender->addCssFile('style.css');
        $sender->addCssFile('vanillicon.css', 'static');

        // Set the title, breadcrumbs, canonical
        $sender->title(t('Best Of'));
        $sender->setData('Breadcrumbs', [['Name' => t('Best Of'), 'Url' => '/bestof/everything']]);
        $sender->canonicalUrl(
            url(concatSep('/', 'bestof/'.$reaction, pageNumber($offset, $limit, true, Gdn::session()->UserID != 0)), true),
            Gdn::session()->UserID == 0
        );

        // Render the page (or deliver the view)
        $sender->render($view, '', 'plugins/Reactions');
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
     * @param CommentModel $commentModel
     */
    public function commentModel_afterConstruct_handler($commentModel) {
        if (!c('Plugins.Reactions.CommentSortEnabled')) {
            return;
        }

        $sort = self::commentSort();
        switch (strtolower($sort)) {
            case 'score':
                $commentModel->orderBy(['coalesce(c.Score, 0) desc', 'c.CommentID']);
                break;
            case 'date':
            default:
                $commentModel->orderBy('c.DateInserted');
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

        $sort = getIncomingValue('Sort', '');
        if (Gdn::session()->isValid()) {
            if ($sort == '') {
                // No sort was specified so grab it from the user's preferences.
                $sort = Gdn::session()->getPreference('Plugins.Reactions.CommentSort', 'score');
            } else {
                // Save the sort to the user's preferences.
                Gdn::session()->setPreference('Plugins.Reactions.CommentSort', $sort == 'score' ? 'score' : $sort);
            }
        }

        if (!in_array($sort, ['score', 'date'])) {
            $sort = 'date';
        }

        self::$_CommentSort = $sort;

        return $sort;
    }

    /**
     * Allow comments to be sorted by score?
     *
     * @param discussionController $sender
     */
    public function discussionController_beforeCommentDisplay_handler($sender) {
        if (!c('Plugins.Reactions.CommentSortEnabled')) {
            return;
        }

        if (val('Type', $sender->EventArguments, 'Comment') == 'Comment' && !val('VoteHeaderWritten', $this)) {
            ?>
            <li class="Item">
                <span class="NavLabel"><?php echo t('Sort by'); ?></span>
            <span class="DiscussionSort NavBar">
            <?php
                $query = Gdn::request()->get();

                $query['Sort'] = 'score';

                echo anchor('Points',
                    url('?'.http_build_query($query), true),
                    'NoTop Button'.(self::commentSort() == 'score' ? ' Active' : ''),
                    ['rel' => 'nofollow', 'alt' => t('Sort by reaction points')]
                );

                $query['Sort'] = 'date';

                echo anchor('Date Added',
                    url('?'.http_build_query($query), true),
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
     * @param $row
     * @throws Exception
     */
    function writeReactions($row) {
        $attributes = val('Attributes', $row);
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
            setValue('Attributes', $row, $attributes);
        }

        static $types = null;
        if ($types === null) {
            $types = ReactionModel::getReactionTypes(['Class' => ['Positive', 'Negative'], 'Active' => 1]);
        }
        Gdn::controller()->EventArguments['ReactionTypes'] = &$types;

        if ($iD = val('CommentID', $row)) {
            $recordType = 'comment';
        } elseif ($iD = val('ActivityID', $row)) {
            $recordType = 'activity';
        } else {
            $recordType = 'discussion';
            $iD = val('DiscussionID', $row);
        }
        Gdn::controller()->EventArguments['RecordType'] = $recordType;
        Gdn::controller()->EventArguments['RecordID'] = $iD;

        if (c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
            writeRecordReactions($row);
        }

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        // Write the flags.
        static $flags = null;
        if ($flags === null && checkPermission('Reactions.Flag.Add')) {
            $flags = ReactionModel::getReactionTypes(['Class' => 'Flag', 'Active' => 1]);
            $flagCodes = [];
            foreach ($flags as $flag) {
                $flagCodes[] = $flag['UrlCode'];
            }
            Gdn::controller()->EventArguments['Flags'] = &$flags;
            Gdn::controller()->fireEvent('Flags');
        }

        // Allow addons to work with flags
        Gdn::controller()->EventArguments['Flags'] = &$flags;
        Gdn::controller()->fireEvent('BeforeFlag');

        if (!empty($flags) && is_array($flags)) {
            echo Gdn_Theme::bulletItem('Flags');

            echo ' <span class="FlagMenu ToggleFlyout">';
            // Write the handle.
            echo reactionButton($row, 'Flag', ['LinkClass' => 'FlyoutButton', 'IsHeading' => true]);
            echo '<ul class="Flyout MenuItems Flags" style="display: none;">';

            foreach ($flags as $flag) {
                if (is_callable($flag)) {
                    echo '<li>'.call_user_func($flag, $row, $recordType, $iD).'</li>';
                } else {
                    echo '<li>'.reactionButton($row, $flag['UrlCode']).'</li>';
                }
            }

            Gdn::controller()->fireEvent('AfterFlagOptions');
            echo '</ul>';
            echo '</span> ';
        }
        Gdn::controller()->fireEvent('AfterFlag');

        $score = formatScore(val('Score', $row));
        echo '<span class="Column-Score Hidden">'.$score.'</span>';

        // Write the reactions.
        echo Gdn_Theme::bulletItem('Reactions');
        echo '<span class="ReactMenu">';
        echo '<span class="ReactButtons">';
        foreach ($types as $type) {
            if (isset($type['RecordTypes']) && !in_array($recordType, (array)$type['RecordTypes'])) {
                continue;
            }
            echo ' '.reactionButton($row, $type['UrlCode']).' ';
        }
        echo '</span>';
        echo '</span>';

        if (checkPermission(['Garden.Moderation.Manage', 'Moderation.Reactions.Edit'])) {
            echo Gdn_Theme::bulletItem('ReactionsMod').anchor(
                t('Log'),
                "/reactions/log/{$recordType}/{$iD}",
                'Popup ReactButton ReactButton-Log'
            );
        }

        Gdn::controller()->fireEvent('AfterReactions');

        echo '</div>';
        Gdn::controller()->fireAs('DiscussionController')->fireEvent('Replies');
    }
}
