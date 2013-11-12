<?php if(!defined('APPLICATION')) die();

$PluginInfo['readless'] = array(
   'Name' => 'Read Less',
   'Description' => 'Truncate posts in a discussion to a given max pixel height, but allow the full text to be read by clicking the Read More button.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/readless',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class ReadLess extends Gdn_Plugin {

   protected $max_height;
   protected $max_height_default = 200;

   public function __construct() {
      parent::__construct();
      
      $max_height = intval(C('Plugins.readless.maxheight', $this->max_height_default));
      $this->max_height = (is_int($max_height)) 
              ? $max_height
              : $this->max_height_default;
   }
   
   public function Base_Render_Before($sender) {
      $c = Gdn::Controller();
      $c->AddDefinition('readlessMaxHeight', $this->max_height);
      $sender->AddCssFile('readless.css', 'plugins/readless');
      $c->AddJsFile('readless.js', 'plugins/readless');
   }
   
   /**
    *
    * @param SettingsController $sender
    * @param array $args
    */
   public function SettingsController_Readless_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      $cf->Initialize(array(
          'Plugins.readless.maxheight' => array('LabelCode' => 'Max Height', 'Control' => 'TextBox', 'Description' => 'Set the max pixel height of each post in a discusion. When text goes beyond this limit, a "Read More" button will b displayed to allow the text to be expanded.')
      ));

      $sender->AddSideMenu();
      $sender->SetData('Title', T('Read Less Settings'));
      $cf->RenderAll();
   }
   
   public function Setup() {
      TouchConfig('Plugins.readless.maxheight', $this->max_height_default); 
   }
}
