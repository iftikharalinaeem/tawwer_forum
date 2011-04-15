<?php

class FilesystemTask extends Task {

   protected $SourcecodePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->RootPath = FALSE;
      $this->VanillaPath = FALSE;
      $this->MiscPath = FALSE;
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
            $this->PluginPath = TaskList::CombinePaths($this->MiscPath, 'plugins');
            $this->ThemePath = TaskList::CombinePaths($this->MiscPath, 'themes');
         }
      } else {
         // Root provided and exists
         $this->RootPath = $RootPath;
      
         $VanillaPath = TaskList::CombinePaths($this->RootPath, 'vanilla');
         $MiscPath = TaskList::CombinePaths($this->RootPath, 'misc');
         
         if (is_dir($VanillaPath))
            $this->VanillaPath = $VanillaPath;
            
         if (is_dir($MiscPath)) {
            $this->MiscPath = $MiscPath;
            $this->PluginPath = TaskList::CombinePaths($this->MiscPath, 'plugins');
            $this->ThemePath = TaskList::CombinePaths($this->MiscPath, 'themes');
         }
      }
      
      if ($this->VanillaPath)
         TaskList::Event("Vanilla Path: {$this->VanillaPath}");
      if ($this->MiscPath)
         TaskList::Event("Misc Path: {$this->MiscPath}");
   }
   
   protected function Run() {
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really apply symlinks for {$this->ClientFolder}?","Apply symlinks?",array('yes','no','exit'),'no');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
            
      if ($this->VanillaPath !== FALSE) {
         // Symlink Applications
         $this->Symlink('applications/dashboard', TaskList::CombinePaths($this->VanillaPath,'applications/dashboard'));
         $this->Symlink('applications/conversations', TaskList::CombinePaths($this->VanillaPath,'applications/conversations'));
         $this->Symlink('applications/vanilla', TaskList::CombinePaths($this->VanillaPath,'applications/vanilla'));
         
         // Symlink bootstrap.php
         $this->Symlink('bootstrap.php', TaskList::CombinePaths($this->VanillaPath,'bootstrap.php'));
         
         // Symlink config files
         $this->Symlink('conf/bootstrap.before.php', TaskList::CombinePaths($this->VanillaPath,'conf/bootstrap.before.php'));
         $this->Symlink('conf/config-defaults.php', TaskList::CombinePaths($this->VanillaPath,'conf/config-defaults.php'));
         $this->Symlink('conf/constants.php', TaskList::CombinePaths($this->VanillaPath,'conf/constants.php'));
         $this->Symlink('conf/locale.php', TaskList::CombinePaths($this->VanillaPath,'conf/locale.php'), TRUE);
         
         // Symlink core folders
         $this->Symlink('js', TaskList::CombinePaths($this->VanillaPath,'js'));
         $this->Symlink('library', TaskList::CombinePaths($this->VanillaPath,'library'));
         
         // Symlink all core feature plugins
         $this->Symlink('plugins/HtmLawed', TaskList::CombinePaths($this->VanillaPath,'plugins/HtmLawed'));
         $this->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->VanillaPath,'plugins/Gravatar'));
         $this->Symlink('plugins/VanillaInThisDiscussion', TaskList::CombinePaths($this->VanillaPath,'plugins/VanillaInThisDiscussion'));
         $this->Symlink('plugins/Tagging', TaskList::CombinePaths($this->VanillaPath,'plugins/Tagging'));
         $this->Symlink('plugins/Flagging', TaskList::CombinePaths($this->VanillaPath,'plugins/Flagging'));
         $this->Symlink('plugins/embedvanilla', TaskList::CombinePaths($this->VanillaPath,'plugins/embedvanilla'));
         $this->Symlink('plugins/Emotify', TaskList::CombinePaths($this->VanillaPath,'plugins/Emotify'));
         $this->Symlink('plugins/cleditor', TaskList::CombinePaths($this->VanillaPath,'plugins/cleditor'));
         
         $this->Symlink('plugins/Facebook', TaskList::CombinePaths($this->VanillaPath,'plugins/Facebook'));
         $this->Symlink('plugins/Twitter', TaskList::CombinePaths($this->VanillaPath,'plugins/Twitter'));
         $this->Symlink('plugins/GoogleSignIn', TaskList::CombinePaths($this->VanillaPath,'plugins/GoogleSignIn'));
         $this->Symlink('plugins/OpenID', TaskList::CombinePaths($this->VanillaPath,'plugins/OpenID'));
         
         // Copy the new index file
         $Copied = $this->CopySourceFile('index.php', $this->VanillaPath);
         $this->Cache('Updated',$Copied);
      }
      
      if ($this->MiscPath !== FALSE) {
         //Symlink .htaccess
         $this->Symlink('.htaccess', TaskList::CombinePaths($this->MiscPath,'utils/.htaccess'));
      }
      
      if ($this->PluginPath !== FALSE) {
         // Symlink GettingStartedHosting plugin
         $this->Symlink('plugins/GettingStartedHosting', TaskList::CombinePaths($this->PluginPath,'GettingStartedHosting'));
         
         // Symlink all misc feature plugins
         $this->Symlink('plugins/VanillaConnect', TaskList::CombinePaths($this->PluginPath,'VanillaConnect'));
         $this->Symlink('plugins/ProxyConnect', TaskList::CombinePaths($this->PluginPath,'ProxyConnect'));
         $this->Symlink('plugins/FileUpload', TaskList::CombinePaths($this->PluginPath,'FileUpload'));
         $this->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->PluginPath,'CustomDomain'));
         $this->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->PluginPath,'CustomTheme'));
         $this->Symlink('plugins/googleadsense', TaskList::CombinePaths($this->PluginPath,'googleadsense'));
         $this->Symlink('plugins/Statistics', TaskList::CombinePaths($this->PluginPath,'Statistics'));
         $this->Symlink('plugins/PrivateCommunity', TaskList::CombinePaths($this->PluginPath,'PrivateCommunity'));
         $this->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->PluginPath,'vfoptions'));
      }
      
      if ($this->ThemePath !== FALSE) {
         // Symlink all core themes
         $this->Symlink('themes/minalla', TaskList::CombinePaths($this->ThemePath,'minalla'));
         $this->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->ThemePath,'light-grunge'));
         $this->Symlink('themes/ivanilla', TaskList::CombinePaths($this->ThemePath,'iVanilla'));
         $this->Symlink('themes/simple', TaskList::CombinePaths($this->ThemePath,'simple'));
         $this->Symlink('themes/rounder', TaskList::CombinePaths($this->ThemePath,'rounder'));
         $this->Symlink('themes/vanillaclassic', TaskList::CombinePaths($this->ThemePath,'vanillaclassic'));
         $this->Symlink('themes/v1grey', TaskList::CombinePaths($this->ThemePath,'v1grey'));
         
         $this->Symlink('themes/mobile', TaskList::CombinePaths($this->VanillaPath,'themes/mobile'));
         $this->Symlink('themes/EmbedFriendly', TaskList::CombinePaths($this->VanillaPath,'themes/EmbedFriendly'));
         
         // Replace default theme with smartydefault
         $this->Symlink('themes/default', TaskList::CombinePaths($this->ThemePath,'defaultsmarty'));
      }
      
   }

}

