<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Warnings2'] = array(
    'Name' => 'Warnings & Notes',
    'Description' => "Allows moderators to warn users and add private notes to profiles to help police the community.",
    'Version' => '2.4.0',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'MobileFriendly' => true,
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

/**
 * Plugin that allows moderators to warn users and help police the community.
 *
 * ### Permissions
 * Garden.Moderation.Manage
 *
 * Moderation.UserNotes.View
 * Moderation.UserNotes.Add
 * Moderation.Warnings.Add
 */
class Warnings2Plugin extends Gdn_Plugin {

    /// Propeties ///

    public $pageSize = 20;

    /**
     * @var bool Whether or not to restrict the viewing of warnings on posts.
     */
    public $PublicPostWarnings = false;

    /// Methods ///

    public function __construct() {
        parent::__construct();
        $this->FireEvent('Init');
    }

    public function Setup() {
        $this->Structure();
    }

    public function Structure() {
        require __DIR__ . '/structure.php';

        Gdn::PluginManager()->DisablePlugin('Warnings');
    }

    /**
     * Render warning reaction button
     *
     * @param array $Row
     * @param string $RecordType
     * @param integer $RecordID
     * @return string
     */
    public function WarnButton($Row, $RecordType, $RecordID) {
        $Args = array(
            'userid' => val('InsertUserID', $Row),
            'recordtype' => $RecordType,
            'recordid' => $RecordID
        );

        $Result = Anchor(
            '<span class="ReactSprite ReactWarn"></span> ' . T('Warn'), '/profile/warn?' . http_build_query($Args), 'ReactButton ReactButton-Warn Popup'
        );
        return $Result;
    }

    /// Event Handlers ///

    /**
     * Process warning expiries on sign in
     *
     * @param EntryController $Sender
     * @param array $Args
     */
    public function EntryController_AfterSignIn_Handler($Sender, $Args) {
        if (Gdn::Session()->UserID) {
            $WarningModel = new WarningModel();
            $WarningModel->ProcessWarnings(Gdn::Session()->UserID);
        }
    }

    /**
     *
     * @param UserModel $Sender
     */
    public function UserModel_Visit_Handler($Sender, $Args) {
        if (Gdn::Session()->UserID) {
            $WarningModel = new WarningModel();
            $WarningModel->ProcessWarnings(Gdn::Session()->UserID);
        }
    }

    /**
     *
     * Hijack ban notifications and add a note instead.
     *
     * @param ActivityModel $Sender
     * @param array $Args
     */
    public function ActivityModel_BeforeSave_Handler($Sender, $Args) {
        if (!isset($Args['Activity'])) {
            return;
        }

        $Activity = & $Args['Activity'];
        if (!is_array($Activity)) {
            return;
        }

        $ActivityType = strtolower(val('ActivityType', $Activity));
        if (strcasecmp($ActivityType, 'ban') !== 0) {
            return;
        }

        $Data = $Activity['Data'];
        if (is_string($Data)) {
            $Data = @unserialize($Data);
        }

        $body = val('Story', $Activity);
        if (val('Unban', $Data)) {
            $type = 'unban';
            if (!$body) {
                $body = T('User was unbanned.');
            }
        } else {
            $type = 'ban';
            if (!$body) {
                $body = T('User was banned.');
            }
        }

        $model = new UserNoteModel();
        $row = array(
            'Type' => $type,
            'UserID' => val('ActivityUserID', $Activity),
            'Body' => $body,
            'Format' => val('Format', $Activity, 'text'),
            'InsertUserID' => val('RegardingUserID', $Activity, Gdn::Session()->UserID),
        );
        $model->Save($row);

        // Don't save the activity.
        $Args['Handled'] = true;
    }

    /**
     * Show if this post triggered a warning to give moderators context.
     *
     * @param mixed $Sender
     * @param array $Args
     */
    public function Base_BeforeCommentBody_Handler($Sender, $Args) {
        if (isset($Args['Comment'])) {
            $Row = $Args['Comment'];
        } else {
            $Row = $Args['Discussion'];
        }

        if (!$this->PublicPostWarnings) {
            // Only show warnings to moderators
            if (val('InsertUserID', $Row) != Gdn::Session()->UserID && !Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false)) {
                return;
            }
        }

