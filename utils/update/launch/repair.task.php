<?php

class RepairTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $SqlFile = '/srv/www/misc/utils/update/20100609_vfcom_structure.sql';
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      
      mysql_select_db($DatabaseName, $this->Database);
      $ColResult = mysql_query("SHOW COLUMNS FROM `GDN_Discussion` LIKE 'FirstCommentID'", $this->Database);
      mysql_select_db(DATABASE_MAIN, $this->Database);
      
      $Rows = $ColResult ? mysql_num_rows($ColResult) : 0;
      TaskList::Event("Checking if repairs are needed for '{$DatabaseName}'...", TaskList::NOBREAK);
      if ($Rows) {
         TaskList::Event("yes");
         $Command = sprintf("mysql -u%s --password=%s -h %s '%s' < %s",DATABASE_USER, DATABASE_PASSWORD, DATABASE_HOST, $DatabaseName, $SqlFile);
         TaskList::Event($Command);
         if (!LAME) exec($Command);
      } else {
         TaskList::Event("no");
      }
      
      die();
   }

}

