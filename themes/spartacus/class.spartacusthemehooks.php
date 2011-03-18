<?php if (!defined('APPLICATION')) exit();

class SpartacusThemeHooks implements Gdn_IPlugin {
	
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		Gdn::Locale()->SetTranslation('Comments', 'Comments');
		Gdn::Locale()->SetTranslation('Discussions', 'Discussions');
		Gdn::Locale()->SetTranslation('All Discussions', 'All Discussions');
	}
	
   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}