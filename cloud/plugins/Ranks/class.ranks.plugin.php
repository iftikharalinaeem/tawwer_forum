<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ClientException;
use Vanilla\ApiUtils;
use Vanilla\Formatting\FormatService;

class RanksPlugin extends Gdn_Plugin {

    /** @var RankModel */
    private $rankModel;

    /** @var UserModel */
    private $userModel;

    /** @var null|array  */
    public $ActivityLinks = null;

    /** @var null|array  */
    public $CommentLinks = null;

    /** @var null|array  */
    public $ConversationLinks = null;

    /** @var string  */
    public $LinksNotAllowedMessage = 'You have to be around for a little while longer before you can post links.';

    /** @var FormatService */
    private $formatService;

    /** @var EventManager */
    private $eventManager;

    /**
     * RanksPlugin constructor.
     *
     * @param RankModel $rankModel
     * @param UserModel $userModel
     */
    public function __construct(RankModel $rankModel, UserModel $userModel, FormatService $formatService, EventManager $eventManager) {
        $this->rankModel = $rankModel;
        $this->userModel = $userModel;
        $this->formatService = $formatService;
        $this->eventManager = $eventManager;
        parent::__construct();
    }

    /**
     * Add mapper methods
     *
     * @param SimpleApiPlugin $sender
     */
    public function simpleAPIPlugin_mapper_handler($sender) {
        switch ($sender->Mapper->Version) {
            case '1.0':
                $sender->Mapper->addMap([
                    'ranks/list' => 'settings/ranks',
                    'ranks/get' => 'settings/ranks'
                ]);
                break;
        }
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Run on utility/update.
     */
    public function structure() {
        require dirname(__FILE__).'/structure.php';
        \Gdn::config()->touch([
            'Preferences.Email.Rank' => 1,
            'Preferences.Popup.Rank' => 1
        ]);
    }

    /**
     * Conditionally block links in activity updates.
     *
     * @param ActivityModel $sender
     * @param type $args
     */
    public function activityModel_beforeSave_handler($sender, $args) {
        if ($this->ActivityLinks !== 'no') {
            return;
        }
        $this->checkForLinks($sender, $args['Activity'], 'Story');
    }

    /**
     * Conditionally block links in activity comments.
     *
     * @param ActivityModel $sender
     * @param $args
     */
    public function activityModel_beforeSaveComment_handler($sender, $args) {
        if ($this->ActivityLinks !== 'no') {
            return;
        }
        $this->checkForLinks($sender, $args['Comment'], 'Body');
    }

    /**
     * Add rank title to author info.
     *
     * @param Gdn_Controller $sender
     * @param $args
     */
    public function base_authorInfo_handler($sender, $args) {
        if (isset($args['Comment'])) {
            $userID = valr('Comment.InsertUserID', $args);
        } elseif (isset($args['Discussion'])) {
            $userID = valr('Discussion.InsertUserID', $args);
        } else {
            return;
        }

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if ($user) {
            echo rankTag($user, 'MItem');
        }
    }

    /**
     * Add Ranks option to Dashboard menu.
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Users', t('Ranks'), 'settings/ranks', 'Garden.Settings.Manage', ['class' => 'nav-ranks']);
    }

    /**
     * Show rank-specific messages.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        if (inSection('Dashboard') || !Gdn::session()->isValid()) {
            return;
        }

        $rankID = Gdn::session()->User->RankID;
        if (!$rankID) {
            return;
        }

        $rank = RankModel::ranks($rankID);
        if (!$rank || !val('Message', $rank)) {
            return;
        }

        $iD = "Rank_$rankID";

        $dismissedMessages = Gdn::session()->getPreference('DismissedMessages', []);
        if (in_array($iD, $dismissedMessages)) {
            return;
        }

        $message = [
            'MessageID' => $iD,
            'Content' => $rank['Message'],
            'Format' => 'Html',
            'AllowDismiss' => true,
            'Enabled' => true,
            'AssetTarget' => 'Content',
            'CssClass' => 'Info'
        ];
        $messageModule = new MessageModule($sender, $message);
        $sender->addModule($messageModule);
    }

    /**
     * Helper to check for links in posts.
     *
     * If a user cannot post links, this will check if a link has been included
     * in the main body of their post, and if one has, the message cannot be
     * posted, and the user is notified.
     *
     * @param mixed $sender The given model.
     * @param array $formValues The form post values.
     * @param string $fieldName The field name in the form to check for links.
     */
    public function checkForLinks($sender, $formValues, $fieldName) {
        $content = $formValues[$fieldName] ?? "";
        $format = $formValues["Format"] ?? "";
        $body = $this->formatService->renderHTML($content, $format);

        if ($this->rankModel->hasExternalLinks($body)) {
            $sender->Validation->addValidationResult($fieldName, t($this->LinksNotAllowedMessage));
        }
    }

     /**
      * Prior to saving a comment, check whether the user can post links.
      *
      * @param CommentModel $sender The comment model.
      * @param BeforeSaveComment $args The event properties.
      */
     public function commentModel_beforeSaveComment_handler($sender, $args) {
         if ($this->CommentLinks !== 'no') {
             return;
         }
         $this->checkForLinks($sender, $args['FormPostValues'], 'Body');
     }

     /**
      * Prior to saving a discussion, check whether the user can post links.
      *
      * @param DiscussionModel $sender The discussion model.
      * @param BeforeSaveDiscussion $args The event properties.
      */
     public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
         if ($this->CommentLinks !== 'no') {
             return;
         }
         $this->checkForLinks($sender, $args['FormPostValues'], 'Body');
     }
    /**
     * Prior to saving a draft, check whether the user can post links.
     *
     * @param DraftModel $sender The draft model.
     * @param BeforeSaveDiscussion $args The event properties.
     */
    public function draftModel_beforeSaveDiscussion_handler($sender, $args) {
        if ($this->CommentLinks !== 'no') {
            return;
        }
        $this->checkForLinks($sender, $args['FormPostValues'], 'Body');
    }
     /**
      * Prior to saving a new private conversation, check whether the user can post links.
      *
      * @param ConversationModel $sender The conversation model.
      * @param BeforeSave $args The event properties.
      */
     public function conversationModel_beforeSaveValidation_handler($sender, $args) {
          if ($this->ConversationLinks !== 'no') {
                return;
          }
          $this->checkForLinks($sender, $args['FormPostValues'], 'Body');
     }

