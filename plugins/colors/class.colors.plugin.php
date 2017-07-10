<?php if(!defined('APPLICATION')) die();

class Colors extends Gdn_Plugin {
   
   /**
    * Load CSS into head for editor
    */
   public function AssetModel_StyleCss_Handler($Sender) {   
      $Sender->AddCssFile('spectrum.css', 'plugins/colors');
   }

   
   /**
    * Placed these components everywhere due to some Web sites loading the 
    * editor in some areas where the values were not yet injected into HTML.
    */
   public function Base_Render_Before($Sender) {
      $c = Gdn::Controller();
      // Load JavaScript
      $c->AddJsFile('spectrum.js', 'plugins/colors');
   }
   
   /**
    * 
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_Colors_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Cf = new ConfigurationModule($Sender);

      //$Formats = array_combine($this->Formats, $this->Formats);
      
      $Cf->Initialize([
          'Plugins.colors.header' => ['LabelCode' => 'Header', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard header.'],
          'Plugins.colors.body' => ['LabelCode' => 'Body', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard body.'],
          'Plugins.colors.panel' => ['LabelCode' => 'Panel', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard panel.'],
          'Plugins.colors.footer' => ['LabelCode' => 'Footer', 'Control' => 'Textbox', 'Description' => 'Select a color for the dashboard footer.']
      ]);
      
      // Add some JS and CSS to blur out option when Wysiwyg not chosen.
      $c = Gdn::Controller();
      $c->AddJsFile('colors.js', 'plugins/colors');
      $Sender->AddCssFile('spectrum.css', 'plugins/colors');
      
      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Colors Settings'));
      $Cf->RenderAll();
      //$Sender->Cf = $Cf;
      //$Sender->Render('settings', '', 'plugins/colors');
   }   
   
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Appearance', T('Appearance'));
      $Menu->AddLink('Appearance', 'Colors Settings', 'settings/colors', 'Garden.Settings.Manage');
   }

	public function Setup() {        

	}
   
   public function OnDisable() {
	}

   public function CleanUp() {
	}
}
