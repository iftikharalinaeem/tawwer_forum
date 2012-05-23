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
$PluginInfo['Following'] = array(
   'Name' => 'Following',
   'Description' => 'This plugin allows users to follow others.',
   'Version' => '1.0b',
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

   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      if ($ViewingUserID == $Sender->User->UserID) return;
      
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $IsFollowing = $this->_CheckIfFollowing($ViewingUserID, $Sender->User->UserID);
      $FollowText = ($IsFollowing) ? "Stop following" : "Follow %s";
      $FollowAction = ($IsFollowing) ? 'unfollow' : 'follow';
      $SideMenu->AddLink('Options', sprintf(T($FollowText),$Sender->User->Name), UserUrl($Sender->User, '', $FollowAction), FALSE);
   }
   
   public function ProfileController_Follow_Create($Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      $Args = $Sender->RequestArgs;
      
      $IsValidUser = FALSE;
      if (sizeof($Args)) {
         $FollowedUserID = $Sender->RequestArgs[0];
         try {
            $ValidUser = Gdn::SQL()->Select('u.Name')->From('User u')->Where('u.UserID',$FollowedUserID)->Get();
            $IsValidUser = $ValidUser->NumRows();
            if ($IsValidUser)
               Gdn::SQL()
               ->Insert('Following',array(
                  'UserID' => $ViewingUserID,
                  'FollowedUserID' => $FollowedUserID
               ));
         } catch(Exception $e) {}
      }
      
      if ($IsValidUser) $ValidUserName = $ValidUser->Value('Name');
      return ($IsValidUser) ? $Sender->Activity($FollowedUserID, $ValidUserName) : $Sender->Index();
   }
   
   public function ProfileController_Unfollow_Create($Sender) {
      $ViewingUserID = Gdn::Session()->UserID;
      $Args = $Sender->RequestArgs;
      
      $IsValidUser = FALSE;
      if (sizeof($Args)) {
         $FollowedUserID = $Sender->RequestArgs[0];
         try {
            $ValidUser = Gdn::SQL()->Select('u.Name')->From('User u')->Where('u.UserID',$FollowedUserID)->Get();
            $IsValidUser = $ValidUser->NumRows();
            Gdn::SQL()
               ->Delete('Following',array(
                  'UserID' => $ViewingUserID,
                  'FollowedUserID' => $FollowedUserID
               ));
         } catch(Exception $e) {}
      }
      
      if ($IsValidUser) $ValidUserName = $ValidUser->Value('Name');
      return ($IsValidUser) ? $Sender->Index($FollowedUserID, $ValidUserName) : $Sender->Index();
   }
   
   public function Base_Render_Before($Sender) {
      if ($Sender->ControllerName != 'profilecontroller') return; 
      
      $Sender->AddCssFile($this->GetResource('css/following.css', FALSE, FALSE));
      $UserID = $Sender->User->UserID;
      include_once(PATH_PLUGINS.DS.'Following'.DS.'class.followingmodule.php');
      $Module = new FollowingModule($Sender);
      $Module->SetUser($UserID);
      $Sender->AddModule($Module);
   }
   
   protected function _GetFollowersForUser($UserID) {
      return Gdn::SQL()
         ->Select('f.UserID')
         ->From('Following f')
         ->Where('f.FollowedUserID', $UserID)
         ->Get();
   }
   
   protected function _GetFollowsForUser($UserID) {
      return Gdn::SQL()
         ->Select('f.FollowedUserID')
         ->From('Following f')
         ->Where('f.UserID', $UserID)
         ->Get();
   }
   
   protected function _CheckIfFollowing($UserID, $FollowedUserID) {
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