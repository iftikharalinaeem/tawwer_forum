<?php

error_reporting(E_ALL);
define('APPLICATION', 'VanillaUpdate');
// Represents a config file
require_once("configuration.php");

class TaskList {

   const NOBREAK = FALSE;
   
   public $GroupData;
   protected $Clients;
   protected $Tasks;
   protected $Database;
   protected $ClientList;
   protected $Config;
   protected $NumClients;
   protected $Completed;
   
   public function __construct($UserTaskDirs, $ClientDir) {
   
      $ConfigDefaultsFile = '/srv/www/vanillaforumscom/conf/config-defaults.php';
      $ConfigFile = '/srv/www/vanillaforumscom/conf/config.php';
      $this->Config = new Configuration();
      try {
         $this->Config->Load($ConfigDefaultsFile, 'Use');
         $this->Config->Load($ConfigFile, 'Use');
      } catch (Exception $e) { die ($e->getMessage()); }
      
      $this->Completed = $this->NumClients = 0;
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
      $this->NumClients = $NumClients = count($this->ClientList);
      TaskList::MajorEvent("found {$NumClients}!", TaskList::NOBREAK);
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("","Proceed with task execution?",array('yes','no'),'no');
         if ($Proceed == 'no') exit();
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
            $Chunks[] = '[0-9]';
            $Chunks = array_merge($Chunks, range('a','z'));
            
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

abstract class Task {

   public $Database;
   public $TaskList;
   
   protected $Root;
   protected $ClientRoot;
   protected $ClientFolder;
   protected $ClientInfo;
   protected $ConfigFile;
   protected $Config;

   abstract protected function Run();

   public function __construct($RootFolder) {
      $this->Root = rtrim($RootFolder,'/');
      TaskList::Event("Set root folder to '{$this->Root}'");
      $this->ClientRoot = NULL;
      $this->ClientFolder = NULL;
      $this->ClientInfo = NULL;
      $this->ConfigFile = NULL;
      $this->Config = new Configuration();
   }
   
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
   
   protected function Cache($Key, $Value = NULL) {
      if (is_null($Value))
         return (array_key_exists($Key, $this->TaskList->GroupData)) ? $this->TaskList->GroupData[$Key] : NULL;
         
      return $this->TaskList->GroupData[$Key] = $Value;
   }
   
   protected function Uncache($Key) {
      unset($this->TaskList->GroupData[$Key]);
   }
   
   protected function SaveToConfig($Key, $Value) {
      if (is_null($this->ClientInfo)) return;
      if (LAME) return;
      
      $this->Config->Load($this->ConfigFile, 'Save');
      
      if (!is_array($Key))
         $Key = array($Key => $Value);
      
      foreach ($Key as $k => $v)
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
   
   protected function TokenAuthentication() {
      $RandomDataStream = fopen('/dev/random','r');
      $RandomData = fread($RandomDataStream, 32);
      $TokenString = md5($RandomData);
      
      $EnabledAuthenticators = $this->C('Garden.Authenticator.EnabledSchemes');
      if (!in_array('token', $EnabledAuthenticators)) {
         $EnabledAuthenticators[] = 'token';
         $this->SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledAuthenticators);
      }
      
      $this->SaveToConfig('Garden.Authenticators.token.Token', $TokenString);
      $this->SaveToConfig('Garden.Authenticators.token.Expiry', date('Y-m-d H:i:s',time()+30));
   }
   
   protected function EnablePlugin($PluginName) {
      $Token = $this->TokenAuthentication();
      $Result = $this->Request('plugin/forceenableplugin/'.$PluginName,array(
         'token'  => $Token
      ));
      
      return ($Result == 'TRUE') ? TRUE : FALSE;
   }
   
   protected function DisablePlugin($PluginName) {
      $Token = $this->TokenAuthentication();
      $Result = $this->Request('plugin/forcedisableplugin/'.$PluginName,array(
         'token'  => $Token
      ));
      
      return ($Result == 'TRUE') ? TRUE : FALSE;
   }
   
   protected function Request($RelativeURL, $QueryParams = array()) {
      $Timeout = C('Garden.SocketTimeout', 2.0);
      
      $Url = 'http://'.$this->ClientFolder.'/'.ltrim($RelativeURL,'/').'?'.http_build_query($QueryParams);
      
      $UrlParts = parse_url($Url);
      $Scheme = GetValue('scheme', $UrlParts, 'http');
      $Host = GetValue('host', $UrlParts, '');
      $Port = GetValue('port', $UrlParts, '80');
      $Path = GetValue('path', $UrlParts, '');
      $Query = GetValue('query', $UrlParts, '');
      // Get the cookie.
      $Cookie = '';
      $EncodeCookies = TRUE;
      
      foreach($_COOKIE as $Key => $Value) {
         if(strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;
         
         if(strlen($Cookie) > 0)
            $Cookie .= '; ';
            
         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Response = '';
      if (function_exists('curl_init')) {
         
         //$Url = $Scheme.'://'.$Host.$Path;
         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         if ($Cookie != '')
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);
         
         // TIM @ 2010-06-28: Commented this out because it was forcing all requests with parameters to be POST. Same for the $Url above
         // 
         //if ($Query != '') {
         //   curl_setopt($Handler, CURLOPT_POST, 1);
         //   curl_setopt($Handler, CURLOPT_POSTFIELDS, $Query);
         //}
         
         $Response = curl_exec($Handler);
         $Success = TRUE;
         if ($Response == FALSE) {
            $Success = FALSE;
            $Response = curl_error($Handler);
         }
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         $Referer = Gdn_Url::WebRoot(TRUE);
      
         // Make the request
         $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error);
         if (!$Pointer)
            throw new Exception(sprintf(T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'), $Url, $ErrorNumber, $Error));
   
         if(strlen($Cookie) > 0)
            $Cookie = "Cookie: $Cookie\r\n";
         
         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $Header = "GET $Path?$Query HTTP/1.1\r\n"
            ."Host: {$HostHeader}\r\n"
            // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
            // "Authorization: Basic ". base64_encode ("username:password")."\r\n" . 
            ."User-Agent: ".ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0')."\r\n"
            ."Accept: */*\r\n"
            ."Accept-Charset: utf-8;\r\n"
            ."Referer: {$Referer}\r\n"
            ."Connection: close\r\n";
            
         if ($Cookie != '')
            $Header .= $Cookie;
         
         $Header .= "\r\n";
         
         // Send the headers and get the response
         fputs($Pointer, $Header);
         while ($Line = fread($Pointer, 4096)) {
            $Response .= $Line;
         }
         @fclose($Pointer);
         $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
         $Success = TRUE;
      } else {
         throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
      }
      
      if (!$Success)
         return $Response;
      
      $ResponseHeaderData = trim(substr($Response, 0, strpos($Response, "\r\n\r\n")));
      $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
      
      $ResponseHeaderLines = explode("\n",trim($ResponseHeaderData));
      $Status = array_shift($ResponseHeaderLines);
      $ResponseHeaders = array();
      $ResponseHeaders['HTTP'] = trim($Status);
      
      /* get the numeric status code. 
       * - trim off excess edge whitespace, 
       * - split on spaces, 
       * - get the 2nd element (as a single element array), 
       * - pop the first (only) element off it... 
       * - return that.
       */
      $ResponseHeaders['StatusCode'] = array_pop(array_slice(explode(' ',trim($Status)),1,1));
      foreach ($ResponseHeaderLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $ResponseHeaders[$Key] = $Value;
      }
      
      if ($FollowRedirects) { 
         $Code = GetValue('StatusCode',$ResponseHeaders, 200);
         if (in_array($Code, array(301,302))) {
            if (array_key_exists('Location', $ResponseHeaders)) {
               $Location = GetValue('Location', $ResponseHeaders);
               return ProxyRequest($Location, $OriginalTimeout, $FollowRedirects);
            }
         }
      }
      
      return $Response;
   }

   protected function C($Name = FALSE, $Default = FALSE) {
      if (is_null($this->ClientInfo)) return;
      return $this->Config->Get($Name, $Default);
   }
   
   protected function Symlink($RelativeLink, $Source = NULL, $Respectful = FALSE) {
      $AbsoluteLink = TaskList::CombinePaths($this->ClientRoot,$RelativeLink);
      TaskList::Symlink($AbsoluteLink, $Source, $Respectful);
   }
   
   protected function Mkdir($RelativePath) {
      $AbsolutePath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      TaskList::Mkdir($AbsolutePath);
   }
   
   protected function Touch($RelativePath) {
      $AbsolutePath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      TaskList::Touch($AbsolutePath);
   }
   
   protected function CopySourceFile($RelativePath, $SourcecodePath) {
      $AbsoluteClientPath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      $AbsoluteSourcePath = TaskList::CombinePaths($SourcecodePath,$RelativePath);
      
      $NewFileHash = md5_file($AbsoluteSourcePath);
      $OldFileHash = NULL;
      if (file_exists($AbsoluteClientPath)) {
         $OldFileHash = md5_file($AbsoluteClientPath);
         if ($OldFileHash == $NewFileHash) {
            TaskList::Event("copy aborted. local {$RelativePath} is the same as {$AbsoluteSourcePath}");
            return FALSE;
         }
         if (!LAME) unlink($AbsoluteClientPath);
      }
      
      TaskList::Event("copy '{$AbsoluteSourcePath} / ".md5_file($AbsoluteSourcePath)."' to '{$AbsoluteClientPath} / {$OldFileHash}'");
      if (!LAME) copy($AbsoluteSourcePath, $AbsoluteClientPath);
      return TRUE;
   }

}
