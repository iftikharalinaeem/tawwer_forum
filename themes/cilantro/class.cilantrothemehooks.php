<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class CilantroThemeHooks implements Gdn_IPlugin {
   
   public function Setup() {
/*      $Structure = Gdn::Structure();
      $Structure->Table('Tag')
			->PrimaryKey('TagID')
			->Column('Name', 'varchar(50)')
			->Set();
      
      $Structure->Table('DiscussionTag')
			->Column('DiscussionID', 'int', FALSE, 'key')
			->Column('TagID', 'int', FALSE, 'key')
			->Set();
*/
   }

	public function DiscussionModel_BeforeGet_Handler($Sender) {
		$Sender->SQL->Where('Closed', 0);
	}

	public function DiscussionsController_Closed_Create($Sender) {
		$Offset = GetValue(0, $Sender->RequestArgs, '0');
		$Sender->View = 'index';
      if ($Sender->Head) {
         $Sender->AddJsFile('discussions.js');
         $Sender->AddJsFile('bookmark.js');
         $Sender->AddJsFile('options.js');
         $Sender->Head->AddRss($Sender->SelfUrl.'/feed.rss', $Sender->Head->Title());
         $Sender->Head->Title(T('Closed Issues'));
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      // Add Modules
      $Sender->AddModule('NewDiscussionModule');
      $Sender->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($Sender);
      $BookmarkedModule->GetData();
      $Sender->AddModule($BookmarkedModule);

      $Sender->SetData('Category', FALSE, TRUE);
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new DiscussionModel();
		
      if ($Limit == '') 
         $Limit = C('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $Sender->Database->SQL()
         ->From('Discussion d')
			->Select('d.DiscussionID', 'count', 'Count');
         
      if ($UserID > 0) {
         $Sender->Database->SQL()
            ->Select('w.UserID', '', 'WatchUserID')
            ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->Select('w.CountComments', '', 'CountCommentWatch')
            ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      }
		
		//$Sender->AddArchiveWhere($Sender->Database->SQL());
      
      $CountDiscussions = $Sender->Database->SQL()
			->Where('Closed', 1)
         ->Get()
			->FirstRow()
			->Count;

      $Sender->Database->SQL()
			->Select('d.*')
         ->Select('d.InsertUserID', '', 'FirstUserID')
         ->Select('d.DateInserted', '', 'FirstDate')
         ->Select('iu.Name', '', 'FirstName') // <-- Need these for rss!
         ->Select('iu.Photo', '', 'FirstPhoto')
         ->Select('d.Body') // <-- Need these for rss!
         ->Select('d.Format') // <-- Need these for rss!
         ->Select('d.DateLastComment', '', 'LastDate')
         ->Select('d.LastCommentUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         //->Select('lcup.Name', '', 'LastPhoto')
         //->Select('lc.Body', '', 'LastBody')
         ->Select("' &rarr; ', pc.Name, ca.Name", 'concat_ws', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->From('Discussion d')
         ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // First comment author is also the discussion insertuserid
         //->Join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left') // Last comment
         ->Join('User lcu', 'd.LastCommentUserID = lcu.UserID', 'left') // Last comment user
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left') // Category
         ->Join('Category pc', 'ca.ParentCategoryID = pc.CategoryID', 'left'); // Parent category
         //->Permission('ca', 'CategoryID', 'Vanilla.Discussions.View');
         
      if ($UserID > 0) {
         $Sender->Database->SQL()
            ->Select('w.UserID', '', 'WatchUserID')
            ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->Select('w.CountComments', '', 'CountCommentWatch')
            ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      } else {
            $Sender->Database->SQL()
               ->Select('0', '', 'WatchUserID')
               ->Select('now()', '', 'DateLastViewed')
               ->Select('0', '', 'Dismissed')
               ->Select('0', '', 'Bookmarked')
               ->Select('0', '', 'CountCommentWatch')
					->Select('d.Announce','','IsAnnounce');
      }
		
      $Data = $Sender->Database->SQL()
			->Where('Closed', 1)
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
		
		$DiscussionModel->AddDiscussionColumns($Data);
		
      $Sender->SetData('CountDiscussions', $CountDiscussions);
      $Sender->SetData('DiscussionData', $Data, TRUE);		

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/%1$s'
      );
      
      // Deliver json data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Set a definition of the user's current timezone from the db. jQuery
      // will pick this up, compare to the browser, and update the user's
      // timezone if necessary.
      $CurrentUser = Gdn::Session()->User;
      if (is_object($CurrentUser)) {
         $ClientHour = $CurrentUser->HourOffset + date('G', time());
         $Sender->AddDefinition('SetClientHour', $ClientHour);
      }
      
      // Render the controller
      $Sender->Render();
	}
	
}