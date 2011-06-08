<?php

abstract class Task {

   public $Database;
   public $TaskList;
   
   protected $Root;
   protected $ClientRoot;
   protected $ClientFolder;
   protected $ClientInfo;
   protected $ConfigFile;
   protected $Config;
   
   protected static $ConnectionHandles = array();

   abstract protected function Run();

   public function __construct($RootFolder) {
      $this->Root = rtrim($RootFolder,'/');
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
      
      $this->ConfigDefaultsFile = TaskList::CombinePaths($this->ClientRoot,'conf/config-defaults.php');
      $this->ConfigFile = TaskList::CombinePaths($this->ClientRoot,'conf/config.php');
      $this->Config = new Configuration();
      try {
         $this->Config->Load($this->ConfigDefaultsFile, 'Use');
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
   
   protected function EnablePlugin($PluginName) {
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
   
   protected function DisablePlugin($PluginName) {
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
   
   protected function PrivilegedExec($RelativeURL, $QueryParams = array(), $Absolute = FALSE) {
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
   
   protected function Request($Options, $QueryParams = array(), $Absolute = FALSE) {
      if (is_string($Options)) {
         $Options = array(
             'URL'      => $Options
         );
      }
      if (!array_key_exists('URL', $Options))
         return FALSE;
      
      if ($Absolute && substr($Options['URL'],0,4) !== 'http')
         $Options['URL'] = 'http://'.$this->ClientFolder.'/'.ltrim($Options['URL'],'/');
      
      $ProxyRequest = new ProxyRequest();
      return $ProxyRequest->Request($Options, $QueryParams);
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