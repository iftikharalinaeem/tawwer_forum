<?php

class LogsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init($RootPath = NULL) {}
   
   protected function Run() {
      $this->Mkdir('log');
      $LogDir = TaskList::CombinePaths($this->ClientRoot,'log');
      @chgrp($LogDir, 'www-data');
      @chmod($LogDir, 0774);
   }

}

