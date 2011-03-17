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
		Gdn::Locale()->SetTranslation('%s discussions', '%s');
		Gdn::Locale()->SetTranslation('%s discussion', '%s');
		// Move the howdy stranger module into the BodyMenu asset container
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('GuestModule', $Sender->Assets['Panel'])) {
				$Sender->Assets['BodyMenu']['GuestModule'] = $Sender->Assets['Panel']['GuestModule'];
				unset($Sender->Assets['Panel']['GuestModule']);
			}
		}
	}

	/**
	 * Add the user photo in each discussion list item.
	 */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Photo">'.UserPhoto(UserBuilder($Discussion, 'First'), 'Photo').'</div>';
	}
	
	/**
	 * Add the user photo on each comment in search results.
	 */
	public function Base_BeforeItemContent_Handler($Sender) {
		$User = GetValue('User', $Sender->EventArguments);
		if (is_object($User))
			echo '<div class="Photo">'.UserPhoto($User, 'Photo').'</div>';
		
		$Row = GetValue('Row', $Sender->EventArguments);
		if (is_object($Row))
			echo '<div class="Photo">'.UserPhoto($Row, 'Photo').'</div>';
	}

	/**
	 * Add a discussion excerpt in each discussion list item.
	 */
	public function Base_AfterDiscussionTitle_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Excerpt">'.SliceString(Gdn_Format::Text($Discussion->Body, FALSE), 100).'</div>';
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
	

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}