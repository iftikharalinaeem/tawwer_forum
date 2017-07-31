<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Adds Question & Answer format to Vanilla.
 * 
 * You can set Plugins.QnA.UseBigButtons = TRUE in config to separate 'New Discussion'
 * and 'Ask Question' into "separate" forms each with own big button in Panel.
 */
class SpamCleanerPlugin extends Gdn_Plugin {
   /// Constants ///
   
   const PAGE_LIMIT = 50;
   const MAX_TIME = 30;
   
   static $Types = [
       'Discussion' => [],
       'Comment' => [],
       'Activity' => ['Label' => 'Activities'],
       'ActivityComment' => []];
   
   /// Properties ///
   
   
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $st = Gdn::Structure();
      
      foreach (self::$Types as $type => $options) {
         $st->Table($type)->Column('Verified', 'tinyint(1)', '0')->Set();
      }
   }
   
   public static function TypeInfo($type = NULL) {
      if ($type == NULL)
         $result = self::$Types;
      else
         $result = [$type => self::$Types[$type]];
      
      
      foreach ($result as $t => &$info) {
         TouchValue('Table', $info, $t);
         TouchValue('PrimaryKey', $info, $t.'ID');
         TouchValue('Label', $info, T(Gdn_Form::LabelCode($t).'s'));
      }
      
      if ($type == NULL) {
         return $result;
      } else {
         return array_pop($result);
      }
   }
   
   /// Event Handlers ///
   
   public function Base_GetAppSettingsMenuItems_Handler($sender) {
      $menu = &$sender->EventArguments['SideMenu'];
      
      $menu->AddLink('Moderation', T('Clean Spam'), '/log/cleanspam', 'Garden.Moderation.Manage');
   }
   
   /**
    *
    * @param Gdn_Controller $sender 
    */
   public function LogController_CleanSpam_Create($sender) {
      $sender->Permission('Garden.Moderation.Manage');
      $sender->Title(T('Clean Spam'));
      
      if ($sender->Form->IsPostBack()) {
         $sender->AddDefinition('StartCleanSpam', TRUE);
         $sender->SetData('StartCleanSpam', TRUE);
         
         if ($sender->Form->GetFormValue('VerifyModerators')) {
            $roleModel = new RoleModel();
            $roleIDs = $roleModel->GetByPermission('Garden.Moderation.Manage')->ResultArray();
            $roleIDs = array_column($roleIDs, 'RoleID');
            
            Gdn::SQL()
               ->Update('User u')
               ->Join('UserRole ur', 'u.UserID = ur.UserID')
               ->WhereIn('ur.RoleID', $roleIDs)
               ->Set('u.Verified', 1)
               ->Put();
         }
      } else {
         $sender->Form->SetValue('VerifyModerators', 1);
      }
      
      $sender->AddJsFile('spamcleaner.js', 'plugins/SpamCleaner', ['_Hint' => 'Inline']);
      $sender->SetData('Types', self::TypeInfo());
      $sender->AddSideMenu();
      $sender->Render('CleanSpam', '', 'plugins/SpamCleaner');
   }
   
   public function LogController_CleanSpamTick_Create($sender, $type) {
      $sender->Permission('Garden.Moderation.Manage');
      $form = new Gdn_Form();
      
      if (!$form->IsPostBack())
         return;
      
      $startTime = time();
      
      $typeInfo = self::TypeInfo($type);
      
      // Grab some records to work on.
      $where = ['Verified' => 0];
      
      switch ($type) {
         case 'Activity':
            $types = [
                GetValue('ActivityTypeID', ActivityModel::GetActivityType('WallPost')),
                GetValue('ActivityTypeID', ActivityModel::GetActivityType('WallStatus'))
                ];
            $where['ActivityTypeID'] = $types;
            break;
      }
      
      $data = Gdn::SQL()->GetWhere($type, $where, '', '', self::PAGE_LIMIT)->ResultArray();
      $sender->SetData('Type', $type);
      $sender->SetData('Count', count($data));
      $sender->SetData('Complete', count($data) < self::PAGE_LIMIT);
      
      $countSpam = 0;
      foreach ($data as $row) {
         $pK = $typeInfo['PrimaryKey'];
         $iD = $row[$pK];
         
         // We need to mark the row verified here so if it's restored it doesn't get put back in the spam.
         $row['Verified'] = TRUE;
         
         $spam = SpamModel::IsSpam($type, $row);
         if ($spam) {
            $countSpam++;
            Gdn::SQL()->Delete($type, [$pK => $iD]);
         } else {
            Gdn::SQL()->Put($type, ['Verified' => 1], [$pK => $iD]);
         }
         
         $currentTime = time();
         if ($currentTime > $startTime + self::MAX_TIME)
            break;
      }
      
      $sender->SetData('CountSpam', $countSpam);
      
      $sender->Render('Blank', 'Utility', 'Dashboard');
   }
}
