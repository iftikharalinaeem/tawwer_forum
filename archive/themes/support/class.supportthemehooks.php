<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of the Support plugin for Vanilla 2.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class SupportThemeHooks implements Gdn_IPlugin {
   public function Setup() {
      // Add some fields to the database
      $Structure = Gdn::Structure();
      
      // "Unanswered" or "Answered"
      $Structure->Table('Discussion')
         ->Column('State', 'varchar(30)', TRUE)
         ->Set(FALSE, FALSE); 

      SaveToConfig('Vanilla.Categories.Use', FALSE);
      SaveToConfig('Vanilla.Comments.AutoOffset', FALSE);
   }
   
   // Make sure to turn auto comment tracking back on when disabling this plugin.
   public function OnDisable() {
      SaveToConfig('Vanilla.Comments.AutoOffset', TRUE);
   }
   
   /**
    * Grab the score field whenever the discussions are queried.
   public function DiscussionModel_AfterDiscussionSummaryQuery_Handler(&$Sender) {
      $Sender->SQL->Select('d.Score')
         ->Select('iu.Email', '', 'FirstEmail')
         ->Select('lcu.Email', '', 'LastEmail');
   }
    */

   // Sort the comments by popularity if necessary
   public function CommentModel_BeforeGet_Handler($Sender) {
      $Sort = GetIncomingValue('Sort', 'popular');
      if (!in_array($Sort, array('popular', 'date')))
         $Sort = 'popular';
         
      if ($Sort == 'popular')
         $Sender->SQL->OrderBy('c.Score', 'desc');
   }

/*
   // Add the vote.js file to discussions page
   public function DiscussionController_Render_Before($Sender) {
      $Sender->AddJsFile('vote.js');

      // Define the sort on the controller (for views to use)
      $Sort = GetIncomingValue('Sort', 'popular');
      if (!in_array($Sort, array('popular', 'date')))
         $Sort = 'popular';

      $Sender->Sort = $Sort;
   }
*/

   /**
    * Increment/decrement discussion scores
   public function DiscussionController_VoteDiscussion_Create($Sender) {
      $DiscussionID = GetValue(0, $Sender->RequestArgs, 0);
      $TransientKey = GetValue(1, $Sender->RequestArgs);
      $VoteType = FALSE;
      if ($TransientKey == 'voteup' || $TransientKey == 'votedown') {
         $VoteType = $TransientKey;
         $TransientKey = GetValue(2, $Sender->RequestArgs);
      }
      $Session = Gdn::Session();
      $NewUserVote = 0;
      $Total = 0;
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey) && $DiscussionID > 0) {
         $DiscussionModel = new DiscussionModel();
         $OldUserVote = $DiscussionModel->GetUserScore($DiscussionID, $Session->UserID);

         if ($VoteType == 'voteup')
            $NewUserVote = 1;
         else if ($VoteType == 'votedown')
            $NewUserVote = -1;
         else
            $NewUserVote = $OldUserVote == 1 ? -1 : 1;
         
         $FinalVote = intval($OldUserVote) + intval($NewUserVote);
         // Allow admins to vote unlimited.
         $AllowVote = $Session->CheckPermission('Vanilla.Comments.Edit');
         // Only allow users to vote up or down by 1.
         if (!$AllowVote)
            $AllowVote = $FinalVote > -2 && $FinalVote < 2;
         
         if ($AllowVote)
            $Total = $DiscussionModel->SetUserScore($DiscussionID, $Session->UserID, $FinalVote);
      }
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Sender->SetJson('TotalScore', $Total);
      $Sender->SetJson('FinalVote', $FinalVote);
      $Sender->Render();
   }
   */
   
   /**
    * Increment/decrement comment scores
   public function DiscussionController_VoteComment_Create($Sender) {
      $CommentID = GetValue(0, $Sender->RequestArgs, 0);
      $VoteType = GetValue(1, $Sender->RequestArgs);
      $TransientKey = GetValue(2, $Sender->RequestArgs);
      $Session = Gdn::Session();
      $FinalVote = 0;
      $Total = 0;
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey) && $CommentID > 0) {
         $CommentModel = new CommentModel();
         $OldUserVote = $CommentModel->GetUserScore($CommentID, $Session->UserID);
         $NewUserVote = $VoteType == 'voteup' ? 1 : -1;
         $FinalVote = intval($OldUserVote) + intval($NewUserVote);
         // Allow admins to vote unlimited.
         $AllowVote = $Session->CheckPermission('Vanilla.Comments.Edit');
         // Only allow users to vote up or down by 1.
         if (!$AllowVote)
            $AllowVote = $FinalVote > -2 && $FinalVote < 2;
         
         if ($AllowVote)
            $Total = $CommentModel->SetUserScore($CommentID, $Session->UserID, $FinalVote);
      }
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Sender->SetJson('TotalScore', $Total);
      $Sender->SetJson('FinalVote', $FinalVote);
      $Sender->Render();
   }
    */
   
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
   
   /**
    * Load popular discussions.
   public function DiscussionsController_Popular_Create($Sender) {
      $Sender->Title(T('Popular'));
      $Sender->Head->Title($Sender->Head->Title());

      $Offset = GetValue('0', $Sender->RequestArgs, '0');

      // Get rid of announcements from this view
      if ($Sender->Head) {
         $Sender->AddJsFile('discussions.js');
         $Sender->AddJsFile('bookmark.js');
			$Sender->AddJsFile('jquery.menu.js');
         $Sender->AddJsFile('options.js');
         $Sender->Head->AddRss($Sender->SelfUrl.'/feed.rss', $Sender->Head->Title());
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Add Modules
      $Sender->AddModule('NewDiscussionModule');
      $BookmarkedModule = new BookmarkedModule($Sender);
      $BookmarkedModule->GetData();
      $Sender->AddModule($BookmarkedModule);

      $Sender->SetData('Category', FALSE, TRUE);
      $Limit = C('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new DiscussionModel();
      $CountDiscussions = $DiscussionModel->GetCount();
      $Sender->SetData('CountDiscussions', $CountDiscussions);
      $Sender->AnnounceData = FALSE;
		$Sender->SetData('Announcements', array(), TRUE);
      $DiscussionModel->SQL->OrderBy('d.CountViews', 'desc');
      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit);
      $Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
      $Sender->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/popular/%1$s'
      );
      
      // Deliver json data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Render the controller
      $Sender->View = 'index';
      $Sender->Render();
   }
    */
   
}