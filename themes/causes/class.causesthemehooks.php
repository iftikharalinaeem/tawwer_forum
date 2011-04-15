<?php if (!defined('APPLICATION')) exit();

class CausesThemeHooks implements Gdn_IPlugin {
	
	public function Base_Render_Before($Sender) {
		// Set the favicon if there is a head module
		if (property_exists($Sender, 'Head') && is_object($Sender->Head))
			$Sender->Head->SetFavIcon(Asset('themes/causes/design/images/favicon.gif'));
	}
	
	// Don't let users see any of the registration screens (they must use facebook).
	public function EntryController_Render_Before($Sender) {
		/*
		if (in_array(strtolower($Sender->RequestMethod), array('signout', 'register')))
			Redirect('discussions');
		*/
	}
	
	public function CategoriesController_Render_Before($Sender) {
		if (strtolower($Sender->RequestMethod) == 'all')
			Redirect('categories');
	}
	
   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
}