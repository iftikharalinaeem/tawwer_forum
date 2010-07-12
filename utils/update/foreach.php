<?php

error_reporting(E_ALL);
define('APPLICATION', 'VanillaUpdate');
// Represents a config file
require_once("configuration.php");

class TaskList {

   const NOBREAK = FALSE;
   
   protected $Clients;
   protected $Tasks;
   protected $Database;
   protected $ClientList;

   public function __construct($UserTaskDirs, $ClientDir) {
   
      $this->Clients = $ClientDir;
      $this->Tasks = array();
      $this->Database = mysql_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, TRUE); // Open the db connection, new link please
      if (!$this->Database)
         die("Could not connect to database as '".DATABASE_USER."'@'".DATABASE_HOST."'\n");
         
      mysql_select_db(DATABASE_MAIN, $this->Database);
      
      TaskList::MajorEvent("Connected to ".DATABASE_MAIN." @ ".DATABASE_HOST);
      
      chdir(dirname(__FILE__));
   
      // Setup tasks
      TaskList::MajorEvent("Setting up task objects...");
      
      if (!is_array($UserTaskDirs))
         $UserTaskDirs = array($UserTaskDirs);
         
      $TaskDirs = array_merge(array('global'), $UserTaskDirs);
      
      // Looping task dirs
      foreach ($TaskDirs as $TaskDir) {
         if (!is_dir($TaskDir) || !is_readable($TaskDir)) 
            die("Could not find task_dir '{$TaskDir}', or it does not have read permissions.\n");
         
         if (!$TaskDirectory = opendir($TaskDir))
            die("Could not open task_dir '{$TaskDir}' for reading.\n");
            
         TaskList::Event("Scanning {$TaskDir} for task objects...");
         
         while (($FileName = readdir($TaskDirectory)) !== FALSE) {
            if ($FileName == '.' || $FileName == '..') continue;
            if (!preg_match('/^(.*)\.task\.php$/', $FileName, $Matches)) continue;
            
            $Taskname = $Matches[1];
            $IncludePath = trim($TaskDir,'/').'/'.$FileName;
            $Classes = get_declared_classes();
            require_once($IncludePath);
            $NewClasses = array_diff(get_declared_classes(), $Classes);
            
            foreach ($NewClasses as $Class) {
               if (is_subclass_of($Class, 'Task')) {
                  TaskList::Event(strtolower($Class));
                  $NewTask = new $Class($ClientDir);
                  $NewTask->Database = $this->Database;
                  $this->Tasks[$Taskname] = array(
                     'name'      => str_replace('Task', '', $Class),
                     'task'      => $NewTask
                  );
               }
            }
            TaskList::Event("");
         }
         closedir($TaskDirectory);
      }
            
      TaskList::MajorEvent("Scanning for clients...", TaskList::NOBREAK);
      $this->ClientList = array();
      $FolderList = scandir($this->Clients);
      if ($FolderList === FALSE) {
         TaskList::MajorEvent("could not open client folder.");
         return FALSE;
      }
      
      foreach ($FolderList as $ClientFolder) {
         if ($ClientFolder == '.' || $ClientFolder == '..') continue;
         $this->ClientList[$ClientFolder] = 1;
      }
      $NumClients = count($this->ClientList);
      TaskList::MajorEvent("found {$NumClients}!", TaskList::NOBREAK);
      
