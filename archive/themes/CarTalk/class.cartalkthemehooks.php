<?php if (!defined('APPLICATION')) exit();

class CarTalkThemeHooks extends Gdn_Plugin {
   /**
    * @param Gdn_Controller $Sender
    * @param <type> $Args
    */
   public function Base_Render_Before($Sender, $Args) {
      $Controller = strtolower(StringEndsWith($Sender->ControllerName, 'Controller', TRUE, TRUE));

      if (in_array($Controller, array('discussion', 'discussions', 'profile', 'categories', 'activity')))
         $this->AddAssets();
   }
   public function DiscussionsController_Rules_Create($Sender, $Args = array()) {
      $Sender->Render();
   }
   public function DiscussionsController_Toolbox_Create($Sender, $Args = array()) {
      $Sender->Render();
   }
   
   protected static $_InPopular = FALSE;
   
   /**
    *
    * @param DiscussionsController $Sender
    * @param array $Args 
    */
   public function DiscussionsController_Popular_Create($Sender, $Args = array()) {
      $Sender->Title('Popular Discussions');
      $Sender->View = 'Index';
      $Sender->SetData('_PagerUrl', 'discussions/popular/{Page}');
      self::$_InPopular = TRUE;
      $Sender->Index(GetValue(0, $Args));
      self::$_InPopular = FALSE;
   }
   
   public function DiscussionModel_BeforeGetCount_Handler($Sender, $Args) {
      if (self::$_InPopular)
         $Sender->SQL->Where(1, 0, FALSE, FALSE);
   }
   
   /**
    *
    * @param DiscussionModel $Sender
    * @param type $Args
    * @return type 
    */
   public function DiscussionModel_BeforeGet_Handler($Sender, $Args) {
      if (!self::$_InPopular)
         return;
      
      $this->_PopularWhere($Sender->SQL);
      $Sender->SQL->OrderBy('CountComments', 'desc');
   }
   
   /**
    *
    * @param Gdn_SQLDriver $SQL 
    */
   protected function _PopularWhere($SQL) {
      // Popular discussions must be newer than two weeks old.
      $BaseDate = time();
      $BaseDate -= 14 * 24 * 60 * 60;
      $SQL->Where('d.DateLastComment >=', Gdn_Format::ToDateTime($BaseDate))
         ->Where('d.Announce', 0);
   }

   public function UtilityController_Serve_Create($Sender, $Args = array()) {
      $Filename = GetValue(0, $Args);
      $Path = dirname(__FILE__).'/'.$Filename;
      if (file_exists($Path)) {
         readfile($Path);
         die();
      } else {
         throw NotFoundException();
      }

   }

   public function SearchController_Index_Create($Sender, $Args = array()) {
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