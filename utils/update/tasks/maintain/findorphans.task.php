<?php

class FindOrphansTask extends Task {
   
   protected $ReallyRun;
   protected $LogForums;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->ReallyRun = FALSE;
   }
   
   public function Init($RootPath = NULL) {
   
      $ReallyRun = TaskList::Question("Find orphan clients with missing databases?", "Find orphans", array('yes','no','exit'), 'yes');
      if ($ReallyRun == 'no') return;
      if ($ReallyRun == 'exit') exit();
      
      $this->LogForums = array();
      $this->ReallyRun = TRUE;
      
      $this->TaskList->RequireValid = FALSE;
      $this->TaskList->IgnoreSymlinks = TRUE;
   }
   
   protected function Run() {
      if (!$this->ReallyRun) return FALSE;
      
      try {
         $ClientConfigDBHost = $this->Client->C('Database.Host');
         if ($ClientConfigDBHost == 'dbhost')
            throw new OrphanException('Undefined Database.Name');
         
         $ClientDBName = $this->ClientInfo('DatabaseName');
         $ClientConfigDBName = $this->Client->C('Database.Name');
         if ($ClientDBName != $ClientConfigDBName) {
            try {
               $Database = $this->Database();
            } catch (Exception $e) {
               throw new OrphanException("DB Name mismatch: s({$ClientDBName}) != c({$ClientConfigDBName})");
            }
            
            // Otherwise, DB connected, so update site table
         }
         
         $ClientFolder = $this->ClientFolder();
      } catch (OrphanException $e) {
         $Reason = $e->getMessage();
         TaskList::Event("Orphan detected: {$Reason}");
         $this->LogOrphan($Reason);
//         if (TaskList::Cautious()) {
//            $ReallyPrune = TaskList::Question("Really delete forum {$ClientFolder}?", "Delete", array('yes','no','exit'), 'yes');
//            if ($ReallyPrune == 'no') return;
//            if ($ReallyPrune == 'exit') exit();
//         }
//
//         $AbsClientFolder = TaskList::CombinePaths($this->ClientRoot(), $ClientFolder);
//         TaskList::RemoveFolder($AbsClientFolder);
      }
   }
   
   protected function LogOrphan($Reason) {
      $this->LogForums[] = array($this->ClientFolder(), $Reason);
   }
   
   public function Shutdown() {
      $NumOrphans = sizeof($this->LogForums);
      TaskList::MajorEvent("Orphan forums: {$NumOrphans}");
      foreach ($this->LogForums as $Forum)
         TaskList::Event("http://{$Forum[0]} => {$Forum[1]}");
   }
   
}

class OrphanException extends Exception {}
