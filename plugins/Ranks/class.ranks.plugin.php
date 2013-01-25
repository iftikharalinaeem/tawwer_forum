<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Ranks'] = array(
   'Name' => 'Ranks',
   'Description' => "Adds user ranks to the application.",
   'Version' => '1.1.7',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => TRUE
);

class RanksPlugin extends Gdn_Plugin {
   /// Properties ///
   
   public $ActivityLinks = NULL;
   
   public $CommentLinks = NULL;
   
   /// Methods ///
   
   /**
    * Add mapper methods
    * 
    * @param SimpleApiPlugin $Sender
    */
   public function SimpleApiPlugin_Mapper_Handler($Sender) {
      switch ($Sender->Mapper->Version) {
         case '1.0':
            $Sender->Mapper->AddMap(array(
               'ranks/list'            => 'dashboard/settings/ranks',
               'ranks/get'             => 'dashboard/settings/ranks'
            ));
            break;
      }
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      require dirname(__FILE__).'/structure.php';
   }
   
   /// Event Handlers ///
   
   /**
    * 
    * 
    * @param ActivityModel $Sender
    * @param type $Args
    */
   public function ActivityModel_BeforeSave_Handler($Sender, $Args) {
      if ($this->ActivityLinks !== 'no')
         return;
      
      $Activity = $Args['Activity'];
      
      if (preg_match('`https?://`i', $Activity['Story'])) {
         $Sender->Validation->AddValidationResult('Story', 'You have to be around for a little while longer before you can post links.');
      }
   }
   
   public function ActivityModel_BeforeSaveComment_Handler($Sender, $Args) {
      if ($this->ActivityLinks !== 'no')
         return;
      
      $Comment = $Args['Comment'];
      
      if (preg_match('`https?://`i', $Comment['Body'])) {
         $Sender->Validation->AddValidationResult('Body', 'You have to be around for a little while longer before you can post links.');
      }
   }
   
