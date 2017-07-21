<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

class RanksPlugin extends Gdn_Plugin {

    /** @var null|array  */
    public $ActivityLinks = null;

    /** @var null|array  */
    public $CommentLinks = null;

    /** @var null|array  */
    public $ConversationLinks = null;

    /** @var string  */
    public $LinksNotAllowedMessage = 'You have to be around for a little while longer before you can post links.';

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
        $menu->addLink('Users', T('Ranks'), 'settings/ranks', 'Garden.Settings.Manage', ['class' => 'nav-ranks']);
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
          if (preg_match('`https?://`i', $formValues[$fieldName])) {
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
        if ($user) {
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
     */
    protected function addManualRanks($Sender) {
        if (!checkPermission('Garden.Settings.Manage')) {
            return;
        }

        // Grab a list of all of the manual ranks.
        $CurrentRankID = $Sender->data('Profile.RankID');
        $AllRanks = RankModel::ranks();
        $Ranks = [];
        foreach ($AllRanks as $RankID => $Rank) {
            if ($RankID == $CurrentRankID || valr('Criteria.Manual', $Rank)) {
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
                    throw NotFoundException('Rank');
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
     * Evaluate users for new rank when saving their state.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_afterSave_handler($sender, $args) {
        if (!Gdn::controller()) {
            return;
        }
        $userID = Gdn::controller()->data('Profile.UserID');
        if ($userID != $args['UserID']) {
            return;
        }

        // Check to make sure the rank has changed.
        $oldRankID = Gdn::controller()->data('Profile.RankID');
        $newRankID = val('RankID', $args['Fields']);
        if ($newRankID && $newRankID != $oldRankID) {
            // Send the user a notification.
            $rankModel = new RankModel();
            $rankModel->notify(Gdn::userModel()->getID($userID), $rankModel->getID($newRankID));
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

            if (valr('Abilities.Signatures', $rank) == 'no') {
                setValue('HideSignature', $args['User'], true);
            }

            if (valr('Abilities.Titles', $rank) == 'no') {
                setValue('Title', $args['User'], '');
            }

            if (valr('Abilities.Locations', $rank) == 'no') {
                setValue('Location', $args['User'], '');
            }

            $v = valr('Abilities.Verified', $rank, null);
            if (!is_null($v)) {
                $verified = ['yes' => 1, 'no'  => 0];
                $verified = val($v, $verified, null);
                if (is_integer($verified)) {
                    setValue('Verified', $args['User'], $verified);
                }
            }
        }
    }
}

if (!function_exists('WriteUserRank')):
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
