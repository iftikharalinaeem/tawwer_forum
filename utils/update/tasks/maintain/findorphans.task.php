<?php

class FindOrphansTask extends Task {
   
   protected $Orphans;
   protected $Dead;
   
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
      
      $this->Orphans = array();
      $this->Dead = array();
      
      $this->ReallyRun = TRUE;
      
      $this->TaskList->RequireValid = FALSE;
      $this->TaskList->IgnoreSymlinks = TRUE;
   }
   
   protected function Run() {
      if (!$this->ReallyRun) return FALSE;
      
      $ClientInfo = $this->ClientInfo();
      try {
         $ClientDBName = $this->ClientInfo('DatabaseName');
         $ClientConfigDBHost = $this->Client->C('Database.Host');
         if ($ClientConfigDBHost == 'dbhost') {
            if (!empty($ClientDBName)) {
               try {
                  $RootHost = $this->TaskList->C('Database.Host');
                  $RootUser = $this->TaskList->C('Database.User');
                  $RootPass = $this->TaskList->C('Database.Pass');
                  
                  $this->TaskList->Database($RootHost, $RootUser, $RootPass, $ClientDBName);
                  throw new OrphanException("Missing Database.Host from config, defaults work OK");
               } catch (Exception $e) {}
            }
            
            throw new DeadException('Missing Database.Host from config file, and no Site entry');
         }
         
         $ClientConfigDBName = $this->Client->C('Database.Name');
         if ($ClientDBName != $ClientConfigDBName) {
            try {
               $Database = $this->Database();
            } catch (Exception $e) {
               /*
                * Unable to connect to the database
                */
               throw new DeadException($e->getMessage());
            }
            
            /*
             * Connected to database
             */
            
            if (empty($ClientInfo) || !sizeof($ClientInfo)) {
               throw new OrphanException("No Site table entry");
            }
            
            if (empty($ClientDBName)) {
               throw new OrphanException("No DatabaseName in Site table. Should be '{$ClientConfigDBName}'");
            }
            
            if ($ClientDBName != $ClientConfigDBName) {
               throw new OrphanException("Wrong database name in Site table. '{$ClientDBName}' should be '{$ClientConfigDBName}'");
            }
            
            $NumCIEntries = sizeof($ClientInfo);
            throw new OrphanException("Unknown reason. ClientInfo:{$NumCIEntries} site({$ClientDBName}) config({$ClientConfigDBName}) - connected ok");
            // Otherwise, DB connected, so update site table
         }
         
         $SiteID = $this->Client->C('VanillaForums.SiteID', NULL);
         if (empty($SiteID)) {
            throw new OrphanException("Config file missing VanillaForums.* entries");
         }
         
      } catch (OrphanException $e) {
         $Reason = $e->getMessage();
         TaskList::Event("Orphan detected: {$Reason}");
         $this->LogOrphan($Reason);

      } catch (DeadException $e) {
         $Reason = $e->getMessage();
         TaskList::Event("Dead detected: {$Reason}");
         $this->LogDead($Reason);
         
         $ClientFolder = $this->ClientFolder();
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
      $this->Orphans[] = array($this->ClientFolder(), $Reason);
   }
   
   protected function LogDead($Reason) {
      $this->Dead[] = array($this->ClientFolder(), $Reason);
   }
   
   public function Shutdown() {
      $OrphanMessage = "";
      $NumOrphans = sizeof($this->Orphans);
      $NumOrphanText = "Orphan forums: {$NumOrphans}";
      TaskList::MajorEvent($NumOrphanText);
      $OrphanMessage .= "{$NumOrphanText}\n";
      foreach ($this->Orphans as $Forum) {
         $ForumLine = "http://{$Forum[0]} => {$Forum[1]}";
         TaskList::Event($ForumLine);
         $OrphanMessage .= "{$ForumLine}\n";
      }
      
      $OrphanMessage .= "\n";
      
      TaskList::MajorEvent("");
      $NumDead = sizeof($this->Dead);
      $NumDeadText = "Dead forums: {$NumDead}";
      TaskList::MajorEvent($NumDeadText);
      $OrphanMessage .= "{$NumDeadText}\n";
      foreach ($this->Dead as $Forum) {
         $ForumLine = "http://{$Forum[0]} => {$Forum[1]}";
         TaskList::Event($ForumLine);
         $OrphanMessage .= "{$ForumLine}\n";
      }
      
      if ($NumOrphans || $NumDead) {
         try {
            $Email = new Email($this->Client);
            $Email->To('tim@vanillaforums.com', 'Tim Gunter')
               ->From('runner@vanillaforums.com','VFCom Runner')
               ->Subject("VFCom Orphans Found")
               ->Message($OrphanMessage)
               ->Send();
         } catch (Exception $e) {}
      }
   }
   
}

class DeadException extends Exception {}
class OrphanException extends Exception {}
