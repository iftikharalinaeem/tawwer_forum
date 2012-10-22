<?php if (!defined('APPLICATION')) exit();

class VanillaDiggThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the category module on the discussion & discussions pages.
	 */
	public function Base_Render_Before($Sender) {
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('CategoriesModule', $Sender->Assets['Panel'])) {
				$Sender->Assets['BodyMenu']['CategoriesModule'] = $Sender->Assets['Panel']['CategoriesModule'];
				unset($Sender->Assets['Panel']['CategoriesModule']);
			}
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