      $Proceed = TaskList::Question("","Proceed with task execution?",array('yes','no'),'no');
      if ($Proceed == 'no') exit();
   }
   
   public function RunAll($TaskOrder = NULL) {
      TaskList::MajorEvent("Running through full client list...");
      foreach ($this->ClientList as $ClientFolder => $ClientInfo)
         $this->PerformClient($ClientFolder, $TaskOrder);
   }
   
   public function RunSelectiveRegex($RegularExression, $TaskOrder = NULL, $Internal = FALSE) {
      if (!$Internal) TaskList::MajorEvent("Running regular expression {$RegularExpression} against client list...");
/*
      foreach ($this->ClientList as $ClientFolder => $ClientInfo) {
         if (!preg_match($RegularExpression, $ClientFolder, $Matches)) continue;
         $this->PerformClient($ClientFolder, $TaskOrder);
      }
*/
   }
   
   public function RunClientFromCLI($ClientFolder, $TaskOrder = NULL) {
      TaskList::MajorEvent("Running client {$ClientFolder}...");
      
      if (!array_key_exists($ClientFolder,$this->ClientList))
         die("client not found.\n");
         
      $this->PerformClient($ClientFolder, $TaskOrder);
   }
   
   public function RunChunked($ChunkRule, $TaskOrder) {
      TaskList::MajorEvent("Running client list, chunked by '{$ChunkRule}'...");
      switch ($ChunkRule) {
         case 'alphabet':
            $Chunks = array();
            $Chunks[] = '-';
            $Chunks = array_merge($Chunks, array_keys(array_fill(0,10,'a')));
            for ($i = 97; $i < 123; $i++)
                $Chunks[] = chr($i);
            
            foreach ($Chunks as $ChunkIndex => $Chunk) {
               $ChunkRegex = "/^({$Chunk}.*)\$/i";
               $this->RunSelectiveRegex($ChunkRegex, $TaskOrder);
               $Proceed = TaskList::Question("","Proceed with next chunk?",array('yes','no'),'yes');
               if ($Proceed == 'no') exit();
            }
         break;
         
         case 'tier':
            
         break;
         
         default:
            die("Invalid chunk type.\n");
         break;
      }
   }
   
   public function PerformClient($ClientFolder, $TaskOrder = NULL) {
      $ClientInfo = $this->ClientList[$ClientFolder];
      TaskList::MajorEvent("{$ClientFolder} [{$ClientInfo['SiteID']}]...");
      
      // Run all tasks for this client
      if (!is_null($TaskOrder)) {
         foreach ($TaskOrder as $TaskName) {
            if (!array_key_exists($TaskName, $this->Tasks)) continue;
            $this->Tasks[$TaskName]['task']->SandboxExecute($ClientFolder, $ClientInfo);
         }
      } else {
         foreach ($this->Tasks as $TaskName => &$Task)
            $Task['task']->SandboxExecute($ClientFolder, $ClientInfo);
      }
      TaskList::MajorEvent("");
   }
   
   protected function LookupClientByFolder($ClientFolder) {
      $Query = "SELECT * FROM GDN_Site WHERE Name = '{$ClientFolder}'";
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
   
   // Convenience method
   public static function CombinePaths($Paths, $Delimiter = '/') {
      if (!is_array($Paths)) {
         $Paths = func_get_args();
         $Delimiter = '/';
      }
      
      $MungedPath = implode($Delimiter, $Paths);
      $MungedPath = str_replace(array($Delimiter.$Delimiter.$Delimiter, $Delimiter.$Delimiter), array($Delimiter, $Delimiter), $MungedPath);
      return str_replace('http:/', 'http://', $MungedPath);
   }
   
   public static function Symlink($Link, $Source = NULL) {
      if (file_exists($Link) || is_link($Link))
         if (!LAME) unlink($Link);
      
      if (!is_null($Source)) {
         TaskList::Event("/bin/ln -s {$Source} {$Link}");
         if (!LAME) symlink($Source, $Link);
         //exec("/bin/ln -s {$EscapedSource} {$EscapedLink}");
      }
   }
   
   public static function MinorEvent($Message, $LineBreak = TRUE) {
      if (VERBOSE) {
         echo "    - {$Message}";
         if ($LineBreak) echo "\n";
      }
   }
   
   public static function Event($Message, $LineBreak = TRUE) {
      if (VERBOSE) {
         echo "  {$Message}";
         if ($LineBreak) echo "\n";
      }
   }
   
   public static function MajorEvent($Message, $LineBreak = TRUE) {
      if (VERBOSE) {
         echo "{$Message}";
         if ($LineBreak) echo "\n";
      }

   }
   
   public static function Question($Message, $Prompt, $Options, $Default) {
      echo "\n";
      if ($Message)
         echo $Message."\n";
         
      foreach ($Options as &$Opt)
         $Opt = strtolower($Opt);
         
      $HaveAnswer = FALSE;
      do {
         self::_Prompt($Prompt, $Options, $Default);
         $Answer = trim(fgets(STDIN));
         if ($Answer == '') $Answer = $Default;
         $Answer = strtolower($Answer);
         
         if (in_array($Answer, $Options))
            $HaveAnswer = TRUE;
      } while(!$HaveAnswer);
      return $Answer;
   }
   
   protected static function _Prompt($Prompt, $Options, $Default) {
      echo "{$Prompt}";
      
      if (!sizeof($Options) && $Default !== FALSE && !is_null($Default)) {
         echo " [{$Default}]";
      }
      echo ": ";
      
      if (sizeof ($Options)) {
         $PromptOpts = array();
         foreach ($Options as $Opt)
            $PromptOpts[] = (strtolower($Opt) == strtolower($Default)) ? strtoupper($Opt) : strtolower($Opt);
         echo "(".implode(',',$PromptOpts).") ";
      }
   }
   
   public static function Input($Message, $Prompt, $Default) {
      echo "\n";
      if ($Message)
         echo $Message."\n";
         
      self::_Prompt($Prompt, array(), $Default);
      $Answer = trim(fgets(STDIN));
      if ($Answer == '') $Answer = $Default;
      $Answer = strtolower($Answer);
      return $Answer;
   }
   
}

