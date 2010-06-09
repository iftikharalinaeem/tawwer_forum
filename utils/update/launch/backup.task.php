<?php

class BackupTask extends Task {

   protected $BackupPath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->BackupPath = '/srv/backup/';
   }
   
   protected function Run() {

      $BackupFolder = TaskList::CombinePaths($this->BackupPath,$this->ClientFolder);
      if (!is_dir($BackupFolder))
         mkdir($BackupFolder);
         
      // Perform filesystem backup
      $DateString = date('Y-m-d_H-i-s');
      $PharTarFile = TaskList::CombinePaths($BackupFolder, "{$this->ClientFolder}_{$DateString}.phar.tar");
      
      $Proceed = 'yes';
      if (!Phar::canWrite()) {
         TaskList::Event("Could not create backup archive {$PharTarFile}");
         $Proceed = TaskList::Question("Would you like to continue anyway? 'yes' to continue, 'no' to halt, 'skip' to skip this client:",
            "Continue?", array('yes','no','skip'), 'yes');
            
         if ($Proceed == 'skip') return;
         if ($Proceed == 'no') exit();
      } else {
      
         // Create Phar archive
         $PharArchive = new Phar($PharTarFile);
         
         $Compression = Phar::canCompress(Phar::GZ) ? Phar::GZ : Phar::NONE;
         $PharData = $PharArchive->convertToExecutable(Phar::TAR, $Compression);
         
         // Add directory to archive
         $PharData->startBuffering();
         $PharData->buildFromDirectory($this->ClientRoot);
         $PharData->stopBuffering();      
      
      }
      
      // Perform Database backup
      if ($Proceed == 'yes') {
      
      
      }
      
      die("done test\n");
   }
}

