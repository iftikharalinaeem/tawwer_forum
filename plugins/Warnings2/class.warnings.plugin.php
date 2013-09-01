<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Warnings2'] = array(
   'Name' => 'Warnings',
   'Description' => "Allows moderators to warn users to help police the community.",
   'Version' => '2.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
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
class WarningsPlugin extends Gdn_Plugin {
   /// Propeties ///
   
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      require __DIR__.'/structure.php';
   }
   
   /// Event Handlers ///
   
   /**
    *
    * @param EntryController $Sender 
    */
   public function EntryController_AfterSignIn_Handler($Sender, $Args) {
      if (Gdn::Session()->UserID) {
         $WarningModel = new WarningModel();
         $WarningModel->ProcessWarnings(Gdn::Session()->UserID);
      }
   }
   
   /**
    * Add Warn option to profile options.
    * 
    * @param Gdn_Controller $Sender
    */
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      if (!GetValue('EditMode', Gdn::Controller())) {
         
         if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), FALSE)) {
            $Sender->EventArguments['ProfileOptions'][] = array(
                'Text' => T('Add Note'),
                'Url' => '/profile/note?userid='.$Args['UserID'],
                'CssClass' => 'Popup UserNoteButton'
            );
         }
         
         if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), FALSE) && Gdn::Session()->UserID != $Sender->EventArguments['UserID']) {
            $Sender->EventArguments['ProfileOptions'][] = array(
                'Text' => Sprite('SpWarn').' '.T('Warn'),
                'Url' => '/profile/warn?userid='.$Args['UserID'],
                'CssClass' => 'Popup WarnButton'
            );
         }
      }
   }
   
   public function ProfileController_Card_Render($Sender, $Args) {
      $UserID = $Sender->Data('Profile.UserID');
      if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Garden.Warnings.Add'), FALSE)) {
         $Sender->Data['Actions']['Warn'] = array(
            'Text' => Sprite('SpWarn'),
            'Title' => T('Warn'),
            'Url' => '/profile/warn?userid='.$UserID,
            'CssClass' => 'Popup'
            );
         
         $Level = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.Level');
         $Level = GetValue('Warnings.Level', $Level);
         $Sender->Data['Actions']['Warnings'] = array(
            'Text' => '<span class="Count">notes</span>',
            'Title' => T('Notes & Warnings'),
            'Url' => UserUrl($Sender->Data('Profile'), '', 'notes'),
            'CssClass' => 'Popup'
            );
      }
   }
   
   /**
    * @param ProfileController $Sender
    * @param int $NoteID
    */
   public function ProfileController_DeleteNote_Create($Sender, $NoteID) {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), FALSE);
      
      $Form = new Gdn_Form();
      
      if ($Form->AuthenticatedPostBack()) {
         
         // Delete the note.
         $NoteModel = new UserNoteModel();
         $NoteModel->Delete(array('UserNoteID' => $NoteID));
         
         $Sender->JsonTarget("#UserNote_{$NoteID}", '', 'SlideUp');
      }
      
      $Sender->Title(sprintf(T('Delete %s'), T('Note')));
      $Sender->Render('deletenote', '', 'plugins/Warnings');
   }
   
   public function UserModel_SetCalculatedFields_Handler($Sender, $Args) {
      $Punished = GetValue('Punished', $Args['User']);
      if ($Punished) {
         $CssClass = GetValue('_CssClass', $Args['User']);
         $CssClass .= ' Jailed';
         SetValue('_CssClass', $Args['User'], trim($CssClass));
      }
   }
   
   public function ProfileController_BeforeUserInfo_Handler($Sender, $Args) {
      echo Gdn_Theme::Module('UserWarningModule');
      return;
      
      if (!Gdn::Controller()->Data('Profile.Punished'))
         return;
      
      echo '<div class="Hero Hero-Jailed Message">';
      
      echo '<b>';
      if (Gdn::Controller()->Data('Profile.UserID') == Gdn::Session()->UserID) {
         echo T("You've been Jailed.");
      } else {
         echo sprintf(T("%s has been Jailed."), htmlspecialchars(Gdn::Controller()->Data('Profile.Name')));
      }
      echo '</b>';
      
      echo "<ul>
   <li>Can't post discussions.</li>
   <li>Can't post as often.</li>
   <li>Signature hidden.</li>
</ul>";
      
      echo '</div>';
   }
   
   /**
    * 
    * @param ProfileController $Sender
    * @param int $UserID
    */
   public function ProfileController_Note_Create($Sender, $UserID = FALSE, $NoteID = FALSE) {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.Add'), FALSE);
      
      $Model = new UserNoteModel();
      
      if ($NoteID) {
         $Note = $Model->GetID($NoteID);
         if (!$Note)
            throw NotFoundException('Note');
         
         $UserID = $Note['UserID'];
         $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
         if (!$User)
            throw NotFoundException('User');
      } elseif ($UserID) {
         $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
         if (!$User)
            throw NotFoundException('User');
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
         $Form->SetFormValue('UserNoteID', $NoteID);
         if (!$NoteID)
            $Form->SetFormValue('UserID', $UserID);
         
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
      $Sender->SetData('Title', $NoteID ? T('Add Note') : T('Edit Note'));
      $Sender->Render('note', '', 'plugins/Warnings');
   }
   
   public function ProfileController_ReverseWarning_Create($Sender, $ID) {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), FALSE);
      
      $Form = new Gdn_Form();
      
      if ($Form->AuthenticatedPostBack()) {
         // Delete the note.
         $WarningModel = new WarningModel();
         $WarningModel->Reverse($ID);
         
//         $Sender->JsonTarget("#UserNote_{$ID}", '', 'SlideUp');
         $Sender->JsonTarget('', '', 'Refresh');
      }
      
      $Sender->Title(sprintf(T('Reverse %s'), T('Warning')));
      $Sender->Render('reversewarning', '', 'plugins/Warnings');
   }
   
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('warnings.css', 'plugins/Warnings');
   }
   
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      if (!Gdn::Session()->UserID || !GetValue('Punished', Gdn::Session()->User))
         return;
      
      // The user has been punished so strip some abilities.
      Gdn::Session()->SetPermission('Vanilla.Discussions.Add', array());
      
      // Reduce posting speed to 1 per 150 sec
       SaveToConfig(array(
          'Vanilla.Comment.SpamCount' => 0,
          'Vanilla.Comment.SpamTime'  => 150,
          'Vanilla.Comment.SpamLock'  => 150
       ),NULL,FALSE);
   }
   
   public function ProfileController_AddProfileTabs_Handler($Sender) {
      if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), FALSE)) {
         $Sender->AddProfileTab(T('Notes'), UserUrl($Sender->User, '', 'notes'), 'UserNotes');
      }
   }
   
   /**
    * 
    * @param ProfileController $Sender
    * @param mixed $UserReference
    * @param string $Username
    * @param string $Page
    */
   public function ProfileController_Notes_Create($Sender, $UserReference, $Username, $Page = '') {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.UserNotes.View'), FALSE);
      
      $Sender->EditMode(FALSE);
      $Sender->GetUserInfo($UserReference, $Username);
      $Sender->_SetBreadcrumbs(T('Notes'), UserUrl($Sender->User, '', 'notes'));
      $Sender->SetTabView('Notes', 'Notes', '', 'plugins/Warnings');
      
      list($Offset, $Limit) = OffsetLimit($Page, 10);
      
      $UserNoteModel = new UserNoteModel();
      $Notes = $UserNoteModel->GetWhere(
         array('UserID' => $Sender->User->UserID),
         'DateInserted', 'desc',
         $Limit, $Offset
         )->ResultArray();
      $UserNoteModel->Calculate($Notes);
     
      $Sender->SetData('Notes', $Notes);
      
//      $Sender->Render('notes', '', 'plugins/Warnings');
      $Sender->Render();
   }
   
   /**
    *
    * @param ProfileController $Sender
    * @param int $UserID
    */
   public function ProfileController_Warn_Create($Sender, $UserID) {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Moderation.Warnings.Add'), FALSE);
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         throw NotFoundException();
      $Sender->User = $User;
      
      $Sender->_SetBreadcrumbs(T('Warn'), '/profile/warn?userid='.$User['UserID']);
      
//      $Meta = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.%');
//      $CurrentLevel = GetValue('Warnings.Level', $Meta, 0);
      
      $Form = new Gdn_Form();
      $Form->InputPrefix = '';
      $Sender->Form = $Form;
      
      if (!$UserID)
         throw NotFoundException('User');
      
      // Get the warning types.
      $WarningTypes = Gdn::SQL()->GetWhere('WarningType', array(), 'Points')->ResultArray();
      $Sender->SetData('WarningTypes', $WarningTypes);
      
      if ($Form->AuthenticatedPostBack()) {
         $Model = new WarningModel();
         $Form->SetModel($Model);
         $Form->InputPrefix = '';
         
         $Form->SetFormValue('UserID', $UserID);
         
         if ($Form->Save()) {
            $Sender->InformMessage(T('Your warning was added.'));
            $Sender->JsonTarget('', '', 'Refresh');
         }
      } else {
         $Type = reset($WarningTypes);
         $Form->SetValue('WarningTypeID', GetValue('WarningTypeID', $Type));
      }
      
      $Sender->SetData('Profile', $User);
      $Sender->SetData('Title', sprintf(T('Warn %s'), htmlspecialchars(GetValue('Name', $User))));
      $Sender->Render('Warn', '', 'plugins/Warnings');
   }
   
   /**
    *
    * @param ProfileController $Sender
    * @param string|int $UserReference
    * @param string $Username 
    */
   public function ProfileController_Warnings_Create($Sender, $UserReference, $Username = '') {
      $Sender->EditMode(FALSE);
      $Sender->GetUserInfo($UserReference, $Username);
      $Sender->_SetBreadcrumbs(T('Warnings'), UserUrl($Sender->User, '', 'warnings'));
      $Sender->SetTabView('Warnings', 'Warnings', '', 'plugins/Warnings');
      $Sender->EditMode = FALSE;
      
      $WarningModel = new WarningModel();
      $Warnings = $WarningModel->GetWhere(array('WarnUserID' => $Sender->User->UserID))->ResultArray();
      $Sender->SetData('Warnings', $Warnings);
      
      $Sender->Render();
   }
   
   /**
    * Hide signatures for people in the pokey
    * 
    * @param SignaturesPlugin $Sender 
    */
   public function SignaturesPlugin_BeforeDrawSignature_Handler($Sender) {
      $UserID = $Sender->EventArguments['UserID'];
      $User = Gdn::UserModel()->GetID($UserID);
      if (!GetValue('Punished', $InfractionsCache)) return;
      $Sender->EventArguments['Signature'] = NULL;
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
   
   public function UtilityController_ProcessWarnings_Create($Sender) {
      $WarningModel = new WarningModel();
      $Result = $WarningModel->ProcessAllWarnings();
      
      $Sender->SetData('Result', $Result);
      $Sender->Render('Blank');
   }
}