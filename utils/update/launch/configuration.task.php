<?php

class ConfigurationTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {

      // Remove CustomCSS from enabled plugins
      TaskList::Event("Removing CustomCSS plugin from config");
      $this->RemoveFromConfig('EnabledPlugins.CustomCSS');
      
      // Add VanillaUrl to configs
      TaskList::Event("Adding VanillaUrl to config");
      $this->SaveToConfig('Garden.VanillaUrl', 'http://vanillaforums.com');
   }

}

