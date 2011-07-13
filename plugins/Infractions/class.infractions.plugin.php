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
   'Version' => '1.0.1b',
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
      
      $Context = $Sender->EventArguments['Type'];
      $Url = "profile/assigninfraction/";
      $Url .= (is_object($Sender->EventArguments['Author']) ? $Sender->EventArguments['Author']->UserID : 0).'/';
      $Url .= $Sender->DiscussionID.'/';
      if ($Context == 'Comment')
         $Url .= $Sender->EventArguments['Comment']->CommentID.'/';
			
		$Text = T('Infraction');
		$Style = '';
		// If an infraction has been assigned, highlight it in the infraction anchor
      $Object = GetValue($Context, $Sender->EventArguments);
		$Attributes = unserialize(GetValue('Attributes', $Object));
		$Infracted = GetValue('Infraction', $Attributes);
		if ($Infracted) {
			$Text = T('INFRACTED');
			$Style = array('style' => 'background: #f44; color: #fff; padding: 0 4px;');
		}

      $Sender->Options .= '<span>'.Anchor($Text, $Url, 'Infraction Popup', $Style) . '</span>';
   }
	
	/**
	 * Create 'Infraction' link for activities.
	 */
	public function ActivityController_AfterMeta_Handler($Sender) {
      // Only allow admins to assign infractions
      if (!Gdn::Session()->CheckPermission('Garden.Infractions.Manage')) return;
      
		$Activity = GetValue('Activity', $Sender->EventArguments);
      $Url = "profile/assigninfraction/";
      $Url .= GetValue('InsertUserID', $Activity, '0').'/0/0/';
      $Url .= GetValue('ActivityID', $Activity, '0').'/';
			
		$Text = T('Infraction');
		$Style = '';
		// If an infraction has been assigned, highlight it in the infraction anchor
		$Attributes = unserialize(GetValue('Attributes', $Activity));
		$Infracted = GetValue('Infraction', $Attributes);
		if ($Infracted) {
			$Text = T('INFRACTED');
			$Style = array('style' => 'background: #f44; color: #fff; padding: 0 4px;');
		}

      echo Wrap(Anchor($Text, $Url, 'Infraction Popup', $Style));
	}
   
   /**
    * Allow profile infractions.
    */
   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      if ($ViewingUserID == $Sender->User->UserID || !Gdn::Session()->CheckPermission('Garden.Infractions.Manage')) return;
      
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $SideMenu->AddLink('Options', 'Infraction!', "/profile/assigninfraction/".$Sender->User->UserID,  'Garden.Infractions.Manage', array('class' => 'Infraction Popup'));
   }
   
	/**
	 * Allow Infractions to be Reversed.
	 */
   public function ProfileController_ReverseInfraction_Create($Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.Infractions.Manage')) return;
		
		$InfractionID = GetValue('0', $Sender->RequestArgs);
		$Infraction = Gdn::SQL()->Select()->From('Infraction')->Where('InfractionID', $InfractionID)->Get()->FirstRow();
		$TransientKey = GetValue('1', $Sender->RequestArgs);
		if (Gdn::Session()->ValidateTransientKey($TransientKey) && $Infraction) {
			Gdn::SQL()->Update('Infraction', array('Reversed' => '1'), array('InfractionID' => $InfractionID))->Put();
			// Update the user's infraction cache
			InfractionsPlugin::SetInfractionCache($Infraction->UserID);
			
			// Remove any denotation of the infraction on the affected item
			$Table = 'Discussion';
			$Column = 'DiscussionID';
			$UniqueID = $Infraction->DiscussionID;
			if ($Infraction->ActivityID > 0) {
				$Table = 'Activity';
				$Column = 'ActivityID';
				$UniqueID = $Infraction->ActivityID;
			} else if ($Infraction->CommentID > 0) {
				$Table = 'Comment';
				$Column = 'CommentID';
				$UniqueID = $Infraction->CommentID;
			}
			if (is_numeric($UniqueID) && $UniqueID > 0) {
				$Data = Gdn::SQL()->Select('Attributes')->From($Table)->Where($Column, $UniqueID)->Get()->FirstRow();
				if (is_object($Data)) {
					$Attributes = Gdn_Format::Unserialize($Data->Attributes);
					unset($Attributes['Infraction']);
					Gdn::SQL()->Update($Table)->Set('Attributes', Gdn_Format::Serialize($Attributes))->Where($Column, $UniqueID)->Put();
				}
			}
		}
		Redirect('/profile/infractions/'.$Infraction->UserID.'/unfracted');
	}

   /**
	 * Form to assign infractions.
	 */
   public function ProfileController_AssignInfraction_Create($Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.Infractions.Manage')) return;
		
      $SQL = Gdn::SQL();
      $UserID = GetValue(0, $Sender->RequestArgs, '');
      $DiscussionID = GetValue(1, $Sender->RequestArgs, '');
      $CommentID = GetValue(2, $Sender->RequestArgs, '');
      $ActivityID = GetValue(3, $Sender->RequestArgs, '');

      // Load infraction history datas
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
               $Expires = $ExpiresRange == 'Never' ? NULL : $Sender->Form->GetValue('Plugin.Infraction.Expires').' '.$ExpiresRange;
               break;
         }

         // Is the user going to be autobanned because of this?
         $CurrentPoints = 0;
         foreach ($Sender->Data['InfractionData']->Result() as $Infraction) {
            if ($Infraction->Reversed == '0' && ($Infraction->DateExpires == NULL || Gdn_Format::ToTimestamp($Infraction->DateExpires) > time()) && !$Infraction->Warning)
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

         if (isset($ExpiresRange)) {
            if ($ExpiresRange != 'Never' && !is_numeric($Sender->Form->GetValue('Plugin.Infraction.Expires')))
                  $Sender->Form->AddError('You must specify an expiry.');
         }
            
         if (!is_numeric($Points))
            $Sender->Form->AddError('You must specify a numeric point value.');
            
         if ($BanReason == '' && $BanType != FALSE)
            $Sender->Form->AddError('You must provide a reason for banishment.');

         if ($Note == '')
            $Sender->Form->AddError('You must provide an administrative note.');

         if ($Message == '')
            $Sender->Form->AddError('You must provide a message to the user.');
            
			$InfractionDiscussionID = 0;
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
						'BanReason'			=> $BanReason,
                  'DateExpires'     => $Expires == NULL ? NULL : Gdn_Format::ToDateTime(strtotime('+ '.$Expires)),
                  'Reversed'        => '0',
                  'Warning'         => $Warning,
                  'Note'            => $Note,
                  'InsertUserID'    => Gdn::Session()->UserID,
                  'DateInserted'    => date('Y-m-d H:i:s')
               ));
					
					// Define the infraction discussion name/title.
					$InfractionDiscussionName = 'INFRACTION -- '.$Reason.' -- ' . $Sender->Data['Username'];
               
               // Mark the affected item in it's attributes column so it can be styled differently
               $Table = 'Discussion';
               $Column = 'DiscussionID';
               $UniqueID = $DiscussionID;
               if ($ActivityID > 0) {
                  $Table = 'Activity';
                  $Column = 'ActivityID';
                  $UniqueID = $ActivityID;
						$InfractionDiscussionName = 'Activity: '.$InfractionDiscussionName;
               } else if ($CommentID > 0) {
                  $Table = 'Comment';
                  $Column = 'CommentID';
                  $UniqueID = $CommentID;
						$CategoryData = $SQL
							->Select('ca.Name')
							->From('Category ca')
							->Join('Discussion d', 'ca.CategoryID = d.CategoryID')
							->Join('Comment co', 'd.DiscussionID = co.DiscussionID')
							->Where('co.CommentID', $CommentID)
							->Get()
							->FirstRow();
						if ($CategoryData)
							$InfractionDiscussionName = $CategoryData->Name.': '.$InfractionDiscussionName;
					} else if ($DiscussionID > 0) {
						$CategoryData = $SQL
							->Select('ca.Name')
							->From('Category ca')
							->Join('Discussion d', 'ca.CategoryID = d.CategoryID')
							->Where('d.DiscussionID', $DiscussionID)
							->Get()
							->FirstRow();
						if ($CategoryData)
							$InfractionDiscussionName = $CategoryData->Name.': '.$InfractionDiscussionName;
					} else {
						$InfractionDiscussionName = 'Profile: '.$InfractionDiscussionName;
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
					
					// Insert the infraction discussion into the infractions category
					$InfractionInfo = $this->_InfractionInfo($InfractionID, $Message);
					$DiscussionModel = new DiscussionModel();
					$InfractionDiscussionID = $DiscussionModel->Save(array(
						'Name' => $InfractionDiscussionName,
						'InsertUserID'    => Gdn::Session()->UserID,
                  'DateInserted'    => date('Y-m-d H:i:s'),
						'DateLastComment' => date('Y-m-d H:i:s'),
						'Body' => $InfractionInfo,
						'Format' => 'Html',
						'InfractionID' => $InfractionID,
						'CategoryID' => C('Plugins.Infractions.InfractionCategoryID', 39)
					));
					
               
               // Update the user's infraction cache
               InfractionsPlugin::SetInfractionCache($UserID);
            } catch(Exception $e) {
               $Sender->Form->AddError($e);
            }
				$InformMessage = T("The infraction has been created.");
				if ($InfractionDiscussionID > 0) {
					$InformMessage = Anchor($InformMessage, 'discussion/'.$InfractionDiscussionID.'/'.Gdn_Format::Url($InfractionDiscussionName));
				}
            $Sender->InformMessage(
               '<span class="InformSprite Redflag"></span>'
               .$InformMessage,
               'Dismissable HasSprite'
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
            if (($Row->DateExpires == NULL || Gdn_Format::ToTimestamp($Row->DateExpires) > time()) && $Row->Warning == '0') {
               $InfractionCache['Points'] += $Row->Points;
               $InfractionCache['DateExpires'] = $Row->DateExpires;
               $InfractionCache['ConversationID'] = $Row->ConversationID;
            }
         }

         // Is the account banned or jailed?
         if ($InfractionCache['Points'] >= 8) {
            $InfractionCache['Banned'] = TRUE;
				Gdn::SQL()->Update('User', array('Jailed' => '1', 'TempBanned' => '1', 'Banned' => '1'), array('UserID' => $UserID))->Put();
			} else if ($InfractionCache['Points'] >= 6) {
            $InfractionCache['TempBanned'] = TRUE;
				Gdn::SQL()->Update('User', array('Jailed' => '1', 'TempBanned' => '1', 'Banned' => '0'), array('UserID' => $UserID))->Put();
         } else if ($InfractionCache['Points'] >= 4) {
            $InfractionCache['Jailed'] = TRUE;
				Gdn::SQL()->Update('User', array('Jailed' => '1', 'TempBanned' => '0', 'Banned' => '0'), array('UserID' => $UserID))->Put();
         } else {
				Gdn::SQL()->Update('User', array('Jailed' => '0', 'TempBanned' => '0', 'Banned' => '0'), array('UserID' => $UserID))->Put();
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
	 * Remove comment formatting from banned & jailed users.
	 */
	public function Base_BeforeCommentBody_Handler($Sender) {
		$Object = $Sender->EventArguments['Object'];
		if (
			GetValue('InsertJailed', $Object) == '1'
			|| GetValue('InsertBanned', $Object) == '1'
			|| GetValue('InsertTempBanned', $Object) == '1'
		) {
			$Object->Format = 'Text';
			$Sender->EventArguments['Object'] = $Object;
		}
	}
	
	/**
	 * Switch the user's profile picture out if they are banned.
	 */
	public function ProfileController_Render_Before($Sender) {
		if (is_object($Sender->User)) {
			$Jailed = GetValue('Jailed', $Sender->User) == '1';
			$TempBanned = GetValue('TempBanned', $Sender->User) == '1';
			$Banned = GetValue('Banned', $Sender->User) == '1';
			if ($Banned || $TempBanned)
				$Sender->User->Photo = Asset('themes/pennyarcade/design/images/banned-180.png', TRUE);
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
         ->Select('c.Format', '', 'CommentFormat')
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
    * Write out information about the infraction along with the first message in an infraction conversation.
    */
   public function MessagesController_BeforeConversationMessageBody_Handler($Sender) {
      $Message = $Sender->EventArguments['Message'];
      if ($Message->InfractionID > 0 && $Sender->ControllerName == 'messagescontroller' && $Sender->RequestMethod == 'Index') {
         $FirstMessageDone = GetValue('FirstMessageDone', $Sender->EventArguments);
         $Sender->EventArguments['FirstMessageDone'] = TRUE;
         if (!$FirstMessageDone) {
				$Infraction = Gdn::SQL()
					->Select('i.*')
					->Select('d.Body', '', 'DiscussionBody')
					->Select('d.Name', '', 'DiscussionName')
					->Select('c.Body', '', 'CommentBody')
					->Select('a.Story', '', 'ActivityBody')
					->From('Infraction i')
					->Join('Comment c', 'i.CommentID = c.CommentID', 'left')
					->Join('Activity a', 'i.ActivityID = a.ActivityID', 'left')
					->Join('Discussion d', 'i.DiscussionID = d.DiscussionID', 'left')
					->Where('i.InfractionID', $Message->InfractionID)
					->Get()
					->FirstRow();
				if ($Infraction) {
					echo '<div style="border: 1px solid #f00; background: #fdd; padding: 8px; margin: 0 0 10px;">
						<h4>Infraction</h4>
						<div><strong>';
						$ProfileInfraction = FALSE;
						if ($Infraction->CommentID > 0)
							echo 'Comment Infraction:';
						else if ($Infraction->DiscussionID > 0)
							echo 'Discussion Infraction:';
						else if ($Infraction->ActivityID > 0)
							echo 'Activity Infraction:';
						else {
							$ProfileInfraction = TRUE;
							echo 'Profile Infraction:';
						}
						echo '</strong> '.$Infraction->Note.'</div>';
						if (!$ProfileInfraction) {
							echo '<div><strong>Offending Content:</strong> ';
							echo htmlentities($Infraction->DiscussionBody);
							echo htmlentities($Infraction->CommentBody);
							echo htmlentities($Infraction->ActivityBody);
							echo '</div>';
							$Anchor = '';
							if ($Infraction->CommentID > 0)
								$Anchor = '/discussion/comment/'.$Infraction->CommentID.'/#Comment_'.$Infraction->CommentID;
							else if ($Infraction->DiscussionID > 0)
								$Anchor = '/discussion/'.$Infraction->DiscussionID.'/'.Gdn_Format::Url($Infraction->DiscussionName);
							else if ($Infraction->ActivityID > 0)
								$Anchor = '/activity/item/'.$Infraction->ActivityID;
							
							if ($Anchor != '') {
								$Anchor = Url($Anchor, TRUE);
								echo Wrap(T('Source:'), 'strong').' '.Anchor($Anchor, $Anchor);
							}
						}
					echo '</div>';
						
				}
         }
      }
   }
	
	private function _InfractionInfo($InfractionID, $UserMessage = '') {
		$Infraction = Gdn::SQL()
			->Select('i.*')
			->Select('iu.Name', '', 'InfractionUsername')
			->Select('d.Body', '', 'DiscussionBody')
			->Select('d.Name', '', 'DiscussionName')
			->Select('c.Body', '', 'CommentBody')
			->Select('a.Story', '', 'ActivityBody')
			->From('Infraction i')
			->Join('Comment c', 'i.CommentID = c.CommentID', 'left')
			->Join('Activity a', 'i.ActivityID = a.ActivityID', 'left')
			->Join('Discussion d', 'i.DiscussionID = d.DiscussionID', 'left')
			->Join('User iu', 'i.UserID = iu.UserID', 'left')
			->Where('i.InfractionID', $InfractionID)
			->Get()
			->FirstRow();
		
		$Return = 'Infraction details unavailable.';
		if ($Infraction) {
			$Return = '';
			$Return = '<strong>Source:</strong> ';
			$Anchor = '';
			$Content = '';
			if ($Infraction->CommentID > 0) {
				$Anchor = '/discussion/comment/'.$Infraction->CommentID.'/#Comment_'.$Infraction->CommentID;
				$Text = $Infraction->DiscussionName;
				$Content = $Infraction->CommentBody;
			} else if ($Infraction->DiscussionID > 0) {
				$Anchor = '/discussion/'.$Infraction->DiscussionID.'/'.Gdn_Format::Url($Infraction->DiscussionName);
				$Text = $Infraction->DiscussionName;
				$Content = $Infraction->DiscussionBody;
			} else if ($Infraction->ActivityID > 0) {
				$Anchor = '/activity/item/'.$Infraction->ActivityID;
				$Text = 'Activity';
				$Content = $Infraction->ActivityBody;
			} else if ($Infraction->InfractionUsername) {
				$Anchor = '/profile/'.$Infraction->UserID.'/'.Gdn_Format::Url($Infraction->InfractionUsername);
				$Text = 'Profile Infraction';
				$Content = '';
			}
			
			if ($Anchor != '') {
				$Return .= Anchor($Text, Url($Anchor, TRUE));
			} else {
				$Return .= 'Unknown';
			}
			
			$Return .= "\n";
			$Return .= '<strong>User:</strong> '.Anchor(Gdn_Format::Text($Infraction->InfractionUsername), '/profile/'.$Infraction->UserID.'/'.Gdn_Format::Url($Infraction->InfractionUsername));
			$Return .= "\n";
			$Return .= '<strong>Infraction:</strong> '.$Infraction->Reason;
			$Return .= "\n";
			$Return .= '<strong>Points:</strong> '.$Infraction->Points;
			$Return .= "\n";
			$Return .= '<strong>Administrative Note:</strong>';
			$Return .= Wrap(Gdn_Format::Text($Infraction->Note), 'blockquote');
			$Return .= '<strong>Message to User:</strong>';
			$Return .= Wrap(Gdn_Format::Text($UserMessage), 'blockquote');
			if ($Content != '') {
				$Return .= '<strong>Original Post:</strong>';
				$Return .= Wrap(htmlentities($Content), 'blockquote');
			}
		}
		return $Return;
	}
	
   /**
    * Identify Discussions that have resulted in an infraction.
    */
   public function DiscussionsController_DiscussionMeta_Handler($Sender) {
      $Discussion = GetValue('Discussion', $Sender->EventArguments);
		$Attributes = unserialize(GetValue('Attributes', $Discussion));
		$Infraction = GetValue('Infraction', $Attributes);
		if ($Infraction)
			echo Wrap(Anchor('Infraction!', '/profile/infractions/'.$Discussion->FirstUserID.'/'.Gdn_Format::Url($Discussion->FirstName), array('style' => 'background: #f44; color: #fff; padding: 0 4px;')));
   }
	
	/**
	 * If a user has been banned or tempbanned, sign them out.
	 */
	public function UserModel_AfterGetSession_Handler($Sender) {
		$User = GetValue('User', $Sender->EventArguments);
		if (is_object($User) && GetValue('TempBanned', $User) == '1') {
			$User->Banned = '1';
			$UserModel->EventArguments['User'] = $User;
		}
	}

   public function Setup() {
      $this->Structure();
   }

   /**
    * Create Infractions table & related columns.
    */
   public function Structure() {
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
         ->Column('BanReason', 'text', TRUE)
         ->Column('Note', 'text', TRUE)
         ->Column('DateExpires', 'datetime', NULL)
         ->Column('Reversed', 'tinyint', '0')
         ->Column('Warning', 'tinyint', '0')
         ->Column('InsertUserID', 'int', FALSE)
         ->Column('DateInserted', 'datetime')
         ->Set(FALSE, FALSE);

      // Relate an infraction to a private conversation that the admin & affected user take part in
      $Structure
         ->Table('Conversation')
         ->Column('InfractionID', 'int(11)', NULL)
         ->Set(FALSE, FALSE);

      // Relate a discussion to an infraction for admins to discuss the infraction
      $Structure
         ->Table('Discussion')
         ->Column('InfractionID', 'int(11)', NULL)
         ->Set(FALSE, FALSE);

      // Allow a user to be "Jailed" when they reach a certain number of infractions.
      $Structure
         ->Table('User')
         ->Column('Jailed', 'tinyint', '0')
         ->Column('TempBanned', 'tinyint', '0')
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
	

/* DOES NOT WORK WITH THE GRAVATAR PLUGIN ENABLED!
Add the & banned flags - so that we can change the user icons appropriately. */
	
   // Find all the places where UserBuilder is called, and make sure that there
   // is a related $UserPrefix.'Email' field pulled from the database.
   public function ConversationModel_BeforeGet_Handler($Sender) {
      $Sender->SQL->Select('lmu.Email', '', 'LastMessageEmail')
			->Select('lmu.Jailed', '', 'LastMessageJailed')
			->Select('lmu.TempBanned', '', 'LastMessageTempBanned')
			->Select('lmu.TempBanned', '', 'LastMessageTempBanned');
   }
	public function DiscussionModel_AfterDiscussionSummaryQuery_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
		$Sender->SQL->Select('lcu.Email', '', 'LastEmail')
			->Select('lcu.Jailed', '', 'LastJailed')
			->Select('lcu.Banned', '', 'LastBanned')
			->Select('lcu.TempBanned', '', 'LastTempBanned');
	}
   public function UserModel_BeforeGetActiveUsers_Handler($Sender) {
      $Sender->SQL->Select('u.Email, u.Jailed, u.Banned, u.TempBanned');
   }
   public function AddonCommentModel_BeforeGet_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
   }
   public function ConversationMessageModel_BeforeGet_Handler($Sender) {
      $Sender->SQL->Select('c.InfractionID');
		$this->_JoinInsertUser($Sender);
   }
   public function ActivityModel_BeforeGet_Handler($Sender) {
		$this->_JoinActivityUser($Sender);
		$this->_JoinRegardingUser($Sender);
   }
	public function ActivityModel_BeforeGetNotifications_Handler($Sender) {
		$this->_JoinActivityUser($Sender);
		$this->_JoinRegardingUser($Sender);
	}
   public function ActivityModel_BeforeGetComments_Handler($Sender) {
		$this->_JoinActivityUser($Sender);
   }
	public function DiscussionModel_BeforeGetID_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
	}
	
   public function CommentModel_BeforeGet_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
   }

   public function CommentModel_BeforeGetNew_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
   }
	
	public function CommentModel_BeforeGetIDData_Handler($Sender) {
		$this->_JoinInsertUser($Sender);
	}
	
	private function _JoinInsertUser($Sender) {
      $Sender->SQL->Select('iu.Email', '', 'InsertEmail')
			->Select('iu.Jailed', '', 'InsertJailed')
			->Select('iu.Banned', '', 'InsertBanned')
			->Select('iu.TempBanned', '', 'InsertTempBanned');
		
	}
	private function _JoinActivityUser($Sender) {
      $Sender->SQL
         ->Select('au.Email', '', 'ActivityEmail')
			->Select('au.Jailed', '', 'ActivityJailed')
			->Select('au.Banned', '', 'ActivityBanned')
			->Select('au.TempBanned', '', 'ActivityTempBanned');
	}
	private function _JoinRegardingUser($Sender) {
      $Sender->SQL
         ->Select('ru.Email', '', 'RegardingEmail')
			->Select('ru.Jailed', '', 'RegardingJailed')
			->Select('ru.Banned', '', 'RegardingBanned')
			->Select('ru.TempBanned', '', 'RegardingTempBanned');
	}

}

