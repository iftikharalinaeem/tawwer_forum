<?php

class PushFront {

   protected $Hostname;
   protected $Address;
   
   protected $RemoteUser;
   protected $RemotePass;
   protected $RemotePath;
   
   protected $Exclude;
   protected $RsyncExtra;
   protected $ExpandSymlinks;
   protected $DeleteMissing;
   
   public function __construct($Hostname, $Address) {
      Push::Log(Push::LOG_L_INFO, "Frontend - {$Hostname}/{$Address}");
      $this->Hostname = $Hostname;
      $this->Address = $Address;
      
      $this->RemoteUser = Push::Config('remote user');
      $this->RemotePass = Push::Config('remote password');
      $this->RemotePath = Push::Config('remote path');
      
      $this->Exclude = Push::Config('exclude file', NULL);
      if (!is_null($this->Exclude))
         $this->Exclude = Push::Path($this->Exclude);
      
      $this->RsyncExtra = Push::Config('rsync extra', NULL);
      
      $this->ExpandSymlinks = Push::Config('expand symlinks', TRUE);
      $this->DeleteMissing = Push::Config('delete missing', TRUE);
      
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
      $LocalFolder = Push::Staging($Relative);
      
      $RemotePath = Push::CombinePaths($this->RemotePath,$ObjectType);
      
      $this->Rsync($LocalFolder, $RemotePath);
   }
   
   protected function Rsync($Local, $Remote, $Unpathify = TRUE) {
      if ($Unpathify) {
         $Local = Push::Pathify($Local);
         $Remote = Push::Pathify($Remote);
      }
      
      $Rsync = "rsync ";
      if (!is_null($this->RsyncExtra))
         $Rsync .= rtrim($this->RsyncExtra).' ';
      
      if (!is_null($this->Exclude))
         $Rsync .= "--exclude-from={$this->Exclude} ";
      
      if ($this->ExpandSymlinks)
         $Rsync .= "--copy-links ";
      
      if ($this->DeleteMissing)
         $Rsync .= "--delete ";
      
      $Rsync .= "-avz {$Local} {$this->RemoteUser}@{$this->Address}:{$Remote}";
      $Response = array();
      if (!Push::Config('dry run')) {
         echo "{$Rsync}\n";
         passthru($Rsync);
      }
   }
   
}