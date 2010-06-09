<?php

class ConfigurationTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      TaskList::Event("Removing CustomCSS plugin from config");
      // Remove CustomCSS from enabled plugins
      $this->RemoveFromConfig('EnabledPlugins.CustomCSS');      
   }

}

