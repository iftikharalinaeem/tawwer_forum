<?php

class FilesystemTask extends Task {

   protected $SourcecodePath;
   protected $PluginPath;
   protected $ThemePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->SourcecodePath = '/srv/www/vanilla_source/';
      $this->PluginPath = '/srv/www/misc/plugins/';
      $this->ThemePath = '/srv/www/misc/themes/';
   }
   
   protected function Run() {
      // Remove old 'garden' application symlink and replace with 'dashboard' application
      $this->Symlink('applications/garden');
      $this->Symlink('applications/dashboard', TaskList::CombinePaths($this->SourcecodePath,'applications/dashboard'));
      
      // Remove old GettingStarted plugin symlink and replace with GettingStartedHosting
      $this->Symlink('plugins/GettingStarted');
      $this->Symlink('plugins/GettingStartedHosting', TaskList::CombinePaths($this->PluginPath,'GettingStartedHosting'));
      
      // Symlink all misc feature plugins
      $this->Symlink('plugins/VanillaConnect', TaskList::CombinePaths($this->PluginPath,'VanillaConnect'));
      $this->Symlink('plugins/FileUpload', TaskList::CombinePaths($this->PluginPath,'FileUpload'));
      $this->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->PluginPath,'vfoptions'));
      $this->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->PluginPath,'CustomDomain'));
      $this->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->PluginPath,'CustomTheme'));
      
      // Symlink all core feature plugins
      $this->Symlink('plugins/HtmlPurifier', TaskList::CombinePaths($this->SourcecodePath,'plugins/HtmlPurifier'));
      $this->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->SourcecodePath,'plugins/Gravatar'));
      
      // Symlink all core themes
      $this->Symlink('themes/minalla-yellow', TaskList::CombinePaths($this->ThemePath,'minalla-yellow'));
      $this->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->ThemePath,'light-grunge'));
      $this->Symlink('themes/ivanilla', TaskList::CombinePaths($this->ThemePath,'iVanilla'));
      $this->Symlink('themes/simple', TaskList::CombinePaths($this->ThemePath,'simple'));
      $this->Symlink('themes/rounder', TaskList::CombinePaths($this->ThemePath,'rounder'));
      
      // Replace default theme with smartydefault
      $this->Symlink('themes/default', TaskList::CombinePaths($this->ThemePath,'defaultsmarty'));
      
      // Copy the new index file
      $this->CopySourceFile('index.php', $this->SourcecodePath);
      
   }

}