    /**
     * Prior to saving a response to a private conversation, check whether the user can post links.
     *
     * @param ConversationMessageModel $sender The conversation message model.
     * @param array $args The event properties.
     */
    public function conversationMessageModel_beforeSaveValidation_handler($sender, $args) {
         if ($this->ConversationLinks !== 'no') {
              return;
         }
         $this->checkForLinks($sender, $args['FormPostValues'], 'Body');
    }

    /**
     * Set a user's abilities (perhaps too) early in the page request.
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        if (!Gdn::session()->UserID) {
            return;
        }
        RankModel::applyAbilities();
    }

    /**
     * Grab a schema for use in displaying a minimal subset of rank fields.
     *
     * @return Schema
     */
    public function getRankFragment() {
        static $rankFragment;

        if ($rankFragment === null) {
            $rankFragment = Schema::parse([
                'rankID:i' => 'Rank ID.',
                'name:s' => 'Name of the rank.',
                'userTitle:s' => 'Label that will display beside the user.'
            ]);
        }

        return $rankFragment;
    }

    /**
     * Get a subset of user rank data for use in API output.
     *
     * @param array $user
     * @return array|null
     */
    private function getUserRank(array $user) {
        $result = null;

        $rankID = $user['rankID'] ?? $user['RankID'] ?? null;
        if ($rankID) {
            $rank = $this->rankModel->getID($rankID, DATASET_TYPE_ARRAY);
            if ($rank) {
                // Prepare the rank data.
                $rank = ApiUtils::convertOutputKeys($rank);
                $rank = arrayTranslate($rank, ['label' => 'userTitle'], true);

                // Verify we have everything we need.
                $schema = $this->getRankFragment();
                $rank = $schema->validate($rank);

                $result = $rank;
            }
        }

        return $result;
    }

