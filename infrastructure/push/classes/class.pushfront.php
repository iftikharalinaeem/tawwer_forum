<?php

class PushFront {

   protected $Hostname;
   protected $Address;
   
   protected $RemoteUser;
   protected $RemotePass;
   protected $RemotePath;
   
   public function __construct($Hostname, $Address) {
      Push::Log(Push::LOG_L_INFO, "Frontend - {$Hostname}/{$Address}");
      $this->Hostname = $Hostname;
      $this->Address = $Address;
      
      $this->RemoteUser = Push::Config('remote user');
      $this->RemotePass = Push::Config('remote password');
      $this->RemotePath = Push::Config('remote path');
      
      $this->Objects = Push::Config('compiled objects');
   }
   
   public function Push() {
      $SourceTag = Push::Config('source tag');
      $StrObjects = Push::Config('objects');
      Push::Log(Push::LOG_L_NOTICE, "Pushing {$StrObjects}:{$SourceTag} for {$this->Hostname}");
      
      $ObjectList = explode(',', $StrObjects);
      foreach ($ObjectList as $Object) {
         $Object = trim($Object);
         $this->PushObject($SourceTag, $Object);
      }
   }
   
   protected function PushObject($SourceTag, $ObjectType) {
      Push::Log(Push::LOG_L_NOTICE, "  {$ObjectType}:{$SourceTag}");
      
      /**
       * 
       * rsync -avz <src> <remoteuser>@<remotehost>:<dest>
       * 
       * SPECIFY A TRAILING SLASH to prevent nesting additional directories
       */
      $Relative = "{$SourceTag}/{$ObjectType}";
      $LocalFolder = Push::Relative($Relative);
      
      $RemotePath = Push::Config('remote path');
      $RemotePath = Push::CombinePaths($RemotePath,$ObjectType);
      
      $this->Rsync($LocalFolder, $RemotePath);
   }
   
   protected function Rsync($Local, $Remote, $Unpathify = TRUE) {
      if ($Unpathify) {
         $Local = Push::UnPathify($Local);
         $Remote = Push::UnPathify($Remote);
      }
      
      $RemoteUser = Push::Config('remote user');
      $RemotePass = Push::Config('remote password', NULL);
      $RemoteSystem = $this->Address;
      
      $RemoteAuth = $RemoteUser;
      if (!is_null($RemotePass))
         $RemoteAuth .= ":{$RemotePass}";
      
      $Response = array();
      exec("rsync -avz {$Local} {$RemoteAuth}:{$RemoteSystem}@{$Remote}");
   }
   
}