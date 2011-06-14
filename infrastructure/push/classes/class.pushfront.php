<?php

/*
Copyright 2011, Tim Gunter
This file is part of Push.
Push is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Push is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Push.  If not, see <http://www.gnu.org/licenses/>.
Contact Tim Gunter at tim [at] vanillaforums [dot] com
*/

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
   protected $ClearAutoloader;
   
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
      $this->ClearAutoloader = Push::Config('clear autoloader', TRUE);
      
      $this->Objects = Push::Config('compiled objects');
   }
   
   public function Push() {
      $SourceTag = Push::Config('source tag');
      $StrObjects = Push::Config('objects');
      Push::Log(Push::LOG_L_NOTICE, "Pushing {$StrObjects}:{$SourceTag} for {$this->Hostname}");
      
      if (!Push::Config('fast')) {
         $Proceed = Push::Question(NULL, "Are you sure?", array('yes','no'), 'no');
         if ($Proceed == 'no') return;
      }
      
      $ObjectList = explode(',', $StrObjects);
      foreach ($ObjectList as $Object) {
         $Object = trim($Object);
         $this->PushObject($SourceTag, $Object);
      }
      
      if ($this->ClearAutoloader) {
         Push::Log(Push::LOG_L_NOTICE, "Clearing autoloader cache");
         $RemoteCacheFiles = Push::UnPathify(Push::CombinePaths($this->RemotePath, 'frontend/cache/*.ini'));
         if (!Push::Config('dry run')) {
            passthru("ssh {$this->RemoteUser}@{$this->Address} 'rm -f {$RemoteCacheFiles}'");
         }
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
         passthru($Rsync);
      }
   }
   
}