        $Row->Attributes = Gdn_Format::Unserialize($Row->Attributes);
        if (isset($Row->Attributes['WarningID']) && $Row->Attributes['WarningID']) {

            //Check if warning has been reversed.
            $NoteModel = new UserNoteModel();
            $Warning = $NoteModel->GetID($Row->Attributes['WarningID']);

            if (!isset($Warning['Reversed']) || !$Warning['Reversed']) {

                // Make inline warning message link to specific warning text.
                // It will only be readable by the warned user or moderators.
                $WordWarn = 'warned';
                if (!empty($Row->Attributes['WarningID'])) {
                    $WarningID = $Row->Attributes['WarningID'];
                    $WordWarn = '<a href="' . Url("profile/viewnote/$WarningID") . '" class="Popup">' . $WordWarn . '</a>';
                }
                echo '<div class="DismissMessage Warning">'.
                sprintf(T('%s was %s for this.'), htmlspecialchars(val('InsertName', $Row)), $WordWarn).
                '</div>';
            }
        }
    }

    /**
     * Show the warning context for PMs
     *
     * @param MessagesController $sender
     */
    public function MessagesController_ConversationWarning_Handler($sender) {
        $foreignID = $sender->data('Conversation.ForeignID');
        if (!stristr($foreignID, '-')) {
            return;
        }

        list($warningKey,$warningID) = explode('-', $foreignID);
        $warningModel = new WarningModel;
        $warning = $warningModel->getID($warningID);
        if (!$warning) {
            return;
        }

        $quote = null;
        switch ($warning['RecordType']) {
            // comment warning
            case 'comment':
                $commentModel = new CommentModel;
                $comment = (array)$commentModel->getID($warning['RecordID'], DATASET_TYPE_ARRAY);
                $discussionModel = new DiscussionModel;
                $discussion = (array)$discussionModel->getID($comment['DiscussionID'], DATASET_TYPE_ARRAY);

                $quote = true;
                $context = formatQuote($comment);
                $location = warningContext($comment, $discussion);
                break;

            // discussion warning
            case 'discussion':
                $discussionModel = new DiscussionModel;
                $discussion = (array)$discussionModel->getID($warning['RecordID'], DATASET_TYPE_ARRAY);

                $quote = true;
                $context = formatQuote($discussion);
                $location = warningContext($discussion);
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
            $content = sprintf(T('Re: %s'), "{$location}<br/>{$context}");

            $issuer = Gdn::userModel()->getID($warning['InsertUserID'], DATASET_TYPE_ARRAY);
            $content .= "<br/>";
            $content .= wrap(T('Moderator'), 'strong').' '.userAnchor($issuer);
            $content .= "<br/>";
            $content .= wrap(T('Points'), 'strong').' '.$warning['Points'];

            echo wrap($content, 'div', array(
                'class' => 'WarningContext'
            ));
        }
    }

    /**
     * Add the warning to the list of flags.
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function Base_Flags_Handler($Sender, $Args) {
        if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false)) {
            $Args['Flags']['warn'] = array($this, 'WarnButton');
        }
    }

    /**
     * Add Warn option to profile options.
     *
     * @param ProfileController $Sender
     * @param array $Args
     */
    public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
        if (!val('EditMode', Gdn::Controller())) {

            if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false)) {
                $Sender->EventArguments['ProfileOptions'][] = array(
                    'Text' => T('Add Note'),
                    'Url' => '/profile/note?userid=' . $Args['UserID'],
                    'CssClass' => 'Popup UserNoteButton'
                );
            }

            if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false) && Gdn::Session()->UserID != $Sender->EventArguments['UserID']) {
                $Sender->EventArguments['ProfileOptions'][] = array(
                    'Text' => Sprite('SpWarn') . ' ' . T('Warn'),
                    'Url' => '/profile/warn?userid=' . $Args['UserID'],
                    'CssClass' => 'Popup WarnButton'
                );
            }
        }
    }

    public function ProfileController_Card_Render($Sender, $Args) {
        $UserID = $Sender->Data('Profile.UserID');

        if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false)) {
            $Sender->Data['Actions']['Warn'] = array(
                'Text' => Sprite('SpWarn'),
                'Title' => T('Warn'),
                'Url' => '/profile/warn?userid=' . $UserID,
                'CssClass' => 'Popup'
            );
        }

        if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false)) {
            $Sender->Data['Actions']['Note'] = array(
                'Text' => Sprite('SpNote'),
                'Title' => T('Add Note'),
                'Url' => '/profile/note?userid=' . $UserID,
                'CssClass' => 'Popup'
            );
        }

        if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false)) {
            $Sender->Data['Actions']['Notes'] = array(
                'Text' => '<span class="Count">notes</span>',
                'Title' => T('Notes & Warnings'),
                'Url' => UserUrl($Sender->Data('Profile'), '', 'notes'),
                'CssClass' => 'Popup'
            );
        }

        if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false)) {
            $UserAlertModel = new UserAlertModel();
            $Alert = $UserAlertModel->GetID($UserID, DATASET_TYPE_ARRAY);
            $Sender->SetData('Alert', $Alert);
        }
    }

    /**
     * Create note delete endpoint
     *
     * @param ProfileController $Sender
     * @param int $NoteID
     */
    public function ProfileController_DeleteNote_Create($Sender, $NoteID) {
        $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false);

        $Form = new Gdn_Form();

        if ($Form->AuthenticatedPostBack()) {

            // Delete the note.
            $NoteModel = new UserNoteModel();
            $NoteModel->Delete(array('UserNoteID' => $NoteID));

            $Sender->JsonTarget("#UserNote_{$NoteID}", '', 'SlideUp');
        }

        $Sender->Title(sprintf(T('Delete %s'), T('Note')));
        $Sender->Render('deletenote', '', 'plugins/Warnings2');
    }

    /**
     * Add punishment CSS to users who are punished
     *
     * @param UserModel $Sender
     * @param array $Args
     */
    public function UserModel_SetCalculatedFields_Handler($Sender, $Args) {
        if (val('Banned', $Args['User'])) {
            SetValue('Punished', $Args['User'], 0);
        }
        $Punished = val('Punished', $Args['User']);
        if ($Punished) {
            $CssClass = val('_CssClass', $Args['User']);
            $CssClass .= ' Jailed';
            SetValue('_CssClass', $Args['User'], trim($CssClass));
        }
    }

    /**
     *
     * @param ProfileController $Sender
     * @param array $Args
     * @return void
     */
    public function ProfileController_BeforeUserInfo_Handler($Sender, $Args) {
        echo Gdn_Theme::Module('UserWarningModule');
        return;

        if (!Gdn::Controller()->Data('Profile.Punished')) {
            return;
        }

        echo '<div class="Hero Hero-Jailed Message">';

        echo '<b>';
        if (Gdn::Controller()->Data('Profile.UserID') == Gdn::Session()->UserID) {
            echo T("You've been Jailed.");
        } else {
            echo sprintf(T("%s has been Jailed."), htmlspecialchars(Gdn::Controller()->Data('Profile.Name')));
        }
        echo '</b>';

        echo "<ul>";
        echo wrap(T("Can't post discussions.")."\n", 'li');
        echo wrap(T("Can't post as often.")."\n",'li');
        echo wrap(T("Signature hidden.")."\n",'li');
        echo "</ul>";

        echo '</div>';
    }

    /**
     * Create endpoint for writing notes
     *
     * @param ProfileController $Sender
     * @param integer $UserID
     * @param integer $NoteID
     */
    public function ProfileController_Note_Create($Sender, $UserID = false, $NoteID = false) {
        $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), false);

        $Model = new UserNoteModel();

        if ($NoteID) {
            $Note = $Model->GetID($NoteID);
            if (!$Note) {
                throw NotFoundException('Note');
            }

            $UserID = $Note['UserID'];
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            if (!$User) {
                throw NotFoundException('User');
            }
        } elseif ($UserID) {
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            if (!$User) {
                throw NotFoundException('User');
            }
        } else {
            throw new Gdn_UserException('User or note id is required');
        }

        $Form = new Gdn_Form();
        $Form->InputPrefix = '';
        $Sender->Form = $Form;

        if ($Form->AuthenticatedPostBack()) {
            $Form->SetModel($Model);
            $Form->InputPrefix = '';

            $Form->SetFormValue('Type', 'note');

            if (!$NoteID) {
                $Form->SetFormValue('UserID', $UserID);
            } else {
                $Form->SetFormValue('UserNoteID', $NoteID);
            }

            if ($Form->Save()) {
                $Sender->InformMessage(T('Your note was added.'));
                $Sender->JsonTarget('', '', 'Refresh');
            }
        } else {
            if (isset($Note)) {
                $Form->SetData($Note);
            }
        }

        $Sender->SetData('Profile', $User);
        $Sender->SetData('Title', $NoteID ? T('Edit Note') : T('Add Note'));
        $Sender->Render('note', '', 'plugins/Warnings2');
    }

    public function ProfileController_ReverseWarning_Create($Sender, $ID) {
        $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        $Form = new Gdn_Form();

        if ($Form->AuthenticatedPostBack()) {
            // Delete the note.
            $WarningModel = new WarningModel();
            $WarningModel->Reverse($ID);

//         $Sender->JsonTarget("#UserNote_{$ID}", '', 'SlideUp');
            $Sender->JsonTarget('', '', 'Refresh');
        }

        $Sender->Title(sprintf(T('Reverse %s'), T('Warning')));
        $Sender->Render('reversewarning', '', 'plugins/Warnings2');
    }

    public function AssetModel_StyleCss_Handler($Sender, $Args) {
        $Sender->AddCssFile('warnings.css', 'plugins/Warnings2');
    }

    public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
        if (!Gdn::Session()->UserID || !val('Punished', Gdn::Session()->User)) {
            return;
        }

        // The user has been punished so strip some abilities.
        Gdn::Session()->SetPermission('Vanilla.Discussions.Add', array());

        // Reduce posting speed to 1 per 150 sec
        SaveToConfig(array(
            'Vanilla.Comment.SpamCount' => 0,
            'Vanilla.Comment.SpamTime' => 150,
            'Vanilla.Comment.SpamLock' => 150
        ), null, false);
    }

    public function ProfileController_AddProfileTabs_Handler($Sender) {
        $IsPrivileged = Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$IsPrivileged && Gdn::Session()->UserID != valr('User.UserID', $Sender)) {
            return;
        }
        $Sender->AddProfileTab(T('Moderation'), UserUrl($Sender->User, '', 'notes'), 'UserNotes');
    }

    public function SiteNavModule_profile_handler($sender) {
        $IsPrivileged = Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);
        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$IsPrivileged && Gdn::Session()->UserID != valr('User.UserID', $sender)) {
            return;
        }
        $user = Gdn::Controller()->Data('Profile');
        $user_id = val('UserID', $user);
        $sender->addLink('moderation.notes', array('text' => t('Notes'), 'url' => UserUrl($user, '', 'notes'), 'icon' => icon('edit')));
    }

    /**
     *
     * @param ProfileController $Sender
     * @param mixed $UserReference
     * @param string $Username
     * @param string $Page
     */
    public function ProfileController_Notes_Create($Sender, $UserReference, $Username = '', $Page = '') {
        $Sender->EditMode(false);
        $Sender->GetUserInfo($UserReference, $Username);

        $IsPrivileged = Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), false);

        $Sender->SetData('IsPrivileged', $IsPrivileged);

        // Users should only be able to see their own warnings
        if (!$IsPrivileged && Gdn::Session()->UserID != val('UserID', $Sender->User)) {
            throw PermissionException('Garden.Moderation.Manage');
        }

        $Sender->_SetBreadcrumbs(T('Notes'), UserUrl($Sender->User, '', 'notes'));
        $Sender->SetTabView('Notes', 'Notes', '', 'plugins/Warnings2');

        list($Offset, $Limit) = OffsetLimit($Page, $this->pageSize);

        $UserNoteModel = new UserNoteModel();
        $Where = array('UserID' => $Sender->User->UserID);
        if (!$IsPrivileged) {
           $Where['Type'] = 'warning';
        }
        $Notes = $UserNoteModel->GetWhere(
            $Where, 'DateInserted', 'desc', $Limit, $Offset
        )->ResultArray();
        $UserNoteModel->Calculate($Notes);

        // Join the user records into the warnings
        JoinRecords($Notes, 'Record');

        // If HideWarnerIdentity is true, do not let view render that data.
        $WarningModel = new WarningModel();
        $HideWarnerIdentity = $WarningModel->HideWarnerIdentity;
        array_walk($Notes, function (&$value, $key) use ($HideWarnerIdentity) {
            $value['HideWarnerIdentity'] = $HideWarnerIdentity;
        });

        PagerModule::$DefaultPageSize = $this->pageSize;

        $Sender->SetData('Notes', $Notes);

        $Sender->Render();
    }

    /**
     * View individual note for given user.
     *
     * @param ProfileController $Sender
     * @param $NoteID
     */
    public function ProfileController_ViewNote_Create($Sender, $NoteID) {
        $UserNoteModel = new UserNoteModel();
        $Note = $UserNoteModel->GetID($NoteID);

        $UserID = (count($Note) && !empty($Note['UserID']))
            ? $Note['UserID']
            : NULL;

        if (!$UserID || !count($Note)) {
            throw NotFoundException('Warning');
        }

        $Sender->EditMode(false);

        $Sender->GetUserInfo($UserID, '', $UserID);

        $IsPrivileged = Gdn::Session()->CheckPermission(array(
            'Garden.Moderation.Manage',
            'Moderation.UserNotes.View'),
        false);

        $Sender->SetData('IsPrivileged', $IsPrivileged);

        // Users should only be able to see their own warnings
        if (!$IsPrivileged && Gdn::Session()->UserID != val('UserID', $Sender->User)) {
            throw PermissionException('Garden.Moderation.Manage');
        }

        // Build breadcrumbs.
        $Sender->_SetBreadcrumbs();
        $Breadcrumbs = $Sender->Data('Breadcrumbs');
        $NotesUrl = UserUrl($Sender->User, '', 'notes');
        $NoteUrl = Url("/profile/viewnote/{$Sender->User->UserID}/$NoteID");
        $Breadcrumbs = array_merge($Breadcrumbs, array(
            array('Name' => 'Notes', 'Url' => $NotesUrl),
            array('Name' => 'Note', 'Url' => $NoteUrl)
        ));
        $Sender->SetData('Breadcrumbs', $Breadcrumbs);

        // Add side menu.
        $Sender->SetTabView('ViewNote', 'ViewNote', '', 'plugins/Warnings2');

        // If HideWarnerIdentity is true, do not let view render that data.
        $WarningModel = new WarningModel();
        $Note['HideWarnerIdentity'] = $WarningModel->HideWarnerIdentity;

        // Join record in question with note.
        $Notes = array();
        $Notes[] = $Note;
        JoinRecords($Notes, 'Record');

        $Sender->SetData('Notes', $Notes);
        $Sender->Render('viewnote', '', 'plugins/Warnings2');
    }

    /**
     *
     * @param ProfileController $Sender
     * @param int $UserID
     */
    public function ProfileController_Warn_Create($Sender, $UserID, $RecordType = false, $RecordID = false) {

        //If the user has already been warned, let the mod know and move on.
        if ($RecordID && $RecordType) {
            $WarningModule = new WarningModel();
            $Model = $WarningModule->GetModel($RecordType);
            if ($Model) {
                $Record = $Model->GetID($RecordID);

                if (isset($Record->Attributes['WarningID']) && $Record->Attributes['WarningID']) {
                    $Sender->Title(sprintf(T('Already Warned')));
                    $Sender->Render('alreadywarned', '', 'plugins/Warnings2');
                    return;
                }
            }
        }

        $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), false);

        $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw NotFoundException();
        }
        $Sender->User = $User;

        $Sender->_SetBreadcrumbs(T('Warn'), '/profile/warn?userid=' . $User['UserID']);

