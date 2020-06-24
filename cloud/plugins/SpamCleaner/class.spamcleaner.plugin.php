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
   
   public function setup() {
      $this->structure();
   }
   
   public function structure() {
      $st = Gdn::structure();
      
      foreach (self::$Types as $type => $options) {
         $st->table($type)->column('Verified', 'tinyint(1)', '0')->set();
      }
   }
   
   public static function typeInfo($type = NULL) {
      if ($type == NULL)
         $result = self::$Types;
      else
         $result = [$type => self::$Types[$type]];
      
      
      foreach ($result as $t => &$info) {
         touchValue('Table', $info, $t);
         touchValue('PrimaryKey', $info, $t.'ID');
         touchValue('Label', $info, t(Gdn_Form::labelCode($t).'s'));
      }
      
      if ($type == NULL) {
         return $result;
      } else {
         return array_pop($result);
      }
   }
   
   /// Event Handlers ///
   
   public function base_getAppSettingsMenuItems_handler($sender) {
      $menu = &$sender->EventArguments['SideMenu'];
      
      $menu->addLink('Moderation', t('Clean Spam'), '/log/cleanspam', 'Garden.Moderation.Manage');
   }
   
   /**
    *
    * @param Gdn_Controller $sender 
    */
   public function logController_cleanSpam_create($sender) {
      $sender->permission('Garden.Moderation.Manage');
      $sender->title(t('Clean Spam'));
      
      if ($sender->Form->isPostBack()) {
         $sender->addDefinition('StartCleanSpam', TRUE);
         $sender->setData('StartCleanSpam', TRUE);
         
         if ($sender->Form->getFormValue('VerifyModerators')) {
            $roleModel = new RoleModel();
            $roleIDs = $roleModel->getByPermission('Garden.Moderation.Manage')->resultArray();
            $roleIDs = array_column($roleIDs, 'RoleID');
            
            Gdn::sql()
               ->update('User u')
               ->join('UserRole ur', 'u.UserID = ur.UserID')
               ->whereIn('ur.RoleID', $roleIDs)
               ->set('u.Verified', 1)
               ->put();
         }
      } else {
         $sender->Form->setValue('VerifyModerators', 1);
      }
      
      $sender->addJsFile('spamcleaner.js', 'plugins/SpamCleaner', ['_Hint' => 'Inline']);
      $sender->setData('Types', self::typeInfo());
      $sender->addSideMenu();
      $sender->render('CleanSpam', '', 'plugins/SpamCleaner');
   }
   
   public function logController_cleanSpamTick_create($sender, $type) {
      $sender->permission('Garden.Moderation.Manage');
      $form = new Gdn_Form();
      
      if (!$form->isPostBack())
         return;
      
      $startTime = time();
      
      $typeInfo = self::typeInfo($type);
      
      // Grab some records to work on.
      $where = ['Verified' => 0];
      
      switch ($type) {
         case 'Activity':
            $types = [
                getValue('ActivityTypeID', ActivityModel::getActivityType('WallPost')),
                getValue('ActivityTypeID', ActivityModel::getActivityType('WallStatus'))
                ];
            $where['ActivityTypeID'] = $types;
            break;
      }
      
      $data = Gdn::sql()->getWhere($type, $where, '', '', self::PAGE_LIMIT)->resultArray();
      $sender->setData('Type', $type);
      $sender->setData('Count', count($data));
      $sender->setData('Complete', count($data) < self::PAGE_LIMIT);
      
      $countSpam = 0;
      foreach ($data as $row) {
         $pK = $typeInfo['PrimaryKey'];
         $iD = $row[$pK];
         
         // We need to mark the row verified here so if it's restored it doesn't get put back in the spam.
         $row['Verified'] = TRUE;
         
         $spam = SpamModel::isSpam($type, $row);
         if ($spam) {
            $countSpam++;
            Gdn::sql()->delete($type, [$pK => $iD]);
         } else {
            Gdn::sql()->put($type, ['Verified' => 1], [$pK => $iD]);
         }
         
         $currentTime = time();
         if ($currentTime > $startTime + self::MAX_TIME)
            break;
      }
      
      $sender->setData('CountSpam', $countSpam);
      
      $sender->render('Blank', 'Utility', 'Dashboard');
   }
}
