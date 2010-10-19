<?php

class TaskList {

   const ACTION_CACHE   = 'cache';
   const ACTION_TARGET  = 'target';
   const ACTION_CREATE  = 'create';
   
   const MODE_CHUNKED   = 'chunked';
   const MODE_TARGET    = 'target';
   const MODE_REGEX     = 'regex';

   const NOBREAK        = FALSE;
   const CONFIGDEFAULTS = '/srv/www/vanillaforumscom/conf/config-defaults.php';
   const CONFIG         = '/srv/www/vanillaforumscom/conf/config.php';
   const TASKS          = 'tasks/';
   
   protected $Perform;
   protected $Arguments;
   protected $Mode;
   protected $RequestedTasks;
   
   public $GroupData;
   protected $Clients;
   protected $Tasks;
   protected $Database;
   protected $ClientList;
   protected $Config;
   protected $NumClients;
   protected $Completed;
   
   public function __construct($UserTaskDirs, $ClientDir) {
      $this->Config = new Configuration();
      try {
         $this->Config->Load(TaskList::CONFIGDEFAULTS, 'Use');
         $this->Config->Load(TaskList::CONFIG, 'Use');
      } catch (Exception $e) { die ($e->getMessage()); }
      
      // Get db connection details from vfcom's config
      $this->DBHOST = $this->Config->Get('Database.Host');
      $this->DBUSER = $this->Config->Get('Database.User');
      $this->DBPASS = $this->Config->Get('Database.Password');
      $this->DBMAIN = $this->Config->Get('Database.Name');

      // Open the db connection, new link please
      $this->Database = mysql_connect($this->DBHOST, $this->DBUSER, $this->DBPASS, TRUE);
      if (!$this->Database)
         die("Could not connect to database as '".$this->DBUSER."'@'".$this->DBHOST."'\n");
      
      mysql_select_db($this->DBMAIN, $this->Database);
      
      TaskList::MajorEvent("Connected to ".$this->DBMAIN." @ ".$this->DBHOST);
      
      // Chdir to where we are right now. Root of the utils/update/ folder
      chdir(dirname(__FILE__));
      chdir('../');
      
      $this->Arguments = array();
      $this->Perform = array();
      $this->Clients = NULL;
   }
   
   /**
    * Perform function.
    *
    * Registers an action to be performed immediately before running the actual tasks.
    * This allows TaskList to grab console arguments and request missing arguments.
    * 
    * @access public
    * @param mixed $PerformAction
    * @return void
    */
   public function Perform($PerformAction) {
      if (!in_array($PerformAction, $this->Perform))
         array_push($this->Perform, $PerformAction);
   }
   
   /**
    * PerformAction function.
    *
    * Run a requested perform action.
    * 
    * @access protected
    * @return void
    */
   protected function PerformAction($Perform) {
      switch ($Perform) {
         case TaskList::ACTION_CACHE:
            TaskList::MajorEvent("Scanning for clients...", TaskList::NOBREAK);
            $this->Completed = $this->NumClients = 0;
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
            $this->NumClients = $NumClients = count($this->ClientList);
            TaskList::MajorEvent("found {$NumClients}!", TaskList::NOBREAK);
         break;
         
         case TaskList::ACTION_TARGET:
         case TaskList::ACTION_CREATE:
            $Arguments = $this->Console('t:');
            // Check if the forum exists
            
            $Action = ($Perform == TaskList::ACTION_TARGET) ? 'target' : 'create';
            
            $Forum = GetValue('t', $Arguments, NULL);
            if (is_null($Forum)) {
               $Forum = TaskList::Input("Please provide the name of the forum you wish to {$Action}:","[________].vanillaforums.com: ",NULL);
               if (is_null($Forum)) {
                  TaskList::MajorEvent("No forum provided.");
                  die();
               }
            }
            
            $QualifiedForumName = $Forum.".vanillaforums.com";
            $ForumPath = TaskList::CombinePaths(array($QualifiedForumName, $this->Clients));
            $Exists = is_dir($ForumPath);
            if ($Perform == TaskList::ACTION_CREATE) {
            
               if ($Exists) {
                  TaskList::MajorEvent("The forum you selected already exists.");
                  die();
               }
               
               // mkdir
            }
            
            if ($Perform == TaskList::ACTION_TARGET) {
               if (!$Exists) {
                  TaskList::MajorEvent("The forum you selected does not exist.");
                  die();
               }
            }

            $this->TargetForum = $QualifiedForumName;
         break;
      }
   }
   
