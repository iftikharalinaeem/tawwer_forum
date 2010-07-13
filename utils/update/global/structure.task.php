<?php

class StructureTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->Structure = FALSE;
      
      do {
         $StructureFile = TaskList::Input("Enter the structure file location for selected clients, or 'no' to skip structure", "Structure File", "structure.sql");
         $StructureFilePath = sprintf('/srv/www/update/%s',$StructureFile);
      } while (strtolower($StructureFile) != 'no' && !file_exists($StructureFilePath));
      if (strtolower($StructureFile) != 'no') {
         $this->Structure = $StructureFilePath;
      }
   }
   
   protected function Run() {
      // No structure, no run
      if ($this->Structure === FALSE) return;
      
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      TaskList::Event("Running structure file against client database '{$DatabaseName}'");
      if (!LAME) exec(sprintf("mysql -u%s --password=%s -h %s '%s' < %s",DATABASE_USER, DATABASE_PASSWORD, DATABASE_HOST, $DatabaseName, $this->Structure));
   }

}