//      $Meta = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.%');
//      $CurrentLevel = val('Warnings.Level', $Meta, 0);

        $Form = new Gdn_Form();
        $Form->InputPrefix = '';
        $Sender->Form = $Form;

        if (!$UserID) {
            throw NotFoundException('User');
        }

        // Get the warning types.
        $WarningTypes = Gdn::SQL()->GetWhere('WarningType', array(), 'Points')->ResultArray();
        $Sender->SetData('WarningTypes', $WarningTypes);

        // Get the record.
        if ($RecordType && $RecordID) {
            $Row = GetRecord($RecordType, $RecordID);
            $Sender->SetData('RecordType', $RecordType);
            $Sender->SetData('Record', $Row);

            $Form->AddHidden('RecordBody', $Row['Body']);
            $Form->AddHidden('RecordFormat', $Row['Format']);
            $Form->AddHidden('RecordInsertTime', $Row['DateInserted']);

        }

        if ($Form->AuthenticatedPostBack()) {
            $Model = new WarningModel();
            $Form->SetModel($Model);
            $Form->InputPrefix = '';

            $Form->SetFormValue('UserID', $UserID);

            if ($Form->GetFormValue('AttachRecord')) {
                $Form->SetFormValue('RecordType', $RecordType);
                $Form->SetFormValue('RecordID', $RecordID);
            }

            if ($Form->Save()) {
                $Sender->InformMessage(T('Your warning was added.'));
                $Sender->JsonTarget('', '', 'Refresh');
            }
        } else {
            $Type = reset($WarningTypes);
            $Form->SetValue('WarningTypeID', val('WarningTypeID', $Type));
            $Form->SetValue('AttachRecord', true);
        }

        $Sender->SetData('Profile', $User);
        $Sender->SetData('Title', sprintf(T('Warn %s'), htmlspecialchars(val('Name', $User))));

        $Sender->View = 'Warn';
        $Sender->ApplicationFolder = 'plugins/Warnings2';
        $Sender->Render('', '');
    }

    /**
     * Hide signatures for people in the pokey
     *
     * @param SignaturesPlugin $Sender
     */
    public function SignaturesPlugin_BeforeDrawSignature_Handler($Sender) {
        $UserID = $Sender->EventArguments['UserID'];
        $User = Gdn::UserModel()->GetID($UserID);
        if (!val('Punished', $User)) {
            return;
        }
        $Sender->EventArguments['Signature'] = null;
    }

    public function UtilityController_ProcessWarnings_Create($Sender) {
        $WarningModel = new WarningModel();
        $Result = $WarningModel->ProcessAllWarnings();

        $Sender->SetData('Result', $Result);
        $Sender->Render('Blank', 'Utility', 'Dashboard');
    }

}

