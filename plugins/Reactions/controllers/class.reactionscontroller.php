<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Reactions controller
 *
 * @since 1.0.0
 */
class ReactionsController extends DashboardController {

    /**
     *
     */
    public function initialize() {
        parent::initialize();
        $this->Form = new Gdn_Form;
        $this->Application = 'dashboard';
    }

    /**
     * List reactions.
     */
    public function index() {
        $this->permission('Garden.Community.Manage');
        $this->title(t('Reaction Types'));
        $this->addSideMenu();

        // Grab all of the reaction types.
        $ReactionTypes = ReactionModel::getReactionTypes();
        $this->setData('ReactionTypes', $ReactionTypes);

        include_once $this->fetchViewLocation('settings_functions', '', 'plugins/Reactions');
        $this->render('reactiontypes', '', 'plugins/Reactions');
    }

    /**
     * Get a reaction.
     *
     * @param string $UrlCode
     * @throws
     */
    public function get($UrlCode) {
        $this->permission('Garden.Community.Manage');

        $Reaction = ReactionModel::reactionTypes($UrlCode);
        if (!$Reaction) {
            throw NotFoundException('reaction');
        }

        $this->setData('Reaction', $Reaction);

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Add a reaction
     *
     * Parameters:
     *  UrlCode
     *  Name
     *  Description
     *  Class
     *  Points
     *
     */
    public function add() {
        $this->permission('Garden.Community.Manage');
        $this->title('Add Reaction');
        $this->addSideMenu('reactions');

        $ReactionModel = new ReactionModel();
        if ($this->Form->authenticatedPostBack()) {
            $Reaction = $this->Form->formValues();
            $newReaction = $ReactionModel->defineReactionType($Reaction);

            if ($newReaction) {
                $this->setData('Reaction', $Reaction);

                if ($this->deliveryType() != DELIVERY_TYPE_DATA) {
                    $this->informMessage(formatString(t("New reaction created"), $Reaction));
                    redirect('/reactions');
                }
            }

        }

        $this->render('addedit', '', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param $Type
     * @param $ID
     * @param $Reaction
     * @param $UserID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function undo($Type, $ID, $Reaction, $UserID) {
        $this->permission(['Garden.Moderation.Manage'], false);

        $ReactionModel = new ReactionModel();
        $ReactionModel->react($Type, $ID, 'Undo-'.$Reaction, $UserID);

        $this->jsonTarget('!parent', '', 'SlideUp');
        include_once $this->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * List users who reacted.
     *
     * @param $Type
     * @param $ID
     * @param $Reaction
     * @param null $Page
     * @throws Exception
     */
    public function users($Type, $ID, $Reaction, $Page = null) {
        if (!c('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT)) {
            throw PermissionException();
        }

        $ReactionModel = new ReactionModel();
        list($Offset, $Limit) = OffsetLimit($Page, 10);
        $this->setData('Users', $ReactionModel->getUsers($Type, $ID, $Reaction, $Offset, $Limit));

        $this->render('', 'reactions', 'plugins/Reactions');
    }

    /**
     * Edit a reaction.
     *
     * Parameters:
     *  UrlCode
     *  Name
     *  Description
     *  Class
     *  Points
     *
     * @param string $UrlCode
     * @throws type
     */
    public function edit($UrlCode) {
        $this->permission('Garden.Community.Manage');
        $this->title('Edit Reaction');
        $this->addSideMenu('reactions');

        $ReactionModel = new ReactionModel();
        $Reaction = ReactionModel::reactionTypes($UrlCode);
        if (!$Reaction) {
            throw NotFoundException('reaction');
        }

        $this->setData('Reaction', $Reaction);
        $this->Form->setData($Reaction);

        if ($this->Form->authenticatedPostBack()) {
            $ReactionData = $this->Form->formValues();
            $ReactionData = array_merge($Reaction, $ReactionData);
            $ReactionID = $ReactionModel->defineReactionType($ReactionData);

            if ($ReactionID) {
                $Reaction['ReactionID'] = $ReactionID;
                $this->setData('Reaction', $ReactionData);

                if ($this->deliveryType() != DELIVERY_TYPE_DATA) {
                    $this->informMessage(formatString(t("New reaction created"), $ReactionData));
                    redirect('/reactions');
                }
            }

        }

        $this->render('addedit', '', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param $Type
     * @param $ID
     * @throws Exception
     */
    public function log($Type, $ID) {
        $this->permission(array('Garden.Moderation.Manage', 'Moderation.Reactions.Edit'), false);
        $Type = ucfirst($Type);

        $ReactionModel = new ReactionModel();
        list($Row, $Model) = $ReactionModel->getRow($Type, $ID);
        if (!$Row) {
            throw NotFoundException(ucfirst($Type));
        }

        $ReactionModel->joinUserTags($Row, $Type);
        touchValue('UserTags', $Row, []);
        Gdn::userModel()->joinUsers($Row['UserTags'], ['UserID']);

        $this->Data = $Row;
        $this->setData('RecordType', $Type);
        $this->setData('RecordID', $ID);

        $this->render('log', 'reactions', 'plugins/Reactions');
    }

    /**
     *
     *
     * @param bool $Day
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function recalculateRecordCache($Day = false) {
        $this->permission('Garden.Settings.Manage');

        if (!$this->Request->isAuthenticatedPostBack()) {
            throw ForbiddenException('GET');
        }

        $ReactionModel = new ReactionModel();
        $Count = $ReactionModel->recalculateRecordCache($Day);
        $this->setData('Count', $Count);
        $this->setData('Success', true);
        $this->render();
    }

    /**
     * Toggle a given reaction on or off.
     *
     * @param string $UrlCode
     * @param boolean $Active
     */
    public function toggle($UrlCode, $Active) {
        $this->permission('Garden.Community.Manage');

        if (!$this->Form->authenticatedPostBack()) {
            throw PermissionException('PostBack');
        }

        $ReactionType = ReactionModel::reactionTypes($UrlCode);
        if (!$ReactionType) {
            throw NotFoundException('Reaction Type');
        }

        $ReactionModel = new ReactionModel();
        $Reaction = ReactionModel::reactionTypes($UrlCode);
        $ReactionType['Active'] = $Active;
        $Set = arrayTranslate($ReactionType, ['UrlCode', 'Active']);
        $ReactionModel->defineReactionType($Set);

        $Reaction = array_merge($Reaction, $Set);
        $this->setData('Reaction', $Reaction);

        if ($this->deliveryType() != DELIVERY_TYPE_DATA) {
            // Send back the new button.
            include_once $this->fetchViewLocation('settings_functions', '', 'plugins/Reactions');
            $this->deliveryMethod(DELIVERY_METHOD_JSON);
            $this->jsonTarget("#ReactionType_{$ReactionType['UrlCode']} .ActivateSlider", ActivateButton($ReactionType), 'ReplaceWith');
            $this->jsonTarget("#ReactionType_{$ReactionType['UrlCode']}", 'InActive', $ReactionType['Active'] ? 'RemoveClass' : 'AddClass');
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     *
     */
    public function advanced() {
        $this->permission('Garden.Settings.Manage');

        $Conf = new ConfigurationModule($this);
        $Conf->initialize(array(
            'Plugins.Reactions.ShowUserReactions' => [
                'LabelCode' => 'Show Who Reacted to Posts',
                'Control' => 'RadioList',
                'Items' => ['popup' => 'In a popup', 'avatars' => 'As avatars', 'off' => "Don't show"],
                'Default' => ReactionsPlugin::RECORD_REACTIONS_DEFAULT
            ],
            'Plugins.Reactions.BestOfStyle' => [
                'LabelCode' => 'Best of Style',
                'Control' => 'RadioList',
                'Items' => ['Tiles' => 'Tiles', 'List' => 'List'],
                'Default' => 'Tiles'
            ],
            'Plugins.Reactions.DefaultOrderBy' => [
                'LabelCode' => 'Order Comments By',
                'Control' => 'RadioList',
                'Items' => ['DateInserted' => 'Date', 'Score' => 'Score'],
                'Default' => 'DateInserted',
                'Description' => 'You can order your comments based on reactions. We recommend ordering the comments by date.'
            ],
            'Plugins.Reactions.DefaultEmbedOrderBy' => [
                'LabelCode' => 'Order Embedded Comments By',
                'Control' => 'RadioList',
                'Items' => ['DateInserted' => 'Date', 'Score' => 'Score'],
                'Default' => 'Score',
                'Description' => 'Ordering your embedded comments by reaction will show just the best comments. Then users can head into the community to see the full discussion.'
            ],
            'Reactions.PromoteValue' => [
                'Type' => 'int',
                'LabelCode' => 'Promote Threshold',
                'Description' => 'Points required for a post to be promoted to Best Of. Changes are not retroactive.',
                'Control' => 'DropDown',
                'Items' => [3 => 3, 5 => 5, 10 => 10, 20 => 20],
                'Default' => c('Reactions.PromoteValue', 5)
            ],
            'Reactions.BuryValue' => [
                'Type' => 'int',
                'LabelCode' => 'Bury Threshold',
                'Description' => 'Points required for a post to be buried. Changes are not retroactive.',
                'Control' => 'DropDown',
                'Items' => [-3 => -3, -5 => -5, -10 => -10, -20 => -20],
                'Default' => c('Reactions.BuryValue', -5)
            ],
        ));

        $this->title(sprintf(t('%s Settings'), 'Reaction'));
        $this->addSideMenu('reactions');
        $Conf->renderAll();
    }
}
