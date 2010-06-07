<?php

class TaskList {

   protected $Clients;
   protected $Tasks;
   protected $Database;

   public function __construct($TaskDir, $ClientDir) {
   
      $this->Clients = $ClientDir;
      $this->Tasks = array();
      $this->Database = mysql_connect('localhost', 'root', 'okluijfire4'); // Open the db connection
      mysql_select_db('vfcom', $this->Database);
   
      // Setup tasks
      if (!is_dir($TaskDir) || !is_readable($TaskDir)) 
         die("Could not find task_dir '{$TaskDir}', or it does not have read permissions.\n");
         
      if (!$TaskDirectory = opendir($TaskDir))
         die("Could not open task_dir '{$TaskDir}' for reading.\n");
         
      while (($FileName = readdir($TaskDirectory)) !== FALSE) {
         if ($FileName == '.' || $FileName == '..') continue;
         if (!preg_match('/^.*\.task\.php$/', $FileName)) continue;
         
         $IncludePath = trim($TaskDir,'/').'/'.$FileName;
         $Classes = get_declared_classes();
         require_once($IncludePath);
         $NewClasses = array_diff(get_declared_classes(), $Classes);
         
         foreach ($NewClasses as $Class) {
            if (is_subclass_of($Class, 'Task'))
               $this->Tasks[] = array(
                  'name'      => str_replace('Task', '', $Class),
                  'task'      => new $Class()
               );
         }
      }
      
      closedir($TaskDirectory);
   }
   
   public function RunAll() {
      if ($DirectoryHandle = @opendir($this->Clients) === FALSE) return FALSE;
      
      while (($Item = readdir($this->Clients)) !== FALSE) {
         foreach ($this->Tasks as &$Task) {
            $Task['task']->Run($Item);
         }
      }
      closedir($DirectoryHandle);
      
   }

}

abstract class Task {

   public $Database;

   public function __construct() {
      
   }
   
   abstract public function Run($ClientFolder);
   
   protected function LookupClientByFolder($ClientFolder) {
      $Data = mysql_query("select SiteID from GDN_Site where Name = '{$ClientFolder}'", $this->Database);
      if (mysql_num_rows($Data)) {
         $Row = mysql_fetch_assoc($Data);
         mysql_free_result($Data);
         return $Row;
      }
      return FALSE;
   }

}