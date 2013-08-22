<?php if (!defined('APPLICATION')) exit();

/**
 * User following
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package Misc
 */

// Define the plugin:
$PluginInfo['Following'] = array(
   'Name' => 'Following',
   'Description' => 'This plugin allows users to follow others.',
   'Version' => '1.1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FollowingPlugin extends Gdn_Plugin {
   
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      $ViewingUserID = Gdn::Session()->UserID;
      if ($ViewingUserID == $Sender->User->UserID) return;
      
      $IsFollowing = $this->CheckIfFollowing($ViewingUserID, $Sender->User->UserID);
      $FollowText = ($IsFollowing) ? "Unfollow" : "Follow";
      $Sender->EventArguments['ProfileOptions'][] = array(
         'Text' => sprintf(T($FollowText),$Sender->User->Name),
         'Url' => UserUrl($Sender->User, '', 'following'),
         'CssClass' => 'Hijack UserFollowButton'
      );
   }
   
   /**
    * 
    * 
    * @param ProfileController $Sender
    * @return type
    */
   public function ProfileController_Following_Create($Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      $Args = $Sender->RequestArgs;
      
      $IsValidUser = FALSE;
      if (sizeof($Args)) {
         $FollowedUserID = $Sender->RequestArgs[0];
         try {
            $ValidUser = Gdn::UserModel()->GetID($FollowedUserID, DATASET_TYPE_ARRAY);
            $IsValidUser = (bool)$ValidUser;
            if ($ValidUser) {
               
               $IsFollowing = $this->CheckIfFollowing($ViewingUserID, $Sender->User->UserID);
               if ($IsFollowing) {
                  // Unfollow
                  Gdn::SQL()->Delete('Following',array(
                     'UserID' => $ViewingUserID,
                     'FollowedUserID' => $FollowedUserID
                  ));

                  $Sender->InformMessage(sprintf(T("No longer following %s"), $ValidUser['Name']));
                  $Sender->JsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(T('Follow'), $ValidUser['Name']), 'Text');
               } else {
                  // Follow
                  Gdn::SQL() ->Insert('Following',array(
                     'UserID' => $ViewingUserID,
                     'FollowedUserID' => $FollowedUserID
                  ));
                  $Sender->InformMessage(sprintf(T("Following %s"), $ValidUser['Name']));
                  $Sender->JsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(T('Unfollow'), $ValidUser['Name']), 'Text');
               }
            }
         } catch(Exception $e) {}
      }
      
      $Sender->Render('blank', 'utility', 'dashboard');
   }
   
   public function Base_Render_Before($Sender) {
      if ($Sender->ControllerName != 'profilecontroller') return; 
      
      $Sender->AddCssFile('following.css', 'plugins/Following');
      $UserID = $Sender->User->UserID;
      $Module = new FollowingModule($Sender);
      $Module->SetUser($UserID);
      $Sender->AddModule($Module);
   }
   
   protected function GetFollowersForUser($UserID) {
      return Gdn::SQL()
         ->Select('f.UserID')
         ->From('Following f')
         ->Where('f.FollowedUserID', $UserID)
         ->Get();
   }
   
   protected function GetFollowsForUser($UserID) {
      return Gdn::SQL()
         ->Select('f.FollowedUserID')
         ->From('Following f')
         ->Where('f.UserID', $UserID)
         ->Get();
   }
   
   protected function CheckIfFollowing($UserID, $FollowedUserID) {
      $IsFollowing = Gdn::SQL()
         ->Select('*')
         ->From('Following f')
         ->Where('f.UserID', $UserID)
         ->Where('f.FollowedUserID', $FollowedUserID)
         ->Get();
         
      return ($IsFollowing->NumRows());
   }

   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('Following')
         ->Column('UserID', 'int(11)', FALSE, 'primary')
         ->Column('FollowedUserID', 'int(11)', FALSE, 'primary')
         ->Set(FALSE, FALSE);
   }
         
}