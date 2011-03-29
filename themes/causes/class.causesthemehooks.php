<?php if (!defined('APPLICATION')) exit();

class CausesThemeHooks implements Gdn_IPlugin {
	
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