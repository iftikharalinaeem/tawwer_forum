<?php

/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Warnings2'] = array(
    'Name' => 'Warnings & Notes',
    'Description' => 'Allows moderators to warn users and add private notes to profiles to help police the community.',
    'Version' => '2.5',
    'RequiredApplications' => ['Vanilla' => '2.1'],
    'MobileFriendly' => true,
    'SettingsUrl' => '/settings/warnings',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'Icon' => 'warnings.png'
);

/**
 * Plugin that allows moderators to warn users and help police the community.
 *
 * Permissions
 *
 * - Garden.Moderation.Manage
 * - Moderation.UserNotes.View
 * - Moderation.UserNotes.Add
 * - Moderation.Warnings.Add
 */
class Warnings2Plugin extends Gdn_Plugin {

    /// Properties ///

    public $pageSize = 20;

    /**
     * @var bool Whether or not to restrict the viewing of warnings on posts.
     */
    public $PublicPostWarnings = false;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link Warnings2Plugin}.
     */
    public function __construct() {
        parent::__construct();
        $this->fireEvent('Init');
    }

    /**
     * {@inheritdoc}
     */
    public function setup() {
        $this->structure();
    }

    /**
     * {@inheritdoc}
     */
    public function structure() {
        require __DIR__.'/structure.php';

        if (Gdn::addonManager()->isEnabled('Warnings', \Vanilla\Addon::TYPE_ADDON)) {
            Gdn::pluginManager()->disablePlugin('Warnings');
        }
    }

    /**
     * Create a new endpoint on the SettingsController.
     *
     * @param SettingsController $sender Sending controller instance.
     */
    public function settingsController_warnings_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s Settings'), t('Warnings & Notes')));
        $sender->addSideMenu('settings/warnings');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $warningTypeModel = new WarningTypeModel();
        $sender->setData('Warnings', $warningTypeModel->getAll());

        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/Warnings2'));
    }
    /**
     * Return the HTML for a warning reaction button.
     *
     * @param array $row The record to warn the user for.
     * @param string $recordType The type of record to warn the user for.
     * @param integer $recordID The ID of the record to warn the user for.
     *
     * @return string Returns a string of HTML that represents the warning button.
     */
    public function warnButton($row, $recordType, $recordID) {
        $args = array(
            'userid' => val('InsertUserID', $row),
            'recordtype' => $recordType,
            'recordid' => $recordID
        );

        $Result = anchor(
            '<span class="ReactSprite ReactWarn"></span> '.t('Warn'),
            '/profile/warn?'.http_build_query($args),
            'ReactButton ReactButton-Warn Popup'
        );
        return $Result;
    }

    /// Event Handlers ///

    /**
     * Process expired warning on sign in.
     */
    public function base_afterSignIn_handler() {
        if (Gdn::Session()->UserID) {
            $WarningModel = new WarningModel();
            $WarningModel->processWarnings(Gdn::session()->UserID);
        }
    }

    /**
     * Process warnings when a user visits.
     */
    public function userModel_visit_handler() {
        if (Gdn::session()->UserID) {
            $WarningModel = new WarningModel();
            $WarningModel->processWarnings(Gdn::session()->UserID);
        }
    }

    /**
     * Hijack ban notifications and add a note instead.
     *
     * @param ActivityModel $sender The activity model sending the ban notification.
     * @param array $args Event arguments.
     */
    public function activityModel_beforeSave_handler($sender, $args) {
        if (!isset($args['Activity'])) {
            return;
        }

        $Activity = &$args['Activity'];
        if (!is_array($Activity)) {
            return;
        }

        $ActivityType = strtolower(val('ActivityType', $Activity));
        if (strcasecmp($ActivityType, 'ban') !== 0) {
            return;
        }

        $Data = $Activity['Data'];
        if (is_string($Data)) {
            $Data = dbdecode($Data);
        }

        $body = val('Story', $Activity);
        if (val('Unban', $Data)) {
            $type = 'unban';
            if (!$body) {
                $body = t('User was unbanned.');
            }
        } else {
            $type = 'ban';
            if (!$body) {
                $body = t('User was banned.');
            }
        }

        $model = new UserNoteModel();
        $row = array(
            'Type' => $type,
            'UserID' => val('ActivityUserID', $Activity),
            'Body' => $body,
            'Format' => val('Format', $Activity, 'text'),
            'InsertUserID' => val('RegardingUserID', $Activity, Gdn::session()->UserID),
        );
        $model->save($row);

        // Don't save the activity.
        $args['Handled'] = true;
    }

    /**
     * Show if this post triggered a warning to give moderators context.
     *
     * @param object $sender The event sender.
     * @param array $args The event arguments.
     */
    public function base_beforeCommentBody_handler($sender, $args) {
        if (isset($args['Comment'])) {
            $Row = $args['Comment'];
        } else {
            $Row = $args['Discussion'];
        }

        if (!$this->PublicPostWarnings) {
            // Only show warnings to moderators
            $permissionCheck = !checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);
            if (val('InsertUserID', $Row) != Gdn::session()->UserID && $permissionCheck) {
                return;
            }
        }

        $Row->Attributes = dbdecode($Row->Attributes);
        if (isset($Row->Attributes['WarningID']) && $Row->Attributes['WarningID']) {

            //Check if warning has been reversed.
            $NoteModel = new UserNoteModel();
            $Warning = $NoteModel->getID($Row->Attributes['WarningID']);

            if (!isset($Warning['Reversed']) || !$Warning['Reversed']) {

                // Make inline warning message link to specific warning text.
                // It will only be readable by the warned user or moderators.
                $WordWarn = 'warned';
                if (!empty($Row->Attributes['WarningID'])) {
                    $WarningID = $Row->Attributes['WarningID'];
                    $WordWarn = '<a href="'.url("profile/viewnote/$WarningID").'" class="Popup">'.$WordWarn.'</a>';
                }
                echo '<div class="DismissMessage Warning">'.
                    sprintf(t('%s was %s for this.'), htmlspecialchars(val('InsertName', $Row)), $WordWarn).
                    '</div>';
            }
        }
    }

    /**
     * Show the warning context for private messages.
     *
     * @param MessagesController $sender The event sender.
     */
    public function messagesController_conversationWarning_handler($sender) {
        $foreignID = $sender->data('Conversation.ForeignID');
        if (!stristr($foreignID, '-')) {
            return;
        }

        list($warningKey, $warningID) = explode('-', $foreignID);
        $warningModel = new WarningModel;
        $warning = $warningModel->getID($warningID);
        if (!$warning) {
            return;
        }

        $quote = false;
        switch ($warning['RecordType']) {
            // comment warning
            case 'comment':
                $commentModel = new CommentModel;
                $comment = $commentModel->getID($warning['RecordID'], DATASET_TYPE_ARRAY);
                $discussionModel = new DiscussionModel;
                $discussion = $discussionModel->getID($comment['DiscussionID'], DATASET_TYPE_ARRAY);

                $quote = true;
                if ($comment && $discussion) {
                    $context = formatQuote($comment);
                    $location = warningContext($comment, $discussion);
                // Fallback. Use the warning's "RecordBody" field.
                } else {
                    $context = formatQuote([
                        'Body' => val('RecordBody', $warning),
                        'Format' => val('RecordFormat', $warning),
                    ], false);
                    $location = 'Comment content';
                }
                break;

            // discussion warning
            case 'discussion':
                $discussionModel = new DiscussionModel;
                $discussion = $discussionModel->getID($warning['RecordID'], DATASET_TYPE_ARRAY);

                $quote = true;
                if ($discussion) {
                    $context = formatQuote($discussion);
                    $location = warningContext($discussion);
                // Fallback. Use the warning's "RecordBody" field.
                } else {
                    $context = formatQuote([
                        'Body' => val('RecordBody', $warning),
                        'Format' => val('RecordFormat', $warning),
                    ], false);
                    $location = 'Discussion content';
                }
                break;

            // activity warning
            case 'activity':
                $activityModel = new ActivityModel;
                $activity = $activityModel->getID($warning['RecordID'], DATASET_TYPE_ARRAY);

                $quote = true;
                $context = '';
                $location = warningContext($activity);
                break;

            // profile/direct user warning
            default:
                // Nothing for this
                break;
        }

        if ($quote) {
            $issuer = Gdn::userModel()->getID($warning['InsertUserID'], DATASET_TYPE_ARRAY);

            $content = sprintf(t('Re: %s'), "{$location}<br>{$context}");
            $content .= wrap(t('Moderator'), 'strong').' '.userAnchor($issuer);
            $content .= "<br>";
            $content .= wrap(t('Points'), 'strong').' '.$warning['Points'];


            echo wrap($content, 'div', array(
                'class' => 'WarningContext'
            ));
        }
    }

    /**
     * Show the warning context in the email.
     *
     * @param ActivityModel $sender The event sender.
     * @param array $args Event arguments.
     */
    public function activityModel_beforeSendNotification_handler($sender, $args) {
        if (!isset($args['Email'])) {
            return;
        }
        $request = Gdn::request();
        $path = $request->path();
        if (strpos($path, 'profile/warn') === false) {
            return;
        }

        $recordID = $request->get('recordid');
        $recordType = $request->get('recordtype');
        if (!$recordID || !$recordType) {
            return;
        }

        $recordType = strtolower($recordType);
        if (in_array($recordType, ['comment', 'discussion'])) {
            $modelName = $recordType.'Model';
            $model = new $modelName();
            $record = $model->getID($recordID);

            if ($record) {
                /**
                 * @var $email Gdn_Email
                 */
                $email = $args['Email'];
                $emailTemplate = $email->getEmailTemplate();
                $message = $emailTemplate->getMessage();

                $message .= '<br>'.t('Post that triggered the warning:').formatQuote($record, false);
                $emailTemplate->setMessage($message);
            }
        }
    }

    /**
     * Add the warning to the list of flags.
     *
     * @param Gdn_Controller $sender The event sender.
     * @param array $args The event arguments.
     */
    public function base_flags_handler($sender, $args) {
        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false)) {
            $args['Flags']['warn'] = array($this, 'WarnButton');
        }
    }

    /**
     * Add Warn option to profile options.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function profileController_beforeProfileOptions_handler($sender, $args) {
        if (!val('EditMode', Gdn::controller())) {

            if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.Add'], false)) {
                $sender->EventArguments['ProfileOptions'][] = array(
                    'Text' => t('Add Note'),
                    'Url' => '/profile/note?userid='.$args['UserID'],
                    'CssClass' => 'Popup UserNoteButton'
                );
            }

            if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false)
                && Gdn::session()->UserID != $sender->EventArguments['UserID']
            ) {

                $sender->EventArguments['ProfileOptions'][] = array(
                    'Text' => sprite('SpWarn').' '.t('Warn'),
                    'Url' => '/profile/warn?userid='.$args['UserID'],
                    'CssClass' => 'Popup WarnButton'
                );
            }
        }
    }

    /**
     * Add a warn button to a user card.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function profileController_card_render($sender, $args) {
        $UserID = $sender->data('Profile.UserID');

        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false)) {
            $sender->setData('Actions'.'Warn', [
                'Text' => sprite('SpWarn'),
                'Title' => t('Warn'),
                'Url' => '/profile/warn?userid='.$UserID,
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false)) {
            $sender->setData('Actions'.'Note', [
                'Text' => sprite('SpNote'),
                'Title' => t('Add Note'),
                'Url' => '/profile/note?userid='.$UserID,
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false)) {
            $sender->setData('Actions'.'Notes', [
                'Text' => '<span class="Count">notes</span>',
                'Title' => t('Notes & Warnings'),
                'Url' => userUrl($sender->data('Profile'), '', 'notes'),
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false)) {
            $UserAlertModel = new UserAlertModel();
            $Alert = $UserAlertModel->getID($UserID, DATASET_TYPE_ARRAY);
            $sender->setData('Alert', $Alert);
        }
    }

    /**
     * Create note delete endpoint.
     *
     * @param ProfileController $sender The event sender.
     * @param int $noteID The ID of the note to delete.
     */
    public function profileController_deleteNote_create($sender, $noteID) {
        $sender->permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false);

        $Form = new Gdn_Form();

        if ($Form->authenticatedPostBack()) {

            // Delete the note.
            $NoteModel = new UserNoteModel();
            $NoteModel->delete(array('UserNoteID' => $noteID));

            $sender->jsonTarget("#UserNote_{$noteID}", '', 'SlideUp');
        }

        $sender->title(sprintf(t('Delete %s'), t('Note')));
        $sender->render('deletenote', '', 'plugins/Warnings2');
    }

    /**
     * Add punishment CSS to users who are punished.
     *
     * @param UserModel $sender The event sender.
     * @param array $args The event arguments.
     */
    public function userModel_setCalculatedFields_handler($sender, $args) {
        if (val('Banned', $args['User'])) {
            setValue('Punished', $args['User'], 0);
        }
        $Punished = val('Punished', $args['User']);
        if ($Punished) {
            $CssClass = val('_CssClass', $args['User']);
            $CssClass .= ' Jailed';
            setValue('_CssClass', $args['User'], trim($CssClass));
        }
    }

    /**
     * Render a banner detailing punishment information for a user.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function ProfileController_BeforeUserInfo_Handler($sender, $args) {
        echo Gdn_Theme::module('UserWarningModule');
        return;

        if (!Gdn::controller()->data('Profile.Punished')) {
            return;
        }

        echo '<div class="Hero Hero-Jailed Message">';

        echo '<b>';
        if (Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID) {
            echo t("You've been Jailed.");
        } else {
            echo sprintf(t("%s has been Jailed."), htmlspecialchars(Gdn::controller()->data('Profile.Name')));
        }
        echo '</b>';

        echo "<ul>";
        echo wrap(t("Can't post discussions.")."\n", 'li');
        echo wrap(t("Can't post as often.")."\n", 'li');
        echo wrap(t("Signature hidden.")."\n", 'li');
        echo "</ul>";

        echo '</div>';
    }

    /**
     * Create endpoint for writing notes.
     *
     * @param ProfileController $sender The controller being attached to.
     * @param int ? $userID The ID of the user to create the note for.
     * @param int ? $noteID The ID of the note to edit.
     */
    public function profileController_note_create($sender, $userID = null, $noteID = null) {
        $sender->permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false);

        $Model = new UserNoteModel();

        if ($noteID) {
            $Note = $Model->getID($noteID);
            if (!$Note) {
                throw notFoundException('Note');
            }

            $userID = $Note['UserID'];
            $User = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            if (!$User) {
                throw notFoundException('User');
            }
        } elseif ($userID) {
            $User = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            if (!$User) {
                throw notFoundException('User');
            }
        } else {
            throw new Gdn_UserException('User or note id is required');
        }

        $Form = new Gdn_Form();
        $sender->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            $Form->setModel($Model);

            $Form->setFormValue('Type', 'note');

            if (!$noteID) {
                $Form->setFormValue('UserID', $userID);
            } else {
                $Form->setFormValue('UserNoteID', $noteID);
            }

            if ($Form->save()) {
                $sender->informMessage(t('Your note was added.'));
                $sender->jsonTarget('', '', 'Refresh');
            }
        } else {
            if (isset($Note)) {
                $Form->setData($Note);
            }
        }

        $sender->setData('Profile', $User);
        $sender->setData('Title', $noteID ? t('Edit Note') : t('Add Note'));
        $sender->render('note', '', 'plugins/Warnings2');
    }

    /**
     * An endpoint for reversing a warning.
     *
     * @param ProfileController $sender The controller the endpoint is attached to.
     * @param int $id The ID of the warning to reverse.
     */
    public function profileController_reverseWarning_create($sender, $id) {
        $sender->permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        $Form = new Gdn_Form();

        if ($Form->authenticatedPostBack()) {
            // Delete the note.
            $WarningModel = new WarningModel();
            $WarningModel->reverse($id);

//         $Sender->jsonTarget("#UserNote_{$ID}", '', 'SlideUp');
            $sender->jsonTarget('', '', 'Refresh');
        }

        $sender->title(sprintf(t('Reverse %s'), t('Warning')));
        $sender->render('reversewarning', '', 'plugins/Warnings2');
    }

    /**
     * Add the warnings CSS to the application.
     *
     * @param AssetModel $sender The event sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('warnings.css', 'plugins/Warnings2');
    }

    /**
     * Process a user's jailed status on startup.
     */
    public function gdn_dispatcher_appStartup_handler() {
        if (!Gdn::session()->UserID || !val('Punished', Gdn::session()->User)) {
            return;
        }

        // The user has been punished so strip some abilities.
        Gdn::session()->setPermission('Vanilla.Discussions.Add', array());

        // Reduce posting speed to 1 per 150 sec
        saveToConfig(array(
            'Vanilla.Comment.SpamCount' => 0,
            'Vanilla.Comment.SpamTime' => 150,
            'Vanilla.Comment.SpamLock' => 150
        ), null, false);
    }

    /**
     * Add a profile tab to view a user's notes.
     *
     * @param ProfileController $sender The event sender.
     */
    public function profileController_addProfileTabs_handler($sender) {
        $IsPrivileged = Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$IsPrivileged && Gdn::session()->UserID != valr('User.UserID', $sender)) {
            return;
        }
        $sender->addProfileTab(t('Moderation'), userUrl($sender->User, '', 'notes'), 'UserNotes');
    }

    /**
     * Add a link to a user's notes in the nav module.
     *
     * @param SiteNavModule $sender The event sender.
     */
    public function siteNavModule_profile_handler($sender) {
        $IsPrivileged = Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);
        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$IsPrivileged && Gdn::session()->UserID != valr('User.UserID', $sender)) {
            return;
        }
        $user = Gdn::controller()->data('Profile');
        $user_id = val('UserID', $user);
        $sender->addLink('moderation.notes', array('text' => t('Notes'), 'url' => userUrl($user, '', 'notes'), 'icon' => icon('edit')));
    }

    /**
     *
     * @param ProfileController $Sender
     * @param mixed $UserReference
     * @param string $Username
     * @param string $Page
     */
    public function profileController_notes_create($Sender, $UserReference, $Username = '', $Page = '') {
        $Sender->editMode(false);
        $Sender->getUserInfo($UserReference, $Username);

        $IsPrivileged = Gdn::session()->checkPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false);

        $Sender->setData('IsPrivileged', $IsPrivileged);

        // Users should only be able to see their own warnings
        if (!$IsPrivileged && Gdn::session()->UserID != val('UserID', $Sender->User)) {
            throw permissionException('Garden.Moderation.Manage');
        }

        $Sender->_setBreadcrumbs(t('Notes'), userUrl($Sender->User, '', 'notes'));
        $Sender->setTabView('Notes', 'Notes', '', 'plugins/Warnings2');

        list($Offset, $Limit) = offsetLimit($Page, $this->pageSize);

        $UserNoteModel = new UserNoteModel();
        $Where = array('UserID' => $Sender->User->UserID);
        if (!$IsPrivileged) {
            $Where['Type'] = 'warning';
        }
        $Notes = $UserNoteModel->getWhere(
            $Where,
            'DateInserted',
            'desc',
            $Limit,
            $Offset
        )->resultArray();
        $UserNoteModel->calculate($Notes);

        // Join the user records into the warnings
        joinRecords($Notes, 'Record', false, false);

        // If HideWarnerIdentity is true, do not let view render that data.
        $WarningModel = new WarningModel();
        $HideWarnerIdentity = $WarningModel->HideWarnerIdentity;
        array_walk($Notes, function (&$value, $key) use ($HideWarnerIdentity) {
            $value['HideWarnerIdentity'] = $HideWarnerIdentity;
        });

        PagerModule::$DefaultPageSize = $this->pageSize;

        $Sender->setData('Notes', $Notes);

        $Sender->render();
    }

    /**
     * View individual note for given user.
     *
     * @param ProfileController $Sender
     * @param $NoteID
     */
    public function profileController_viewNote_create($Sender, $NoteID) {
        $UserNoteModel = new UserNoteModel();
        $Note = $UserNoteModel->getID($NoteID);

        $UserID = (count($Note) && !empty($Note['UserID']))
            ? $Note['UserID']
            : null;

        if (!$UserID || !count($Note)) {
            throw notFoundException('Warning');
        }

        $Sender->editMode(false);

        $Sender->getUserInfo($UserID, '', $UserID);

        $IsPrivileged = Gdn::session()->checkPermission(
            array( 'Garden.Moderation.Manage', 'Moderation.UserNotes.View'),
            false
        );

        $Sender->setData('IsPrivileged', $IsPrivileged);

        // Users should only be able to see their own warnings
        if (!$IsPrivileged && Gdn::session()->UserID != val('UserID', $Sender->User)) {
            throw permissionException('Garden.Moderation.Manage');
        }

        // Build breadcrumbs.
        $Sender->_setBreadcrumbs();
        $Breadcrumbs = $Sender->data('Breadcrumbs');
        $NotesUrl = userUrl($Sender->User, '', 'notes');
        $NoteUrl = url("/profile/viewnote/{$Sender->User->UserID}/$NoteID");
        $Breadcrumbs = array_merge($Breadcrumbs, array(
            array('Name' => 'Notes', 'Url' => $NotesUrl),
            array('Name' => 'Note', 'Url' => $NoteUrl)
        ));
        $Sender->setData('Breadcrumbs', $Breadcrumbs);

        // Add side menu.
        $Sender->setTabView('ViewNote', 'ViewNote', '', 'plugins/Warnings2');

        // If HideWarnerIdentity is true, do not let view render that data.
        $WarningModel = new WarningModel();
        $Note['HideWarnerIdentity'] = $WarningModel->HideWarnerIdentity;

        // Join record in question with note.
        $Notes = array();
        $Notes[] = $Note;
        joinRecords($Notes, 'Record');

        $Sender->setData('Notes', $Notes);
        $Sender->render('viewnote', '', 'plugins/Warnings2');
    }

    /**
     *
     * @param ProfileController $Sender
     * @param int $UserID
     */
    public function profileController_warn_create($Sender, $UserID, $RecordType = false, $RecordID = false) {

        //If the user has already been warned, let the mod know and move on.
        if ($RecordID && $RecordType) {
            $WarningModule = new WarningModel();
            $Model = $WarningModule->getModel($RecordType);
            if ($Model) {
                $Record = $Model->getID($RecordID);

                if (isset($Record->Attributes['WarningID']) && $Record->Attributes['WarningID']) {
                    $Sender->title(sprintf(t('Already Warned')));
                    $Sender->render('alreadywarned', '', 'plugins/Warnings2');
                    return;
                }
            }
        }

        $Sender->permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw notFoundException();
        }
        $Sender->User = $User;

        $Sender->_setBreadcrumbs(t('Warn'), '/profile/warn?userid='.$User['UserID']);

