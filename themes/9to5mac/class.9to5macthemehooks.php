<?php if (!defined('APPLICATION')) exit();

class NineToFiveMacThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the guest module over to the BodyMenu asset.
	 */
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
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

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}