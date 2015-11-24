<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class IaThemeHooks implements Gdn_IPlugin {
   
   public function Setup() {
      return TRUE;
   }
   public function OnDisable() {
      return TRUE;
   }
   
   public function DiscussionsController_AfterInitialize_Handler($Sender) {
      // echo 'test';
      // die();
      $Sender->AddJsFile('jquery.autogrow.js');
      // $Sender->ClearJsFiles();
   }
   
   public function ConversationModel_BeforeGet_Handler(&$Sender) {
      $Sender->SQL->Select('lmu.Email', '', 'LastMessageEmail');
   }

   public function ConversationMessageModel_BeforeGet_Handler(&$Sender) {
      $Sender->SQL->Select('iu.Email', '', 'InsertEmail');
   }

   public function ActivityModel_BeforeGet_Handler(&$Sender) {
      $Sender->SQL
         ->Select('au.Email', '', 'ActivityEmail')
         ->Select('ru.Email', '', 'RegardingEmail');
   }

	public function ActivityModel_BeforeGetNotifications_Handler(&$Sender) {
      $Sender->SQL
         ->Select('au.Email', '', 'ActivityEmail')
         ->Select('ru.Email', '', 'RegardingEmail');
	}

   public function ActivityModel_BeforeGetComments_Handler(&$Sender) {
      $Sender->SQL->Select('au.Email', '', 'ActivityEmail');
   }

   public function UserModel_BeforeGetActiveUsers_Handler(&$Sender) {
      $Sender->SQL->Select('u.Email');
   }

   public function CommentModel_BeforeGet_Handler(&$Sender) {
      $Sender->SQL->Select('iu.Email', '', 'InsertEmail');
   }

   public function CommentModel_BeforeGetNew_Handler(&$Sender) {
      $Sender->SQL->Select('iu.Email', '', 'InsertEmail');
   }
   
   public function UserModel_SessionQuery_Handler($Sender) {
      $Sender->SQL->Select('u.Email');
   }
   
   public function DiscussionModel_AfterDiscussionSummaryQuery_Handler(&$Sender) {
      $Sender->SQL->Select('iu.Email', '', 'FirstEmail')
         ->Select('lcu.Email', '', 'LastEmail');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
      $RequestMethod = strtolower($Sender->RequestMethod);
      if (in_array($RequestMethod, array('index', 'comment'))) {
         // Grab all DiscussionIDs to get comments for
         $DiscussionIDs = array();
         foreach ($Sender->DiscussionData->Result() as $Discussion) {
            $DiscussionIDs[] = $Discussion->DiscussionID;
         }
         $Sender->DiscussionIDs = $DiscussionIDs;
         // Load comments for each discussion
         if ($Sender->DiscussionData->NumRows() > 0) {
            $FirstDiscussionID = $Sender->DiscussionData->FirstRow()->DiscussionID;
            $LastDiscussionID = $Sender->DiscussionData->LastRow()->DiscussionID;
            $Sender->CommentModel = new CommentModel();
            $Sender->CommentModel->SQL
               ->Select('iu.Email', '', 'InsertEmail')
               ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
               ->WhereIn('c.DiscussionID', $DiscussionIDs)
               ->OrderBy('d.DateLastComment', 'desc')
               ->OrderBy('c.DateInserted', 'asc');
            $Sender->CommentModel->CommentQuery();
            
            $Sender->CommentData = $Sender->CommentModel->SQL->Get();
            $Sender->AddAsset('SubContent', $Sender->FetchView('previews', 'discussion', 'vanilla'));
         } else {
            $Sender->CommentData = FALSE;
         }
      }
   }
   public function PostController_AfterDiscussionSave_Handler($Sender) {
      $Discussion = GetValue('Discussion', $Sender->EventArguments);
      if ($Discussion) {
         $Discussion->FirstUserID = $Discussion->InsertUserID;
         $Discussion->FirstName = $Discussion->InsertName;
         $Discussion->FirstPhoto = $Discussion->InsertPhoto;
         $Discussion->FirstEmail = $Discussion->InsertEmail;
         $Discussion->FirstDate = $Discussion->DateInserted;
         $Sender->ShowOptions = TRUE;
         ob_clean();
         ob_start();
         include($Sender->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
         WriteDiscussion($Discussion, $Sender, Gdn::Session(), ' Hidden');
         $DiscussionHtml = ob_get_contents();
         ob_end_clean();
         $Sender->SetJson('DiscussionHtml', $DiscussionHtml);
      }
   }
}

if (!function_exists('UserBuilder')) {
   /**
    * Override the default UserBuilder function with one that switches the photo
    * out with a gravatar url if the photo is empty.
    */
   function UserBuilder($Object, $UserPrefix = '') {
      $User = new stdClass();
      $UserID = $UserPrefix.'UserID';
      $Name = $UserPrefix.'Name';
      $Photo = $UserPrefix.'Photo';
      $Email = $UserPrefix.'Email';
      $User->UserID = $Object->$UserID;
      $User->Name = $Object->$Name;
      $User->Photo = property_exists($Object, $Photo) ? $Object->$Photo : '';
      if ($User->Photo == '' && property_exists($Object, $Email)) {
         $User->Photo = 'http://www.gravatar.com/avatar.php?'
            .'gravatar_id='.md5(strtolower($Object->$Email))
            .'&default='.urlencode(Asset(Gdn::Config('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.gif'), TRUE))
            .'&size='.Gdn::Config('Garden.Thumbnail.Width', 40);
      }
		return $User;
   }
}