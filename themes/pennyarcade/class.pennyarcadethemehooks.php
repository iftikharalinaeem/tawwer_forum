<?php if (!defined('APPLICATION')) exit();

class PennyArcadeThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the guest module over to the BodyMenu asset.
	 */
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		Gdn::Locale()->SetTranslation('Apply for Membership', 'Sign Up');
		Gdn::Locale()->SetTranslation('Apply', 'Sign Up');
		Gdn::Locale()->SetTranslation('%s comments', '%s');
		Gdn::Locale()->SetTranslation('%s comment', '%s');
		Gdn::Locale()->SetTranslation('%s threads', '%s');
		Gdn::Locale()->SetTranslation('%s thread', '%s');
		Gdn::Locale()->SetTranslation('My Discussions', 'My Threads');
		Gdn::Locale()->SetTranslation('All Discussions', 'All Threads');
		Gdn::Locale()->SetTranslation('Recent Discussions', 'Recent Threads');
		// Move the howdy stranger module into the BodyMenu asset container
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('GuestModule', $Sender->Assets['Panel'])) {
				$Sender->Assets['BodyMenu']['GuestModule'] = $Sender->Assets['Panel']['GuestModule'];
				unset($Sender->Assets['Panel']['GuestModule']);
			}
		}
		// Remove New Discussion module from the panel
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('NewDiscussionModule', $Sender->Assets['Panel']))
				unset($Sender->Assets['Panel']['NewDiscussionModule']);
		}
	}

	public function CategoriesController_BeforeCategoryItem_Handler($Sender) {
		$NumRows = GetValue('NumRows', $Sender->EventArguments, 0);
		$Counter = GetValue('Counter', $Sender->EventArguments, -1);
		$Category = GetValue('Category', $Sender->EventArguments);
		if (!is_object($Category))
			return;
		
		if ($Counter == -1)
			$Sender->EventArguments['Counter'] = 1;
		else
			$Sender->EventArguments['Counter']++;
			
		if ($Category->Depth == 1 && $Counter > 1)
			$Sender->EventArguments['CatList'] .= '</ul><ul class="DataList CategoryList CategoryListWithHeadings">';
	}
	
	// Make sure that the discussion query pulls enough information to show & gravatar icons.
	public function DiscussionModel_AfterDiscussionSummaryQuery_Handler($Sender) {
		$Sender->SQL
			->Select('iu.About', '', 'InsertStatus')
			->Select('iu.Email', '', 'FirstEmail')
			->Select('lcu.Photo', '', 'LastPhoto')
			->Select('lcu.Email', '', 'LastEmail');
	}
	
	// Grab some extra information about the user for views
	public function CommentModel_AfterCommentQuery_Handler($Sender) {
		$Sender->SQL
			->Select('iu.About', '', 'InsertStatus')
			->Select('iu.Jailed', '', 'InsertJailed')
			->Select('iu.Banned', '', 'InsertBanned');
	}
	public function DiscussionModel_BeforeGetID_Handler($Sender) {
		$Sender->SQL
			->Select('iu.About', '', 'InsertStatus')
			->Select('iu.Jailed', '', 'InsertJailed')
			->Select('iu.Banned', '', 'InsertBanned');
	}
	
	// Add the insert user's roles to the comment data so we can visually identify different roles in the view
	public function DiscussionController_Render_Before($Sender) {
      $JoinDiscussion = array($Sender->Discussion);
      RoleModel::SetUserRoles($JoinDiscussion, 'InsertUserID');
      RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
	}

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}