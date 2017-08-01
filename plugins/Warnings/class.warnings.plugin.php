<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class WarningsPlugin extends Gdn_Plugin {
   /// Propeties ///


   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::structure()->table('Warning')
         ->primaryKey('WarningID')
         ->column('Type', ['Warning', 'Ban', 'Punish'])
         ->column('WarnUserID', 'int') // who we're warning
         ->column('Points', 'smallint')
         ->column('DateInserted', 'datetime')
         ->column('InsertUserID', 'int') // who did the warning.
         ->column('InsertIPAddress', 'ipaddress')
         ->column('Body', 'text', FALSE)
         ->column('ModeratorNote', 'varchar(255)', TRUE)
         ->column('Format', 'varchar(20)', TRUE)
         ->column('DateExpires', 'datetime', TRUE)
         ->column('Expired', 'tinyint(1)')
         ->column('RecordType', 'varchar(10)', TRUE) // Warned for a something they posted?
         ->column('RecordID', 'int', TRUE)
         ->column('ConversationID', 'int', TRUE, 'index')
         ->column('Attributes', 'text', TRUE)
         ->set();

      Gdn::structure()->table('User')
         ->column('Punished', 'tinyint', '0')
         ->set();

      $activityModel = new ActivityModel();
      $activityModel->defineType('Warning');
   }

   /// Event Handlers ///

   /**
    * Add Warn option to profile options.
    */
   public function profileController_beforeProfileOptions_handler($sender, $args) {
      if (!Gdn::session()->checkPermission('Garden.Moderation.Manage'))
         return;

      if (Gdn::session()->UserID == $sender->EventArguments['UserID'])
         return;

      if (!getValue('EditMode', Gdn::controller())) {
         $sender->EventArguments['ProfileOptions'][] = [
             'Text' => sprite('SpWarn').' '.t('Warn'),
             'Url' => '/profile/warn?userid='.$args['UserID'],
             'CssClass' => 'Popup WarnButton'
         ];
      }
   }

   public function profileController_card_render($sender, $args) {
      if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
         $userID = $sender->data('Profile.UserID');

         $sender->Data['Actions']['Warn'] = [
            'Text' => sprite('SpWarn'),
            'Title' => t('Warn'),
            'Url' => '/profile/warn?userid='.$userID,
            'CssClass' => 'Popup'
            ];

         $level = Gdn::userMetaModel()->getUserMeta($userID, 'Warnings.Level');
         $level = getValue('Warnings.Level', $level);
         $sender->Data['Actions']['Warnings'] = [
            'Text' => '<span class="Count">'.(int)$level.'</span>',
            'Title' => t('Warnings'),
            'Url' => userUrl($sender->data('Profile'), '', 'warnings'),
            'CssClass' => 'Popup'
            ];
      }
   }

   public function userModel_setCalculatedFields_handler($sender, $args) {
      $punished = getValue('Punished', $args['User']);
      if ($punished) {
         $cssClass = getValue('_CssClass', $args['User']);
         $cssClass .= ' Jailed';
         setValue('_CssClass', $args['User'], trim($cssClass));
      }
   }

   public function profileController_beforeUserInfo_handler($sender, $args) {
      if (!Gdn::controller()->data('Profile.Punished'))
         return;

      echo '<div class="Hero Hero-Jailed Message">';

      echo '<b>';
      if (Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID) {
         echo t("You've been Jailed.");
      } else {
         echo sprintf(t("%s has been Jailed."), htmlspecialchars(Gdn::controller()->data('Profile.Name')));
      }
      echo '</b>';

      echo "<ul>
   <li>Can't post discussions.</li>
   <li>Can't post as often.</li>
   <li>Signature hidden.</li>
</ul>";

      echo '</div>';
   }

   public function assetModel_styleCss_handler($sender, $args) {
      $sender->addCssFile('warnings.css', 'plugins/Warnings');
   }

   public function gdn_Dispatcher_AppStartup_Handler($sender) {
      if (!Gdn::session()->UserID || !getValue('Punished', Gdn::session()->User))
         return;

      // The user has been punished so strip some abilities.
      Gdn::session()->setPermission('Vanilla.Discussions.Add', []);

      // Reduce posting speed to 1 per 150 sec
       saveToConfig([
          'Vanilla.Comment.SpamCount' => 0,
          'Vanilla.Comment.SpamTime'  => 150,
          'Vanilla.Comment.SpamLock'  => 150
       ],NULL,FALSE);
   }

   public function profileController_addProfileTabs_handler($sender) {
      if (is_object($sender->User) && $sender->User->UserID > 0) {
         $userID = $sender->User->UserID;

//         $WarningsLabel = sprite('SpWarn').t('Warnings');
         $warningsLabel = sprite('SpWarn').' '.t('Warnings');

         $count = '';
         $level = Gdn::userMetaModel()->getUserMeta($userID, 'Warnings.Level');
         $level = getValue('Warnings.Level', $level);
         if ($level) {
            $count = '<span class="Aside"><span class="Count">'.sprintf(t('Level %s'), $level).'</span></span>';
         }

         $sender->addProfileTab(t('Warnings'), userUrl($sender->User, '', 'warnings'), 'Warnings', $warningsLabel.$count);


      }
   }

    /**
     * @param ProfileController $sender
     * @param mixed $warningID
     * @param string|bool $target
     */
   public function profileController_removeWarning_create($sender, $warningID, $target = FALSE) {
      $sender->permission('Garden.Moderation.Manage');

      $warningModel = new WarningModel();
      $warning = $warningModel->getID($warningID, DATASET_TYPE_ARRAY);
      if (!$warningID)
         throw notFoundException('Warning');

      $form = new Gdn_Form();
      $sender->Form = $form;

      if ($form->authenticatedPostBack()) {
//         die($Form->getFormValue('RemoveType'));
//         decho($WarningModel->validationResults());
         switch ($form->getFormValue('RemoveType')) {
            case 'expire':
               $set = arrayTranslate($warning, ['Expired', 'DateExpired', 'Attributes']);
               $set['Expired'] = 1;
               $set['DateExpires'] = Gdn_Format::toDateTime();
               $set['Attributes']['RemovedByUserID'] = Gdn::session()->UserID;
               $warningModel->setField($warningID, $set);
               break;
            case 'delete':
               $warningModel->delete(['WarningID' => $warningID]);
               break;
            default:
               $form->addError(t("Do you want to expire or delete?"));
         }
         $form->setValidationResults($warningModel->validationResults());

         $warningModel->processWarnings($warning['WarnUserID']);
         if ($form->errorCount() == 0) {
            if ($target)
               $sender->setRedirectTo($target);
            else
               $sender->jsonTarget('', '', 'Refresh');
         }
      } else {
         $form->setValue('RemoveType', 'expire');
      }

      $sender->setData('Warning', $warning);
      $sender->setData('Title', t('Remove Warning'));
      $sender->render('RemoveWarning', '', 'plugins/Warnings');
   }

   /**
    *
    * @param ModerationController $sender
    * @param int $userID
    */
   public function profileController_warn_create($sender, $userID) {
      $sender->permission('Garden.Moderation.Manage');
      $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
      $meta = Gdn::userMetaModel()->getUserMeta($userID, 'Warnings.%');

      $currentLevel = getValue('Warnings.Level', $meta, 0);

      $form = new Gdn_Form();
      $sender->Form = $form;

      if (!$userID)
         throw notFoundException('User');

      if ($form->authenticatedPostBack()) {
         $model = new WarningModel();
         $form->setModel($model);

         $form->setFormValue('WarnUserID', $userID);

         if ($form->save()) {
            $sender->informMessage(t('Your warning was added.'));
         }
      } else {
         $form->setValue('ExpireNumber', 7);
         $form->setValue('ExpireUnit', 'days');
         $form->setValue('Level', $currentLevel);
      }

      $sender->setData('Profile', $user);
      $sender->setData('CurrentLevel', $currentLevel);
      $sender->setData('MaxLevel', 5);
      $sender->setData('Title', t('Add a Warning'));
      $sender->render('Warn', '', 'plugins/Warnings');
   }

   /**
    *
    * @param ProfileController $sender
    * @param string|int $userReference
    * @param string $username
    */
   public function profileController_warnings_create($sender, $userReference, $username = '') {
      $sender->editMode(FALSE);
      $sender->getUserInfo($userReference, $username);
      $sender->_SetBreadcrumbs(t('Warnings'), userUrl($sender->User, '', 'warnings'));
      $sender->setTabView('Warnings', 'Warnings', '', 'plugins/Warnings');
      $sender->EditMode = FALSE;

      $warningModel = new WarningModel();
      $warnings = $warningModel->getWhere(['WarnUserID' => $sender->User->UserID])->resultArray();
      $sender->setData('Warnings', $warnings);

      $sender->render();
   }

   /**
    * Hide signatures for people in the pokey
    *
    * @param SignaturesPlugin $sender
    */
   public function signaturesPlugin_beforeDrawSignature_handler($sender) {
      $userID = $sender->EventArguments['UserID'];
      $user = Gdn::userModel()->getID($userID);
      if (!getValue('Punished', $infractionsCache)) return;
      $sender->EventArguments['Signature'] = NULL;
   }

   /**
    *
    * @param UserModel $sender
    */
   public function userModel_visit_handler($sender, $args) {
      if (Gdn::session()->UserID) {
         $warningModel = new WarningModel();
         $warningModel->processWarnings(Gdn::session()->UserID);
      }
   }

   public function utilityController_processWarnings_create($sender) {
      $warningModel = new WarningModel();
      $result = $warningModel->processAllWarnings();

      $sender->setData('Result', $result);
      $sender->render('Blank');
   }
}
