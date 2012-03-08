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

class ReverseCommentSortPlugin extends Gdn_Plugin {

	// Reverse the comment sort order
   public function CommentModel_AfterConstruct_Handler($CommentModel) {
	   $CommentModel->OrderBy('c.DateInserted desc');
   }
	
	// Redirect back to the top of the discussion when posting a comment.
	public function PostController_BeforeCommentRender_Handler($Sender) {
		if (!GetValue('Draft', $Sender->EventArguments) && !GetValue('Editing', $Sender->EventArguments)) {
			$Comment = GetValue('Comment', $Sender->EventArguments);
			// Redirect to the permalink of the new comment
			if ($Comment)
				$Sender->RedirectUrl = Gdn::Request()->Url('/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID, TRUE);
		}
	}

	/**
	 * Insert comment form after the first comment.
	 */
	public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
		$Type = GetValue('Type', $Sender->EventArguments, 'Comment');
		if ($Type == 'Comment' && !GetValue('CommentFormWritten', $Sender)) {
			echo '<style type="text/css">
			div.CommentForm,
			#Content div.Foot,
			div.CommentForm div.Tabs {
				display: none;
			}
			ul.MessageList div.CommentForm {
				display: block;
			}
			</style>
			<li>';
			if ($Sender->Discussion->Closed == '1') {
		   ?>
<div class="Foot Closed">
	<div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
	<?php echo Anchor(T('&larr; All Discussions'), 'discussions', 'TabLink'); ?>
</div>
<?php
			} else if (Gdn::Session()->IsValid()) { 
				echo $Sender->FetchView('comment', 'post');
			} else {
?>
<div class="Foot">
	<?php
	echo Anchor(T('Add a Comment'), Gdn::Authenticator()->SignInUrl($Sender->SelfUrl), 'TabLink'.(C('Garden.SignIn.Popup') ? ' SignInPopup' : ''));
	?> 
</div>
<?php 
			}
			echo '</li>';
			
	      $Sender->CommentFormWritten = TRUE;
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