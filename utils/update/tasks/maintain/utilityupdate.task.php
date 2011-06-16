<?php

class UtilityUpdateTask extends Task {
   
   protected $Utility = FALSE;
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init() {
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Run utility/update as part of this update?","Run utility/update?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      $this->Utility = TRUE;
      
      $ReportFailures = TaskList::GetConsoleOption('report-failures', FALSE);
      $this->ReportFailures = $ReportFailures;
      
      $this->TaskList->RequireTargetDatabase = TRUE;
      $this->TaskList->RequireValid = TRUE;
   }
   
   protected function Run() {
      $ClientFolder = $this->ClientFolder();
      
      // No structure, no run
      if ($this->Utility === FALSE) return;
      //if ($this->Cache('Updated') !== TRUE) return;
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really run utility/update for {$ClientFolder}?","Run update?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      TaskList::Event("Running utility/update...");
      if (!LAME) {
         $UtilityUpdate = FALSE;
         try {
            mysql_query("DELETE FROM GDN_UserMeta WHERE Name='Garden.Update.LastTimestamp' AND UserID=0",$this->Database());
            mysql_query("DELETE FROM GDN_UserMeta WHERE Name='Garden.Update.Count' AND UserID=0",$this->Database());
            
            $UtilityUpdate = $this->Client->Request(array(
               'URL'       => 'utility/update.json',
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
            try {
               $Email = new Email($this->Client);
               $Email->To('tim@vanillaforums.com', 'Tim Gunter')
                  ->From('runner@vanillaforums.com','VFCom Runner')
                  ->Subject("{$ClientFolder} update failed")
                  ->Message("Automatic remote utility/update failed.\n\n{$UtilityUpdate}")
                  ->Send();
            } catch (Exception $e) {}
         }
      }
   }

}

