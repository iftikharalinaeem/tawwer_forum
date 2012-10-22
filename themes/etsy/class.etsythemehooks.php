<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of the Support plugin for Vanilla 2.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class EtsyThemeHooks implements Gdn_IPlugin {
   public function Setup() {
      // Add some fields to the database

      // Add Likes to UserDiscussion
      // -> UserDiscussion->Like enum('1','0') not null default '0';
      
      // Count "Likes" on discussions
      // -> Discussion.CountLikes int

      // Allow comments to be "Liked"
      // -> Comment.CountLikes int
      
      // -> Place who "liked" a comment in it's attributes field.
      
      // Questions are "Unanswered" or "Answered"
      // Ideas are "Suggested", "Planned", "Not Planned" or "Completed"
      // Problems are "Unsolved" or "Solved"
      // Praise is -
      // Announcements are -
      // -> Discussion.State varchar(20)
      
      // Add new categories and remove old ones (unless they are already appropriately named).
      // Discussions from deleted categories are placed in the Questions category.
   }
   
   public function SettingsController_Index_Create($Sender) {
      $Sender->AddJsFile('settings.js');
      $Sender->Title(T('Dashboard'));
         
      $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Routes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applications.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Plugins.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Themes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Registration.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applicants.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Roles.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $Sender->FireEvent('DefineAdminPermissions');
      $Sender->Permission($Sender->RequiredAdminPermissions, '', FALSE);
      $Sender->AddSideMenu('garden/settings');
      $Sender->Render();
   }

   public function DiscussionModel_AfterDiscussionSummaryQuery_Handler(&$Sender) {
      $Sender->SQL->Select('iu.Email', '', 'FirstEmail')
         ->Select('lcu.Email', '', 'LastEmail');
   }
   
   public function Base_Render_Before(&$Sender) {
      // do nothing
   }
   
}