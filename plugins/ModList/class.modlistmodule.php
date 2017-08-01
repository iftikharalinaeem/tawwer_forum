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
      $this->Style = c('Plugins.ModList.Style', ModListPlugin::DEFAULT_STYLE);
   }
   
   /**
    * Get list of moderators for category
    * 
    * @return void
    */
   public function getData() {
      if (is_null($this->CategoryModerators)) {
         
         if (is_null($this->CategoryID)) {

            // Manually assigned CategoryID?
            if (!is_null($this->CategoryID)) {

               $category = CategoryModel::categories($this->CategoryID);

            // Lookup CategoryID
            } else {

               $category = Gdn::controller()->data('Category');
               if (!$category) {
                  $categoryID = Gdn::controller()->data('CategoryID');
                  if ($categoryID)
                     $category = CategoryModel::categories($categoryID);
               }

            }

            // No way to get the category? Leave.
            if (!$category)
               return;

            $this->CategoryID = getValue('CategoryID', $category);
         }
         
         // Grab the moderator list.
         $this->CategoryModerators = ModListPlugin::instance()->moderators($this->CategoryID);
         
      }
      
      $this->setData('Moderators', $this->CategoryModerators);
   }
   
   public function toString() {
      $this->getData();
      
      if ($this->data('Moderators', NULL) === NULL || !sizeof($this->data('Moderators')))
         return '';
      
      return parent::toString();
   }
}