//      $Meta = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.%');
//      $CurrentLevel = val('Warnings.Level', $Meta, 0);

        $Form = new Gdn_Form();
        $Sender->Form = $Form;

        if (!$UserID) {
            throw notFoundException('User');
        }

        // Get the warning types.
        $WarningTypes = Gdn::sql()->getWhere('WarningType', array(), 'Points')->resultArray();
        $Sender->setData('WarningTypes', $WarningTypes);

        // Get the record.
        if ($RecordType && $RecordID) {
            $Row = getRecord($RecordType, $RecordID);
            $Sender->setData('RecordType', $RecordType);
            $Sender->setData('Record', $Row);

            $Form->addHidden('RecordBody', $Row['Body']);
            $Form->addHidden('RecordFormat', $Row['Format']);
            $Form->addHidden('RecordInsertTime', $Row['DateInserted']);

        }

        if ($Form->authenticatedPostBack()) {
            $Model = new WarningModel();
            $Form->setModel($Model);

            $Form->setFormValue('UserID', $UserID);

            if ($Form->getFormValue('AttachRecord')) {
                $Form->setFormValue('RecordType', $RecordType);
                $Form->setFormValue('RecordID', $RecordID);
            }

            if ($Form->save()) {
                $Sender->informMessage(T('Your warning was added.'));
                $Sender->jsonTarget('', '', 'Refresh');
            }
        } else {
            $Type = reset($WarningTypes);
            $Form->setValue('WarningTypeID', val('WarningTypeID', $Type));
            $Form->setValue('AttachRecord', true);
        }

        $Sender->setData('Profile', $User);
        $Sender->setData('Title', sprintf(t('Warn %s'), htmlspecialchars(val('Name', $User))));

        $Sender->View = 'Warn';
        $Sender->ApplicationFolder = 'plugins/Warnings2';
        $Sender->render('', '');
    }

    /**
     * Hide signatures for people in the pokey.
     *
     * @param SignaturesPlugin $Sender
     */
    public function signaturesPlugin_beforeDrawSignature_handler($Sender) {
        $UserID = $Sender->EventArguments['UserID'];
        $User = Gdn::userModel()->getID($UserID);
        if (!val('Punished', $User)) {
            return;
        }
        $Sender->EventArguments['Signature'] = null;
    }

    public function utilityController_processWarnings_create($Sender) {
        $WarningModel = new WarningModel();
        $Result = $WarningModel->processAllWarnings();

        $Sender->setData('Result', $Result);
        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

}