   public function Base_AuthorInfo_Handler($Sender, $Args) {
      if (isset($Args['Comment']))
         $UserID = GetValueR('Comment.InsertUserID', $Args);
      elseif (isset($Args['Discussion']))
         $UserID = GetValueR('Discussion.InsertUserID', $Args);
      else
         return;
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if ($User) {
         echo RankTag($User, 'MItem');
      }
   }
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Reputation', T('Ranks'), 'settings/ranks', 'Garden.Settings.Manage');
   }
   
   public function Base_Render_Before($Sender) {
      if (InSection('Dashboard') || !Gdn::Session()->IsValid())
         return;
      
      $RankID = Gdn::Session()->User->RankID;
      if (!$RankID)
         return;
      
      $Rank = RankModel::Ranks($RankID);
      if (!$Rank || !GetValue('Message', $Rank))
         return;
      
      $ID = "Rank_$RankID";
      
      $DismissedMessages = Gdn::Session()->GetPreference('DismissedMessages', array());
      if (in_array($ID, $DismissedMessages))
         return;
      
      $Message = array(
         'MessageID' => $ID,
         'Content' => $Rank['Message'],
         'Format' => 'Html',
         'AllowDismiss' => TRUE,
         'Enabled' => TRUE,
         'AssetTarget' => 'Content',
         'CssClass' => 'Info'
      );
      $MessageModule = new MessageModule($Sender, $Message);
      $Sender->AddModule($MessageModule);
   }
   
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      if (!Gdn::Session()->UserID)
         return;
      
      RankModel::ApplyAbilities();
   }
   
   public function ProfileController_UsernameMeta_Handler($Sender, $Args) {
      $User = $Sender->Data('Profile');
      if ($User){
         echo RankTag($User, '', ' '.Bullet().' ');
      }
   }
   
   public function ProfileController_EditMyAccountAfter_Handler($Sender, $Args) {
      if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         return;
      }
      
      // Grab a list of all of the manual ranks.
      $CurrentRankID = $Sender->Data('Profile.RankID');
      $AllRanks = RankModel::Ranks();
      $Ranks = array();
      foreach ($AllRanks as $RankID => $Rank) {
         if ($RankID == $CurrentRankID || GetValueR('Criteria.Manual', $Rank)) {
            $Ranks[$RankID] = $Rank['Name'];
         }
      }
      if (count($Ranks) == 0)
         return;
      
      $Sender->SetData('_Ranks', $Ranks);
      
      include $Sender->FetchViewLocation('Rank_Formlet', '', 'plugins/Ranks');
   }
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param type $RankID 
    */
   public function SettingsController_DeleteRank_Create($Sender, $RankID) {
      $Sender->Permission('Garden.Settings.Manage');
      
      if ($Sender->Form->IsPostBack()) {
         if ($Sender->Form->GetFormValue('Yes')) {
            $RankModel = new RankModel();
            $RankModel->Delete(array('RankID' => $RankID));
            
            $Sender->JsonTarget("#Rank_$RankID", NULL, 'SlideUp');
         }
      }

      $Sender->Title(sprintf(T('Delete %s'), T('Rank')));
      $Sender->Render('Rank_Delete', '', 'plugins/Ranks');
   }
   
   /**
    *
    * @param SettingsController $Sender
    * @param int $RankID 
    */
   public function SettingsController_EditRank_Create($Sender, $RankID) {
      $Sender->Title(sprintf(T('Edit %s'), T('Rank')));
      $this->_AddEdit($Sender, $RankID);
   }
   
   protected function _AddEdit($Sender, $RankID = FALSE) {
      $Sender->Permission('Garden.Settings.Manage');
      
      $RankModel = new RankModel();
      
      $DefaultFormat = strtolower(C('Garden.InputFormatter'));
      if ($DefaultFormat == 'textex')
         $DefaultFormat = 'text, links, youtube';
      
      $Formats = array('Text' => 'text', 'TextEx' => 'text, links, and youtube', '' => sprintf('default (%s)', $DefaultFormat));
      $Sender->SetData('_Formats', $Formats);
      
      if ($Sender->Form->IsPostBack()) {
         $Data = $Sender->Form->FormValues();
         unset($Data['hpt'], $Data['Checkboxes'], $Data['Save']);
         
         $SaveData = array();
         foreach ($Data as $Key => $Value) {
            if (strpos($Key, '_') !== FALSE) {
               if ($Value === '')
                  continue;
               
               $Parts = explode('_', $Key, 2);
               $SaveData[$Parts[0]][$Parts[1]] = $Value;
            } else {
               $SaveData[$Key] = $Value;
            }
         }
         
         $Result = $RankModel->Save($SaveData);
         $Sender->Form->SetValidationResults($RankModel->ValidationResults());
         if ($Result) {
            $Sender->InformMessage(T('Your changes have been saved.'));
            $Sender->RedirectUrl = '/settings/ranks';
            
            $Sender->SetData('Rank', RankModel::Ranks($Result));
         }
      } else {
         if ($RankID) {
            $Data = $RankModel->GetID($RankID);

            if (!$Data) {
               throw NotFoundException('Rank');
            }
            
            $SetData = array();
            foreach ($Data as $Key => $Value) {
               if (is_array($Value)) {
                  foreach ($Value as $Key2 => $Value2) {
                     $SetData[$Key.'_'.$Key2] = $Value2;
                  }
               } else {
                  $SetData[$Key] = $Value;
               }
            }
            
            $Sender->Form->SetData($SetData);
            $Sender->Form->AddHidden('RankID', $RankID);
         }
      }
      $Sender->AddSideMenu();
      $Sender->Render('Rank', '', 'plugins/Ranks');
   }
   
   public function SettingsController_AddRank_Create($Sender) {
      $Sender->Title('Add Rank');
      $this->_AddEdit($Sender);
   }
   
   public function SettingsController_Ranks_Create($Sender, $RankID = NULL) {
      $Sender->Permission('Garden.Settings.Manage');
      
      $RankModel = new RankModel();
      
      if (empty($RankID))
         $Ranks = $RankModel->GetWhere(FALSE, 'Level')->ResultArray();
      else {
         $Rank = $RankModel->GetID($RankID);
         $Ranks = array($Rank);
      }
     
      $Sender->SetData('Ranks', $Ranks);
      $Sender->AddSideMenu();
      $Sender->Render('Ranks', '', 'plugins/Ranks');
   }
   
   public function UserModel_AfterRegister_Handler($Sender, $Args) {
      $UserID = $Args['UserID'];
      $User = Gdn::UserModel()->GetID($UserID);
      
      $RankModel = new RankModel();
      $RankModel->ApplyRank($User);
   }
   
   public function UserModel_AfterSignIn_Handler($Sender, $Args) {
      if (!Gdn::Session()->User)
         return;
      
      $RankModel = new RankModel();
      $RankModel->ApplyRank(Gdn::Session()->User);
   }
   
   public function UserModel_AfterSave_Handler($Sender, $Args) {
      if (!Gdn::Controller()) return;
      $UserID = Gdn::Controller()->Data('Profile.UserID');
      if ($UserID != $Args['UserID'])
         return;
      
      // Check to make sure the rank has changed.
      $OldRankID = Gdn::Controller()->Data('Profile.RankID');
      $NewRankID = GetValue('RankID', $Args['Fields']);
      if ($NewRankID && $NewRankID != $OldRankID) {
         // Send the user a notification.
         $RankModel = new RankModel();
         $RankModel->Notify(Gdn::UserModel()->GetID($UserID), $RankModel->GetID($NewRankID));
      }
   }
   
   public function UserModel_GivePoints_Handler($Sender, $Args) {
      $UserID = $Args['UserID'];
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      $RankModel = new RankModel();
      $RankModel->ApplyRank($User);
   }
   
   public function UserModel_Visit_Handler($Sender, $Args) {
      if (!Gdn::Session()->IsValid())
         return;
      
      $RankModel = new RankModel();
      $RankModel->ApplyRank(Gdn::Session()->User);
   }
   
   public function UserModel_SetCalculatedFields_Handler($Sender, $Args) {
      $RankID = GetValue('RankID', $Args['User'], 0);
      $Rank = RankModel::Ranks($RankID);
      
      if ($Rank && isset($Rank['CssClass'])) {
         $CssClass = GetValue('_CssClass', $Args['User']);
         $CssClass .= ' '.$Rank['CssClass'];
         SetValue('_CssClass', $Args['User'], trim($CssClass));
         
         if (GetValueR('Abilities.Signatures', $Rank) == 'no') {
            SetValue('HideSignature', $Args['User'], TRUE);
         }
         if (GetValueR('Abilities.Titles', $Rank) == 'no') {
            SetValue('Title', $Args['User'], '');
         }
      }
   }
}

if (!function_exists('WriteUserRank')):
   
function RankTag($User, $CssClass, $Px = ' ') {
   $RankID = GetValue('RankID', $User);
   if (!$RankID)
      return;
   
   $Rank = RankModel::Ranks($RankID);
   if (!$Rank)
      return;
   
   $CssClass = ConcatSep(' ', 'Rank', $CssClass, GetValue('CssClass', $Rank));
   $Result = $Px.Wrap($Rank['Label'], 'span', array('class' => $CssClass, 'title' => $Rank['Name']));
   return $Result;
}
   
endif;