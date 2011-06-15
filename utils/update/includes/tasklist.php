<?php

class TaskList {

   const ACTION_CACHE   = 'cache';
   const ACTION_TARGET  = 'target';
   const ACTION_CREATE  = 'create';
   
   const MODE_CHUNKED   = 'chunked';
   const MODE_TARGET    = 'target';
   const MODE_REGEX     = 'regex';

   const NOBREAK        = FALSE;
   //const CONFIGDEFAULTS = '/var/www/vanilla/vanilla/conf/config-defaults.php';
   //const CONFIG         = '/var/www/vanilla/clients/dev.vanilla.tim/conf/config.php';
   const CONFIGDEFAULTS = '/srv/www/vanillaforumscom/conf/config-defaults.php';
   const CONFIG         = '/srv/www/vanillaforumscom/conf/config.php';
   
   const TASKS          = 'tasks/';
   
   const OUTMODE_CLI    = 'cli';
   const OUTMODE_JSON   = 'json';
   
   // List of perform tasks
   protected $Perform;
   
   // Exec mode
   protected $Mode;
   
   // List of requested tasks
   protected $RequestedTasks;
   
   // Client Folder
   protected $Clients;
   
   // Known tasklist (contains instances)
   protected $Tasks;
   
   // Link to database resource
   protected $Database;
   protected $Databases;
   
   // Cached list of clients
   protected $ClientList;
   
   // Config object
   protected $Config;
   
   // Where sourcecode tags live
   protected $SourceRoot;
   
   // What domain we're working on
   protected $HostingDomain;
   
   // Int number of known clients
   protected $NumClients;
   
   // Int number of completed clients
   protected $Completed;
   
   // Argument parser instance
   public static $Args = NULL;
   
   // Boolean flag whether or not to require a valid client to perform client
   public $RequireValid;
   
   // Boolean flag whether or not to require a pre-targetted client DB
   public $RequireTargetDatabase;
   
   // Boolean flag whether or not to ignore symlinks (multiname clients)
   public $IgnoreSymlinks;
   
   // Boolean flag whether or not to ignore real folders
   public $IgnoreReal;
   
   public function __construct() {
   
      define("FAST", ((TaskList::GetConsoleOption("fast", FALSE) || TaskList::GetConsoleOption("veryfast", FALSE)) !== FALSE) ? TRUE : FALSE);
      define("VERYFAST", (TaskList::GetConsoleOption("veryfast", FALSE) !== FALSE) ? TRUE : FALSE);
      
      define("OUTPUTMODE", TaskList::GetConsoleOption("mode", TaskList::OUTMODE_CLI));
      
      $IsVerbose = !(bool)TaskList::GetConsoleOption("quiet", FALSE);
      define("VERBOSE", $IsVerbose);
      $IsLame = (bool)TaskList::GetConsoleOption("lame", FALSE);
      define("LAME", $IsLame);
   
      $Configs = array(
            "config-defaults"    => TaskList::GetConsoleOption("config-defaults", TaskList::CONFIGDEFAULTS),
            "config"             => TaskList::GetConsoleOption("config", TaskList::CONFIG)
      );
   
      $this->Config = new Configuration();
      try {
         if (!file_exists($Configs['config-defaults']))
            throw new Exception("cannot read ".$Configs['config-defaults']);
            
         if (!file_exists($Configs['config']))
            throw new Exception("cannot read ".$Configs['config']);
         
         $this->Config->Load($Configs['config-defaults'], 'Use');
         $this->Config->Load($Configs['config'], 'Use');
      } catch (Exception $e) { 
         TaskList::MajorEvent("Fatal error loading core config:");
         TaskList::MinorEvent($e->getMessage());
         die();
      }
      
      $this->SourceRoot = $this->C('VanillaForums.Spawn.SourceRoot','/srv/www/source');
      if (!file_exists($this->SourceRoot) || !is_dir($this->SourceRoot))
         TaskList::FatalError("Invalid sourcecode root folder '{$this->SourceRoot}'");
         
      $this->HostingDomain = $this->C('VanillaForums.Spawn.HostingDomain', 'vanillaforums.com');
      
      // Get db connection details from vfcom's config
      $this->DBHOST = $this->Config->Get('Database.Host', NULL);
      $this->DBUSER = $this->Config->Get('Database.User', NULL);
      $this->DBPASS = $this->Config->Get('Database.Password', NULL);
      $this->DBMAIN = $this->Config->Get('Database.Name', NULL);

      // Open the db connection, new link please
      $this->Database = &$this->RootDatabase();
      TaskList::MajorEvent("Connected to ".$this->DBMAIN." @ ".$this->DBHOST);
      
      // Chdir to where we are right now. Root of the utils/update/ folder
      chdir(dirname(__FILE__));
      chdir('../');
      
      $this->Perform = array();
      $this->Clients = NULL;
      
      // By default, process all clients
      $this->RequireValid = FALSE;
      
      // By default, don't automake and target client DBs
      $this->RequireTargetDatabase = FALSE;
      
      // By default, don't consider symlinks to be real clients
      $this->IgnoreSymlinks = TRUE;
      
      // By default, don't ignore real folders
      $this->IgnoreReal = FALSE;
   }
   
