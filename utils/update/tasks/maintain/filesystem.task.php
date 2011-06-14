<?php

class FilesystemTask extends Task {

   protected static $SourceRoot;

   protected $ReallyRun = FALSE;

   protected $SourceCodePath;
      
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init() {
      
      if (TaskList::Cautious()) {
         $ReallyRun = TaskList::Question("Apply all new symlinks?", "Apply symlinks", array('yes','no','exit'), 'yes');
         if ($ReallyRun == 'no') return;
         if ($ReallyRun == 'exit') exit();
      }
      
      $this->ReallyRun = TRUE;
      
      $ForceSourceTag = FALSE;
      
      // Allow slaving of FileSystem to other tasks' SCT
      if (!$ForceSourceTag)
         $ForceSourceTag = Task::Get('SourceCodeTag', FALSE);
      
      // Allow forcing SCT from command line
      if (!$ForceSourceTag)
         $ForceSourceTag = TaskList::GetConsoleOption('source', FALSE);
      
      if ($ForceSourceTag !== FALSE) {
         $SourceCodeFolder = $ForceSourceTag;
         $SourceCodePath = $this->TaskList->IsValidSourceTag($ForceSourceTag);
         if (!$SourceCodePath)
            TaskList::FatalError("Specified sourcecode tag is invalid.");
      } else {
         $DefaultSCT = $this->TaskList->C('VanillaForums.DeploymentVersion', 'unstable');
         do {
            $SourceCodeFolder = TaskList::Input("Enter new sourcecode version for selected clients, or 'no' to skip", "Sourcecode Version", $DefaultSCT);
            $SourceCodePath = $this->TaskList->IsValidSourceTag($SourceCodeFolder);
         } while (strtolower($SourceCodeFolder) != 'no' && !is_dir($SourceCodePath));
      }
      
      if (strtolower($SourceCodeFolder) != 'no') {
         $this->SourceCodePath = $SourceCodePath;
         TaskList::MinorEvent("Source code tag: {$this->SourceCodePath}");
      } else {
         $this->ReallyRun = FALSE;
      }
   }
   
