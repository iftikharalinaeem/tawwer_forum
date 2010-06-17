<?php

class UncacheTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      // Remove old 'garden' application symlink and replace with 'dashboard' application
      $CachePath = TaskList::CombinePaths(array($this->ClientRoot, 'cache'));
      
      foreach (array('library', 'locale', 'controller') as $CacheItem) {
         $RealPath = TaskList::CombinePaths(array($CachePath, sprintf('%s_mappings.php',$CacheItem)));
         if (file_exists($RealPath))
            @unlink($RealPath);
      }
   }

}