   /**
    *
    * @param type $Key 
    * @return mysql
    */
   public function Database($Host, $User, $Pass, $Name = NULL, $Reuse = TRUE) {
      
      if (!is_array($this->Databases))
         $this->Databases = array();
      
      $Key = "{$Host}:{$User}:{$Pass}";
      if (!array_key_exists($Key, $this->Databases) || $Reuse == FALSE) {
         // Open the db connection, new link please
         $Database = @mysql_connect($Host, $User, $Pass, TRUE);
         if (!$Database) {
            throw new ConnectException("Could not connect to database as '{$User}'@'{$Host}'.");
         }
         
         if ($Reuse) {
            $this->Databases[$Key] = $Database;
         }
      } else {
         $Database = $this->Databases[$Key];
      }
      
      if (!is_null($Name) && $Database) {
         $SelectedDatabase = @mysql_select_db($Name, $Database);
         if (!$SelectedDatabase)
            throw new SelectException("Could not select database '{$Name}' on '{$Host}'.");
      }
      
      return $Database;
   }
   
   public function RootDatabase() {
      static $RootDatabase = NULL;
      if (is_null($RootDatabase)) {
         $RootDatabase = $this->Database($this->DBHOST, $this->DBUSER, $this->DBPASS, $this->DBMAIN, FALSE);
      }
      return $RootDatabase;
   }
   
   public function IsValidSourceTag($SourceCodeTag) {
      // Did we get given a tag at all?
      if (empty($SourceCodeTag))
         return FALSE;
      
      // Does this tag even exist?
      $SourceCodeFolder = $SourceCodeTag;
      $SourceCodePath = TaskList::Pathify(TaskList::CombinePaths(array($this->SourceRoot, $SourceCodeFolder)));
      if (!is_dir($SourceCodePath))
         return FALSE;
      
      // Make sure this is actually a valid source tag based on contents
      $SubFolders = scandir($SourceCodePath);
      if (!in_array('vanilla', $SubFolders))
         return FALSE;
      if (!in_array('misc', $SubFolders))
         return FALSE;
      
      return $SourceCodePath;
   }
   
   public function SourceRoot() {
      return $this->SourceRoot;
   }
   
   public function C($Name = FALSE, $Default = NULL) {
      return $this->Config->Get($Name, $Default);
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
               $RealClientFolder = TaskList::CombinePaths($this->Clients, $ClientFolder);
               
               $IsSymlink = is_link($RealClientFolder);
               
               // If needed, detect and ignore symlinks
               if ($this->IgnoreSymlinks && $IsSymlink) {
                  continue;
               }
               
               // If needed, detect and ignore real folders
               if ($this->IgnoreReal && !$IsSymlink) {
                  continue;
               }
               $this->ClientList[$ClientFolder] = 1;
            }
            $this->NumClients = $NumClients = count($this->ClientList);
            TaskList::MajorEvent("found {$NumClients}!");
         break;
         
         case TaskList::ACTION_TARGET:
         case TaskList::ACTION_CREATE:
            // Check if the forum exists
            
            $Action = ($Perform == TaskList::ACTION_TARGET) ? 'target' : 'create';
            
            $Forum = TaskList::GetConsoleOption('target', NULL);
            if (is_null($Forum)) {
               $Forum = TaskList::Input("Please provide the name of the forum you wish to {$Action}, or 'no' to quit :","Forum name [________].{$this->HostingDomain}",NULL);
               if (is_null($Forum) || $Forum == 'no')
                  TaskList::FatalError("No forum provided.");
            }
            
