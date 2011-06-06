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
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run default plugin enable commands for {$this->ClientFolder}?","Enable default plugins?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      // Enable plugins
      $this->SaveToConfig('EnabledPlugins.HtmLawed', TRUE);
      $this->SaveToConfig('EnabledPlugins.GettingStartedHosting', TRUE);
      $this->SaveToConfig('EnabledPlugins.CustomTheme', TRUE);
      $this->SaveToConfig('EnabledPlugins.Gravatar', TRUE);
      $this->SaveToConfig('EnabledPlugins.embedvanilla', TRUE);
      $this->SaveToConfig('EnabledPlugins.Facebook', TRUE);
      $this->SaveToConfig('EnabledPlugins.Twitter', TRUE);
      $this->SaveToConfig('EnabledPlugins.GoogleSignIn', TRUE);
      $this->SaveToConfig('EnabledPlugins.OpenID', TRUE);
      $this->SaveToConfig('EnabledPlugins.vfspoof', TRUE);
                  
      // Politely enable plugins with structures
      //$this->EnablePlugin('Statistics');
      
      // We do this to ensure that vfoptions goes to the end. Dirty hacks ;)
      $this->RemoveFromConfig('EnabledPlugins.vfoptions');
      $this->SaveToConfig('EnabledPlugins.vfoptions', TRUE);

   }

}

