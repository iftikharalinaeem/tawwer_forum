<?php if (!defined('APPLICATION')) exit();

class CarTalkThemeHooks extends Gdn_Plugin {
   public function Base_Render_Before($Sender, $Args) {
      $this->AddAssets();
   }
   public function DiscussionsController_Rules_Create($Sender, $Args) {
      $Sender->Render();
   }
   public function DiscussionsController_Toolbox_Create($Sender, $Args) {
      $Sender->Render();
   }

   public function SearchController_Index_Create($Sender, $Args) {
      $Sender->Render();
   }

   public function AddAssets() {
      $SearchBox = '<!-- Searchbox -->
         <form action="'.Url('/search').'" id="cse-search-box">
         <div>
         <input type="hidden" name="cx" value="partner-pub-7133054861616181:2yk2yg-lkla" />
         <input type="hidden" name="cof" value="FORID:11" />
         <input type="hidden" name="ie" value="ISO-8859-1" />
         <input type="text" class="InputBox" name="q" size="31" id="cse_text" />
         <input type="submit" class="Button" name="sa" value="Search" />
         </div>
         </form>
         <script type="text/javascript" src="http://www.google.com/cse/brand?form=cse-search-box&lang=en"></script>
         <!-- Endsearchbox -->';
      Gdn::Controller()->AddAsset('Panel', $SearchBox, 'SearchBox');

      $PocketText = PocketsPlugin::PocketString('Toolbox');
      Gdn::Controller()->AddAsset('Panel', $PocketText, 'Toolbox');
   }

}