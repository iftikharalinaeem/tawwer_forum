<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class WarningsPlugin extends Gdn_Plugin {
   /// Propeties ///


   /// Methods ///

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()->Table('Warning')
         ->PrimaryKey('WarningID')
         ->Column('Type', ['Warning', 'Ban', 'Punish'])
         ->Column('WarnUserID', 'int') // who we're warning
         ->Column('Points', 'smallint')
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int') // who did the warning.
         ->Column('InsertIPAddress', 'ipaddress')
         ->Column('Body', 'text', FALSE)
         ->Column('ModeratorNote', 'varchar(255)', TRUE)
         ->Column('Format', 'varchar(20)', TRUE)
         ->Column('DateExpires', 'datetime', TRUE)
         ->Column('Expired', 'tinyint(1)')
         ->Column('RecordType', 'varchar(10)', TRUE) // Warned for a something they posted?
         ->Column('RecordID', 'int', TRUE)
         ->Column('ConversationID', 'int', TRUE, 'index')
         ->Column('Attributes', 'text', TRUE)
         ->Set();

      Gdn::Structure()->Table('User')
         ->Column('Punished', 'tinyint', '0')
         ->Set();

      $activityModel = new ActivityModel();
      $activityModel->DefineType('Warning');
   }

   /// Event Handlers ///

   /**
    * Add Warn option to profile options.
    */
   public function ProfileController_BeforeProfileOptions_Handler($sender, $args) {
      if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         return;

      if (Gdn::Session()->UserID == $sender->EventArguments['UserID'])
         return;

      if (!GetValue('EditMode', Gdn::Controller())) {
         $sender->EventArguments['ProfileOptions'][] = [
             'Text' => Sprite('SpWarn').' '.T('Warn'),
             'Url' => '/profile/warn?userid='.$args['UserID'],
             'CssClass' => 'Popup WarnButton'
         ];
      }
   }

   public function ProfileController_Card_Render($sender, $args) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $userID = $sender->Data('Profile.UserID');

         $sender->Data['Actions']['Warn'] = [
            'Text' => Sprite('SpWarn'),
            'Title' => T('Warn'),
            'Url' => '/profile/warn?userid='.$userID,
            'CssClass' => 'Popup'
            ];

         $level = Gdn::UserMetaModel()->GetUserMeta($userID, 'Warnings.Level');
         $level = GetValue('Warnings.Level', $level);
         $sender->Data['Actions']['Warnings'] = [
            'Text' => '<span class="Count">'.(int)$level.'</span>',
            'Title' => T('Warnings'),
            'Url' => UserUrl($sender->Data('Profile'), '', 'warnings'),
            'CssClass' => 'Popup'
            ];
      }
   }

   public function UserModel_SetCalculatedFields_Handler($sender, $args) {
      $punished = GetValue('Punished', $args['User']);
      if ($punished) {
         $cssClass = GetValue('_CssClass', $args['User']);
         $cssClass .= ' Jailed';
         SetValue('_CssClass', $args['User'], trim($cssClass));
      }
   }

   public function ProfileController_BeforeUserInfo_Handler($sender, $args) {
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

   public function AssetModel_StyleCss_Handler($sender, $args) {
      $sender->AddCssFile('warnings.css', 'plugins/Warnings');
   }

   public function Gdn_Dispatcher_AppStartup_Handler($sender) {
      if (!Gdn::Session()->UserID || !GetValue('Punished', Gdn::Session()->User))
         return;

      // The user has been punished so strip some abilities.
      Gdn::Session()->SetPermission('Vanilla.Discussions.Add', []);

      // Reduce posting speed to 1 per 150 sec
       SaveToConfig([
          'Vanilla.Comment.SpamCount' => 0,
          'Vanilla.Comment.SpamTime'  => 150,
          'Vanilla.Comment.SpamLock'  => 150
       ],NULL,FALSE);
   }

   public function ProfileController_AddProfileTabs_Handler($sender) {
      if (is_object($sender->User) && $sender->User->UserID > 0) {
         $userID = $sender->User->UserID;

//         $WarningsLabel = Sprite('SpWarn').T('Warnings');
         $warningsLabel = Sprite('SpWarn').' '.T('Warnings');

         $count = '';
         $level = Gdn::UserMetaModel()->GetUserMeta($userID, 'Warnings.Level');
         $level = GetValue('Warnings.Level', $level);
         if ($level) {
            $count = '<span class="Aside"><span class="Count">'.sprintf(T('Level %s'), $level).'</span></span>';
         }

         $sender->AddProfileTab(T('Warnings'), UserUrl($sender->User, '', 'warnings'), 'Warnings', $warningsLabel.$count);


      }
   }

    /**
     * @param ProfileController $sender
     * @param mixed $warningID
     * @param string|bool $target
     */
   public function ProfileController_RemoveWarning_Create($sender, $warningID, $target = FALSE) {
      $sender->Permission('Garden.Moderation.Manage');

      $warningModel = new WarningModel();
      $warning = $warningModel->GetID($warningID, DATASET_TYPE_ARRAY);
      if (!$warningID)
         throw NotFoundException('Warning');

      $form = new Gdn_Form();
      $sender->Form = $form;

      if ($form->AuthenticatedPostBack()) {
//         die($Form->GetFormValue('RemoveType'));
//         decho($WarningModel->ValidationResults());
         switch ($form->GetFormValue('RemoveType')) {
            case 'expire':
               $set = ArrayTranslate($warning, ['Expired', 'DateExpired', 'Attributes']);
               $set['Expired'] = 1;
               $set['DateExpires'] = Gdn_Format::ToDateTime();
               $set['Attributes']['RemovedByUserID'] = Gdn::Session()->UserID;
               $warningModel->SetField($warningID, $set);
               break;
            case 'delete':
               $warningModel->Delete(['WarningID' => $warningID]);
               break;
            default:
               $form->AddError(T("Do you want to expire or delete?"));
         }
         $form->SetValidationResults($warningModel->ValidationResults());

         $warningModel->ProcessWarnings($warning['WarnUserID']);
         if ($form->ErrorCount() == 0) {
            if ($target)
               $sender->setRedirectTo($target);
            else
               $sender->JsonTarget('', '', 'Refresh');
         }
      } else {
         $form->SetValue('RemoveType', 'expire');
      }

      $sender->SetData('Warning', $warning);
      $sender->SetData('Title', T('Remove Warning'));
      $sender->Render('RemoveWarning', '', 'plugins/Warnings');
   }

   /**
    *
    * @param ModerationController $sender
    * @param int $userID
    */
   public function ProfileController_Warn_Create($sender, $userID) {
      $sender->Permission('Garden.Moderation.Manage');
      $user = Gdn::UserModel()->GetID($userID, DATASET_TYPE_ARRAY);
      $meta = Gdn::UserMetaModel()->GetUserMeta($userID, 'Warnings.%');

      $currentLevel = GetValue('Warnings.Level', $meta, 0);

      $form = new Gdn_Form();
      $sender->Form = $form;

      if (!$userID)
         throw NotFoundException('User');

      if ($form->AuthenticatedPostBack()) {
         $model = new WarningModel();
         $form->SetModel($model);

         $form->SetFormValue('WarnUserID', $userID);

         if ($form->Save()) {
            $sender->InformMessage(T('Your warning was added.'));
         }
      } else {
         $form->SetValue('ExpireNumber', 7);
         $form->SetValue('ExpireUnit', 'days');
         $form->SetValue('Level', $currentLevel);
      }

      $sender->SetData('Profile', $user);
      $sender->SetData('CurrentLevel', $currentLevel);
      $sender->SetData('MaxLevel', 5);
      $sender->SetData('Title', T('Add a Warning'));
      $sender->Render('Warn', '', 'plugins/Warnings');
   }

   /**
    *
    * @param ProfileController $sender
    * @param string|int $userReference
    * @param string $username
    */
   public function ProfileController_Warnings_Create($sender, $userReference, $username = '') {
      $sender->EditMode(FALSE);
      $sender->GetUserInfo($userReference, $username);
      $sender->_SetBreadcrumbs(T('Warnings'), UserUrl($sender->User, '', 'warnings'));
      $sender->SetTabView('Warnings', 'Warnings', '', 'plugins/Warnings');
      $sender->EditMode = FALSE;

      $warningModel = new WarningModel();
      $warnings = $warningModel->GetWhere(['WarnUserID' => $sender->User->UserID])->ResultArray();
      $sender->SetData('Warnings', $warnings);

      $sender->Render();
   }

   /**
    * Hide signatures for people in the pokey
    *
    * @param SignaturesPlugin $sender
    */
   public function SignaturesPlugin_BeforeDrawSignature_Handler($sender) {
      $userID = $sender->EventArguments['UserID'];
      $user = Gdn::UserModel()->GetID($userID);
      if (!GetValue('Punished', $infractionsCache)) return;
      $sender->EventArguments['Signature'] = NULL;
   }

   /**
    *
    * @param UserModel $sender
    */
   public function UserModel_Visit_Handler($sender, $args) {
      if (Gdn::Session()->UserID) {
         $warningModel = new WarningModel();
         $warningModel->ProcessWarnings(Gdn::Session()->UserID);
      }
   }

   public function UtilityController_ProcessWarnings_Create($sender) {
      $warningModel = new WarningModel();
      $result = $warningModel->ProcessAllWarnings();

      $sender->SetData('Result', $result);
      $sender->Render('Blank');
   }
}
