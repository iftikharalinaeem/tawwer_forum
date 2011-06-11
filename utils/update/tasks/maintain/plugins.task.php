<?php

class PluginsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->ReallyRun = FALSE;
   }
   
   public function Init($RootPath = NULL) {
   
      $ReallyRun = TaskList::Question("Run all default plugin enable commands?", "Enable default plugins", array('yes','no','exit'), 'yes');
      if ($ReallyRun == 'no') return;
      if ($ReallyRun == 'exit') exit();
      
      $this->ReallyRun = TRUE;
   }
   
   protected function Run() {
      if ($this->ReallyRun !== TRUE) return;
      $ClientFolder = $this->ClientFolder();
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run default plugin enable commands for {$ClientFolder}?","Enable default plugins?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      // Enable plugins
      $this->Client->SaveToConfig('EnabledPlugins.HtmLawed', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.GettingStartedHosting', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.CustomTheme', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.Gravatar', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.embedvanilla', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.Facebook', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.Twitter', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.GoogleSignIn', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.OpenID', TRUE);
      $this->Client->SaveToConfig('EnabledPlugins.vfspoof', TRUE);
                  
      // Politely enable plugins with structures
      //$this->EnablePlugin('Statistics');
      
      // We do this to ensure that vfoptions goes to the end. Dirty hacks ;)
      $this->Client->RemoveFromConfig('EnabledPlugins.vfoptions');
      $this->Client->SaveToConfig('EnabledPlugins.vfoptions', TRUE);

   }

}

