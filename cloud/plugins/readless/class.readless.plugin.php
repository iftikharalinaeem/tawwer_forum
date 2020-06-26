<?php if(!defined('APPLICATION')) die();

class ReadLess extends Gdn_Plugin {

   protected $max_height;
   protected $max_height_default = 200;

   public function __construct() {
      parent::__construct();
      
      $max_height = intval(c('Plugins.readless.maxheight', $this->max_height_default));
      $this->max_height = (is_int($max_height)) 
              ? $max_height
              : $this->max_height_default;
   }
   
   public function base_render_before($sender) {
      $c = Gdn::controller();
      $c->addDefinition('readlessMaxHeight', $this->max_height);
      $sender->addCssFile('readless.css', 'plugins/readless');
      $c->addJsFile('readless.js', 'plugins/readless');
   }
   
   /**
    *
    * @param SettingsController $sender
    * @param array $args
    */
   public function settingsController_readless_create($sender, $args) {
      $sender->permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      $cf->initialize([
          'Plugins.readless.maxheight' => ['LabelCode' => 'Max Height', 'Control' => 'TextBox', 'Description' => 'Set the max pixel height of each post in a discusion. When text goes beyond this limit, a "Read More" button will b displayed to allow the text to be expanded.']
      ]);

      $sender->addSideMenu();
      $sender->setData('Title', t('Read Less Settings'));
      $cf->renderAll();
   }
   
   public function setup() {
      \Gdn::config()->touch('Plugins.readless.maxheight', $this->max_height_default); 
   }
}