            $QualifiedForumName = $Forum.".{$this->HostingDomain}";
            $ForumPath = TaskList::CombinePaths(array($this->Clients,$QualifiedForumName));
            $Exists = is_dir($ForumPath);
            if ($Perform == TaskList::ACTION_CREATE) {
            
               // When creating, do not require DB.
               $this->RequireValid = FALSE;
               $this->RequireTargetDatabase = FALSE;
               
               if ($Exists && VERBOSE) {
                  $Delete = TaskList::Question("The forum you selected already exists.","Delete it?",array("yes","no"),"no");                     
                  if ($Delete == 'yes') {
                     TaskList::Rmdir($ForumPath);
                  }
               }
               
               if (is_dir($ForumPath)) {
                  TaskList::FatalError(array(
                     'Message'   => "The forum name you requested is already in use.",
                     'Type'      => 'user',
                     'Code'      => '002'
                  ));
               }
            }
            
            if ($Perform == TaskList::ACTION_TARGET) {
               if (!$Exists)
                  TaskList::FatalError("The forum you selected does not exist.");
            }

            $this->TargetForum = $QualifiedForumName;
         break;
      }
   }
   
   public function Clients($ClientDir = NULL) {
      if (is_null($ClientDir))
         $ClientDir = $this->C('VanillaForums.Spawn.Clients','/srv/www/vhosts');
         
      if (!is_dir($ClientDir))
         TaskList::FatalError("Could not open client folder.");
      
      $this->Clients = TaskList::Pathify($ClientDir);
   }
   
   public static function GetConsoleOption($Option, $Default = NULL) {
      if (is_null(TaskList::$Args)) TaskList::$Args = new Args();
            
      if (($Flag = TaskList::$Args->Flag($Option)) !== FALSE)
         return $Flag;
      return $Default;
   }
   
   public function Configure($TaskOrder) {
      if (is_null($this->Clients))
         TaskList::FatalError("No client folder supplied.");
      
      // Setup tasks
      TaskList::MajorEvent("Creating task objects...");
      
      $this->TaskFolder = TaskList::CombinePaths(array(
         TaskList::Pathify(getcwd()),
         TaskList::TASKS
      ));
      $this->ScanTaskFolder($this->TaskFolder, TRUE);
      
      // Setup tasks
      TaskList::MajorEvent("Configuring task objects...");
      foreach ($TaskOrder as $TaskQualifier) {
         if (array_key_exists($TaskQualifier, $this->Tasks) && array_key_exists('task', $this->Tasks[$TaskQualifier]) && $this->Tasks[$TaskQualifier]['task'] instanceof Task) {
            TaskList::Event("Configuring task: {$TaskQualifier}");
            if (method_exists($this->Tasks[$TaskQualifier]['task'], 'Init'))
               $this->Tasks[$TaskQualifier]['task']->Init();
         }
      }
   }
   
   protected function ScanTaskFolder($TaskFolder, $TopLevel = FALSE) {
      $TaskFolder = TaskList::Pathify($TaskFolder);
      
      if (!is_dir($TaskFolder)) {
         // Only really care about a vocal error if its the first item, in which case die too.
         if ($TopLevel === TRUE)
            TaskList::FatalError("[find tasks] Could not find primary task repository '{$TaskFolder}'.");
         return FALSE;
      }
      
      if (!$FolderItems = opendir($TaskFolder))
         TaskList::FatalError("[find tasks] Could not open folder '{$TaskFolder}'... does it have read permissions?");
      
      if ($TopLevel === TRUE)
         $this->Tasks = array();
      
      // Loop over file list
      while (($FolderItem = readdir($FolderItems)) !== FALSE) {
         $FolderItem = trim($FolderItem,'/');
         if ($FolderItem == '.' || $FolderItem == '..') continue;
         $AbsFolderItem = TaskList::CombinePaths(array($TaskFolder,$FolderItem));
         
         // Recurse if we hit a nested folder
         if (is_dir($AbsFolderItem)) {
            $this->ScanTaskFolder($AbsFolderItem);
            continue;
         }
         
         // Otherwise try to read the task file
         try {
            // Not readable
            if (!is_readable($AbsFolderItem)) throw new Exception('cannot read');
            
            // Not a valid task name
            if (!preg_match('/^(.*)\.task\.php$/', $FolderItem, $Matches)) continue;
            
            $Taskname = $Matches[1];
            $QualifiedTaskName = rtrim(dirname(str_replace($this->TaskFolder, '', $AbsFolderItem)),'/').'/'.$Taskname;
            
            // Not a requested task, so don't set it up
            if (!in_array($QualifiedTaskName, $this->RequestedTasks)) continue;
            
            // Include the taskfile and track the change in declared classes
            $Classes = get_declared_classes();
            require_once($AbsFolderItem);
            $NewClasses = array_diff(get_declared_classes(), $Classes);
            
            // For each new class, if it is a taskfile, instantiate it and break.
            foreach ($NewClasses as $Class) {
               if (is_subclass_of($Class, 'Task')) {
                  TaskList::Event("Creating task: {$QualifiedTaskName} (".strtolower($Class).")");
                  $NewTask = new $Class($this->Clients);
                  $NewTask->TaskList =& $this;
                  $NewTask->QualifiedName = $QualifiedTaskName;
                  $this->Tasks[$QualifiedTaskName] = array(
                     'name'            => str_replace('Task', '', $Class),
                     'qualifiedname'   => $QualifiedTaskName,
                     'task'            => $NewTask
                  );
                  break;
               }
            }
                        
         } catch (Exception $e) {
            TaskList::MajorEvent("[{$AbsFolderItem}] ".$e->getMessage());
            continue;
         }
      }
      closedir($FolderItems);
   }
   
   public function Run($RunMode, $TaskList) {
      $this->Mode = $RunMode;
      $this->RequestedTasks = $TaskList;
      
      // Set up tasks
      $this->Configure($TaskList);
      
      // Run pre-tasks
      foreach ($this->Perform as $Perform) {
         $this->PerformAction($Perform);
      }
      
      TaskList::MajorEvent("");
      TaskList::MajorEvent("Configuration:");
      $ValidClients = (($this->RequireValid) ? 'yes' : 'no');
      TaskList::Event("Valid Clients: {$ValidClients}");
      $TargetDatabase = (($this->RequireTargetDatabase) ? 'yes' : 'no');
      TaskList::Event("Auto Database: {$TargetDatabase}");
      TaskList::Event("Running Mode : {$RunMode}");
      TaskList::MajorEvent("");
      
      // Check one more time
      if (TaskList::Cautious()) {
         TaskList::Event("TaskMode: {$this->Mode}");
         $Proceed = TaskList::Question("","Proceed with task execution?",array('yes','no'),'yes');
         if ($Proceed == 'no') exit();
      }
      
      // Go
      switch ($RunMode) {
         case TaskList::MODE_TARGET:
            if (!$this->TargetForum)
               TaskList::FatalError("Attempting to run targeted task without pre-targeting.");
               
            $this->PerformClient($this->TargetForum, $TaskList);
         break;
         
         case TaskList::MODE_CHUNKED:
            $ChunkRule = $this->GetConsoleOption('chunk-mode', 'alphabet');
            $this->RunChunked($ChunkRule, $TaskList);
         break;
         
         case TaskList::MODE_REGEX:
            $RegexRule = $this->GetConsoleOption('rule', NULL);
            if (is_null($RegexRule))
               TaskList::FatalError("No valid regex rule supplied.");
               
            $this->RunSelectiveRegex($RegexRule, $TaskList);
         break;
      }
      
      foreach ($this->Tasks as $TaskName => &$Task) {
         if (method_exists($Task['task'], 'Shutdown'))
            $Task['task']->Shutdown();
      }
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
   
   public function RunChunked($ChunkRule, $TaskOrder) {
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
               $ChunkRules = explode(',',TaskList::$Args->args[0]);
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
            
         //break;
         
         case 'range':
            
         //break;
         
         default:
            die("Invalid chunk type.\n");
         break;
      }
   }
   
   public function PerformClient($ClientFolder, $TaskOrder = NULL) {
      $ClientInfo = $this->LookupClientByFolder($ClientFolder);
      $SiteID = GetValue('SiteID', $ClientInfo, 'unknown site id');
      TaskList::MajorEvent("{$ClientFolder} [{$SiteID}]...");
      $this->Completed++;
      
      if ($this->RequireValid) {
         if (!$ClientInfo || !sizeof($ClientInfo) || !isset($ClientInfo['SiteID'])) {
            TaskList::Event("skipped... no db");
            return;
         }
      }
      
      $ClientName = trim(str_replace($this->HostingDomain, '', $ClientFolder),' .');
      
      try {
         $Client = new Client($this->Clients, $ClientName, $ClientFolder, $ClientInfo);
         $Client->Configure($this, $this->Tasks);
         $Client->Run($TaskOrder);
      } catch (Exception $e) {
         TaskList::MajorEvent($e->getMessage());
         
         try {
            $Email = new Email($Client);
            
            $TaskList = implode(",\n",array_keys($this->Tasks));
            $Error = $e->getMessage();
            
            $Email->To('tim@vanillaforums.com', 'Tim Gunter')
               ->From('runner@vanillaforums.com','VFCom Runner')
               ->Subject("{$ClientFolder} failed to run")
               ->Message("Client {$ClientFolder} experienced an error while running:
               
Task list was:
{$TaskList}

Error:
{$Error}")
               ->Send();
         } catch (Exception $e) {}
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
   public static function Pathify($Path) {
      return rtrim($Path, '/').'/';
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
   
   public static function Rmdir($AbsolutePath) {
      if (!file_exists($AbsolutePath)) return true;
      
      self::RemoveFolder($AbsolutePath);
      return file_exists($AbsolutePath);
   }
   
   /**
    * Remove a folder (and all the sub-folders and files).
    * Taken from http://php.net/manual/en/function.rmdir.php
    * 
    * @param string $Dir 
    * @return void
    */
   public static function RemoveFolder($Path) {
      if (is_file($Path)) {
         unlink($Path);
         return;
      }

      $Path = rtrim($Path, '/').'/';

      // Get all of the files in the directory.
      if ($dh = opendir($Path)) {
         while (($File = readdir($dh)) !== false) {
            if (trim($File, '.') == '')
               continue;

            $SubPath = $Path.$File;

            if (is_dir($SubPath))
               self::RemoveFolder($SubPath);
            else
               unlink($SubPath);
         }
         closedir($dh);
      }
      rmdir($Path);
   }
   
   public static function Touch($AbsolutePath) {
      return @touch($AbsolutePath);
   }
   
   public static function Chmod($AbsolutePath, $FileMode) {
      return @chmod($AbsolutePath, $FileMode);
   }
   
   public static function Chown($AbsolutePath, $Owner = NULL, $Group = NULL) {
      $Success = TRUE;
      if (!is_null($Owner))
         $Success &= @chown($AbsolutePath, $Owner);
      
      if (!is_null($Group))
         $Success &= @chgrp($AbsolutePath, $Group);
      
      return $Success;
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
   
   public static function FatalError($Message, $LineBreak = TRUE) {
      if (is_array($Message)) {
         $Data = $Message;
      } else {
         $Data = array('Message' => $Message);
      }
      
      $Message = GetValue('Message', $Data, NULL);
      $Code = GetValue('Code', $Data, 'unknown');
      $Type = GetValue('Type', $Data, 'internal');
      switch (OUTPUTMODE) {
         case TaskList::OUTMODE_JSON:
            echo json_encode(array_merge($Data, array(
                'Status'   => false,
                'Code'     => $Code,
                'Type'     => $Type,
                'Message'  => $Message
            )));
            break;
         case TaskList::OUTMODE_CLI:
         default:
            echo "{$Message}";
            if ($LineBreak) echo "\n";
            break;
      }
      die();
   }
   
   public static function Success($Message, $LineBreak = TRUE) {
      if (is_array($Message)) {
         $Data = $Message;
      } else {
         $Data = array('Message' => $Message);
      }
      
      $Message = GetValue('Message', $Data, NULL);
      switch (OUTPUTMODE) {
         case TaskList::OUTMODE_JSON:
            echo json_encode(array_merge($Data, array(
                'Status'   => true,
                'Message'  => $Message
            )));
            break;
         case TaskList::OUTMODE_CLI:
         default:
            echo "{$Message}";
            if ($LineBreak) echo "\n";
            break;
      }
      die();
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

class ConnectException extends Exception {}
class SelectException extends Exception {}