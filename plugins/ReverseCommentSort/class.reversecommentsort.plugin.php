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
$PluginInfo['ReverseCommentSort'] = array(
   'Name' => 'Reverse Comment Sort',
   'Description' => 'Reverses the sorting of comments so that the most recent one appears first. Also turns off the AutoOffset so people do not jump to the bottom of the page.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class VotingPlugin extends Gdn_Plugin {

   /**
	 * Sort the comments by popularity if necessary
	 */
   public function CommentModel_AfterCommentQuery_Handler($Sender) {
      $Sender->OrderDirection = 'desc';
   }
	// Redirect back to the top of the discussion when posting a comment.
	public function PostController_BeforeCommentRender_Handler($Sender) {
		if (!GetValue('Draft', $Sender->EventArguments) && !GetValue('Editing', $Sender->EventArguments)) {
			$Discussion = GetValue('Discussion', $Sender->EventArguments);
			if ($Discussion)
				$Sender->RedirectUrl = Gdn::Request()->Url('discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/#', TRUE);
		}
	}

	/**
	 * If turning off reversing, make the forum go back to the traditional "jump
	 * to what I last read" functionality.
	 */
   public function OnDisable() {
		SaveToConfig('Vanilla.Comments.AutoOffset', TRUE);
   }
   public function Setup() {
      SaveToConfig('Vanilla.Comments.AutoOffset', FALSE);
   }
	
}