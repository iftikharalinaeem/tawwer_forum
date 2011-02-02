<?php if (!defined('APPLICATION')) exit();

class VanillaOverflowThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the guest module over to the BodyMenu asset.
	 */
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Most recent by %1$s', '%1$s');
	}
	
   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}