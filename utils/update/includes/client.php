<?php

class Client {
   
   protected $Database;
   public $TaskList;
   public $Tasks;
   public $GroupData;
   
   protected $Root;
   protected $ClientRoot;
   protected $ClientFolder;
   protected $ClientInfo;
   protected $ConfigFile;
   protected $Config;
   
   public function __construct($RootFolder, $ClientFolder, $ClientInfo) {
      $this->Root = rtrim($RootFolder,'/');
      
      $this->ClientFolder = $ClientFolder;
      $this->ClientRoot = TaskList::CombinePaths($this->Root, $this->ClientFolder );
      $this->ClientInfo = $ClientInfo;
      $this->ConfigDefaultsFile = TaskList::CombinePaths($this->ClientRoot,'conf/config-defaults.php');
      $this->ConfigFile = TaskList::CombinePaths($this->ClientRoot,'conf/config.php');
      
      $this->Config = new Configuration();
      try {
         $this->Config->Load($this->ConfigDefaultsFile, 'Use');
         $this->Config->Load($this->ConfigFile, 'Use');
      } catch (Exception $e) { die ($e->getMessage()); }
   }
   
   public function Configure(&$TaskList, &$Tasks) {
      $this->TaskList = $TaskList;
      $this->Tasks = $Tasks;
      
      $Host = $this->C('Database.Host', NULL);
      if (is_null($Host))
         throw new Exception("Unknown client database host");
      
      $User = $this->C('Database.User', NULL);
      $Pass = $this->C('Database.Password', NULL);
      $Name = $this->C('Database.Name', NULL);
      if (is_null($Host))
         throw new Exception("Unknown client database name");
      
      $this->Database = &$this->TaskList->Database($Host, $User, $Pass, $Name);
   }
   
   public function Run($TaskOrder) {
      
      $ClientDBName = $this->C('Database.Name');
      mysql_select_db($ClientDBName, $this->Database);
      
      $this->GroupData = array();
      // Run all tasks for this client
      if (!is_null($TaskOrder)) {
         foreach ($TaskOrder as $TaskName) {
            if (!array_key_exists($TaskName, $this->Tasks)) continue;
            $this->Tasks[$TaskName]['task']->SandboxExecute($this);
         }
      } else {
         foreach ($this->Tasks as $TaskName => &$Task)
            $Task['task']->SandboxExecute($this);
      }
   }
   
   public function SaveToConfig($Key, $Value) {
      if (is_null($this->ClientInfo)) return;
      if (LAME) return;
      
      $this->Config->Load($this->ConfigFile, 'Save');
      
      if (!is_array($Key))
         $Key = array($Key => $Value);
      
      foreach ($Key as $k => $v)
         $this->Config->Set($k, $v);
      
      return $this->Config->Save($this->ConfigFile);
   }
   
   public function RemoveFromConfig($Key) {
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
   
   public function TokenAuthentication() {
      $TokenString = md5(RandomString(32).microtime(true));
      
      $EnabledAuthenticators = $this->C('Garden.Authenticator.EnabledSchemes',array());
      if (!is_array($EnabledAuthenticators) || !sizeof($EnabledAuthenticators)) {
         TaskList::Event("Failed to read current authenticator list from config");
         return FALSE;
      }
      
      if (!in_array('token', $EnabledAuthenticators)) {
         $EnabledAuthenticators[] = 'token';
         $this->SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledAuthenticators);
      }
      
      $this->SaveToConfig('Garden.Authenticators.token.Token', $TokenString);
      $this->SaveToConfig('Garden.Authenticators.token.Expiry', date('Y-m-d H:i:s',time()+30));
      return $TokenString;
   }
   
   public function EnablePlugin($PluginName) {
      TaskList::Event("Enabling plugin '{$PluginName}'...", TaskList::NOBREAK);
      try {
         $Token = $this->TokenAuthentication();
         if ($Token === FALSE) throw new Exception("could not generate token");
         $Result = $this->Request('plugin/forceenableplugin/'.$PluginName,array(
            'token'  => $Token
         ));
      } catch (Exception $e) {
         $Result = 'msg: '.$e->getMessage();
      }
      TaskList::Event((($Result == "TRUE") ? "success" : "failure ({$Result})"));
      return ($Result == 'TRUE') ? TRUE : FALSE;
   }
   
   public function DisablePlugin($PluginName) {
      TaskList::Event("Disabling plugin '{$PluginName}'...", TaskList::NOBREAK);
      try {
         $Token = $this->TokenAuthentication();
         if ($Token === FALSE) throw new Exception("could not generate token");
         $Result = $this->Request('plugin/forcedisableplugin/'.$PluginName,array(
            'token'  => $Token
         ));
      } catch (Exception $e) {
         $Result = 'msg: '.$e->getMessage();
      }
      TaskList::Event((($Result == "TRUE") ? "success" : "failure ({$Result})"));
      return ($Result == 'TRUE') ? TRUE : FALSE;
   }
   
   public function PrivilegedExec($RelativeURL, $QueryParams = array(), $Absolute = FALSE) {
      try {
         $Token = $this->TokenAuthentication();
         if ($Token === FALSE) 
            throw new Exception("could not generate token");
         $QueryParams['token'] = $Token;
         $Result = $this->Request($RelativeURL,$QueryParams);
      } catch (Exception $e) {
         $Result = 'msg: '.$e->getMessage();
      }
      return $Result;
   }
   
   public function Request($Options, $QueryParams = array(), $Absolute = FALSE) {
      if (is_string($Options)) {
         $Options = array(
             'URL'      => $Options
         );
      }
      if (!array_key_exists('URL', $Options))
         return FALSE;
      
      $Url = &$Options['URL'];
      if (!$Absolute && substr($Url,0,4) !== 'http')
         $Url = 'http://'.$this->ClientFolder.'/'.ltrim($Url,'/');
      
      $ProxyRequest = new ProxyRequest();
      return $ProxyRequest->Request($Options, $QueryParams);
   }

   public function C($Name = FALSE, $Default = FALSE) {
      if (is_null($this->ClientInfo)) return;
      return $this->Config->Get($Name, $Default);
   }
   
   public function Symlink($RelativeLink, $Source = NULL, $Respectful = FALSE) {
      $AbsoluteLink = TaskList::CombinePaths($this->ClientRoot,$RelativeLink);
      TaskList::Symlink($AbsoluteLink, $Source, $Respectful);
   }
   
   public function Mkdir($RelativePath) {
      $AbsolutePath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      TaskList::Mkdir($AbsolutePath);
   }
   
   public function Touch($RelativePath) {
      $AbsolutePath = TaskList::CombinePaths($this->ClientRoot,$RelativePath);
      TaskList::Touch($AbsolutePath);
   }
   
   public function CopySourceFile($RelativePath, $SourcecodePath) {
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