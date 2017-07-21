<?php if(!defined('APPLICATION')) die();

class Colors extends Gdn_Plugin {
   
   /**
    * Load CSS into head for editor
    */
   public function AssetModel_StyleCss_Handler($sender) {   
      $sender->AddCssFile('spectrum.css', 'plugins/colors');
   }

   
   /**
    * Placed these components everywhere due to some Web sites loading the 
    * editor in some areas where the values were not yet injected into HTML.
    */
   public function Base_Render_Before($sender) {
      $c = Gdn::Controller();
      // Load JavaScript
      $c->AddJsFile('spectrum.js', 'plugins/colors');
   }
   
   /**
    * 
    * @param SettingsController $sender
    * @param array $args
    */
   public function SettingsController_Colors_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      //$Formats = array_combine($this->Formats, $this->Formats);
      
      $cf->Initialize([
          'Plugins.colors.header' => ['LabelCode' => 'Header', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard header.'],
          'Plugins.colors.body' => ['LabelCode' => 'Body', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard body.'],
          'Plugins.colors.panel' => ['LabelCode' => 'Panel', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard panel.'],
          'Plugins.colors.footer' => ['LabelCode' => 'Footer', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard footer.']
      ]);
      
      // Add some JS and CSS to blur out option when Wysiwyg not chosen.
      $c = Gdn::Controller();
      $c->AddJsFile('colors.js', 'plugins/colors');
      $sender->AddCssFile('spectrum.css', 'plugins/colors');
      
      $sender->AddSideMenu();
      $sender->SetData('Title', T('Colors Settings'));
      $cf->RenderAll();
      //$Sender->Cf = $Cf;
      //$Sender->Render('settings', '', 'plugins/colors');
   }   
   
   
   public function Base_GetAppSettingsMenuItems_Handler($sender) {
      $menu = $sender->EventArguments['SideMenu'];
      $menu->AddItem('Appearance', T('Appearance'));
      $menu->AddLink('Appearance', 'Colors Settings', 'settings/colors', 'Garden.Settings.Manage');
   }

	public function Setup() {        

	}
   
   public function OnDisable() {
	}

   public function CleanUp() {
	}
}
