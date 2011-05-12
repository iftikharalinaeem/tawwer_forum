<?php if (!defined('APPLICATION')) exit();

class CarTalkThemeHooks extends Gdn_Plugin {
   public function DiscussionsController_Rules_Create($Sender, $Args) {
      $Sender->Render();
   }
   public function DiscussionsController_Toolbox_Create($Sender, $Args) {
      $Sender->Render();
   }

}