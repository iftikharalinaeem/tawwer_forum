<?php

class PluginsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->ReallyRun = FALSE;
   }
   
   public function Init($RootPath = NULL) {
   
      $ReallyRun = TaskList::Question("Run all default plugin enable commands?", "Enable default plugins", array('yes','no','exit'), 'no');
      if ($ReallyRun == 'no') return;
      if ($ReallyRun == 'exit') exit();
      
      $this->ReallyRun = TRUE;
   }
   
   protected function Run() {
      if ($this->ReallyRun !== TRUE) return;
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run default plugin enable commands for {$this->ClientFolder}?","Enable default plugins?",array('yes','no','exit'),'no');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      // Enable plugins
      $this->SaveToConfig('EnabledPlugins.HtmLawed','HtmLawed');
      $this->SaveToConfig('EnabledPlugins.GettingStartedHosting','GettingStartedHosting');
      $this->SaveToConfig('EnabledPlugins.CustomTheme','CustomTheme');
      $this->SaveToConfig('EnabledPlugins.Gravatar','Gravatar');
      $this->SaveToConfig('EnabledPlugins.embedvanilla','embedvanilla');
      $this->SaveToConfig('EnabledPlugins.Facebook','Facebook');
      $this->SaveToConfig('EnabledPlugins.Twitter','Twitter');
      $this->SaveToConfig('EnabledPlugins.GoogleSignIn','GoogleSignIn');
      $this->SaveToConfig('EnabledPlugins.OpenID','OpenID');

      
      $this->SaveToConfig('EnabledPlugins.vfoptions','vfoptions');
            
      // Politely enable plugins with structures
      $this->EnablePlugin('Statistics');
   }

}