   protected function Run() {
      if ($this->ReallyRun === FALSE) return;
      $ClientFolder = $this->ClientFolder();
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really apply symlinks for {$ClientFolder}?","Apply symlinks",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
            
      if ($this->Path('vanilla') !== FALSE) {
         // Symlink Applications
         $this->Client->Symlink('applications/dashboard', TaskList::CombinePaths($this->Path('vanilla'), 'applications/dashboard'));
         $this->Client->Symlink('applications/conversations', TaskList::CombinePaths($this->Path('vanilla'), 'applications/conversations'));
         $this->Client->Symlink('applications/vanilla', TaskList::CombinePaths($this->Path('vanilla'), 'applications/vanilla'));
         
         // Symlink bootstrap.php
         $this->Client->Symlink('bootstrap.php', TaskList::CombinePaths($this->Path('vanilla'), 'bootstrap.php'));
         
         // Symlink config files
         $this->Client->Symlink('conf/bootstrap.before.php', TaskList::CombinePaths($this->Path('vanilla'), 'conf/bootstrap.before.php'));
         $this->Client->Symlink('conf/config-defaults.php', TaskList::CombinePaths($this->Path('vanilla'), 'conf/config-defaults.php'));
         $this->Client->Symlink('conf/constants.php', TaskList::CombinePaths($this->Path('vanilla'), 'conf/constants.php'));
         $this->Client->Symlink('conf/locale.php', TaskList::CombinePaths($this->Path('vanilla'), 'conf/locale.php'), TRUE);
         
         // Symlink core folders
         $this->Client->Symlink('js', TaskList::CombinePaths($this->Path('vanilla'), 'js'));
         $this->Client->Symlink('library', TaskList::CombinePaths($this->Path('vanilla'), 'library'));
         
         // Symlink all core feature plugins
         $this->Client->Symlink('plugins/HtmLawed', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/HtmLawed'));
         $this->Client->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Gravatar'));
         $this->Client->Symlink('plugins/VanillaInThisDiscussion', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/VanillaInThisDiscussion'));
         $this->Client->Symlink('plugins/Tagging', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Tagging'));
         $this->Client->Symlink('plugins/Flagging', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Flagging'));
         $this->Client->Symlink('plugins/embedvanilla', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/embedvanilla'));
         $this->Client->Symlink('plugins/Emotify', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Emotify'));
         $this->Client->Symlink('plugins/cleditor', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/cleditor'));
         
         $this->Client->Symlink('plugins/Facebook', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Facebook'));
         $this->Client->Symlink('plugins/Twitter', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/Twitter'));
         $this->Client->Symlink('plugins/GoogleSignIn', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/GoogleSignIn'));
         $this->Client->Symlink('plugins/OpenID', TaskList::CombinePaths($this->Path('vanilla'), 'plugins/OpenID'));
         
         // Copy the new index file
         $Copied = $this->CopySourceFile('index.php', $this->Path('vanilla'));
         $this->Cache('Updated',$Copied);
      }
      
      if ($this->Path('misc') !== FALSE) {
         //Symlink .htaccess
         $this->Client->Symlink('.htaccess', TaskList::CombinePaths($this->Path('misc'),'utils/.htaccess'));
      }
      
      if ($this->Path('plugins') !== FALSE) {
         // Symlink GettingStartedHosting plugin
         $this->Client->Symlink('plugins/GettingStartedHosting', TaskList::CombinePaths($this->Path('plugins'),'GettingStartedHosting'));
         
         // Symlink all misc feature plugins
         $this->Client->Symlink('plugins/VanillaConnect', TaskList::CombinePaths($this->Path('plugins'), 'VanillaConnect'));
         $this->Client->Symlink('plugins/ProxyConnect', TaskList::CombinePaths($this->Path('plugins'), 'ProxyConnect'));
         $this->Client->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->Path('plugins'), 'CustomDomain'));
         $this->Client->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->Path('plugins'), 'CustomTheme'));
         $this->Client->Symlink('plugins/googleadsense', TaskList::CombinePaths($this->Path('plugins'), 'googleadsense'));
         $this->Client->Symlink('plugins/Statistics', TaskList::CombinePaths($this->Path('plugins'), 'Statistics'));
         $this->Client->Symlink('plugins/PrivateCommunity', TaskList::CombinePaths($this->Path('plugins'),'PrivateCommunity'));
         $this->Client->Symlink('plugins/vfspoof', TaskList::CombinePaths($this->Path('plugins'), 'vfspoof'));
         $this->Client->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->Path('plugins'), 'vfoptions'));
      }
      
      if ($this->Path('addons') !== FALSE) {
         $this->Client->Symlink('plugins/FileUpload', TaskList::CombinePaths($this->Path('addons'), 'plugins/FileUpload'));
      }
      
      if ($this->Path('themes') !== FALSE) {
         // Symlink misc themes
         $this->Client->Symlink('themes/minalla', TaskList::CombinePaths($this->Path('themes'), 'minalla'));
         $this->Client->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->Path('themes'), 'light-grunge'));
         $this->Client->Symlink('themes/ivanilla', TaskList::CombinePaths($this->Path('themes'), 'iVanilla'));
         $this->Client->Symlink('themes/simple', TaskList::CombinePaths($this->Path('themes'), 'simple'));
         $this->Client->Symlink('themes/rounder', TaskList::CombinePaths($this->Path('themes'), 'rounder'));
         $this->Client->Symlink('themes/vanillaclassic', TaskList::CombinePaths($this->Path('themes'), 'vanillaclassic'));
         $this->Client->Symlink('themes/v1grey', TaskList::CombinePaths($this->Path('themes'), 'v1grey'));
         
         // Symlink core themes
         $this->Client->Symlink('themes/mobile', TaskList::CombinePaths($this->Path('vanilla'), 'themes/mobile'));
         $this->Client->Symlink('themes/EmbedFriendly', TaskList::CombinePaths($this->Path('vanilla'), 'themes/EmbedFriendly'));
         
         // Replace default theme with smartydefault
         $this->Client->Symlink('themes/default', TaskList::CombinePaths($this->Path('themes'), 'defaultsmarty'));
      }
      
   }
   
   protected function Path($PathType) {
      static $Compiled = array();
      
      if (array_key_exists($PathType, $Compiled))
         return $Compiled[$PathType];
      
      switch ($PathType) {
         case 'vanilla': return $Compiled[$PathType] = TaskList::CombinePaths($this->SourceCodePath,'vanilla');
         case 'addons': return $Compiled[$PathType] = TaskList::CombinePaths($this->SourceCodePath,'addons');
         case 'misc': return $Compiled[$PathType] = TaskList::CombinePaths($this->SourceCodePath,'misc');
         case 'plugins': return $Compiled[$PathType] = TaskList::CombinePaths($this->Path('misc'),'plugins');
         case 'themes': return $Compiled[$PathType] = TaskList::CombinePaths($this->Path('misc'),'themes');
         default: return FALSE;
      }
   }

}

