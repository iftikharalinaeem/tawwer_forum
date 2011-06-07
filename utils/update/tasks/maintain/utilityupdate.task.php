<?php

class UtilityUpdateTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->Utility = FALSE;
      
      $Proceed = TaskList::Question("Run utility/update as part of this update?","Run utility/update?",array('yes','no'),'yes');
      if ($Proceed == 'no') return;
      $this->Utility = TRUE;
   }
   
   protected function Run() {
      
      // No structure, no run
      if ($this->Utility === FALSE) return;
      //if ($this->Cache('Updated') !== TRUE) return;
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run utility/update for {$this->ClientFolder}?","Run update?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      TaskList::Event("Running utility/update...", TaskList::NOBREAK);
      if (!LAME) {
         $UtilityUpdate = FALSE;
         try {
            $UtilityUpdate = $this->PrivilegedExec(array(
               'URL'       => 'utility/update.json',
               'Timeout'   => 0
            ));
         } catch (Exception $e) {}
         
         $JsonResponse = @json_decode($UtilityUpdate);
         
         if ($JsonResponse !== FALSE) {
            if (GetValue('Success', $JsonResponse, FALSE) === TRUE) {
               TaskList::Event('success');
               return;
            }
         }
         
         TaskList::Event('failed');
      }
   }

}

