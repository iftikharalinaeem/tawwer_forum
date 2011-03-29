<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Infractions'] = array(
   'Description' => 'Infraction punishment system designed by/for Penny Arcade. Note: once this plugin is enabled, you must apply the Infractions permission to the appropriate role before users can begin assigning infractions.',
   'Version' => '1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'RegisterPermissions' => FALSE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class InfractionsPlugin extends Gdn_Plugin {

   /**
    * Create 'Infraction' link for comments in a discussion.
    */
   public function DiscussionController_CommentOptions_Handler($Sender) {
      // Only allow admins to assign infractions
      if (!Gdn::Session()->CheckPermission('Garden.Infractions.Manage')) return;
      
      $Context = strtolower($Sender->EventArguments['Type']);
      $Url = "profile/assigninfraction/";
      $Url .= (is_object($Sender->EventArguments['Author']) ? $Sender->EventArguments['Author']->UserID : 0).'/';
      $Url .= $Sender->EventArguments['Discussion']->DiscussionID.'/';
      if (GetValue('Type', $Sender->EventArguments) == 'Comment')
         $Url .= $Sender->EventArguments['Comment']->CommentID.'/';

      $Sender->Options .= '<span>'.Anchor(T('Infraction'), $Url, 'Infraction Popup') . '</span>';
   }
   
   /**
    * Allow profile infractions.
    */
   public function ProfileController_AfterAddSideMenu_Handler(&$Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      if ($ViewingUserID == $Sender->User->UserID) return;
      
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $SideMenu->AddLink('Options', 'Infraction!', "/profile/assigninfraction/".$Sender->User->UserID,  'Garden.Infractions.Manage', array('class' => 'Infraction Popup'));
   }
   

   /**
	 * Form to assign infractions.
	 */
   public function ProfileController_AssignInfraction_Create($Sender) {
      $SQL = Gdn::SQL();
      $UserID = GetValue(0, $Sender->RequestArgs, '');
      $DiscussionID = GetValue(1, $Sender->RequestArgs, '');
      $CommentID = GetValue(2, $Sender->RequestArgs, '');
      $ActivityID = GetValue(3, $Sender->RequestArgs, '');

      // Load infraction history data
      $this->_LoadInfractionHistory($Sender, $UserID);

      // Handle infraction form
      $Sender->SetData('Plugin.Infraction.Data', array(
         'UserID'       => $UserID,
         'DiscussionID' => $DiscussionID,
         'CommentID'    => $CommentID,
         'ActivityID'   => $ActivityID
      ));
      
      // Get the user's name for display purposes
      $Sender->Data['Username'] = $SQL
         ->Select('Name')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow()
         ->Name;
      
      if (!$Sender->Form->AuthenticatedPostBack()) {
         $Sender->Form->SetValue('Plugin.Infraction.Reason', 'Minor Offense');
      } else {
         $Warning = $Sender->Form->GetValue('Plugin.Infraction.Warning');
         $Note = $Sender->Form->GetValue('Plugin.Infraction.Note');
         $Message = $Sender->Form->GetValue('Plugin.Infraction.Message');
         $Reason = $Sender->Form->GetValue('Plugin.Infraction.Reason');
         $BanReason = $Sender->Form->GetValue('Plugin.Infraction.BanReason');
         $Points = 0;
         $Expires = 0;
         switch ($Reason) {
            case "Minor Offense":
               $Points = 2;
               $Expires = "30 days";
               break;
            case "Serious Offense":
               $Points = 3;
               $Expires = "60 days";
               break;
            case "Alternate Account":
               $Points = 8;
               $Expires = 0; // never
               break;
            case "Spamming":
               $Points = 8;
               $Expires = 0; // never
               break;
            default:
               $Reason = $Sender->Form->GetValue('Plugin.Infraction.CustomReason');
               $Points = $Sender->Form->GetValue('Plugin.Infraction.Points');
               $ExpiresRange = $Sender->Form->GetValue('Plugin.Infraction.ExpiresRange');
               $Expires = $ExpiresRange == 'Never' ? 0 : $Sender->Form->GetValue('Plugin.Infraction.Expires').' '.$ExpiresRange;
               break;
         }

         // Is the user going to be autobanned because of this?
         $CurrentPoints = 0;
         foreach ($Sender->Data['InfractionData']->Result() as $Infraction) {
            if ($Infraction->Reversed == '0' && Gdn_Format::ToTimestamp($Infraction->DateExpires) > time() && !$Infraction->Warning)
               $CurrentPoints += $Infraction->Points;
         }
         $BanType = false;
         if ($Points + $CurrentPoints >= 8 && !$Warning)
            $BanType = 'PermaBan';
         else if ($Points + $CurrentPoints >= 6 && !$Warning)
            $BanType = 'TempBan';
         
         // Error handling
         if ($Reason == '')
            $Sender->Form->AddError('You must specify a reason for the infraction.');
            
         if (!is_numeric($Points))
            $Sender->Form->AddError('You must specify a numeric point value.');
            
         if ($BanReason == '' && $BanType != FALSE)
            $Sender->Form->AddError('You must provide a reason for banishment.');

         if ($Note == '')
            $Sender->Form->AddError('You must provide an administrative note.');

         if ($Message == '')
            $Sender->Form->AddError('You must provide a message to the user.');
            
         if ($Sender->Form->ErrorCount() == 0) {
            try {
               // Insert the infraction
               $InfractionID = $SQL->Insert('Infraction', array(
                  'DiscussionID'    => $DiscussionID,
                  'CommentID'       => $CommentID,
                  'ActivityID'      => $ActivityID,
                  'UserID'          => $UserID,
                  'Points'          => $Points,
                  'Reason'          => $Reason,
                  'DateExpires'     => $Expires == 0 ? '' : Gdn_Format::ToDateTime(strtotime('+ '.$Expires)),
                  'Reversed'        => '0',
                  'Warning'         => $Warning,
                  'Note'            => $Note,
                  'InsertUserID'    => Gdn::Session()->UserID,
                  'DateInserted'    => date('Y-m-d H:i:s')
               ));
               
               // Mark the affected item in it's attributes column so it can be styled differently
               $Table = 'Discussion';
               $Column = 'DiscussionID';
               $UniqueID = $DiscussionID;
               if ($ActivityID > 0) {
                  $Table = 'Activity';
                  $Column = 'ActivityID';
                  $UniqueID = $ActivityID;
               } else if ($CommentID > 0) {
                  $Table = 'Comment';
                  $Column = 'CommentID';
                  $UniqueID = $CommentID;
               }
               $Data = $SQL->Select('Attributes')->From($Table)->Where($Column, $UniqueID)->Get()->FirstRow();
               if (is_object($Data)) {
                  $Attributes = Gdn_Format::Unserialize($Data->Attributes);
                  $Attributes['Infraction'] = TRUE;
                  $SQL->Update($Table)->Set('Attributes', Gdn_Format::Serialize($Attributes))->Where($Column, $UniqueID)->Put();
               }
               
               // Insert the conversation message
               $ConversationModel = new ConversationModel();
               $ConversationMessageModel = new ConversationMessageModel();
               $ConversationModel->Save(array(
                  'RecipientUserID' => array($UserID, Gdn::Session()->UserID),
                  'Body' => $Message,
                  'InfractionID' => $InfractionID
               ), $ConversationMessageModel);
               
               // Update the user's infraction cache
               InfractionsPlugin::SetInfractionCache($UserID);
            } catch(Exception $e) {
               $Sender->Form->AddError($e);
            }
            $Sender->InformMessage(
               '<span class="InformSprite Redflag"></span>'
               .T("The infraction has been created."),
               'Dismissable AutoDismiss HasSprite'
            );
         }
      }
      $Sender->Render($this->GetView('assigninfraction.php'));
   }
   
   /**
    * Add CSS class to Discussion comment items for styling.
    */
   public function DiscussionController_BeforeDiscussionName_Handler($Sender) {
      $Attributes = GetValue('Attributes', $Sender->EventArguments['Discussion']);
      if (is_array($Attributes) && GetValue('Infraction', $Attributes) == TRUE)
         $Sender->EventArguments['CssClass'] .= ' Infraction';
   }
   
   /**
    * Add CSS class to Activity items for styling.
    */
   public function ActivityController_BeforeActivity_Handler($Sender) {
      $Attributes = GetValue('Attributes', $Sender->EventArguments['Activity']);
      if (is_array($Attributes) && GetValue('Infraction', $Attributes) == TRUE)
         $Sender->EventArguments['CssClass'] .= ' Infraction';
   }
   
   /**
    * Adds 'Infractions' tab to profiles.
    */ 
   public function ProfileController_AddProfileTabs_Handler($Sender) {
      if (is_object($Sender->User) && $Sender->User->UserID > 0)
         $Sender->AddProfileTab(T('Infractions'), 'profile/infractions/'.$Sender->User->UserID.'/'.urlencode($Sender->User->Name));
   }
   
   /**
	 * Creates infractions tab on ProfileController.
	 */
   public function ProfileController_Infractions_Create($Sender) {
      $UserReference = GetValue(0, $Sender->RequestArgs, '');
		$Username = GetValue(1, $Sender->RequestArgs, '');

      // Tell the ProfileController what tab to load
		$Sender->GetUserInfo($UserReference, $Username);
      $Sender->SetTabView('Infractions', 'plugins/Infractions/views/summary.php');
      
      // Load infraction history
      $this->_LoadInfractionHistory($Sender, $Sender->User->UserID);
      
      // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
      $Sender->HandlerType = HANDLER_TYPE_NORMAL;
      
      // Render the ProfileController
      $Sender->Render();
   }
   
   /**
    * A simple "about infractions" FAQ page.
    */
   public function DiscussionController_Infractions_Create($Sender) {
      $Sender->Render('plugins/Infractions/views/aboutinfractions.php');
   }
   
   /**
    * Retrieve cached infractions from GDN_User.Attributes for the specified UserID.
    * If the cache is invalidated, it will be automatically reset here.
    */
   public static function GetInfractionCache($UserID) {
      $InfractionCache = Gdn::UserModel()->GetAttribute($UserID, 'InfractionCache');
      if (is_array($InfractionCache)) {
         $DateExpires = GetValue('DateExpires', $InfractionCache);
         // Should the cache be invalidated & reset?
         if ($DateExpires != '' && Gdn_Format::ToTimestamp($DateExpires) < time())
            $InfractionCache = InfractionsPlugin::SetInfractionCache($UserID);

      } else {
         $InfractionCache = InfractionsPlugin::SetInfractionCache($UserID);
      }
      
      return $InfractionCache;
   }
   
   /**
    * Load/save infraction cache data for the specified user id
    */
   public static function SetInfractionCache($UserID, $InfractionCache = FALSE) {
      if ($InfractionCache == FALSE) {
         // Load all active infractions.
         $Data = Gdn::SQL()
            ->Select('i.*, c.ConversationID')
            ->From('Infraction i')
            ->Join('Conversation c', 'i.InfractionID = c.InfractionID', 'left')
            ->Where('UserID', $UserID)
            ->Where('Reversed', '0')
            ->OrderBy('DateExpires', 'desc')
            ->Get();

         $InfractionCache = array();
         $InfractionCache['ConversationID'] = 0;
         $InfractionCache['Count'] = 0;
         $InfractionCache['Points'] = 0;
         $InfractionCache['DateExpires'] = '';
         $InfractionCache['Banned'] = FALSE;
         $InfractionCache['Jailed'] = FALSE;
         foreach ($Data->Result() as $Row) {
            $InfractionCache['Count']++;
            if (Gdn_Format::ToTimestamp($Row->DateExpires) > time() && $Row->Warning == '0') {
               $InfractionCache['Points'] += $Row->Points;
               $InfractionCache['DateExpires'] = $Row->DateExpires;
               $InfractionCache['ConversationID'] = $Row->ConversationID;
            }
         }
         
         // Is the account banned or jailed?
         if ($InfractionCache['Points'] >= 8) {
            $InfractionCache['Banned'] = TRUE;
         } else if ($InfractionCache['Points'] >= 4) {
            $InfractionCache['Jailed'] = TRUE;
         }
      }
      
      Gdn::UserModel()->SaveAttribute($UserID, 'InfractionCache', $InfractionCache);
      return $InfractionCache;
   }
   
   /**
    * Add a notice to the screen if the viewing user has infractions (so they know what is going on)
    */
   public function Base_Render_Before($Sender) {
      // Check / Redefine the infraction cache on each pageload
      $Session = Gdn::Session();
      if ($Session->IsValid()) {
         $InfractionCache = InfractionsPlugin::GetInfractionCache($Session->UserID);
         $Points = GetValue('Points', $InfractionCache, 0);
         if ($Points == 0)
            return '';
         
         // If the count is equal or less than the last time it was dismissed, don't show the message.
         $Count = GetValue('Count', $InfractionCache, 0);
         $LastDismissCount = $Session->GetAttribute('Infractions.LastDismissCount', -1);
         if ($LastDismissCount > 0 && $LastDismissCount >= $Count)
            return;
         
         $Jailed = GetValue('Jailed', $InfractionCache);
         $Banned = GetValue('Banned', $InfractionCache);
         $String = '<span class="InformSprite Redflag"></span> <strong>Infraction!</strong> ';
         if ($Banned)
            $String .= Wrap("Your account has been Banned.", 'strong');
         else if ($Jailed)
            $String .= Wrap("Your account has been Jailed.", 'strong');

         $String .= Wrap(Anchor(
            sprintf(
               'You have %1$s and %2$s',
               Plural($Count, '%d infraction', '%d infractions'),
               Plural($Points, '%d active infraction point', '%d active infraction points')
            ), 'profile/infractions/'.$Session->User->UserID.'/'.Gdn_Format::Url($Session->User->Name)), 'div');
         
         $String .= Wrap(Anchor('Find out how infractions work', 'discussion/infractions', array('class' => 'Popup')).'.', 'div');
         $Sender->InformMessage($String, array('CssClass' => 'Dismissable HasSprite', 'DismissCallbackUrl' => 'profile/dismissinfractionmessage'));
      }
   }
   
   /**
    * Allow infraction inform messages to be dismissed.
    */
   public function ProfileController_DismissInfractionMessage_Create($Sender) {
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Session = Gdn::Session();
      if ($Session->IsValid() && $Session->ValidateTransientKey(GetValue('TransientKey', $_POST, ''))) {
         $InfractionCache = InfractionsPlugin::GetInfractionCache($Session->UserID);
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'Infractions.LastDismissCount', GetValue('Count', $InfractionCache, 0));
      }
      
      if ($Sender->DeliveryType() === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', '/profile'));

      $Sender->Render();
   }

   /**
    * Retrieve the infraction history for the specified userid and place it in
    * the sender's data collection.
    */
   private function _LoadInfractionHistory($Sender, $UserID) {
      // Load infraction history data
		$InfractionData = Gdn::SQL()
         ->Select('i.*')
         ->Select('i.UserID', '', 'AuthUserID')
         ->Select('u.Name', '', 'AuthName')
         ->Select('iu.Name', '', 'InsertName')
         ->Select('a.Story', '', 'ActivityBody')
         ->Select('c.Body', '', 'CommentBody')
         ->Select('d.Name', '', 'DiscussionName')
         ->From('Infraction i')
         ->Join('User u', 'i.UserID = u.UserID')
         ->Join('User iu', 'i.InsertUserID = iu.UserID')
         ->Join('Activity a', 'i.ActivityID = a.ActivityID', 'left')
         ->Join('Discussion d', 'i.DiscussionID = d.DiscussionID', 'left')
         ->Join('Comment c', 'i.CommentID = c.CommentID', 'left')
         ->Where('i.UserID', $UserID)
         ->OrderBy('i.DateInserted', 'desc')
         ->Get();
		$Sender->SetData('InfractionData', $InfractionData);
   }
   
   /**
    * Include the infraction information when retrieving conversation messages.
    */
   public function ConversationMessageModel_BeforeGet_Handler($Sender) {
      $Sender->SQL->Select('c.InfractionID');
   }

   /**
    * Write out information about the infraction along with the first message.
    */
   public function MessagesController_BeforeConversationMessageBody_Handler($Sender) {
      $Message = $Sender->EventArguments['Message'];
      if ($Message->InfractionID > 0 && $Sender->ControllerName == 'messagescontroller' && $Sender->RequestMethod == 'Index') {
         $FirstMessageDone = GetValue('FirstMessageDone', $Sender->EventArguments);
         $Sender->EventArguments['FirstMessageDone'] = TRUE;
         if (!$FirstMessageDone) {
            echo Wrap('infraction DEETS', 'div');
         }
      }
   }

   /**
    * Create Infractions table & related columns.
    */
   public function Setup() {
      $Structure = Gdn::Structure();
      
      // Infraction table
      $Structure
         ->Table('Infraction')
         ->PrimaryKey('InfractionID')
         ->Column('DiscussionID', 'int(11)', FALSE, 'key')
         ->Column('CommentID', 'int(11)', FALSE)
         ->Column('ActivityID', 'int(11)', FALSE)
         ->Column('UserID', 'int(11)', FALSE, 'key')
         ->Column('Points', 'int(11)', FALSE)
         ->Column('Reason', 'varchar(255)', TRUE)
         ->Column('Note', 'text', TRUE)
         ->Column('DateExpires', 'datetime')
         ->Column('Reversed', 'int', FALSE, '0')
         ->Column('Warning', 'int', FALSE, '0')
         ->Column('InsertUserID', 'int', FALSE)
         ->Column('DateInserted', 'datetime')
         ->Set(FALSE, FALSE);

      // Relate an infraction to a private conversation that the admin & affected user take part in
      $Structure
         ->Table('Conversation')
         ->Column('InfractionID', 'int(11)', FALSE)
         ->Set(FALSE, FALSE);

      // Allow a user to be "Jailed" when they reach a certain number of infractions.
      $Structure
         ->Table('User')
         ->Column('Jailed', 'int', FALSE, '0')
         ->Set(FALSE, FALSE);
         
// BUG: "Jailed is required" on user forms.
         
      // Add an attributes column to the Activity table
      $Structure
         ->Table('Activity')
         ->Column('Attributes', 'text', TRUE)
         ->Set(FALSE, FALSE);

      // Create a new permission for infractions
      $PermissionModel = Gdn::PermissionModel();
      $PermissionModel->Database = Gdn::Database();
      $PermissionModel->SQL = Gdn::SQL();
      $PermissionModel->Define(array('Garden.Infractions.Manage'));
      // NOTE: WILL NEED TO MANUALLY ENABLE THIS PERMISSION ON APPLICABLE ROLES
   }
}