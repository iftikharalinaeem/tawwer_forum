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
      if (is_null($RootPath) || !is_dir($RootPath)) {
         do {
            $SourceCodeFolder = TaskList::Input("Enter new sourcecode location for selected clients, or 'no' to skip sourcecodepath", "Sourcecode Folder", "vanilla_source");
            $SourceCodePath = sprintf('/srv/www/%s/',$SourceCodeFolder);
         } while (strtolower($SourceCodeFolder) != 'no' && !is_dir($SourceCodePath));
         if (strtolower($SourceCodeFolder) != 'no') {
            $this->VanillaPath = $SourceCodePath;
         }
         
         do {
            $MiscFolder = TaskList::Input("Enter new misc location for selected clients, or 'no' to skip miscpath", "Misc Folder", "misc");
            $MiscPath = sprintf('/srv/www/%s/', $MiscFolder);
         } while (strtolower($MiscFolder) != 'no' && !is_dir($MiscPath));
         if (strtolower($MiscFolder) != 'no') {
            $this->MiscPath = $MiscPath;
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
      
      TaskList::MajorEvent("New targets:");
      if ($this->VanillaPath)
         TaskList::Event("SourcecodePath: {$this->SourcecodePath}");
      if ($this->MiscPath)
         TaskList::Event("MiscPath: {$this->MiscPath}");
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
         $this->Symlink('conf/locale.php', TaskList::CombinePaths($this->VanillaPath,'conf/locale.php'));
         
         // Symlink core folders
         $this->Symlink('js', TaskList::CombinePaths($this->VanillaPath,'js'));
         $this->Symlink('library', TaskList::CombinePaths($this->VanillaPath,'library'));
         
         // Symlink all core feature plugins
         $this->Symlink('plugins/HtmlLawed', TaskList::CombinePaths($this->VanillaPath,'plugins/HtmlLawed'));
         $this->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->VanillaPath,'plugins/Gravatar'));
         $this->Symlink('plugins/VanillaInThisDiscussion', TaskList::CombinePaths($this->VanillaPath,'plugins/VanillaInThisDiscussion'));
         $this->SaveToConfig('EnabledPlugins.HtmlLawed','HtmlLawed');
         $this->RemoveFromConfig('EnabledPlugins.HtmlPurifier');
         
         // Copy the new index file
         $this->CopySourceFile('index.php', $this->VanillaPath);
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
         $this->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->PluginPath,'vfoptions'));
         $this->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->PluginPath,'CustomDomain'));
         $this->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->PluginPath,'CustomTheme'));
         $this->Symlink('plugins/googleadsense', TaskList::CombinePaths($this->PluginPath,'googleadsense'));
      }
      
      if ($this->ThemePath !== FALSE) {
         // Symlink all core themes
         $this->Symlink('themes/minalla', TaskList::CombinePaths($this->ThemePath,'minalla'));
         $this->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->ThemePath,'light-grunge'));
         $this->Symlink('themes/ivanilla', TaskList::CombinePaths($this->ThemePath,'iVanilla'));
         $this->Symlink('themes/simple', TaskList::CombinePaths($this->ThemePath,'simple'));
         $this->Symlink('themes/rounder', TaskList::CombinePaths($this->ThemePath,'rounder'));
         $this->Symlink('themes/vanilla-classic', TaskList::CombinePaths($this->ThemePath,'vanilla-classic'));
         $this->Symlink('themes/v1grey', TaskList::CombinePaths($this->ThemePath,'v1grey'));
         
         // Replace default theme with smartydefault
         $this->Symlink('themes/default', TaskList::CombinePaths($this->ThemePath,'defaultsmarty'));
      }
   }

}

