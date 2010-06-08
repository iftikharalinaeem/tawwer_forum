<?php

class SymlinkTask extends Task {

   public function __construct() {
      parent::__construct();
   }
   
   public function Run($ClientFolder) {
      $Client = $this->LookupClientByFolder($ClientFolder);
      if (!$Client) return FALSE;
      
      
   }

}

