<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['SpamCleaner'] = array(
   'Name' => 'Spam Cleaner',
   'Description' => "Gives the functionality to go through an entire forum to clean it of spam.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'SettingsUrl' => '/log/cleanspam',
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

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
   
   static $Types = array(
       'Discussion' => array(),
       'Comment' => array(),
       'Activity' => array('Label' => 'Activities'),
       'ActivityComment' => array());
   
   /// Properties ///
   
   
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $St = Gdn::Structure();
      
      foreach (self::$Types as $Type => $Options) {
         $St->Table($Type)->Column('Verified', 'tinyint(1)', '0')->Set();
      }
   }
   
   public static function TypeInfo($Type = NULL) {
      if ($Type == NULL)
         $Result = self::$Types;
      else
         $Result = array($Type => self::$Types[$Type]);
      
      
      foreach ($Result as $T => &$Info) {
         TouchValue('Table', $Info, $T);
         TouchValue('PrimaryKey', $Info, $T.'ID');
         TouchValue('Label', $Info, T(Gdn_Form::LabelCode($T).'s'));
      }
      
      if ($Type == NULL) {
         return $Result;
      } else {
         return array_pop($Result);
      }
   }
   
   /// Event Handlers ///
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      
      $Menu->AddLink('Moderation', T('Clean Spam'), '/log/cleanspam', 'Garden.Moderation.Manage');
   }
   
   /**
    *
    * @param Gdn_Controller $Sender 
    */
   public function LogController_CleanSpam_Create($Sender) {
      $Sender->Permission('Garden.Moderation.Manage');
      $Sender->Title(T('Clean Spam'));
      
      $Sender->Form->InputPrefix = '';
      
      if ($Sender->Form->IsPostBack()) {
         $Sender->AddDefinition('StartCleanSpam', TRUE);
         $Sender->SetData('StartCleanSpam', TRUE);
         
         if ($Sender->Form->GetFormValue('VerifyModerators')) {
            $RoleModel = new RoleModel();
            $RoleIDs = $RoleModel->GetByPermission('Garden.Moderation.Manage')->ResultArray();
            $RoleIDs = ConsolidateArrayValuesByKey($RoleIDs, 'RoleID');
            
            Gdn::SQL()
               ->Update('User u')
               ->Join('UserRole ur', 'u.UserID = ur.UserID')
               ->WhereIn('ur.RoleID', $RoleIDs)
               ->Set('u.Verified', 1)
               ->Put();
         }
      } else {
         $Sender->Form->SetValue('VerifyModerators', 1);
      }
      
      $Sender->AddJsFile('spamcleaner.js', 'plugins/SpamCleaner', array('_Hint' => 'Inline'));
      $Sender->SetData('Types', self::TypeInfo());
      $Sender->AddSideMenu();
      $Sender->Render('CleanSpam', '', 'plugins/SpamCleaner');
   }
   
   public function LogController_CleanSpamTick_Create($Sender, $Type) {
      $Sender->Permission('Garden.Moderation.Manage');
      $Form = new Gdn_Form();
      
      if (!$Form->IsPostBack())
         return;
      
      $StartTime = time();
      
      $TypeInfo = self::TypeInfo($Type);
      
      // Grab some records to work on.
      $Where = array('Verified' => 0);
      
      switch ($Type) {
         case 'Activity':
            $Types = array(
                GetValue('ActivityTypeID', ActivityModel::GetActivityType('WallPost')),
                GetValue('ActivityTypeID', ActivityModel::GetActivityType('WallStatus'))
                );
            $Where['ActivityTypeID'] = $Types;
            break;
      }
      
      $Data = Gdn::SQL()->GetWhere($Type, $Where, '', '', self::PAGE_LIMIT)->ResultArray();
      $Sender->SetData('Type', $Type);
      $Sender->SetData('Count', count($Data));
      $Sender->SetData('Complete', count($Data) < self::PAGE_LIMIT);
      
      $CountSpam = 0;
      foreach ($Data as $Row) {
         $PK = $TypeInfo['PrimaryKey'];
         $ID = $Row[$PK];
         
         // We need to mark the row verified here so if it's restored it doesn't get put back in the spam.
         $Row['Verified'] = TRUE;
         
         $Spam = SpamModel::IsSpam($Type, $Row);
         if ($Spam) {
            $CountSpam++;
            Gdn::SQL()->Delete($Type, array($PK => $ID));
         } else {
            Gdn::SQL()->Put($Type, array('Verified' => 1), array($PK => $ID));
         }
         
         $CurrentTime = time();
         if ($CurrentTime > $StartTime + self::MAX_TIME)
            break;
      }
      
      $Sender->SetData('CountSpam', $CountSpam);
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
}