   public function ClientDir($ClientDir) {
      if (!is_dir($ClientDir)) {
         TaskList::MajorEvent("Could not open client folder.");
         die();
      }
      
      $this->Clients = $ClientDir;
   }
   
   public function Run($RunMode, $TaskList) {
      $this->Mode = $RunMode;
      $this->RequestedTasks = $TaskList;
      
      // Set up tasks
      $this->Configure();
      
      // Run pre-tasks
      foreach ($this->Perform as $Perform) {
         $this->PerformAction($Perform);
      }
   
      // Check one more time
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("","Proceed with task execution?",array('yes','no'),'no');
         if ($Proceed == 'no') exit();
      }
      
      // Go
      //$this->
   }
   
   protected function Console($ConsoleDefinition, $Defaults = NULL) {
      
      getopt();
   }
   
   public function Configure() {
      if (is_null($this->Clients)) {
         TaskList::MajorEvent("No client folder supplied.");
         die();
      }
      
      $this->Tasks = array();
   
      // Setup tasks
      TaskList::MajorEvent("Setting up task objects...");
      
      $TaskFolder = rtrim(getcwd(), '/').'/';
      if (!$TaskFolders = opendir($TaskFolder))
         die("Could not find task_dir '{$TaskFolder}', or it does not have read permissions.\n");
         
      // Looping task dirs
      while (($TaskDir = readdir($TaskFolders)) !== FALSE) {
         $TaskDir = trim($TaskDir,'/');
         if ($TaskDir == '.' || $TaskDir == '..') continue;
         $AbsTaskDir = $TaskFolder.$TaskDir;
         
         TaskList::Event("Scanning '{$TaskDir}' for task objects... ", TaskList::NOBREAK);
         try {
            if (!is_dir($AbsTaskDir) || !is_readable($AbsTaskDir)) throw new Exception('cannot read');
            if (!$TaskDirectory = opendir($AbsTaskDir)) throw new Exception('cannot open');
         } catch (Exception $e) {
            TaskList::MajorEvent($e->getMessage(), TaskList::NOBREAK);
            continue;
         }
         TaskList::MajorEvent('opened', TaskList::NOBREAK);
         
         while (($FileName = readdir($TaskDirectory)) !== FALSE) {
            if ($FileName == '.' || $FileName == '..') continue;
            
            // Not a valid task name
            if (!preg_match('/^(.*)\.task\.php$/', $FileName, $Matches)) continue;
            
            $Taskname = $Matches[1];
            $QualifiedTaskName = $TaskDir.'/'.$Taskname;
            
            // Not a requested task
            if (!in_array($QualifiedTaskName, $this->RequestedTasks)) continue;
            
            $IncludePath = $AbsTaskDir.'/'.$FileName;
            $Classes = get_declared_classes();
            require_once($IncludePath);
            $NewClasses = array_diff(get_declared_classes(), $Classes);
            
            foreach ($NewClasses as $Class) {
               if (is_subclass_of($Class, 'Task')) {
                  TaskList::Event(strtolower($Class));
                  $NewTask = new $Class($ClientDir);
                  $NewTask->Database = $this->Database;
                  $NewTask->TaskList =& $this;
                  $this->Tasks[$Taskname] = array(
                     'name'      => str_replace('Task', '', $Class),
                     'task'      => $NewTask
                  );
                  if (method_exists($NewTask, 'Init'))
                     $NewTask->Init();
               }
            }
            TaskList::Event("");
         }
         closedir($TaskDirectory);
      }
      closedir($TaskFolders);
   }
   
   public function RunAll($TaskOrder = NULL) {
      TaskList::MajorEvent("Running through full client list...");
      foreach ($this->ClientList as $ClientFolder => $ClientInfo)
         $this->PerformClient($ClientFolder, $TaskOrder);
   }
   
   public function RunSelectiveRegex($RegularExpression, $TaskOrder = NULL, $Internal = FALSE) {
      if (!$Internal) TaskList::MajorEvent("Running regular expression {$RegularExpression} against client list...");
      $Matched = 0;
      foreach ($this->ClientList as $ClientFolder => $ClientInfo) {
         if (!preg_match($RegularExpression, $ClientFolder, $Matches)) continue;
         $Matched++;
         $this->PerformClient($ClientFolder, $TaskOrder);
      }
      return $Matched;
   }
   
   public function RunClientFromCLI($ClientFolder, $TaskOrder = NULL) {
      TaskList::MajorEvent("Running client {$ClientFolder}...");
      
      if (!array_key_exists($ClientFolder,$this->ClientList))
         die("client not found.\n");
         
      $this->PerformClient($ClientFolder, $TaskOrder);
   }
   
   public function RunChunked($ChunkRule, $TaskOrder) {
      global $argv;
      $args = $argv;
      TaskList::MajorEvent("Running client list, chunked by '{$ChunkRule}'...");
      switch ($ChunkRule) {
         case 'alphabet':
         case 'alfast':
            $Chunks = array();
            if ($ChunkRule == 'alphabet') {
               $Chunks[] = '-';
               $Chunks[] = '[0-9]';
               $Chunks = array_merge($Chunks, range('a','z'));
            }
            
            if ($ChunkRule == 'alfast') {
               $ChunkRules = explode(',',array_pop($args));
               foreach ($ChunkRules as $FastChunkRule) {
                  if (strlen($FastChunkRule) == 1)
                     $Chunks[] = $FastChunkRule;
                  else {
                     $RangeSplit = explode('::', $FastChunkRule);
                     $Chunks = array_merge($Chunks, range($RangeSplit[0],$RangeSplit[1]));
                  }
               }
            }
            
            foreach ($Chunks as $ChunkIndex => $Chunk) {
               $ChunkRegex = "/^({$Chunk}.*)\$/i";
               $Matches = $this->RunSelectiveRegex($ChunkRegex, $TaskOrder);
               if (!$Matches) {
                  TaskList::Event("No matches for {$ChunkRegex}, skipping to next chunk");
                  continue;
               }
               
               $Completion = round(($this->Completed / $this->NumClients) * 100,0);
               TaskList::MajorEvent("Completion: {$this->Completed}/{$this->NumClients} ({$Completion}%)");
               TaskList::MajorEvent("\x07\x07\x07");
               if (!TaskList::Carefree()) {
                  $Proceed = TaskList::Question("","Proceed with next chunk?",array('yes','no'),'yes');
                  if ($Proceed == 'no') exit();
               }
            }
         break;
         
         case 'tier':
            
         break;
         
         case 'range':
            
         break;
         
         default:
            die("Invalid chunk type.\n");
         break;
      }
   }
   
   public function PerformClient($ClientFolder, $TaskOrder = NULL) {
      $ClientInfo = $this->LookupClientByFolder($ClientFolder);
      TaskList::MajorEvent("{$ClientFolder} [{$ClientInfo['SiteID']}]...");
      $this->Completed++;
      if (!$ClientInfo || !sizeof($ClientInfo) || !isset($ClientInfo['SiteID'])) {
         TaskList::Event("skipped... no db");
         return;
      }
      
      $this->GroupData = array();
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
   
   public function ExecTask($TaskName, $ClientFolder, $ClientInfo) {
      if (!array_key_exists($TaskName, $this->Tasks)) return;
      $this->Tasks[$TaskName]['task']->SandboxExecute($ClientFolder, $ClientInfo);
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
   
   public static function Symlink($Link, $Source = NULL, $Respectful = FALSE) {
      if (file_exists($Link) || is_link($Link)) {
         if ($Respectful) return TRUE;
         if (!LAME) unlink($Link);
      }
      
      if (!is_null($Source)) {
         TaskList::Event("/bin/ln -s {$Source} {$Link}");
         if (!LAME) symlink($Source, $Link);
         //exec("/bin/ln -s {$EscapedSource} {$EscapedLink}");
      }
   }
   
   public static function Mkdir($AbsolutePath) {
      if (file_exists($AbsolutePath)) return true;
      
      mkdir($AbsolutePath);
      return file_exists($AbsolutePath);
   }
   
   public static function Touch($AbsolutePath) {
      return touch($AbsolutePath);
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
   
   public static function Cautious() {
      if (!defined('FAST')) return TRUE;
      if (!FAST) return TRUE;
      
      return FALSE;
   }
   
   public static function Carefree() {
      if (!defined('VERYFAST')) return FALSE;
      if (!VERYFAST) return FALSE;
      
      return TRUE;
   }
   
}

function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
	$Result = $Default;
	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
		$Result = $Collection[$Key];
      if($Remove)
         unset($Collection[$Key]);
	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
		$Result = $Collection->$Key;
      if($Remove)
         unset($Collection->$Key);
   }
		
   return $Result;
}

function RandomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
   $CharLen = strlen($Characters) - 1;
   $String = '' ;
   for ($i = 0; $i < $Length; ++$i) {
     $Offset = rand() % $CharLen;
     $String .= substr($Characters, $Offset, 1);
   }
   return $String;
}
