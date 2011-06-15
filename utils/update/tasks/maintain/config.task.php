<?php

class ConfigTask extends Task {
   
   protected $ReallyRun = FALSE;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init($RootPath = NULL) {
   
      if (TaskList::Cautious()) {
         $ReallyRun = TaskList::Question("Apply all default config settings?", "Enable default config", array('yes','no','exit'), 'yes');
         if ($ReallyRun == 'no') return;
         if ($ReallyRun == 'exit') exit();
      }
      
      $this->ReallyRun = TRUE;
   }
   
   protected function Run() {
      if ($this->ReallyRun !== TRUE) return;
      $ClientFolder = $this->ClientFolder();
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really apply default config settings for {$ClientFolder}?","Enable default config?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      // Central database
      $this->Client->SaveToConfig('VanillaForums.Database.Host',     'vfdb1.vanillaforums.com');
      $this->Client->SaveToConfig('VanillaForums.Database.User',     'vfcom');
      $this->Client->SaveToConfig('VanillaForums.Database.Password', 'eKUs86bLcf');
      $this->Client->SaveToConfig('VanillaForums.Database.Name',     'vfcom');

   }

}




