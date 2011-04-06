<?php

class PushFront {

   protected $Hostname;
   protected $Address;
   
   protected $RemoteUser;
   protected $RemotePass;
   protected $RemotePath;
   
   public function __construct($Hostname, $Address) {
      echo "New pushfront - {$Hostname}/{$Address}\n";
      $this->Hostname = $Hostname;
      $this->Address = $Address;
      
      $this->RemoteUser = Push::Config('remote user');
      $this->RemotePass = Push::Config('remote password');
      $this->RemotePath = Push::Config('remote path');
      
      $this->Objects = Push::Config('compiled objects');
   }
   
   public function Push() {
      $SourceLevel = Push::Config('source level');
      $StrObjects = Push::Config('objects');
      echo "Pushing {$StrObjects}:{$SourceLevel} for {$this->Hostname}\n";
      
      $ObjectList = explode(',', $Objects);
      foreach ($ObjectList as $Object) {
         $Object = trim($Object);
         $this->PushObject($SourceLevel, $ObjectType);
      }
   }
   
   protected function PushObject($ObjectType) {
      
      /**
       * 
       * rsync -avz <src> <remoteuser>@<remotehost>:<dest>
       * 
       * SPECIFY A TRAILING SLASH to prevent nesting additional directories
       */
      
      
      
   }
   
}