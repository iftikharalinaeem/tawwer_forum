<?php

class BackupTask extends Task {

   protected $BackupPath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $BackupDateString = date('Y-m-d');
      $this->BackupPath = "/srv/backups/upgrade-{$BackupDateString}/vhosts";
      
      $FolderParts = explode('/',$this->BackupPath);
      for ($i = 0; $i < sizeof($FolderParts); $i++) {
         $TryFolder = '/'.implode('/',array_slice($FolderParts, 0, $i+1));

         if (!is_dir($TryFolder))
            @mkdir($TryFolder);
         else
            continue;
            
         if (!is_dir($TryFolder)) {
            TaskList::Event("Could not access or create the primary backup folder '{$this->BackupPath}'. Failed at '{$TryFolder}'.");
            $Proceed = TaskList::Question("Would you like to continue anyway? 'yes' to continue, 'no' to halt the entire script:",
               "Continue?", array('yes','no'), 'no');
               
            if ($Proceed == 'skip') return;
            if ($Proceed == 'no') exit();
         }
      }
      
   }
   
   protected function Run() {

      $BackupFolder = TaskList::CombinePaths($this->BackupPath,$this->ClientFolder);
      if (!is_dir($BackupFolder))
         @mkdir($BackupFolder);
         
      if (!is_dir($BackupFolder)) {
         TaskList::Event("Could not access or create the client's backup folder '{$BackupFolder}'");
         $Proceed = TaskList::Question("Would you like to continue anyway? 'yes' to continue, 'no' to halt the entire script, 'skip' to skip just this client:",
            "Continue?", array('yes','no','skip'), 'no');
            
         if ($Proceed == 'skip') return;
         if ($Proceed == 'no') exit();
      }
      
      // Datestring to tag backup files
      $DateString = date('Y-m-d_H-i-s');
      
      // Perform filesystem backup
      $BackupTarFile = TaskList::CombinePaths($BackupFolder, "{$this->ClientFolder}_{$DateString}.tgz");
      
      $Proceed = 'yes';
      if (!is_writable($BackupFolder)) {
         TaskList::Event("Could not create backup archive {$BackupTarFile}");
         $Proceed = TaskList::Question("Would you like to continue anyway? 'yes' to continue, 'no' to halt entire script, 'skip' to skip just this client:",
            "Continue?", array('yes','no','skip'), 'no');
            
         if ($Proceed == 'skip') return;
         if ($Proceed == 'no') exit();
      } else {
      
         // Create TGZ archive
         TaskList::Event("Backing up client vhost data to {$BackupTarFile}...", TaskList::NOBREAK);
         if (!LAME) { ob_start(); $trash = shell_exec("tar -czf {$BackupTarFile} {$this->ClientRoot}"); ob_end_clean(); }
         TaskList::MajorEvent("done");
      
      }
      
      // Perform Database backup
      if ($Proceed == 'yes') {
         print_r($this->ClientInfo);
         $DatabaseName = $this->ClientInfo['DatabaseName'];
         $BackupSQLFile = TaskList::CombinePaths($BackupFolder, "database_".strtolower($DatabaseName)."_{$DateString}.%s");
         
         $RawSQLFile = sprintf($BackupSQLFile,'sql');
         $CompressedSQLFile = sprintf($BackupSQLFile,'tgz');
         
         TaskList::Event("Backing up client database to {$RawSQLFile}...", TaskList::NOBREAK);
         if (!LAME) $trash = shell_exec("mysqldump --extended-insert --no-create-db -u".DATABASE_USER." --password=".DATABASE_PASSWORD." -h ".DATABASE_HOST." {$DatabaseName} > {$RawSQLFile}");
         TaskList::MajorEvent("done");
         
         if (file_exists($RawSQLFile)) {
            TaskList::Event("Compressing...", TaskList::NOBREAK);
            if (!LAME) { ob_start(); $trash = shell_exec("tar --remove-files -zcf {$CompressedSQLFile} {$RawSQLFile}"); ob_end_clean(); }
            TaskList::MajorEvent("done");
         } else {
            if (!LAME) TaskList::Event("Could not create backup of sql data {$RawSQLFile}");
         }
         
      }
   }
}

