<?php

class UtilityUpdateTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->Utility = FALSE;
      
      $Proceed = TaskList::Question("Run utility/update as part of this update?","Run utility/update?",array('yes','no'),'no');
      if ($Proceed == 'no') return;
      $this->Utility = TRUE;
   }
   
   protected function Run() {
      
      // No structure, no run
      if ($this->Utility === FALSE) return;
      if ($this->Cache('Updated') !== TRUE) return;
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run utility/update for {$this->ClientFolder}?","Run update?",array('yes','no','exit'),'no');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      TaskList::Event("Running utility/update...", NOBREAK);
      if (!LAME) {
         $UtilityUpdate = FALSE;
         try {
            $UtilityUpdate = $this->Request('utility/update');
         } catch (Exception $e) {}
         
         if ($UtilityUpdate == 'Success') {
            TaskList::Event('success');
            return;
         }
         TaskList::Event('failed');
      }
   }

}

