<?php

class FixpermissionsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init($RootPath = NULL) {

   }
   
   protected function Run() {
      $Conf = TaskList::CombinePaths($this->ClientRoot, 'conf');
      $Cache = TaskList::CombinePaths($this->ClientRoot, 'cache');
      $Uploads = TaskList::CombinePaths($this->ClientRoot, 'uploads');
      
   }

}

