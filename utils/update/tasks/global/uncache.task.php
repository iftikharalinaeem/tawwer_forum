<?php

class UncacheTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $CachePath = TaskList::CombinePaths(array($this->ClientRoot, 'cache'));
      
      foreach (scandir($CachePath) as $CacheItem) {
         if (in_array($CacheItem, array('.','..'))) continue;
         
         $RealPath = TaskList::CombinePaths(array($CachePath, $CacheItem));
         if (file_exists($RealPath))
            @unlink($RealPath);
      }
   }

}

