<?php if(!defined('APPLICATION')) die();

class Colors extends Gdn_Plugin {
   
   /**
    * Load CSS into head for editor
    */
   public function assetModel_styleCss_handler($sender) {   
      $sender->addCssFile('spectrum.css', 'plugins/colors');
   }

   
   /**
    * Placed these components everywhere due to some Web sites loading the 
    * editor in some areas where the values were not yet injected into HTML.
    */
   public function base_render_before($sender) {
      $c = Gdn::controller();
      // Load JavaScript
      $c->addJsFile('spectrum.js', 'plugins/colors');
   }
   
   /**
    * 
    * @param SettingsController $sender
    * @param array $args
    */
   public function settingsController_colors_create($sender, $args) {
      $sender->permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      //$Formats = array_combine($this->Formats, $this->Formats);
      
      $cf->initialize([
          'Plugins.colors.header' => ['LabelCode' => 'Header', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard header.'],
          'Plugins.colors.body' => ['LabelCode' => 'Body', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard body.'],
          'Plugins.colors.panel' => ['LabelCode' => 'Panel', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard panel.'],
          'Plugins.colors.footer' => ['LabelCode' => 'Footer', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard footer.']
      ]);
      
      // Add some JS and CSS to blur out option when Wysiwyg not chosen.
      $c = Gdn::controller();
      $c->addJsFile('colors.js', 'plugins/colors');
      $sender->addCssFile('spectrum.css', 'plugins/colors');
      
      $sender->addSideMenu();
      $sender->setData('Title', t('Colors Settings'));
      $cf->renderAll();
      //$Sender->Cf = $Cf;
      //$Sender->render('settings', '', 'plugins/colors');
   }   
   
   
   public function base_getAppSettingsMenuItems_handler($sender) {
      $menu = $sender->EventArguments['SideMenu'];
      $menu->addItem('Appearance', t('Appearance'));
      $menu->addLink('Appearance', 'Colors Settings', 'settings/colors', 'Garden.Settings.Manage');
   }

	public function setup() {        

	}
   
   public function onDisable() {
	}

   public function cleanUp() {
	}
}
