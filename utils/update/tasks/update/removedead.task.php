<?php

class RemoveDeadTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->ReallyRun = FALSE;
   }
   
   public function Init($RootPath = NULL) {
   
      $ReallyRun = TaskList::Question("Prune old clients with missing databases?", "Prune clients", array('yes','no','exit'), 'yes');
      if ($ReallyRun == 'no') return;
      if ($ReallyRun == 'exit') exit();
      
      $this->ReallyRun = TRUE;
   }
   
   protected function Run() {
      if (!$this->ReallyRun) return FALSE;
      if ($ClientInfo && sizeof($ClientInfo))
         return FALSE;
      
      if (TaskList::Cautious()) {
         $ReallyPrune = TaskList::Question("Really delete forum {$this->ClientFolder}?", "Delete", array('yes','no','exit'), 'yes');
         if ($ReallyPrune == 'no') return;
         if ($ReallyPrune == 'exit') exit();
      }
      TaskList::RemoveFolder($this->ClientFolder);
   }

}

