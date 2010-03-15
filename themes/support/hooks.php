<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of the Support plugin for Vanilla 2.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ThemeHooks implements Gdn_IPlugin {
   public function Setup() {
      // Add some fields to the database
      // Treat bookmarks as "follows" (they count as "likes")
      $Structure = Gdn::Structure();
      
      // Count "Likes" (bookmarks) on discussions
      // -> Discussion.Score int

      // Questions are "Unanswered" or "Answered"
      // Ideas are "Suggested", "Planned", "Not Planned" or "Completed"
      // Problems are "Unsolved" or "Solved"
      // Praise is -
      // Announcements are -
      // -> Discussion.State varchar(30)
      $Structure->Table('Discussion')
         ->Column('State', 'varchar(30)', TRUE)
         ->Column('Score', 'int', 0)
         ->Set(FALSE, FALSE); 
      
      // Allow comments to be "Liked"
      // -> Comment.Score int
      $Structure->Table('Comment')
         ->Column('Score', 'int', 0)
         ->Set(FALSE, FALSE);
         
      $SQL = Gdn::Database()->SQL();
      
      // Add new categories and remove old ones (unless they are already appropriately named).
      if ($SQL->Select('CategoryID')->From('Category')->Where('Name', 'Question')->Get()->NumRows() == 0)
         $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'Question', 'Description' => 'Ask a question', 'Sort' => '1'));
      
      if ($SQL->Select('CategoryID')->From('Category')->Where('Name', 'Idea')->Get()->NumRows() == 0)
         $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'Idea', 'Description' => 'Share an idea', 'Sort' => '1'));

      if ($SQL->Select('CategoryID')->From('Category')->Where('Name', 'Problem')->Get()->NumRows() == 0)
         $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'Problem', 'Description' => 'Report a problem', 'Sort' => '1'));

      if ($SQL->Select('CategoryID')->From('Category')->Where('Name', 'Kudos')->Get()->NumRows() == 0)
         $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'Kudos', 'Description' => 'Give some kudos', 'Sort' => '1'));
      
      if ($SQL->Select('CategoryID')->From('Category')->Where('Name', 'Announcement')->Get()->NumRows() == 0)
         $SQL->Insert('Category', array('InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Format::ToDateTime(), 'DateUpdated' => Format::ToDateTime(), 'Name' => 'Announcement', 'Description' => 'Administrative announcements', 'Sort' => '1'));
         
      // Delete old categories
      $SQL->WhereNotIn('Name', array('Question', 'Idea', 'Problem', 'Kudos', 'Announcement'))->Delete('Category');
      
      // Discussions from deleted categories are placed in the Questions category.
      $CategoryID = $SQL->Select('CategoryID')->From('Category')->Where('Name', 'Question')->Get()->FirstRow()->CategoryID;
      $SQL->Update('Discussion', array('CategoryID' => $CategoryID))->Put();
   }
   
   /**
    * Grab the score field whenever the discussions are queried.
    */
   public function Gdn_DiscussionModel_AfterDiscussionSummaryQuery_Handler(&$Sender) {
      $Sender->SQL->Select('d.Score')
         ->Select('iu.Email', '', 'FirstEmail')
         ->Select('lcu.Email', '', 'LastEmail');
   }
   
   /**
    * When a discussion is bookmarked or unbookmarked, increase or decrease it's score.
    */
   public function Gdn_DiscussionModel_AfterBookmarkDiscussion_Handler($Sender) {
      $Discussion = $Sender->EventArguments['Discussion'];
      $State = $Sender->EventArguments['State'];
      if (is_object($Discussion)) {
         $Math = 'Score ' . ($State == '1' ? '+ 1' : '- 1');
         $Sender->SQL->Update('Discussion')->Set('Score', $Math, FALSE)->Where('DiscussionID', $Discussion->DiscussionID)->Put();
      }
   }
   
   /**
    * Remove Vanilla category management (we want to structure them our own way).
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->RemoveLink('Forum', Gdn::Translate('Categories'));
   }

   /**
   * Don't let the users access the category management screens.
   */
   public function SettingsController_Render_Before(&$Sender) {
      if (strpos(strtolower($Sender->RequestMethod), 'categor') > 0)
         Redirect($Sender->Routes['DefaultPermission']);
   }
   
}