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
$PluginInfo['IPTracking'] = array(
   'Name' => 'IPTracking',
   'Description' => "This plugin adds fields to the User, Comment and Discussion tables that track the IPs of users as they log-in and post.",
   'Version' => '0.9',
   'RequiredApplications' => array('Vanilla' => '2.0.4a'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class IPTrackingPlugin extends Gdn_Plugin {

   public function UserInfoModule_OnBasicInfo_Handler(&$Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.Users.Edit')) return;
      
      $UserID = $Sender->User->UserID;
      $LastIP = GetValue("LastIP", Gdn::Database()->Query(sprintf("SELECT LastIP FROM GDN_User u WHERE UserID = %d",$UserID))->FirstRow(DATASET_TYPE_ARRAY),0);
      
      if (!is_null($LastIP) && $LastIP != '') {
         echo "<dt>".T('Last IP')."</dt>\n";
         echo "<dd>".$LastIP."</dd>";
      }
   }
   
   public function UserModel_AfterInsertUser_Handler($Sender) {
      $UserID = $Sender->EventArguments['InsertUserID'];
      try {
         Gdn::SQL()->Update('User',array(
            'LastIP'    => Gdn::Request()->GetValue('REMOTE_ADDR')
         ), array(
            'UserID'    => $UserID
         ))->Put();
      } catch (Exception $e) {
         // Do nothing
      }
   }
   
   public function UserController_ApplicantInfo_Handler($Sender) {
      $User = GetValue('User', $Sender->EventArguments, NULL);
      if (!is_null($User) && !is_null($LastIP = GetValue('LastIP', $User))) {
         echo " [{$LastIP}]";
      }
   }
   
   public function Gdn_Auth_AuthSuccess_Handler($Sender) {
      $UserID = Gdn::Session()->UserID;
      try {
         Gdn::SQL()->Update('User',array(
            'LastIP'    => Gdn::Request()->GetValue('REMOTE_ADDR')
         ), array(
            'UserID'    => $UserID
         ))->Put();
      } catch (Exception $e) {
         // Do nothing
      }
   }
   
   /*
public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
      $Sender->EventArguments['FormPostValues']['LastIP'] = Gdn::Request()->GetValue('REMOTE_ADDR');
   }
*/
   
   public function PostController_AfterDiscussionSave_Handler($Sender) {
      $DiscussionID = $Sender->EventArguments['Discussion']->DiscussionID;
      try {
         Gdn::SQL()->Update('Discussion',array(
            'LastIP'    => Gdn::Request()->GetValue('REMOTE_ADDR')
         ), array(
            'DiscussionID' => $DiscussionID
         ))->Put();
      } catch (Exception $e) {
         // Do nothing
      }
   }
   
   /*
public function CommentModel_BeforeSaveComment_Handler($Sender) {
      $Sender->EventArguments['FormPostValues']['LastIP'] = Gdn::Request()->GetValue('REMOTE_ADDR');
   }
*/
   
   public function PostController_AfterCommentSave_Handler($Sender) {
      $CommentID = $Sender->EventArguments['Comment']->CommentID;
      try {
         Gdn::SQL()->Update('Comment',array(
            'LastIP'    => Gdn::Request()->GetValue('REMOTE_ADDR')
         ), array(
            'CommentID'    => $CommentID
         ))->Put();
      } catch (Exception $e) {
         // Do nothing
      }
   }
   
   public function DiscussionController_CommentInfo_Handler(&$Sender) {
      $this->AttachIP($Sender);
   }
   
   public function PostController_CommentInfo_Handler(&$Sender) {
      $this->AttachIP($Sender);
   }
   
   protected function AttachIP(&$Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.Users.Edit')) return;
      
      $IP = ArrayValue('LastIP',$Sender->EventArguments['Object'],NULL);
      if (is_null($IP)) $IP = T('Unknown');
      echo '<span>'.T('IP: ').$IP.'</span>';
   }
      
   public function Setup() {
      $Structure = Gdn::Structure();
      $Structure
         ->Table('User')
         ->Column('LastIP', 'varchar(14)', TRUE)
         ->Set(FALSE, FALSE);
         
      $Structure
         ->Table('Comment')
         ->Column('LastIP', 'varchar(14)', TRUE)
         ->Set(FALSE, FALSE);
         
      $Structure
         ->Table('Discussion')
         ->Column('LastIP', 'varchar(14)', TRUE)
         ->Set(FALSE, FALSE);
   }
   
   public function OnDisable() {
      
   }
   
}