    /**
     * Set the rank of the profile owner.
     *
     * @param ProfileController $sender
     */
    public function profileController_render_before($sender) {
        $rankID = $sender->data('Profile.RankID');
        $rank = RankModel::ranks($rankID);
        if ($rank) {
            $rank = arrayTranslate($rank, ['RankID', 'Level', 'Name', 'Label']);
            $sender->setData('Rank', $rank);
        }
    }

    /**
     * Add rank to user meta list on profile.
     *
     * @param ProfileController $sender
     */
    public function profileController_usernameMeta_handler($sender) {
        $user = $sender->data('Profile');
        $rank = RankModel::ranks($sender->data('Profile.RankID'));
        if ($user && $rank['Label'] !== $sender->User->Title) {
            echo rankTag($user, '', ' '.Gdn_Theme::bulletItem('Rank').' ');
        }
    }

    /**
     * Add option to change rank to profile edit.
     *
     * @param ProfileController $sender
     */
    public function profileController_editMyAccountAfter_handler($sender) {
        $this->addManualRanks($sender);
    }

    /**
     * Add option to change rank to user edit.
     *
     * @param UserController $sender
     */
    public function userController_customUserFields_handler($sender) {
        $this->addManualRanks($sender);
    }

    /**
     * Add the rank changer dropdown to a page.
     *
     * @param Gdn_Controller $Sender
     * @throws Exception
     */
    protected function addManualRanks($Sender) {
        if (!checkPermission(['Garden.Settings.Manage', 'Garden.Users.Edit'])) {
            return;
        }

        // Grab a list of all of the manual ranks.
        $AllRanks = RankModel::ranks();
        $Ranks = [];
        foreach ($AllRanks as $RankID => $Rank) {
            if (valr('Criteria.Manual', $Rank)) {
                $Ranks[$RankID] = $Rank['Name'];
            }
        }
        if (count($Ranks) == 0) {
            return;
        }

        $Sender->setData('_Ranks', $Ranks);
        include $Sender->fetchViewLocation('Rank_Formlet', '', 'plugins/Ranks');
    }

