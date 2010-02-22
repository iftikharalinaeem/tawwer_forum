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
      
      // Count "Likes" (bookmarks) on discussions
      // -> Discussion.CountLikes int
      // Allow comments to be "Liked"
      // -> Comment.CountLikes int
      
      // Questions are "Unanswered" or "Answered"
      // Ideas are "Suggested", "Planned", "Not Planned" or "Completed"
      // Problems are "Unsolved" or "Solved"
      // Praise is -
      // Announcements are -
      // -> Discussion.State varchar(20)
      
      // Add new categories and remove old ones (unless they are already appropriately named).
      // Discussions from deleted categories are placed in the Questions category.
   }
   
   public function Base_Render_Before(&$Sender) {
      // do nothing
   }
   
}