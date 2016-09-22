<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Ranks'] = array(
    'Name' => 'Ranks',
    'Description' => "Adds user ranks to the application.",
    'Version' => '1.3.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'MobileFriendly' => true,
    'Icon' => 'ranks.png'
);

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
     * @param SimpleApiPlugin $Sender
     */
    public function simpleApiPlugin_Mapper_Handler($Sender) {
        switch ($Sender->Mapper->Version) {
            case '1.0':
                $Sender->Mapper->addMap([
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
     * @param ActivityModel $Sender
     * @param type $Args
     */
    public function activityModel_beforeSave_handler($Sender, $Args) {
        if ($this->ActivityLinks !== 'no') {
            return;
        }
        $this->checkForLinks($Sender, $Args['Activity'], 'Story');
    }

    /**
     * Conditionally block links in activity comments.
     *
     * @param ActivityModel $Sender
     * @param $Args
     */
    public function activityModel_beforeSaveComment_handler($Sender, $Args) {
        if ($this->ActivityLinks !== 'no') {
            return;
        }
        $this->checkForLinks($Sender, $Args['Comment'], 'Body');
    }

    /**
     * Add rank title to author info.
     *
     * @param Gdn_Controller $Sender
     * @param $Args
     */
    public function base_authorInfo_handler($Sender, $Args) {
        if (isset($Args['Comment'])) {
            $UserID = valr('Comment.InsertUserID', $Args);
        } elseif (isset($Args['Discussion'])) {
            $UserID = valr('Discussion.InsertUserID', $Args);
        } else {
            return;
        }

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if ($User) {
            echo rankTag($User, 'MItem');
        }
    }

    /**
     * Add Ranks option to Dashboard menu.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_getAppSettingsMenuItems_handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->addLink('Reputation', T('Ranks'), 'settings/ranks', 'Garden.Settings.Manage', ['class' => 'nav-ranks']);
    }

    /**
     * Show rank-specific messages.
     *
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        if (inSection('Dashboard') || !Gdn::session()->isValid()) {
            return;
        }

        $RankID = Gdn::session()->User->RankID;
        if (!$RankID) {
            return;
        }

        $Rank = RankModel::ranks($RankID);
        if (!$Rank || !val('Message', $Rank)) {
            return;
        }

        $ID = "Rank_$RankID";

        $DismissedMessages = Gdn::session()->getPreference('DismissedMessages', []);
        if (in_array($ID, $DismissedMessages)) {
            return;
        }

        $Message = [
            'MessageID' => $ID,
            'Content' => $Rank['Message'],
            'Format' => 'Html',
            'AllowDismiss' => true,
            'Enabled' => true,
            'AssetTarget' => 'Content',
            'CssClass' => 'Info'
        ];
        $MessageModule = new MessageModule($Sender, $Message);
        $Sender->addModule($MessageModule);
    }

     /**
      * Helper to check for links in posts.
      *
      * If a user cannot post links, this will check if a link has been included
      * in the main body of their post, and if one has, the message cannot be
      * posted, and the user is notified.
      *
      * @param mixed $Sender The given model.
      * @param array $FormValues The form post values.
      * @param string $FieldName The field name in the form to check for links.
      */
     public function checkForLinks($Sender, $FormValues, $FieldName) {
          if (preg_match('`https?://`i', $FormValues[$FieldName])) {
                $Sender->Validation->addValidationResult($FieldName, t($this->LinksNotAllowedMessage));
          }
     }

     /**
      * Prior to saving a comment, check whether the user can post links.
      *
      * @param CommentModel $Sender The comment model.
      * @param BeforeSaveComment $Args The event properties.
      */
     public function commentModel_beforeSaveComment_handler($Sender, $Args) {
         if ($this->CommentLinks !== 'no') {
             return;
         }
         $this->checkForLinks($Sender, $Args['FormPostValues'], 'Body');
     }

     /**
      * Prior to saving a discussion, check whether the user can post links.
      *
      * @param DiscussionModel $Sender The discussion model.
      * @param BeforeSaveDiscussion $Args The event properties.
      */
     public function discussionModel_beforeSaveDiscussion_handler($Sender, $Args) {
         if ($this->CommentLinks !== 'no') {
             return;
         }
         $this->checkForLinks($Sender, $Args['FormPostValues'], 'Body');
     }

     /**
      * Prior to saving a new private conversation, check whether the user can post links.
      *
      * @param ConversationModel $Sender The conversation model.
      * @param BeforeSave $Args The event properties.
      */
     public function conversationModel_beforeSaveValidation_handler($Sender, $Args) {
          if ($this->ConversationLinks !== 'no') {
                return;
          }
          $this->checkForLinks($Sender, $Args['FormPostValues'], 'Body');
     }

    /**
     * Prior to saving a response to a private conversation, check whether the user can post links.
     *
     * @param ConversationMessageModel $Sender The conversation message model.
     * @param array $Args The event properties.
     */
    public function conversationMessageModel_BeforeSaveValidation_Handler($Sender, $Args) {
         if ($this->ConversationLinks !== 'no') {
              return;
         }
         $this->checkForLinks($Sender, $Args['FormPostValues'], 'Body');
    }

    /**
     * Set a user's abilities (perhaps too) early in the page request.
     *
     * @param Gdn_Dispatcher $Sender
     */
    public function gdn_dispatcher_appStartup_handler($Sender) {
        if (!Gdn::session()->UserID) {
            return;
        }
        RankModel::applyAbilities();
    }

    /**
     * Set the rank of the profile owner.
     *
     * @param ProfileController $Sender
     */
    public function profileController_Render_Before($Sender) {
        $RankID = $Sender->data('Profile.RankID');
        $Rank = RankModel::ranks($RankID);
        if ($Rank) {
            $Rank = arrayTranslate($Rank, ['RankID', 'Level', 'Name', 'Label']);
            $Sender->setData('Rank', $Rank);
        }
    }

    /**
     * Add rank to user meta list on profile.
     *
     * @param ProfileController $Sender
     */
    public function profileController_usernameMeta_handler($Sender) {
        $User = $Sender->data('Profile');
        if ($User) {
            echo rankTag($User, '', ' '.Gdn_Theme::bulletItem('Rank').' ');
        }
    }

    /**
     * Add option to change rank to profile edit.
     *
     * @param ProfileController $Sender
     */
    public function profileController_editMyAccountAfter_handler($Sender) {
        $this->addManualRanks($Sender);
    }

    /**
     * Add option to change rank to user edit.
     *
     * @param UserController $Sender
     */
    public function userController_customUserFields_handler($Sender) {
        $this->addManualRanks($Sender);
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
     * @param Gdn_Controller $Sender
     * @param type $RankID
     */
    public function settingsController_deleteRank_create($Sender, $RankID) {
        $Sender->permission('Garden.Settings.Manage');

        if ($Sender->Form->authenticatedPostBack()) {
            $RankModel = new RankModel();
            $RankModel->delete(array('RankID' => $RankID));
            $Sender->jsonTarget("#Rank_$RankID", null, 'SlideUp');
        }

        $Sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Endpoint to edit a rank.
     *
     * @param SettingsController $Sender
     * @param int $RankID
     */
    public function settingsController_editRank_create($Sender, $RankID) {
        $Sender->title(sprintf(t('Edit %s'), t('Rank')));
        $this->addEdit($Sender, $RankID);
    }

     /**
      * Generic add/edit form for a rank.
      *
      * @param Gdn_Controller $Sender
      * @param int|bool $RankID
      * @throws Exception
      */
    protected function addEdit($Sender, $RankID = false) {
        $Sender->permission('Garden.Settings.Manage');

        $RankModel = new RankModel();

        // Load the default from the bak first because the user editing this rank may not have
        $DefaultFormat = strtolower(c('Garden.InputFormatterBak', c('Garden.InputFormatter')));
        if ($DefaultFormat === 'textex') {
            $DefaultFormat = 'text, links, youtube';
        }

        $Formats = ['Text' => 'text', 'TextEx' => 'text, links, and youtube', '' => sprintf('default (%s)', $DefaultFormat)];
        $Sender->setData('_Formats', $Formats);

        $roles = RoleModel::roles();
        $roles = array_column($roles, 'Name', 'RoleID');
        $Sender->setData('_Roles', $roles);

        if ($Sender->Form->authenticatedPostBack()) {
            $Data = $Sender->Form->formValues();
            unset($Data['hpt'], $Data['Checkboxes'], $Data['Save']);

            $SaveData = [];
            foreach ($Data as $Key => $Value) {
                if (strpos($Key, '_') !== false) {
                    if ($Value === '') {
                        continue;
                    }
                    $Parts = explode('_', $Key, 2);
                    $SaveData[$Parts[0]][$Parts[1]] = $Value;
                } else {
                    $SaveData[$Key] = $Value;
                }
            }

            $Result = $RankModel->save($SaveData);
            $Sender->Form->setValidationResults($RankModel->validationResults());
            if ($Result) {
                $Sender->informMessage(t('Your changes have been saved.'));
                $Sender->RedirectUrl = url('/settings/ranks');
                $Sender->setData('Rank', RankModel::ranks($Result));
            }
        } else {
            if ($RankID) {
                $Data = $RankModel->getID($RankID);

                if (!$Data) {
                    throw NotFoundException('Rank');
                }

                $SetData = [];
                foreach ($Data as $Key => $Value) {
                    if (is_array($Value)) {
                        foreach ($Value as $Key2 => $Value2) {
                            $SetData[$Key.'_'.$Key2] = $Value2;
                        }
                    } else {
                        $SetData[$Key] = $Value;
                    }
                }

                $Sender->Form->setData($SetData);
                $Sender->Form->addHidden('RankID', $RankID);
            }
        }
        $Sender->addSideMenu();
        $Sender->render('Rank', '', 'plugins/Ranks');
    }

    /**
     * Endpoint to add a rank.
     *
     * @param SettingsController $Sender
     */
    public function settingsController_addRank_create($Sender) {
        $Sender->title('Add Rank');
        $this->addEdit($Sender);
    }

    /**
     * Endpoint to view all ranks.
     *
     * @param SettingsController $Sender
     * @param null|int $RankID
     */
    public function settingsController_ranks_create($Sender, $RankID = null) {
        $Sender->permission('Garden.Settings.Manage');

        $RankModel = new RankModel();

        if (empty($RankID)) {
            $Ranks = $RankModel->getWhere(false, 'Level')->resultArray();
        } else {
            $Rank = $RankModel->getID($RankID);
            $Ranks = array($Rank);
        }

        $Sender->setData('Ranks', $Ranks);
        $Sender->addSideMenu();
        $Sender->render('Ranks', '', 'plugins/Ranks');
    }

    /**
     * Endpoint to apply a rank to a user.
     *
     * @param ProfileController $Sender
     */
    public function profileController_applyRank_create($Sender) {
        $User = Gdn::session()->User;
        if (!$User) {
            return;
        }

        $RankModel = new RankModel();
        $Result = $RankModel->applyRank($User);

        $Sender->Data = $Result;
        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Evaluate users for new rank when registering.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_afterRegister_handler($Sender, $Args) {
        $UserID = $Args['UserID'];
        $User = Gdn::userModel()->getID($UserID);

        $RankModel = new RankModel();
        $RankModel->applyRank($User);
    }

    /**
     * Evaluate users for new rank when signing in.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_afterSignIn_handler($Sender, $Args) {
        if (!Gdn::session()->User) {
            return;
        }
        $RankModel = new RankModel();
        $RankModel->applyRank(Gdn::session()->User);
    }

    /**
     * Evaluate users for new rank when saving their state.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_afterSave_handler($Sender, $Args) {
        if (!Gdn::controller()) {
            return;
        }
        $UserID = Gdn::controller()->data('Profile.UserID');
        if ($UserID != $Args['UserID']) {
            return;
        }

        // Check to make sure the rank has changed.
        $OldRankID = Gdn::controller()->data('Profile.RankID');
        $NewRankID = val('RankID', $Args['Fields']);
        if ($NewRankID && $NewRankID != $OldRankID) {
            // Send the user a notification.
            $RankModel = new RankModel();
            $RankModel->notify(Gdn::userModel()->getID($UserID), $RankModel->getID($NewRankID));
        }
    }

    /**
     * Evaluate users for new rank when receiving points.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_givePoints_handler($Sender, $Args) {
        $UserID = $Args['UserID'];
        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        $RankModel = new RankModel();
        $RankModel->applyRank($User);
    }

    /**
     * Evaluate users for new rank on each visit.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_visit_handler($Sender, $Args) {
        if (!Gdn::session()->isValid()) {
            return;
        }
        $RankModel = new RankModel();
        $RankModel->applyRank(Gdn::session()->User);
    }

    /**
     * Set a user's properties.
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function userModel_setCalculatedFields_handler($Sender, $Args) {
        $RankID = val('RankID', $Args['User'], 0);
        $Rank = RankModel::ranks($RankID);

        if ($Rank) {
            if (isset($Rank['CssClass'])) {
                $CssClass = val('_CssClass', $Args['User']);
                $CssClass .= ' '.$Rank['CssClass'];
                setValue('_CssClass', $Args['User'], trim($CssClass));
            }

            if (valr('Abilities.Signatures', $Rank) == 'no') {
                setValue('HideSignature', $Args['User'], true);
            }

            if (valr('Abilities.Titles', $Rank) == 'no') {
                setValue('Title', $Args['User'], '');
            }

            if (valr('Abilities.Locations', $Rank) == 'no') {
                setValue('Location', $Args['User'], '');
            }

            $V = valr('Abilities.Verified', $Rank, null);
            if (!is_null($V)) {
                $Verified = ['yes' => 1, 'no'  => 0];
                $Verified = val($V, $Verified, null);
                if (is_integer($Verified)) {
                    setValue('Verified', $Args['User'], $Verified);
                }
            }
        }
    }
}

if (!function_exists('WriteUserRank')):
    /**
     * Output HTML for a user's rank.
     *
     * @param array|object $User
     * @param string $CssClass
     * @param string $Px
     * @return string|void
     */
    function rankTag($User, $CssClass, $Px = ' ') {
        $RankID = val('RankID', $User);
        if (!$RankID) {
            return;
        }
        $Rank = RankModel::ranks($RankID);
        if (!$Rank) {
            return;
        }
        $CssClass = concatSep(' ', 'Rank', $CssClass, val('CssClass', $Rank));
        $Result = $Px.wrap($Rank['Label'], 'span', ['class' => $CssClass, 'title' => $Rank['Name']]);
        return $Result;
    }

endif;
