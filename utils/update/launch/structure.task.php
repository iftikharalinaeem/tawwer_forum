<?php

class StructureTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $SqlFile = '/srv/www/misc/utils/update/20100609_vfcom_structure.sql';
   
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      TaskList::Event("Running structure file against client database '{$DatabaseName}'");
      if (!LAME) exec(sprintf("mysql -u%s --password=%s -h %s %s < %s",DATABASE_USER, DATABASE_PASSWORD, DATABASE_HOST, $DatabaseName, $SqlFile));
   }

}

