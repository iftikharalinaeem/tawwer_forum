<?php

class LogsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init($RootPath = NULL) {}
   
   protected function Run() {
      $this->Mkdir('log/');
   }

}

