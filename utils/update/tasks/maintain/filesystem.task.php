<?php

class FilesystemTask extends Task {

   protected $SourcecodePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->ReallyRun = TRUE;
      
      $this->RootPath = FALSE;
      $this->VanillaPath = FALSE;
      $this->MiscPath = FALSE;
      $this->AddonsPath = FALSE;
      $this->PluginPath = FALSE;
      $this->ThemePath = FALSE;
   }
   
   public function Init($RootPath = NULL) {
   
      $this->SourceRoot = TaskList::Pathify(TaskList::GetConsoleOption('source','/srv/www/source'));
      if (!file_exists($this->SourceRoot) || !is_dir($this->SourceRoot))
         TaskList::FatalError("Could not find sourcecode root folder.");
      
      
      if (is_null($RootPath) || !is_dir($RootPath)) {
         do {
            $SourceCodeFolder = TaskList::Input("Enter new sourcecode version for selected clients, or 'no' to skip", "Sourcecode Version", "unstable");
            $SourceCodePath = TaskList::Pathify(TaskList::CombinePaths(array($this->SourceRoot, $SourceCodeFolder)));
            
         } while (strtolower($SourceCodeFolder) != 'no' && !is_dir($SourceCodePath));
         if (strtolower($SourceCodeFolder) != 'no') {
            $this->VanillaPath = TaskList::CombinePaths($SourceCodePath,'vanilla');
            $this->MiscPath = TaskList::CombinePaths($SourceCodePath,'misc');
            $this->AddonsPath = TaskList::CombinePaths($SourceCodePath,'addons');
            
            $this->PluginPath = TaskList::CombinePaths($this->MiscPath, 'plugins');
            $this->ThemePath = TaskList::CombinePaths($this->MiscPath, 'themes');
         } else {
            $this->ReallyRun = FALSE;
         }
      } else {
         // Root provided and exists
         $this->RootPath = $RootPath;
      
         $VanillaPath = TaskList::CombinePaths($this->RootPath, 'vanilla');
         $MiscPath = TaskList::CombinePaths($this->RootPath, 'misc');
         $AddonsPath = TaskList::CombinePaths($this->RootPath, 'addons');
         
         if (is_dir($VanillaPath))
            $this->VanillaPath = $VanillaPath;
            
         if (is_dir($MiscPath)) {
            $this->MiscPath = $MiscPath;
            $this->PluginPath = TaskList::CombinePaths($this->MiscPath, 'plugins');
            $this->ThemePath = TaskList::CombinePaths($this->MiscPath, 'themes');
         }
         
         if (is_dir($AddonsPath)) {
            $this->AddonsPath = $AddonsPath;
         }
      }
      
      if ($this->VanillaPath)
         TaskList::Event("Vanilla Path: {$this->VanillaPath}");
      if ($this->MiscPath)
         TaskList::Event("Misc Path: {$this->MiscPath}");
   }
   
   protected function Run() {
      if ($this->ReallyRun === FALSE) return;
      $ClientFolder = $this->ClientFolder();
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really apply symlinks for {$ClientFolder}?","Apply symlinks?",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
            
      if ($this->VanillaPath !== FALSE) {
         // Symlink Applications
         $this->Client->Symlink('applications/dashboard', TaskList::CombinePaths($this->VanillaPath,'applications/dashboard'));
         $this->Client->Symlink('applications/conversations', TaskList::CombinePaths($this->VanillaPath,'applications/conversations'));
         $this->Client->Symlink('applications/vanilla', TaskList::CombinePaths($this->VanillaPath,'applications/vanilla'));
         
         // Symlink bootstrap.php
         $this->Client->Symlink('bootstrap.php', TaskList::CombinePaths($this->VanillaPath,'bootstrap.php'));
         
         // Symlink config files
         $this->Client->Symlink('conf/bootstrap.before.php', TaskList::CombinePaths($this->VanillaPath,'conf/bootstrap.before.php'));
         $this->Client->Symlink('conf/config-defaults.php', TaskList::CombinePaths($this->VanillaPath,'conf/config-defaults.php'));
         $this->Client->Symlink('conf/constants.php', TaskList::CombinePaths($this->VanillaPath,'conf/constants.php'));
         $this->Client->Symlink('conf/locale.php', TaskList::CombinePaths($this->VanillaPath,'conf/locale.php'), TRUE);
         
         // Symlink core folders
         $this->Client->Symlink('js', TaskList::CombinePaths($this->VanillaPath,'js'));
         $this->Client->Symlink('library', TaskList::CombinePaths($this->VanillaPath,'library'));
         
         // Symlink all core feature plugins
         $this->Client->Symlink('plugins/HtmLawed', TaskList::CombinePaths($this->VanillaPath,'plugins/HtmLawed'));
         $this->Client->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->VanillaPath,'plugins/Gravatar'));
         $this->Client->Symlink('plugins/VanillaInThisDiscussion', TaskList::CombinePaths($this->VanillaPath,'plugins/VanillaInThisDiscussion'));
         $this->Client->Symlink('plugins/Tagging', TaskList::CombinePaths($this->VanillaPath,'plugins/Tagging'));
         $this->Client->Symlink('plugins/Flagging', TaskList::CombinePaths($this->VanillaPath,'plugins/Flagging'));
         $this->Client->Symlink('plugins/embedvanilla', TaskList::CombinePaths($this->VanillaPath,'plugins/embedvanilla'));
         $this->Client->Symlink('plugins/Emotify', TaskList::CombinePaths($this->VanillaPath,'plugins/Emotify'));
         $this->Client->Symlink('plugins/cleditor', TaskList::CombinePaths($this->VanillaPath,'plugins/cleditor'));
         
         $this->Client->Symlink('plugins/Facebook', TaskList::CombinePaths($this->VanillaPath,'plugins/Facebook'));
         $this->Client->Symlink('plugins/Twitter', TaskList::CombinePaths($this->VanillaPath,'plugins/Twitter'));
         $this->Client->Symlink('plugins/GoogleSignIn', TaskList::CombinePaths($this->VanillaPath,'plugins/GoogleSignIn'));
         $this->Client->Symlink('plugins/OpenID', TaskList::CombinePaths($this->VanillaPath,'plugins/OpenID'));
         
         // Copy the new index file
         $Copied = $this->CopySourceFile('index.php', $this->VanillaPath);
         $this->Cache('Updated',$Copied);
      }
      
      if ($this->MiscPath !== FALSE) {
         //Symlink .htaccess
         $this->Client->Symlink('.htaccess', TaskList::CombinePaths($this->MiscPath,'utils/.htaccess'));
      }
      
      if ($this->PluginPath !== FALSE) {
         // Symlink GettingStartedHosting plugin
         $this->Client->Symlink('plugins/GettingStartedHosting', TaskList::CombinePaths($this->PluginPath,'GettingStartedHosting'));
         
         // Symlink all misc feature plugins
         $this->Client->Symlink('plugins/VanillaConnect', TaskList::CombinePaths($this->PluginPath,'VanillaConnect'));
         $this->Client->Symlink('plugins/ProxyConnect', TaskList::CombinePaths($this->PluginPath,'ProxyConnect'));
         $this->Client->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->PluginPath,'CustomDomain'));
         $this->Client->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->PluginPath,'CustomTheme'));
         $this->Client->Symlink('plugins/googleadsense', TaskList::CombinePaths($this->PluginPath,'googleadsense'));
         $this->Client->Symlink('plugins/Statistics', TaskList::CombinePaths($this->PluginPath,'Statistics'));
         $this->Client->Symlink('plugins/PrivateCommunity', TaskList::CombinePaths($this->PluginPath,'PrivateCommunity'));
         $this->Client->Symlink('plugins/vfspoof', TaskList::CombinePaths($this->PluginPath,'vfspoof'));
         $this->Client->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->PluginPath,'vfoptions'));
      }
      
      if ($this->AddonsPath !== FALSE) {
         $this->Client->Symlink('plugins/FileUpload', TaskList::CombinePaths($this->AddonsPath,'plugins/FileUpload'));
      }
      
      if ($this->ThemePath !== FALSE) {
         // Symlink all core themes
         $this->Client->Symlink('themes/minalla', TaskList::CombinePaths($this->ThemePath,'minalla'));
         $this->Client->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->ThemePath,'light-grunge'));
         $this->Client->Symlink('themes/ivanilla', TaskList::CombinePaths($this->ThemePath,'iVanilla'));
         $this->Client->Symlink('themes/simple', TaskList::CombinePaths($this->ThemePath,'simple'));
         $this->Client->Symlink('themes/rounder', TaskList::CombinePaths($this->ThemePath,'rounder'));
         $this->Client->Symlink('themes/vanillaclassic', TaskList::CombinePaths($this->ThemePath,'vanillaclassic'));
         $this->Client->Symlink('themes/v1grey', TaskList::CombinePaths($this->ThemePath,'v1grey'));
         
         $this->Client->Symlink('themes/mobile', TaskList::CombinePaths($this->VanillaPath,'themes/mobile'));
         $this->Client->Symlink('themes/EmbedFriendly', TaskList::CombinePaths($this->VanillaPath,'themes/EmbedFriendly'));
         
         // Replace default theme with smartydefault
         $this->Client->Symlink('themes/default', TaskList::CombinePaths($this->ThemePath,'defaultsmarty'));
      }
      
   }

}

