<?php if (!defined('APPLICATION')) exit();

/**
 * @var $PluginInfo Array of data about the plugin.
 */
$PluginInfo['ForumMerge'] = array(
   'Name' => 'Forum Merge',
   'Description' => 'Merge another Vanilla 2 forum into this one.',
   'Version' => '1.0',
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com'
);

/**
 * Forum Merge plugin.
 * 
 * @todo Allow multiple merges by resetting OldID to NULL before run.
 * @todo Add additional datatypes (noted at end of script)
 */
class ForumMergePlugin implements Gdn_IPlugin {
	/**
	 * Add to the dashboard menu.
	 */
   public function Base_GetAppSettingsMenuItems_Handler($Sender, $Args) {
      $Args['SideMenu']->AddLink('Import', T('Merge'), 'utility/merge', 'Garden.Settings.Manage');
	}
   
	/**
	 * Admin screen for merging forums.
	 */
   public function UtilityController_Merge_Create($Sender) {
		$Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('utility/merge');
		
		if ($Sender->Form->IsPostBack()) {
			$Database = $Sender->Form->GetFormValue('Database');
			$Prefix = $Sender->Form->GetFormValue('Prefix');
         $this->MergeCategories = ($Sender->Form->GetFormValue('MergeCategories')) ? TRUE : FALSE;
			$this->MergeForums($Database, $Prefix);
		}
		
		$Sender->Render($Sender->FetchViewLocation('merge', '', 'plugins/ForumMerge'));
	}
	
	/**
	 * 
	 *
	 * @return string CSV list of columns in both copies of the table minus the primary key.
	 */
	public function GetColumns($Table, $OldDatabase, $OldPrefix) {
		Gdn::Structure()->Database->DatabasePrefix = '';
		$OldColumns = Gdn::Structure()->Get($OldDatabase.'.'.$OldPrefix.$Table)->Columns();
		
		Gdn::Structure()->Database->DatabasePrefix = C('Database.DatabasePrefix');
		$NewColumns = Gdn::Structure()->Get($Table)->Columns();
		
		$Columns = array_intersect_key($OldColumns, $NewColumns);
		unset($Columns[$Table.'ID']);
		return trim(implode(',',array_keys($Columns)),',');
	}
	
