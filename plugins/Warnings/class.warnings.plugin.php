<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Warnings'] = array(
   'Name' => 'Warnings',
   'Description' => "Allows moderators to warn users to help police the community.",
   'Version' => '1.0.1b',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class WarningsPlugin extends Gdn_Plugin {
   /// Propeties ///
   
   
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()->Table('Warning')
         ->PrimaryKey('WarningID')
         ->Column('Type', array('Warning', 'Ban', 'Punish'))
         ->Column('WarnUserID', 'int') // who we're warning
         ->Column('Points', 'smallint')
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int') // who did the warning.
         ->Column('InsertIPAddress', 'varchar(15)')
         ->Column('Body', 'text', FALSE)
         ->Column('ModeratorNote', 'varchar(255)', TRUE)
         ->Column('Format', 'varchar(20)', TRUE)
         ->Column('DateExpires', 'datetime', TRUE)
         ->Column('Expired', 'tinyint(1)')
         ->Column('RecordType', 'varchar(10)', TRUE) // Warned for a something they posted?
         ->Column('RecordID', 'int', TRUE)
         ->Column('Attributes', 'text', TRUE)
         ->Set();

      Gdn::Structure()->Table('User')
         ->Column('Punished', 'tinyint', '0')
         ->Set();
      
      $ActivityModel = new ActivityModel();
      $ActivityModel->DefineType('Warning');
   }
   
   /// Event Handlers ///
   
   /**
    * Add Warn option to profile options.
    */
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         return;
      
      if (Gdn::Session()->UserID == $Sender->EventArguments['UserID'])
         return;
      
      if (!GetValue('EditMode', Gdn::Controller())) {
         $Sender->EventArguments['ProfileOptions'][] = array(
             'Text' => T('Warn'),
             'Url' => '/profile/warn?userid='.$Args['UserID'],
             'CssClass' => 'Popup WarnButton'
         );
      }
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
   <li>Signature removed.</li>
</ul>";
      
      echo '</div>';
   }
   
   /**
    * @param Gdn_Controller $Sender 
    */
   public function Base_Render_Before($Sender) {
      if (!InSection('Dashboard')) {
         $Sender->AddCssFile('warnings.css', 'plugins/Warnings');
         
//         $Sender->AddJsFile('warnings.js', 'plugins/Warnings');
      }
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
      if (is_object($Sender->User) && $Sender->User->UserID > 0) {
         $UserID = $Sender->User->UserID;
         
//         $WarningsLabel = Sprite('SpWarn').T('Warnings');
         $WarningsLabel = Sprite('SpWarnings').T('Warnings');
         
         $Count = '';
         $Level = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.Level');
         $Level = GetValue('Warnings.Level', $Level);
         if ($Level) {
            $Count = '<span class="Aside"><span class="Count">'.sprintf(T('Level %s'), $Level).'</span></span>';
         }
         
         $Sender->AddProfileTab(T('Warnings'), UserUrl($Sender->User, '', 'warnings'), 'Warnings', $WarningsLabel.$Count);
         
         
      }
   }
   
   public function ProfileController_RemoveWarning_Create($Sender, $WarningID, $Target = FALSE) {
      $Sender->Permission('Garden.Moderation.Manage');
      
      $WarningModel = new WarningModel();
      $Warning = $WarningModel->GetID($WarningID, DATASET_TYPE_ARRAY);
      if (!$WarningID)
         throw NotFoundException('Warning');
      
      $Form = new Gdn_Form();
      $Form->InputPrefix = '';
      $Sender->Form = $Form;
      
      if ($Form->IsPostBack()) {
//         die($Form->GetFormValue('RemoveType'));
//         decho($WarningModel->ValidationResults());
         switch ($Form->GetFormValue('RemoveType')) {
            case 'expire':
               $Set = ArrayTranslate($Warning, array('Expired', 'DateExpired', 'Attributes'));
               $Set['Expired'] = 1;
               $Set['DateExpires'] = Gdn_Format::ToDateTime();
               $Set['Attributes']['RemovedByUserID'] = Gdn::Session()->UserID;
               $WarningModel->SetField($WarningID, $Set);
               break;
            case 'delete':
               $WarningModel->Delete(array('WarningID' => $WarningID));
               break;
            default:
               $Form->AddError(T("Do you want to expire or delete?"));
         }
         $Form->SetValidationResults($WarningModel->ValidationResults());
         
         $WarningModel->ProcessWarnings($Warning['WarnUserID']);
         if ($Form->ErrorCount() == 0)
            $Sender->RedirectUrl = Url($Target);
      } else {
         $Form->SetValue('RemoveType', 'expire');
      }
      
      $Sender->SetData('Warning', $Warning);
      $Sender->SetData('Title', T('Remove Warning'));
      $Sender->Render('RemoveWarning', '', 'plugins/Warnings');
   }
   
   /**
    *
    * @param ModerationController $Sender
    * @param int $UserID
    */
   public function ProfileController_Warn_Create($Sender, $UserID) {
      $Sender->Permission('Garden.Moderation.Manage');
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      $Meta = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.%');
      
      $CurrentLevel = GetValue('Warnings.Level', $Meta, 0);
      
      $Form = new Gdn_Form();
      $Form->InputPrefix = '';
      $Sender->Form = $Form;
      
      if (!$UserID)
         throw NotFoundException('User');
      
      if ($Form->IsPostBack()) {
         $Model = new WarningModel();
         $Form->SetModel($Model);
         $Form->InputPrefix = '';
         
         $Form->SetFormValue('WarnUserID', $UserID);
         
         if ($Form->Save()) {
            $Sender->InformMessage(T('Your warning was added.'));
         }
      } else {
         $Form->SetValue('ExpireNumber', 7);
         $Form->SetValue('ExpireUnit', 'days');
         $Form->SetValue('Level', $CurrentLevel);
      }
      
      $Sender->SetData('User', $User);
      $Sender->SetData('CurrentLevel', $CurrentLevel);
      $Sender->SetData('MaxLevel', 5);
      $Sender->SetData('Title', T('Add a Warning'));
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