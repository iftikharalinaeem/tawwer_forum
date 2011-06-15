<?php

class InstalledTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init() {
   }
   
   public function Run() {
      $this->Client->SaveToConfig('Garden.Installed', TRUE);
      TaskList::Success(array(
         'Message'      => "Installed",
         'Hostname'     => $this->Client->ClientFolder,
         'DatabaseHost' => $this->Client->C('Database.Host'),
         'DatabaseName' => $this->Client->C('Database.Name')
      ));
   }
}