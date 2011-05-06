<?php if (!defined('APPLICATION')) exit();

class CarTalkThemeHooks extends Gdn_Plugin {

   public function DiscussionController_Render_Before($Sender) {
      $this->_AddAssets($Sender);
   }
   public function DiscussionsController_Render_Before($Sender) {
      $this->_AddAssets($Sender);
   }
   public function CategoriesController_Render_Before($Sender) {
      $this->_AddAssets($Sender);
   }

   /**
    * @param Gdn_Controller $Sender
    */
   protected function _AddAssets($Sender) {
      // Search box.
      $SearchBox = '<div class="Search"><form method="get" action="/search">
<div>
<input type="text" id="Form_Search" name="Search" value="" class="InputBox" /><input type="submit" id="Form_Go" value="Go" class="Button" />
</div>
</form></div>';

      $Sender->AddAsset('Panel', $SearchBox, 'SearchBox');

      // Cartalk toolbox.
      if (class_exists('PocketsPlugin')) {
         $Toolbox = PocketsPlugin::PocketString('Toolbox');
         $Sender->AddAsset('Panel', $Toolbox, 'Toolbox');
      }
   }

   public function DiscussionsController_Rules_Create($Sender, $Args) {
      $Sender->Render();
   }
   public function DiscussionsController_Toolbox_Create($Sender, $Args) {
      $Sender->Render();
   }

}