<?php

class EnablestatsTask extends Task {

   protected $SourcecodePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init($RootPath = NULL) {

   }
   
   protected function Run() {
      $this->SaveToConfig('Plugins.Statistics.Enabled',TRUE);
   }

}

