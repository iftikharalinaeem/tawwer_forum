<?php

class SymlinkTask extends Task {

   public function __construct() {
      parent::__construct();
   }
   
   protected function Run() {
      $ClientDashboardLink = TaskList::CombinePaths($this->ClientRoot,'applications/dashboard');
      @unlink($ClientDashboardLink);
      
      
   }

}