/*
 * Global Functions
 */

if (!function_exists('FormatQuote')):

    /**
     * Build our warned content quote for PMs.
     *
     * @param $content
     * @param $user
     *
     * @return string
     */
    function formatQuote($content, $user = null) {
        if (is_object($content)) {
            $content = (array)$content;
        } elseif (is_string($content)) {
            return $content;
        }

        if (is_null($user)) {
            $user = Gdn::userModel()->getID(val('InsertUserID', $content));
        }

        if ($user) {
            $result = '<blockquote class="Quote Media">'.
                '<div class="Img">'.userPhoto($user).'</div>'.
                '<div class="Media-Body">'.
                '<div>'.userAnchor($user).' - '.Gdn_Format::dateFull($content['DateInserted'], 'html').'</div>'.
                Gdn_Format::to($content['Body'], $content['Format']).
                '</div>'.
                '</blockquote>';
        } else {
            $result = '<blockquote class="Quote">'.
                Gdn_Format::to($content['Body'], $content['Format']).
                '</blockquote>';
        }

        return $result;
    }

endif;

if (!function_exists('WarningContext')):

    /**
     * Create a linked sentence about the context of the warning.
     *
     * @param $context array or object being warned.
     *
     * @return string Html message to direct moderators to the content.
     */
    function warningContext($context, $discussion = null, $category = null) {
        if (is_object($context)) {
            $context = (array)$context;
        }

        if ($activityID = val('ActivityID', $context)) {

            // Point to an activity
            $type = val('ActivityType', $context);
            if ($type == 'Status') {
                // Link to author's wall
                $contextHtml = sprintf(
                    t('Warning Status Context', '%1$s by <a href="%2$s">%3$s</a>'),
                    t('Activity Status', 'Status'),
                    userUrl($context, 'Activity').'#Activity_'.$activityID,
                    Gdn_Format::text($context['ActivityName'])
                );
            } elseif ($type == 'WallPost') {
                // Link to recipient's wall
                $contextHtml = sprintf(
                    t('Warning WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'),
                    userUrl($context, 'Regarding').'#Activity_'.$activityID, // Post on recipient's wall
                    t('Activity WallPost', 'Wall Post'),
                    userUrl($context, 'Activity'), // Author's profile
                    Gdn_Format::text($context['ActivityName']),
                    userUrl($context, 'Regarding'), // Recipient's profile
                    Gdn_Format::text($context['RegardingName'])
                );
            }
        } elseif (val('CommentID', $context)) {

            // Point to comment & its discussion
            if (is_null($discussion)) {
                $discussionModel = new DiscussionModel();
                $discussion = (array)$discussionModel->getID(val('DiscussionID', $context));
            }
            $contextHtml = sprintf(
                t('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                commentUrl($context),
                t('Comment'),
                strtolower(t('Discussion')),
                discussionUrl($discussion),
                Gdn_Format::text($discussion['Name'])
            );
        } elseif (val('DiscussionID', $context)) {

            // Point to discussion & its category
            if (is_null($category)) {
                $discussionModel = new DiscussionModel();
                $category = CategoryModel::categories($context['CategoryID']);
            }
            $contextHtml = sprintf(
                t('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                discussionUrl($context),
                t('Discussion'),
                strtolower(t('Category')),
                categoryUrl($category),
                Gdn_Format::text($category['Name']),
                Gdn_Format::text($context['Name']) // In case folks want the full discussion name
            );
        } else {
            return null;
        }

        return $contextHtml;
    }
endif;