	/**
	 * Grab second forum's data and merge with current forum.
	 * 
	 * Merge Users on email address. Keeps this forum's username/password.
	 * Merge Roles, Tags, and Categories on precise name matches.
    * 
    * @todo Compare column names between forums and use intersection
	 */
	public function MergeForums($OldDatabase, $OldPrefix) {
		$NewPrefix = C('Database.DatabasePrefix');
		
		// USERS //
		/*$UserColumns = $this->GetColumns('User', $OldDatabase, $OldPrefix);
		
		// Merge IDs of duplicate users
		Gdn::SQL()->Query('update '.$NewPrefix.'User u set u.OldID = 
			(select u2.UserID from `'.$OldDatabase.'`.'.$OldPrefix.'User u2 where u2.Email = u.Email limit 1)');
		
		// Copy non-duplicate users
		Gdn::SQL()->Query('insert into '.$NewPrefix.'User ('.$UserColumns.', OldID) 
			select '.$UserColumns.', UserID 
			from `'.$OldDatabase.'`.'.$OldPrefix.'User
			where Email not in (select Email from '.$NewPrefix.'User)');
      
      // UserMeta
      Gdn::SQL()->Query('insert ignore into '.$NewPrefix.'UserMeta (UserID, Name, Value) 
         select u.UserID, um.Name, um.Value
         from '.$NewPrefix.'User u, `'.$OldDatabase.'`.'.$OldPrefix.'UserMeta um
         where u.OldID = um.UserID');*/

/*
      
		// ROLES //
		$RoleColumns = $this->GetColumns('Role', $OldDatabase, $OldPrefix);
		
		// Merge IDs of duplicate roles
		Gdn::SQL()->Query('update '.$NewPrefix.'Role r set r.OldID = 
			(select r2.RoleID from `'.$OldDatabase.'`.'.$OldPrefix.'Role r2 where r2.Name = r.Name)');
		
		// Copy non-duplicate roles
		Gdn::SQL()->Query('insert into '.$NewPrefix.'Role ('.$RoleColumns.', OldID) 
			select '.$RoleColumns.', RoleID 
			from `'.$OldDatabase.'`.'.$OldPrefix.'Role
			where Name not in (select Name from '.$NewPrefix.'Role)');
		
		// UserRole
		Gdn::SQL()->Query('insert ignore into '.$NewPrefix.'UserRole (RoleID, UserID) 
			select r.RoleID, u.UserID 
			from '.$NewPrefix.'User u, '.$NewPrefix.'Role r, `'.$OldDatabase.'`.'.$OldPrefix.'UserRole ur
			where u.OldID = (ur.UserID) and r.OldID = (ur.RoleID)');

*/
      
		// CATEGORIES //
		$CategoryColumns = $this->GetColumns('Category', $OldDatabase, $OldPrefix);

      /*if ($this->MergeCategories) {
         // Merge IDs of duplicate category names
         Gdn::SQL()->Query('update '.$NewPrefix.'Category c set c.OldID =
            (select c2.CategoryID from `'.$OldDatabase.'`.'.$OldPrefix.'Category c2 where c2.Name = c.Name)');

         // Copy non-duplicate categories
         Gdn::SQL()->Query('insert into '.$NewPrefix.'Category ('.$CategoryColumns.', OldID)
            select '.$CategoryColumns.', CategoryID
            from `'.$OldDatabase.'`.'.$OldPrefix.'Category
            where Name not in (select Name from '.$NewPrefix.'Category)');
      }
      else {*/
         // Import categories
         Gdn::SQL()->Query('insert into '.$NewPrefix.'Category ('.$CategoryColumns.', OldID)
         select '.$CategoryColumns.', CategoryID
            from `'.$OldDatabase.'`.'.$OldPrefix.'Category
            where Name <> "Root"');

         // Remap hierarchy in the ugliest way possible
         $CategoryMap = array();
         $Categories = Gdn::SQL()->Select('CategoryID')
            ->Select('ParentCategoryID')
            ->Select('OldID')
            ->From('Category')
            ->Where(array('OldID >' => 0))
            ->Get()->Result(DATASET_TYPE_ARRAY);
         foreach ($Categories as $Category) {
            $CategoryMap[$Category['OldID']] = $Category['CategoryID'];
         }
         foreach ($Categories as $Category) {
            if ($Category['ParentCategoryID'] > 0) {
               $ParentID = $CategoryMap[$Category['ParentCategoryID']];
               Gdn::SQL()->Update('Category')
                  ->Set(array('ParentCategoryID' => $ParentID))
                  ->Where(array('CategoryID' => $Category['CategoryID']))
                  ->Put();
            }
         }
         $CategoryModel = new CategoryModel();
         $CategoryModel->RebuildTree();

      //}

      // Update ParentCategoryIDs
      //
      //

		// UserCategory
		//
		//
		

		
		// DISCUSSIONS //
	   $DiscussionColumns = $this->GetColumns('Discussion', $OldDatabase, $OldPrefix);
      
      // Copy over all discussions
      Gdn::SQL()->Query('insert into '.$NewPrefix.'Discussion ('.$DiscussionColumns.', OldID) 
         select '.$DiscussionColumns.', DiscussionID 
         from `'.$OldDatabase.'`.'.$OldPrefix.'Discussion');
      
      // Convert imported discussions to use new UserIDs
      Gdn::SQL()->Query('update '.$NewPrefix.'Discussion d
        set d.InsertUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = d.InsertUserID)
        where d.OldID > 0');
      Gdn::SQL()->Query('update '.$NewPrefix.'Discussion d
        set d.UpdateUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = d.UpdateUserID)
        where d.OldID > 0');
      Gdn::SQL()->Query('update '.$NewPrefix.'Discussion d
        set d.CategoryID = (SELECT c.CategoryID from '.$NewPrefix.'Category c where c.OldID = d.CategoryID)
        where d.OldID > 0');
 /*
      // UserDiscussion
      Gdn::SQL()->Query('insert ignore into '.$NewPrefix.'UserDiscussion 
            (DiscussionID, UserID, Score, CountComments, DateLastViewed, Dismissed, Bookmarked) 
         select d.DiscussionID, u.UserID, ud.Score, ud.CountComments, ud.DateLastViewed, ud.Dismissed, ud.Bookmarked
         from '.$NewPrefix.'User u, '.$NewPrefix.'Discussion d, `'.$OldDatabase.'`.'.$OldPrefix.'UserDiscussion ud
         where u.OldID = (ud.UserID) and d.OldID = (ud.DiscussionID)');    
 */
      
      
      // COMMENTS //
      $CommentColumns = $this->GetColumns('Comment', $OldDatabase, $OldPrefix);
      
      // Copy over all comments
      Gdn::SQL()->Query('insert into '.$NewPrefix.'Comment ('.$CommentColumns.', OldID) 
         select '.$CommentColumns.', CommentID 
         from `'.$OldDatabase.'`.'.$OldPrefix.'Comment');
            
      // Convert imported comments to use new UserIDs
      Gdn::SQL()->Query('update '.$NewPrefix.'Comment c
        set c.InsertUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = c.InsertUserID)
        where c.OldID > 0');
      Gdn::SQL()->Query('update '.$NewPrefix.'Comment c
        set c.UpdateUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = c.UpdateUserID)
        where c.OldID > 0');
      
      // Convert imported comments to use new DiscussionIDs
      Gdn::SQL()->Query('update '.$NewPrefix.'Comment c
        set c.DiscussionID = (SELECT d.DiscussionID from '.$NewPrefix.'Discussion d where d.OldID = c.DiscussionID)
        where c.OldID > 0');
      
      
/*
		// MEDIA //
		$MediaColumns = $this->GetColumns('Media', $OldDatabase, $OldPrefix);
      
		// Copy over all media
      Gdn::SQL()->Query('insert into '.$NewPrefix.'Media ('.$MediaColumns.', OldID) 
         select '.$MediaColumns.', MediaID 
         from `'.$OldDatabase.'`.'.$OldPrefix.'Media');
		
      // InsertUserID
      Gdn::SQL()->Query('update '.$NewPrefix.'Media m
        set m.InsertUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = m.InsertUserID)
        where m.OldID > 0');
      
      // ForeignID / ForeignTable
      //Gdn::SQL()->Query('update '.$NewPrefix.'Media m
      //  set m.ForeignID = (SELECT c.CommentID from '.$NewPrefix.'Comment c where c.OldID = m.ForeignID)
      //  where m.OldID > 0 and m.ForeignTable = \'comment\'');
      Gdn::SQL()->Query('update '.$NewPrefix.'Media m
        set m.ForeignID = (SELECT d.DiscussionID from '.$NewPrefix.'Discussion d where d.OldID = m.ForeignID)
        where m.OldID > 0 and m.ForeignTable = \'discussion\'');
		

      
		// CONVERSATION //
		$ConversationColumns = $this->GetColumns('Conversation', $OldDatabase, $OldPrefix);

      // Copy over all Conversations
      Gdn::SQL()->Query('insert into '.$NewPrefix.'Conversation ('.$ConversationColumns.', OldID) 
         select '.$ConversationColumns.', ConversationID 
         from `'.$OldDatabase.'`.'.$OldPrefix.'Conversation');
      // InsertUserID
      Gdn::SQL()->Query('update '.$NewPrefix.'Conversation c
        set c.InsertUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = c.InsertUserID)
        where c.OldID > 0');
      // UpdateUserID
      Gdn::SQL()->Query('update '.$NewPrefix.'Conversation c
        set c.UpdateUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = c.UpdateUserID)
        where c.OldID > 0');
      // Contributors
      // a. Build userid lookup
*/
      /*$Users = Gdn::SQL()->Query('select UserID, OldID from '.$NewPrefix.'User');
      $UserIDLookup = array();
      foreach($Users->Result() as $User) {
         $OldID = GetValue('OldID', $User);
         $UserIDLookup[$OldID] = GetValue('UserID', $User);
      }
      // b. Translate contributor userids
      $Conversations = Gdn::SQL()->Query('select ConversationID, Contributors 
         from '.$NewPrefix.'Conversation 
         where Contributors <> ""');
      foreach($Conversations->Result() as $Conversation) {
         $Contributors = unserialize(GetValue('Contributors', $Conversation));
         if (!is_array($Contributors))
            continue;
         $UpdatedContributors = array();
         foreach($Contributors as $UserID) {
            if (isset($UserIDLookup[$UserID]))
               $UpdatedContributors[] = $UserIDLookup[$UserID];
         }
         // c. Update each conversation
         $ConversationID = GetValue('ConversationID', $Conversation);
         Gdn::SQL()->Query('update '.$NewPrefix.'Conversation 
            set Contributors = "'.mysql_real_escape_string(serialize($UpdatedContributors)).'"
            where ConversationID = '.$ConversationID);
      }*/
		
		// ConversationMessage      
      // Copy over all ConversationMessages
/*      Gdn::SQL()->Query('insert into '.$NewPrefix.'ConversationMessage (ConversationID,Body,Format,
            InsertUserID,DateInserted,InsertIPAddress,OldID) 
         select ConversationID,Body,Format,InsertUserID,DateInserted,InsertIPAddress,MessageID 
         from `'.$OldDatabase.'`.'.$OldPrefix.'ConversationMessage');
      // ConversationID
      Gdn::SQL()->Query('update '.$NewPrefix.'ConversationMessage cm
        set cm.ConversationID = 
           (SELECT c.ConversationID from '.$NewPrefix.'Conversation c where c.OldID = cm.ConversationID)
        where cm.OldID > 0');
      // InsertUserID
      Gdn::SQL()->Query('update '.$NewPrefix.'ConversationMessage c
        set c.InsertUserID = (SELECT u.UserID from '.$NewPrefix.'User u where u.OldID = c.InsertUserID)
        where c.OldID > 0');
        
      // Conversation FirstMessageID
      Gdn::SQL()->Query('update '.$NewPrefix.'Conversation c
        set c.FirstMessageID = 
           (SELECT cm.MessageID from '.$NewPrefix.'ConversationMessage cm where cm.OldID = c.FirstMessageID)
        where c.OldID > 0');
      // Conversation LastMessageID
      Gdn::SQL()->Query('update '.$NewPrefix.'Conversation c
        set c.LastMessageID = 
           (SELECT cm.MessageID from '.$NewPrefix.'ConversationMessage cm where cm.OldID = c.LastMessageID)
        where c.OldID > 0');
      
		// UserConversation
		Gdn::SQL()->Query('insert ignore into '.$NewPrefix.'UserConversation 
            (ConversationID, UserID, CountReadMessages, DateLastViewed, DateCleared,
            Bookmarked, Deleted, DateConversationUpdated) 
         select c.ConversationID, u.UserID,  uc.CountReadMessages, uc.DateLastViewed, uc.DateCleared, 
            uc.Bookmarked, uc.Deleted, uc.DateConversationUpdated
         from '.$NewPrefix.'User u, '.$NewPrefix.'Conversation c, `'.$OldDatabase.'`.'.$OldPrefix.'UserConversation uc
         where u.OldID = (uc.UserID) and c.OldID = (uc.ConversationID)');    
*/
      
      
		////
		
		// Draft - new UserIDs
		// Activity - wallpost, activitycomment
		// Tag - new UserID, merge on name
		// TagDiscussion - new DiscussionID, TagID
		// Update counters
		// LastCommentID
	}

   /**
    * Nuke every OldID column before a second merge.
    */
   public function UtilityController_MergeReset_Create() {
      Gdn::SQL()->Update('Activity')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Category')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Comment')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Conversation')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('ConversationMessage')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Discussion')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Media')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('Media')->Set('OldID', NULL)->Put();
      Gdn::SQL()->Update('User')->Set('OldID', NULL)->Put();
   }

   public function Setup() {
   	Gdn::Structure()->Table('Activity')->Column('OldID', 'int', TRUE, 'key')->Set();
   	Gdn::Structure()->Table('Category')->Column('OldID', 'int', TRUE, 'key')->Set();
   	Gdn::Structure()->Table('Comment')->Column('OldID', 'int', TRUE, 'key')->Set();
		Gdn::Structure()->Table('Conversation')->Column('OldID', 'int', TRUE, 'key')->Set();
      Gdn::Structure()->Table('ConversationMessage')->Column('OldID', 'int', TRUE, 'key')->Set();
		Gdn::Structure()->Table('Discussion')->Column('OldID', 'int', TRUE, 'key')->Set();
		//Gdn::Structure()->Table('Draft')->Column('OldID', 'int', TRUE, 'key')->Set();
		Gdn::Structure()->Table('Media')->Column('OldID', 'int', TRUE, 'key')->Set();
		Gdn::Structure()->Table('Role')->Column('OldID', 'int', TRUE, 'key')->Set();
		//Gdn::Structure()->Table('Tag')->Column('OldID', 'int', TRUE, 'key')->Set();
		Gdn::Structure()->Table('User')->Column('OldID', 'int', TRUE, 'key')->Set();
   }
}