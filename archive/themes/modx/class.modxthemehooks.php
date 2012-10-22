<?php if (!defined('APPLICATION')) exit();

class ModxThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the guest module over to the BodyMenu asset.
	 */
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		
		// Take message modules out of content asset and move into custom messages asset
		if (array_key_exists('Content', $Sender->Assets)) {
			if (array_key_exists('MessageModule', $Sender->Assets['Content'])) {
				$Sender->Assets['Messages']['MessageModule'] = $Sender->Assets['Content']['MessageModule'];
				unset($Sender->Assets['Content']['MessageModule']);
			}
		}
		
	}

	/**
	 * Add the user photo in each discussion list item.
	 */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion)) {
			$Photo = UserPhoto(UserBuilder($Discussion, 'First'), 'Photo');
			if ($Photo != '')
				echo '<div class="Photo">'.$Photo.'</div>';
		}
	}
	
	/**
	 * Add the user photo on each comment in search results.
	 */
	public function Base_BeforeItemContent_Handler($Sender) {
		$User = GetValue('User', $Sender->EventArguments);
		if (is_object($User)) {
			$Photo = UserPhoto($User, 'Photo');
			if ($Photo != '')
				echo '<div class="Photo">'.$Photo.'</div>';
		}
		
		$Row = GetValue('Row', $Sender->EventArguments);
		if (is_object($Row)) {
			$Photo = UserPhoto($Row, 'Photo');
			if ($Photo != '')
				echo '<div class="Photo">'.$Photo.'</div>';
		}
	}

	/**
	 * Add a discussion excerpt in each discussion list item.
	 */
	public function Base_AfterDiscussionTitle_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Excerpt">'.SliceString(Gdn_Format::Text($Discussion->Body, FALSE), 100).'</div>';
	}

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}