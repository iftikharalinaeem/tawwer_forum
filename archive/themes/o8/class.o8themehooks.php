<?php if (!defined('APPLICATION')) exit();
class o8ThemeHooks implements Gdn_IPlugin {
	
   public function Setup() {
		// Set the order for the modules (make sure new discussion module is before content).
		SaveToConfig('Modules.Vanilla.Content', array('MessageModule', 'Notices', 'NewConversationModule', 'NewDiscussionModule', 'Content', 'Ads'));
   }

   public function OnDisable() {
      return TRUE;
   }
   
   public function CategoriesController_Render_Before($Sender) {
		$Sender->AddModule('NewDiscussionModule', 'Content');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
		$Sender->AddModule('NewDiscussionModule', 'Content');
   }

   public function DiscussionController_Render_Before($Sender) {
		$Sender->AddModule('NewDiscussionModule', 'Content');
   }

   public function DraftsController_Render_Before($Sender) {
		$Sender->AddModule('NewDiscussionModule', 'Content');
   }
	
	public function MessagesController_Render_Before($Sender) {
		$Sender->AddModule('NewConversationModule', 'Content');
	}
   
}