/*
 * Global Functions
 */

if (!function_exists('FormatQuote')):

    /**
     * Build our warned content quote for PMs
     *
     * @param $content
     * @param $user
     * @return string
     */
    function FormatQuote($content, $user = null) {
        if (is_object($content)) {
            $content = (array)$content;
        } elseif (is_string($content)) {
            return $content;
        }

        if (is_null($user)) {
            $user = Gdn::userModel()->getID(val('InsertUserID', $content));
        }

        if ($user) {
            $result = '<blockquote class="Quote Media">' .
                '<div class="Img">' . userPhoto($user) . '</div>' .
                '<div class="Media-Body">' .
                '<div>'.userAnchor($user).' - '.Gdn_Format::DateFull($content['DateInserted'],'html').'</div>'.
                Gdn_Format::to($content['Body'], $content['Format']) .
                '</div>' .
                '</blockquote>';
        } else {
            $result = '<blockquote class="Quote">' .
                Gdn_Format::To($content['Body'], $content['Format']) .
                '</blockquote>';
        }

        return $result;
    }

endif;

if (!function_exists('WarningContext')):

    /**
     * Create a linked sentence about the context of the warning
     *
     * @param $context array or object being warned.
     * @return string Html message to direct moderators to the content.
     */
    function WarningContext($context, $discussion = null, $category = null) {
        if (is_object($context)) {
            $context = (array)$context;
        }

        if ($activityID = val('ActivityID', $context)) {

            // Point to an activity
            $type = val('ActivityType', $context);
            if ($type == 'Status') {
                // Link to author's wall
                $contextHtml = sprintf(T('Warning Status Context', '%1$s by <a href="%2$s">%3$s</a>'), T('Activity Status', 'Status'), userUrl($context, 'Activity') . '#Activity_' . $activityID, Gdn_Format::Text($context['ActivityName'])
                );
            } elseif ($type == 'WallPost') {
                // Link to recipient's wall
                $contextHtml = sprintf(T('Warning WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'), userUrl($context, 'Regarding') . '#Activity_' . $activityID, // Post on recipient's wall
                        T('Activity WallPost', 'Wall Post'), userUrl($context, 'Activity'), // Author's profile
                        Gdn_Format::Text($context['ActivityName']), userUrl($context, 'Regarding'), // Recipient's profile
                        Gdn_Format::Text($context['RegardingName'])
                );
            }
        } else if (val('CommentID', $context)) {

            // Point to comment & its discussion
            if (is_null($discussion)) {
                $discussionModel = new DiscussionModel();
                $discussion = (array)$discussionModel->getID(val('DiscussionID', $context));
            }
            $contextHtml = sprintf(T('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'), commentUrl($context), T('Comment'), strtolower(T('Discussion')), discussionUrl($discussion), Gdn_Format::Text($discussion['Name'])
            );
        } elseif (val('DiscussionID', $context)) {

            // Point to discussion & its category
            if (is_null($category)) {
                $discussionModel = new DiscussionModel();
                $category = CategoryModel::categories($context['CategoryID']);
            }
            $contextHtml = sprintf(T('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'), discussionUrl($context), T('Discussion'), strtolower(T('Category')), categoryUrl($category), Gdn_Format::Text($category['Name']), Gdn_Format::Text($context['Name']) // In case folks want the full discussion name
            );
        } else {
            return null;
        }

        return $contextHtml;
    }

endif;
