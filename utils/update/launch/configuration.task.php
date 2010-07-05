<?php

class ConfigurationTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {

/*
      if (!$this->CheckConfig("EnabledPlugins.CustomTheme")) {
         $Proceed = TaskList::Question("This customer does not have CustomTheme.","Try to fix?",array('yes','no','end'),'yes');
         
         if ($Proceed == 'no') return;
         if ($Proceed == 'end') exit();
      } else {
         return;
      }
*/

      // Remove CustomCSS from enabled plugins
      TaskList::Event("Removing EnabledBplugins.CustomCSS plugin from config");
      $this->RemoveFromConfig('EnabledPlugins.CustomCSS');
      
      // Add VanillaUrl to configs
      TaskList::Event("Adding Garden.VanillaUrl to config");
      $this->SaveToConfig('Garden.VanillaUrl', 'http://vanillaforums.com');
      
      // Enable CustomTheme for everyone
      TaskList::Event("Adding EnabledPlugins.CustomTheme to config");
      $this->SaveToConfig('EnabledPlugins.CustomTheme', 'CustomTheme');
      
      // Add Bonk type errors to all forums
      TaskList::Event("Adding Bonk MasterView to config");
      $this->SaveToConfig('Garden.Errors.MasterView', 'error.master.php');
      
   }

}

