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
      
      $Defaults = array(
          'Url'         => NULL,
          'Timeout'     => $this->C('Garden.SocketTimeout', 2.0),
          'Redirects'   => TRUE,
          'Recycle'     => FALSE
      );
      
      $Options = array_merge($Defaults, $Options);
      
      $RelativeURL = GetValue('URL', $Options);
      $FollowRedirects = GetValue('Redirects', $Options);
      $Timeout = GetValue('Timeout', $Options);
      $Recycle = GetValue('Recycle', $Options, FALSE);
      
      if (!$Absolute)
         $Url = 'http://'.$this->ClientFolder.'/'.ltrim($RelativeURL,'/').'?'.http_build_query($QueryParams);
      else {
         $Url = $RelativeURL;
         if (stristr($RelativeURL, '?'))
            $Url .= '&';
         else
            $Url .= '?';
         $Url .= http_build_query($QueryParams);
      }
      
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
         if (strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;
         
         if (strlen($Cookie) > 0)
            $Cookie .= '; ';
            
         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Response = '';
      if (function_exists('curl_init') && !$Recycle) {
         
         //$Url = $Scheme.'://'.$Host.$Path;
         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, GetValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         
         if ($Timeout > 0)
            curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
         
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
         $Pointer = FALSE;
         $HostAddress = gethostbyname($Host);
         if ($HostAddress == $Host)
            $Recycle = FALSE;
         
         $Recycled = FALSE;
         
         // If we're trying to recycle, look for an existing handler
         if ($Recycle && array_key_exists($HostAddress, self::$ConnectionHandles)) {
            $Pointer = self::$ConnectionHandles[$HostAddress];
            $StreamMeta = @stream_get_meta_data($Pointer);
            
            if ($Pointer && !GetValue('timed_out', $Pointer)) {
               TaskList::MinorEvent("Loaded existing pointer for {$HostAddress}");
               $Recycled = TRUE;
            } else {
               unset($Pointer);
               unset(self::$ConnectionHandles[$HostAddress]);
               TaskList::MinorEvent("Threw away dead pointer for {$HostAddress}");
            }
         }
         
         if (!$Pointer) {
            if ($Recycle)
               TaskList::MinorEvent("Making a new reusable pointer for {$HostAddress}");
            $Pointer = @fsockopen($HostAddress, $Port, $ErrorNumber, $Error);
         }
         
         if (!$Pointer)
            throw new Exception(sprintf('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s', $Url, $ErrorNumber, $Error));
   
         if ($Recycle && !$Recycled) {
            self::$ConnectionHandles[$HostAddress] = &$Pointer;
         }
         
         if ($Timeout > 0 && !$Recycle)
            stream_set_timeout($Pointer, $Timeout);
         
         if (strlen($Cookie) > 0)
            $Cookie = "Cookie: $Cookie\r\n";
         
         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $Header = "GET $Path?$Query HTTP/1.1\r\n"
            ."Host: {$HostHeader}\r\n"
            // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
            // "Authorization: Basic ". base64_encode ("username:password")."\r\n" . 
            ."User-Agent: ".GetValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0')."\r\n"
            ."Accept: */*\r\n"
            ."Accept-Charset: utf-8;\r\n";
            
            if (!$Recycle)
               $Header .= "Connection: close\r\n";
            
         if ($Cookie != '')
            $Header .= $Cookie;
         
         $Header .= "\r\n";
         
         // Send the headers and get the response
         fputs($Pointer, $Header);
         while ($Line = fread($Pointer, 4096)) {
            $Response .= $Line;
         }
         
         if (!$Recycle) {
            TaskList::MinorEvent("Closing onetime pointer for {$HostAddress}");
            @fclose($Pointer);
         }
         
         $Bytes = strlen($Response);
         $Response = trim($Response);
         $Success = TRUE;
         
         $StreamInfo = stream_get_meta_data($Pointer);
         if (GetValue('timed_out', $StreamInfo, FALSE) === TRUE) {
            $Success = FALSE;
            $Response = "Operation timed out after {$Timeout} seconds with {$Bytes} bytes received.";
         }
      } else {
         throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.');
      }
      
      if (!$Success)
         return $Response;
      
      $ResponseParts = explode("\r\n\r\n", $Response);
      
      $ResponseHeaderData = trim(array_shift($ResponseParts));
      $Response = trim(implode("\r\n\r\n",$ResponseParts));
      
      $ResponseHeaderLines = explode("\n",$ResponseHeaderData);
      $Status = trim(array_shift($ResponseHeaderLines));
      $ResponseHeaders = array();
      $ResponseHeaders['HTTP'] = $Status;
      
      /* get the numeric status code. 
       * - trim off excess edge whitespace, 
       * - split on spaces, 
       * - get the 2nd element (as a single element array), 
       * - pop the first (only) element off it... 
       * - return that.
       */
      $ResponseHeaders['StatusCode'] = array_pop(array_slice(explode(' ',$Status),1,1));
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
               return $this->Request($Location, $QueryParams, TRUE);
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