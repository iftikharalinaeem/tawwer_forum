<?php if (!defined('APPLICATION')) exit();

class VFComThemeHooks implements Gdn_IPlugin {

   public function Setup() {
      return TRUE;
   }
	
   public function OnDisable() {
      return TRUE;
   }
   
   public function Base_Render_Before($Sender) {
      if (in_array(strtolower($Sender->ControllerName), array('discussionscontroller', 'categoriescontroller')))
         $Sender->AddModule('DiscussionSearchModule');
   }
   
}