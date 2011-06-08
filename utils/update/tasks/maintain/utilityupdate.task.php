<?php

class UtilityUpdateTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->Utility = FALSE;
      
      $Proceed = TaskList::Question("Run utility/update as part of this update?","Run utility/update?",array('yes','no'),'yes');
      if ($Proceed == 'no') return;
      $this->Utility = TRUE;
      
      $ReportFailures = TaskList::GetConsoleOption('report-failures', FALSE);
      $this->ReportFailures = $ReportFailures;
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
      TaskList::Event("Running utility/update...");
      if (!LAME) {
         $UtilityUpdate = FALSE;
         try {
            $UtilityUpdate = $this->Request(array(
               'URL'       => 'utility/alive.json',
               'Timeout'   => 0,
               'Recycle'   => TRUE
            ));
         } catch (Exception $e) {
            TaskList::FatalError($e->getMessage());
         }
         
         $JsonResponse = @json_decode($UtilityUpdate);
         
         if ($JsonResponse !== FALSE) {
            if (GetValue('Success', $JsonResponse, FALSE) === TRUE) {
               TaskList::MinorEvent('Update success');
               return;
            }
         }
         
         TaskList::MinorEvent('Update failed');
         
         if ($this->ReportFailures) {
            $Email = new Email($this);
            $Email->To('tim@vanillaforums.com', 'Tim Gunter')
               ->From('runner@vanillaforums.com','VFCom Runner')
               ->Subject("{$this->ClientFolder} update failed")
               ->Message("Automatic remote utility/update failed.\n\n{$UtilityUpdate}")
               ->Send();
         }
      }
   }

}

