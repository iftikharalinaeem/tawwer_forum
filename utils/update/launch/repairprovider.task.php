<?php

class RepairproviderTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $DatabaseName = $this->ClientInfo['DatabaseName'];
      
      $ChooseDB = mysql_select_db($DatabaseName, $this->Database);
      if ($ChooseDB)
         $RepairResult = mysql_query("ALTER TABLE `GDN_UserAuthenticationProvider` CHANGE RegistrationUrl RegisterUrl VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL",$this->Database);
      mysql_select_db(DATABASE_MAIN, $this->Database);
   }

}
