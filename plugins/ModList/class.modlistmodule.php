<?php if (!defined('APPLICATION')) exit;

class ModListModule extends Gdn_Module {
   
   /**
    * List of moderators for this context
    * @var array
    */
   protected $CategoryModerators;
   
   /**
    * Render style. 'pictures' or 'links'
    * @var string
    */
   public $Style;
   
   /**
    * Category ID
    * @var integer
    */
   public $CategoryID;
   
   public function __construct($sender = '') {
      parent::__construct($sender);
      
      $this->_ApplicationFolder = 'plugins/ModList';
      $this->CategoryModerators = NULL;
      $this->CategoryID = NULL;
      $this->Style = C('Plugins.ModList.Style', ModListPlugin::DEFAULT_STYLE);
   }
   
   /**
    * Get list of moderators for category
    * 
    * @return void
    */
   public function GetData() {
      if (is_null($this->CategoryModerators)) {
         
         if (is_null($this->CategoryID)) {

            // Manually assigned CategoryID?
            if (!is_null($this->CategoryID)) {

               $category = CategoryModel::Categories($this->CategoryID);

            // Lookup CategoryID
            } else {

               $category = Gdn::Controller()->Data('Category');
               if (!$category) {
                  $categoryID = Gdn::Controller()->Data('CategoryID');
                  if ($categoryID)
                     $category = CategoryModel::Categories($categoryID);
               }

            }

            // No way to get the category? Leave.
            if (!$category)
               return;

            $this->CategoryID = GetValue('CategoryID', $category);
         }
         
         // Grab the moderator list.
         $this->CategoryModerators = ModListPlugin::Instance()->Moderators($this->CategoryID);
         
      }
      
      $this->SetData('Moderators', $this->CategoryModerators);
   }
   
   public function ToString() {
      $this->GetData();
      
      if ($this->Data('Moderators', NULL) === NULL || !sizeof($this->Data('Moderators')))
         return '';
      
      return parent::ToString();
   }
}
