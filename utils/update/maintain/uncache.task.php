<?php

class UncacheTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $CachePath = TaskList::CombinePaths(array($this->ClientRoot, 'cache'));
      
      foreach (array('library', 'locale', 'controller') as $CacheItem) {
         $RealPath = TaskList::CombinePaths(array($CachePath, sprintf('%s_mappings.php',$CacheItem)));
         if (file_exists($RealPath))
            @unlink($RealPath);
            
         $RealPath = TaskList::CombinePaths(array($CachePath, sprintf('%s_map.ini',$CacheItem)));
         if (file_exists($RealPath))
            @unlink($RealPath);
      }
   }

}

