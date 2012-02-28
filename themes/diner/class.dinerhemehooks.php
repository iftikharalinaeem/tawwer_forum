<?php if (!defined('APPLICATION')) exit();

class DinerThemeHooks implements Gdn_IPlugin {
	
	public function DiscussionController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function DiscussionsController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function CategoriesController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function DraftsController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
   public function Setup() {
		return TRUE;
   }
   public function OnDisable() {
      return TRUE;
   }
}