    /**
     * Endpoint to delete a rank.
     *
     * @param Gdn_Controller $sender
     * @param type $rankID
     */
    public function settingsController_deleteRank_create($sender, $rankID) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            $rankModel = new RankModel();
            $rankModel->delete(['RankID' => $rankID]);
            $sender->jsonTarget("#Rank_$rankID", null, 'SlideUp');
        }

        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Endpoint to edit a rank.
     *
     * @param SettingsController $sender
     * @param int $rankID
     */
    public function settingsController_editRank_create($sender, $rankID) {
        $sender->title(sprintf(t('Edit %s'), t('Rank')));
        $this->addEdit($sender, $rankID);
    }

     /**
      * Generic add/edit form for a rank.
      *
      * @param Gdn_Controller $sender
      * @param int|bool $rankID
      * @throws Exception
      */
    protected function addEdit($sender, $rankID = false) {
        $sender->permission('Garden.Settings.Manage');

        $rankModel = new RankModel();

        // Load the default from the bak first because the user editing this rank may not have
        $defaultFormat = strtolower(c('Garden.InputFormatterBak', c('Garden.InputFormatter')));
        if ($defaultFormat === 'textex') {
            $defaultFormat = 'text, links, youtube';
        }

        $formats = ['Text' => 'text', 'TextEx' => 'text, links, and youtube', '' => sprintf('default (%s)', $defaultFormat)];
        $sender->setData('_Formats', $formats);

        $roles = RoleModel::roles();
        $roles = array_column($roles, 'Name', 'RoleID');
        $sender->setData('_Roles', $roles);

        // Allow other plugins to add controls to the Rank form.
        $extendedControls = $sender->data('_ExtendedControls', []);
        $extendedControls = $this->eventManager->fireFilter('ranksPlugin_extendControls', $extendedControls);
        $sender->setData('_ExtendedControls', $extendedControls);

        if ($sender->Form->authenticatedPostBack()) {
            $data = $sender->Form->formValues();
            unset($data['hpt'], $data['Checkboxes'], $data['Save']);

            $saveData = [];
            foreach ($data as $key => $value) {
                if (strpos($key, '_') !== false) {
                    if ($value === '') {
                        continue;
                    }
                    $parts = explode('_', $key, 2);
                    $saveData[$parts[0]][$parts[1]] = $value;
                } else {
                    $saveData[$key] = $value;
                }
            }

            $sender->EventArguments['SaveData'] = &$saveData;
            $sender->fireEvent('RankBeforeSave');
            $result = $rankModel->save($saveData);
            $sender->Form->setValidationResults($rankModel->validationResults());
            if ($result) {
                $sender->informMessage(t('Your changes have been saved.'));
                $sender->setRedirectTo('/settings/ranks');
                $sender->setData('Rank', RankModel::ranks($result));
            }
        } else {
            if ($rankID) {
                $data = $rankModel->getID($rankID);

                if (!$data) {
                    throw notFoundException('Rank');
                }

                $setData = [];
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $key2 => $value2) {
                            $setData[$key.'_'.$key2] = $value2;
                        }
                    } else {
                        $setData[$key] = $value;
                    }
                }

                $sender->Form->setData($setData);
                $sender->Form->addHidden('RankID', $rankID);
            }
        }
        $sender->addSideMenu();
        $sender->render('Rank', '', 'plugins/Ranks');
    }

    /**
     * Endpoint to add a rank.
     *
     * @param SettingsController $sender
     */
    public function settingsController_addRank_create($sender) {
        $sender->title('Add Rank');
        $this->addEdit($sender);
    }

    /**
     * Endpoint to view all ranks.
     *
     * @param SettingsController $sender
     * @param null|int $rankID
     */
    public function settingsController_ranks_create($sender, $rankID = null) {
        $sender->permission('Garden.Settings.Manage');

        $rankModel = new RankModel();

        if (empty($rankID)) {
            $ranks = $rankModel->getWhere(false, 'Level')->resultArray();
        } else {
            $rank = $rankModel->getID($rankID);
            $ranks = [$rank];
        }

        $sender->setData('Ranks', $ranks);
        $sender->addSideMenu();
        $sender->render('Ranks', '', 'plugins/Ranks');
    }

    /**
     * Endpoint to apply a rank to a user.
     *
     * @param ProfileController $sender
     */
    public function profileController_applyRank_create($sender) {
        $user = Gdn::session()->User;
        if (!$user) {
            return;
        }

        $rankModel = new RankModel();
        $result = $rankModel->applyRank($user);

        $sender->Data = $result;
        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Evaluate users for new rank when registering.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_afterRegister_handler($sender, $args) {
        $userID = $args['UserID'];
        $user = Gdn::userModel()->getID($userID);

        $rankModel = new RankModel();
        $rankModel->applyRank($user);
    }

    /**
     * Evaluate users for new rank when signing in.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_afterSignIn_handler($sender, $args) {
        if (!Gdn::session()->User) {
            return;
        }
        $rankModel = new RankModel();
        $rankModel->applyRank(Gdn::session()->UserID);
    }

    /**
     * Handles manual ranks.
     * Prevents insertion of empty strings in the int RankID column.
     * Prevents overwriting "auto" ranks with NULL value from the manual ranks drop down.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_beforeSave_handler($sender, $args) {
        $oldRankID = valr('User.RankID', $args);
        $newRankID = valr('Fields.RankID', $args);

        if (isset($args['Fields']['RankID']) && empty($args['Fields']['RankID'])) {
            $args['Fields']['RankID'] = $newRankID = null;
        }

        $rankModel = new RankModel();
        $oldRank = $rankModel->getID($oldRankID);
        $newRank = $rankModel->getID($newRankID);

        // The empty rank option was selected.
        // Remove the RankID from the form if
        // RankID is NULL and current rank is an "auto" rank;
        // Old rank and new rank are the same;
        // New rank is not null and is not a manual rank.
        if (($newRankID === null && !valr('Criteria.Manual', $oldRank)) || ($oldRankID == $newRankID) || ($newRankID !== null && !valr('Criteria.Manual', $newRank))) {
            unset($args['Fields']['RankID']);
        }
    }

    /**
     * Evaluate users for new rank when saving their state.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_afterSave_handler($sender, $args) {
        if (!Gdn::controller()) {
            return;
        }

        // The if portion of this if/else statement was kept for assumed backwards compatibility.
        if (Gdn::controller() instanceof ProfileController) {
            $userID = Gdn::controller()->data('Profile.UserID');
            if ($userID != $args['UserID']) {
                return;
            }
            $oldRankID = Gdn::controller()->data('Profile.RankID');
        } else {
            $userID = $args['UserID'];
            $oldRankID = valr('User.RankID', $args);
        }

        $newRankID = val('RankID', $args['Fields']);

        // Check to make sure the rank has changed.
        if ($oldRankID != $newRankID && $newRankID !== false) {
            $rankModel = new RankModel();
            // We have overridden a previously manually applied rank with the null value.
            if ($newRankID === null) {
                $rankModel->applyRank($userID);
            // We have applied a new manual rank.
            } else {
                $rankModel->notify(Gdn::userModel()->getID($userID), $rankModel->getID($newRankID));
            }
        }
    }

    /**
     * Evaluate users for new rank when receiving points.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_givePoints_handler($sender, $args) {
        $userID = $args['UserID'];
        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        $rankModel = new RankModel();
        $rankModel->applyRank($user);
    }

    /**
     * Evaluate users for new rank on each visit.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_visit_handler($sender, $args) {
        if (!Gdn::session()->isValid()) {
            return;
        }
        $rankModel = new RankModel();
        $rankModel->applyRank(Gdn::session()->UserID);
    }

    /**
     * Set a user's properties.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_setCalculatedFields_handler($sender, $args) {
        $rankID = val('RankID', $args['User'], 0);
        $rank = RankModel::ranks($rankID);

        if ($rank) {
            if (isset($rank['CssClass'])) {
                $cssClass = val('_CssClass', $args['User']);
                $cssClass .= ' '.$rank['CssClass'];
                setValue('_CssClass', $args['User'], trim($cssClass));
            }

            if (($rank['Abilities']['Signatures'] ?? false) == 'no') {
                setValue('HideSignature', $args['User'], true);
            }

            if (($rank['Abilities']['Titles'] ?? false) == 'no') {
                // Strip away a title if it exists.
                setValue('Title', $args['User'], '');
            } elseif (!getValue('Title', $args['User'])) {
                $rankLabel = $rank['Label'] ?? '';
                // HTML rank titles go into a special field because they need special handling (bypass sanitization).
                setValue('Label', $args['User'], $rankLabel);
            }

            if (($rank['Abilities']['Locations'] ?? false) == 'no') {
                setValue('Location', $args['User'], '');
            }

            $v = $rank['Abilities']['Verified'] ?? null;
            if (!is_null($v)) {
                $verified = ['yes' => 1, 'no'  => 0];
                $verified = val($v, $verified, null);
                if (is_integer($verified)) {
                    setValue('Verified', $args['User'], $verified);
                }
            }
        }
    }

    /**
     * Alter a schema's expand parameter to include reactions.
     *
     * @param Schema $schema
     */
    private function updateSchemaExpand(Schema $schema) {
        /** @var Schema $expand */
        $expandEnum = $schema->getField('properties.expand.items.enum');
        if (is_array($expandEnum)) {
            if (!in_array('rank', $expandEnum)) {
                $expandEnum[] = 'rank';
                $schema->setField('properties.expand.items.enum', $expandEnum);
            }
        } else {
            $schema->merge(Schema::parse([
                'expand?' => ApiUtils::getExpandDefinition(['rank'])
            ]));
        }
    }

    /**
     * Modify the data on /api/v2/users/:id to include rank.
     *
     * @param array $result Post-validated data.
     * @param UsersApiController $sender
     * @param Schema $inSchema
     * @param array $query The request query.
     * @param array $row Pre-validated data.
     * @return array
     */
    public function usersApiController_getOutput(array $result, UsersApiController $sender, Schema $inSchema, array $query, array $row) {
        $expand = $query['expand'] ?? [];

        if ($sender->isExpandField('rank', $expand)) {
            $rank = $this->getUserRank($row);
            if ($rank) {
                $result['rank'] = $rank;
            }
        }

        return $result;
    }

    /**
     * Modify the data on /api/v2/users index to include ranks.
     *
     * @param array $result Post-validated data.
     * @param UsersApiController $sender
     * @param Schema $inSchema
     * @param array $query The request query.
     * @param array $rows Raw result.
     * @return array
     */
    public function usersApiController_indexOutput(array $result, UsersApiController $sender, Schema $inSchema, array $query, array $rows) {
        $expand = $query['expand'] ?? [];

        if ($sender->isExpandField('rank', $expand)) {
            $rows = array_column($rows, null, 'userID');

            foreach ($result as &$row) {
                $userID = $row['userID'];
                $rank = $this->getUserRank($rows[$userID]);
                if ($rank) {
                    $row['rank'] = $rank;
                }
            }
        }

        return $result;
    }

    /**
     * Update a user's rank.
     *
     * @param int $id
     * @param UsersApiController $sender
     * @param array $body
     */
    public function usersApiController_put_rank(UsersApiController $sender, $id, array $body) {
        $sender->permission('Garden.Users.Edit');

        $in = $sender->schema([
            'rankID:i|n' => 'ID of the user rank.'
        ], 'in')->setDescription('Update the rank of a user.');
        $out = $sender->schema(['rankID:i|n' => 'ID of the user rank.'], 'out');

        $body = $in->validate($body);
        $user = $sender->userByID($id);

        if ($body['rankID'] === null) {
            $user['RankID'] = null;
            $rankID = $this->rankModel->determineUserRank($user);
        } else {
            $rankID = $body['rankID'];
            $manualRank = $this->rankModel->getID($rankID);
            if (!$manualRank) {
                throw new NotFoundException('Rank', ['rankID' => $rankID]);
            }
            $isManual = valr('Criteria.Manual', $manualRank);
            if (!$isManual) {
                throw new ClientException('Rank is not configured to be applied manually.', 400, ['rankID' => $rankID]);
            }
        }

        $this->userModel->setField($id, 'RankID', $rankID);
        $sender->validateModel($this->userModel);

        $updatedUser = $sender->userByID($id);
        $result = $out->validate($updatedUser);
        return $result;
    }

    /**
     * Add rank data to the user row schema.
     *
     * @param Schema $schema
     */
    public function userSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'rankID:i|n' => 'ID of the user rank.',
            'rank?' => $this->getRankFragment()
        ]));
    }

    /**
     * Update the /users/get input schema.
     *
     * @param Schema $schema
     */
    public function userGetSchema_init(Schema $schema) {
        $this->updateSchemaExpand($schema);
    }

    /**
     * Update the /users index input schema.
     *
     * @param Schema $schema
     */
    public function userIndexSchema_init(Schema $schema) {
        $this->updateSchemaExpand($schema);
    }

    /**
     * Adds status notification options to profiles.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.Rank'] = t('PreferenceRankEmail', 'Notify me when my rank changes.');
        $sender->Preferences['Notifications']['Popup.Rank'] = t('PreferenceRankPopup', 'Notify me when my rank changes.');
    }
}

if (!function_exists('rankTag')):
    /**
     * Output HTML for a user's rank.
     *
     * @param array|object $user
     * @param string $cssClass
     * @param string $px
     * @return string|void
     */
    function rankTag($user, $cssClass, $px = ' ') {
        $rankID = val('RankID', $user);
        if (!$rankID) {
            return;
        }
        $rank = RankModel::ranks($rankID);
        if (!$rank) {
            return;
        }
        $cssClass = concatSep(' ', 'Rank', $cssClass, val('CssClass', $rank));
        $result = $px.wrap($rank['Label'], 'span', ['class' => $cssClass, 'title' => $rank['Name']]);
        return $result;
    }

endif;
