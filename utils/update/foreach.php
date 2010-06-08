<?php

// Represents a config file
require_once("configuration.php");

class TaskList {

   protected $Clients;
   protected $Tasks;
   protected $Database;

   public function __construct($TaskDir, $ClientDir) {
   
      $this->Clients = $ClientDir;
      $this->Tasks = array();
      $this->Database = mysql_connect('localhost', 'root', 'Va2aWu5A'); // Open the db connection
      mysql_select_db('vfcom', $this->Database);
   
      chdir(dirname(__FILE__));
   
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
            if (is_subclass_of($Class, 'Task')) {
               $NewTask = new $Class($TaskDir);
               $NewTask->Database = $this->Database;
               $this->Tasks[] = array(
                  'name'      => str_replace('Task', '', $Class),
                  'task'      => $NewTask
               );
            }
         }
      }
      
      closedir($TaskDirectory);
   }
   
   public function RunAll() {
      if (($DirectoryHandle = @opendir($this->Clients)) === FALSE) return FALSE;
      
      while (($ClientFolder = readdir($DirectoryHandle)) !== FALSE) {
         if ($ClientFolder == '.' || $ClientFolder == '..') continue;
         
         $ClientInfo = $this->LookupClientByFolder($ClientFolder);
         // Run all tasks for this client
         foreach ($this->Tasks as &$Task)
            $Task['task']->SandboxExecute($ClientFolder, $ClientInfo);
         
      }
      closedir($DirectoryHandle);
      
   }
   
   protected function LookupClientByFolder($ClientFolder) {
      $Query = "select * from GDN_Site where Name = '{$ClientFolder}'";
      $Data = mysql_query($Query, $this->Database);
      if ($Data && mysql_num_rows($Data)) {
         $Row = mysql_fetch_assoc($Data);
         mysql_free_result($Data);
         return $Row;
      }
      return FALSE;
   }
   
   // Convenience method for Configuration
   public static function Unserialize($SerializedString) {
		$Result = $SerializedString;
		
      if(is_string($SerializedString)) {
			if(substr_compare('a:', $SerializedString, 0, 2) === 0 || substr_compare('O:', $SerializedString, 0, 2) === 0)
				$Result = unserialize($SerializedString);
			elseif(substr_compare('obj:', $SerializedString, 0, 4) === 0)
            $Result = json_decode(substr($SerializedString, 4), FALSE);
         elseif(substr_compare('arr:', $SerializedString, 0, 4) === 0)
            $Result = json_decode(substr($SerializedString, 4), TRUE);
      }
      return $Result;
   }
   
   // Convenience method for Configuration
   public static function Serialize($Mixed) {
		if(is_array($Mixed) || is_object($Mixed)
			|| (is_string($Mixed) && (substr_compare('a:', $Mixed, 0, 2) === 0 || substr_compare('O:', $Mixed, 0, 2) === 0
				|| substr_compare('arr:', $Mixed, 0, 4) === 0 || substr_compare('obj:', $Mixed, 0, 4) === 0))) {
			$Result = serialize($Mixed);
		} else {
			$Result = $Mixed;
		}
		return $Result;
   }
   
   // Convenience method for Configuration
   public static function ToDateTime($Timestamp = '') {
      if ($Timestamp == '')
         $Timestamp = time();
      return date('Y-m-d H:i:s', $Timestamp);
   }
   
   // Convenience method for Configuration
   public static function ArrayValueForPhp($String) {
      return str_replace('\\', '\\', html_entity_decode($String, ENT_QUOTES));
   }

}

abstract class Task {

   public $Database;
   protected $ClientRoot;
   protected $ClientFolder;
   protected $ClientInfo;
   protected $ConfigFile;
   protected $Config;

   public function __construct($RootFolder) {
      $this->ClientRoot = $RootFolder;
      $this->ClientFolder = NULL;
      $this->ClientInfo = NULL;
      $this->ConfigFile = NULL;
      $this->Config = new Configuration();
   }
   
   abstract protected function Run();
   
   public function SandboxExecute($ClientFolder, $ClientInfo) {
      $this->ClientFolder = $ClientFolder;
      $this->ClientInfo = $ClientInfo;
      
      $this->ConfigFile = trim($this->ClientRoot,'/').'/'.$this->ClientFolder.'/conf/config.php';
      $this->Config = new Configuration();
      $this->Config->Load($ConfigFile, 'Use');
      
      $this->Run();
   }
   
   protected function SaveToConfig($Key, $Value) {
      if (is_null($this->ClientInfo)) return;
      
      $this->Config->Load($this->ConfigFile, 'Save');
      
      if (!is_array($Key))
         $Key = array($Key => $Value);
      
      foreach ($Name as $k => $v)
         $this->Config->Set($k, $v);
      
      return $this->Config->Save($this->ConfigFile);
   }
   
   protected function RemoveFromConfig($Key) {
      if (is_null($this->ClientInfo)) return;
      
      $this->Config->Load($this->ConfigFile, 'Save');

      if (!is_array($Key))
         $Key = array($Key);
      
      foreach ($Key as $k)
         $this->Config->Remove($k);
      
      $Result = $this->Config->Save($this->ConfigFile);
      if ($Result)
         $this->Config->Load($this->ConfigFile, 'Use');
      return $Result;
   }

   protected function C($Name = FALSE, $Default = FALSE) {
      if (is_null($this->ClientInfo)) return;
      return $this->Config->Get($Name, $Default);
   }

}