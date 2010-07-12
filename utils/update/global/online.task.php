<?php

class OfflineTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      TaskList::Event("Client online...");
      $this->RemoveFromConfig('Garden.UpdateMode');
   }

}