if (!function_exists('UserBuilder')) {
   /**
    * Override the default UserBuilder function with one that switches the photo
    * out with a gravatar url if the photo is empty.
    */
   function UserBuilder($Object, $UserPrefix = '') {
		$Object = (object)$Object;
      $User = new stdClass();
      $UserID = $UserPrefix.'UserID';
      $Name = $UserPrefix.'Name';
      $Photo = $UserPrefix.'Photo';
      $Email = $UserPrefix.'Email';
		$Jailed = $UserPrefix.'Jailed';
		$Banned = $UserPrefix.'Banned';
		$TempBanned = $UserPrefix.'TempBanned';
      $User->UserID = $Object->$UserID;
      $User->Name = $Object->$Name;
      $User->Photo = property_exists($Object, $Photo) ? $Object->$Photo : '';
      $Protocol =  (strlen(GetValue('HTTPS', $_SERVER, 'No')) != 'No' || GetValue('SERVER_PORT', $_SERVER) == 443) ? 'https://secure.' : 'http://www.';
      $User->Email = GetValue($Email, $Object);
      /*      if ($User->Photo == '' && property_exists($Object, $Email)) {
         $User->Photo = $Protocol.'gravatar.com/avatar.php?'
            .'gravatar_id='.md5(strtolower($Object->$Email))
            .'&amp;default='.urlencode(Asset(Gdn::Config('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.gif'), TRUE))
            .'&amp;size='.Gdn::Config('Garden.Thumbnail.Width', 40);
      }
*/
		$User->Jailed = GetValue($Jailed, $Object);
		$User->Banned = GetValue($Banned, $Object);
		$User->TempBanned = GetValue($TempBanned, $Object);
		return $User;
   }
}
if (!function_exists('UserPhoto')) {
   function UserPhoto($User, $Options = array()) {
		$User = (object)$User;
      if (is_string($Options))
         $Options = array('LinkClass' => $Options);
      
      $LinkClass = GetValue('LinkClass', $Options, 'ProfileLink');
      $ImgClass = GetValue('ImageClass', $Options, 'ProfilePhotoBig');
      
      $LinkClass = $LinkClass == '' ? '' : ' class="'.$LinkClass.'"';

      $Photo = $User->Photo;
      if (!$Photo && function_exists('UserPhotoDefaultUrl'))
         $Photo = UserPhotoDefaultUrl($User);

      if ($Photo) {
         if (!preg_match('`^https?://`i', $Photo)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
         } else {
            $PhotoUrl = $Photo;
         }
			
			$Jailed = GetValue('Jailed', $User) == '1';
			$TempBanned = GetValue('TempBanned', $User) == '1';
			$Banned = GetValue('Banned', $User) == '1';
			if ($Banned || $TempBanned) {
				$PhotoUrl = 'themes/pennyarcade/design/images/banned-80.png';
				$Jailed = '';
			} else if ($Jailed)
				$Jailed = Img('themes/pennyarcade/design/images/jailed-80.png', array('alt' => 'Jailed', 'class' => 'JailedIcon'));
			else
				$Jailed = '';
         
         return '<a title="'.htmlspecialchars($User->Name).'" href="'.Url('/profile/'.$User->UserID.'/'.rawurlencode($User->Name)).'"'.$LinkClass.'>'
            .$Jailed
				.Img($PhotoUrl, array('alt' => urlencode($User->Name), 'class' => $ImgClass))
            .'</a>';
      } else {
         return '';
      }
   }
}