abstract class Task {

   public $Database;
   protected $Root;
   protected $ClientRoot;
   protected $ClientFolder;
   protected $ClientInfo;
   protected $ConfigFile;
   protected $Config;

   public function __construct($RootFolder) {
      $this->Root = rtrim($RootFolder,'/');
      TaskList::Event("Set root folder to '{$this->Root}'");
      $this->ClientRoot = NULL;
      $this->ClientFolder = NULL;
      $this->ClientInfo = NULL;
      $this->ConfigFile = NULL;
      $this->Config = new Configuration();
   }
   
   abstract protected function Run();
   
   public function SandboxExecute($ClientFolder, $ClientInfo) {
      $this->ClientFolder = $ClientFolder;
      $this->ClientRoot = TaskList::CombinePaths($this->Root, $this->ClientFolder );
      $this->ClientInfo = $ClientInfo;
      
      $this->ConfigFile = TaskList::CombinePaths($this->ClientRoot,'conf/config.php');
      $this->Config = new Configuration();
      try {
         $this->Config->Load($this->ConfigFile, 'Use');
      } catch (Exception $e) { die ($e->getMessage()); }

      $this->Run();
   }
   
   protected function SaveToConfig($Key, $Value) {
      if (is_null($this->ClientInfo)) return;
      if (LAME) return;
      
      $this->Config->Load($this->ConfigFile, 'Save');
      
      if (!is_array($Key))
         $Key = array($Key => $Value);
      
      foreach ($Name as $k => $v)
         $this->Config->Set($k, $v);
      
      return $this->Config->Save($this->ConfigFile);
   }
   
   protected function RemoveFromConfig($Key) {
      if (is_null($this->ClientInfo)) return;
      if (LAME) return;
      
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
   
   protected function Symlink($RelativeLink, $Source = NULL) {
      $AbsoluteLink = TaskList::CombinePaths($this->ClientRoot,$RelativeLink);
      TaskList::Symlink($AbsoluteLink, $Source);
   }
   
   protected function CopySourceFile($RelativePath, $SourcecodePath) {
      $AbsoluteClientPath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      $AbsoluteSourcePath = TaskList::CombinePaths($SourcecodePath,$RelativePath);
      
      $NewFileHash = md5_file($AbsoluteSourcePath);
      
      if (file_exists($AbsoluteClientPath)) {
         $OldFileHash = md5_file($AbsoluteClientPath);
         if ($OldFileHash == $NewFileHash) {
            TaskList::Event("copy aborted. local {$RelativePath} is the same as {$AbsoluteSourcePath}");
            return;
         }
         if (!LAME) unlink($AbsoluteClientPath);
      }
      
      TaskList::Event("copy '{$AbsoluteSourcePath} / ".md5_file($AbsoluteSourcePath)."' to '{$AbsoluteClientPath} / {$OldFileHash}'");
      if (!LAME) copy($AbsoluteSourcePath, $AbsoluteClientPath);
   }

}
