<?php

class RetargetTask extends Task {

   protected $SourcecodePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->SourcecodePath = FALSE;
      $this->MiscPath = FALSE;
      $this->PluginPath = FALSE;
      $this->ThemePath = FALSE;
      
      $SourceCodeFolder = TaskList::Input("Enter new sourcecode location for selected clients, or 'no' to skip sourcecodepath", "Sourcecode Folder", "vanilla_source");
      if (strtolower($SourceCodeFolder) != 'no') {
         $this->SourcecodePath = sprintf('/srv/www/%s/',$SourceCodeFolder);
      }
      
      $MiscFolder = TaskList::Input("Enter new misc location for selected clients, or 'no' to skip miscpath", "Misc Folder", "misc");
      if (strtolower($MiscFolder) != 'no') {
         $this->MiscPath = sprintf('/srv/www/%s/', $MiscFolder);
         $this->PluginPath = sprintf('/srv/www/%s/plugins/', $MiscFolder);
         $this->ThemePath = sprintf('/srv/www/%s/themes/', $MiscFolder);
      }
      
      TaskList::MajorEvent("New targets:");
      if ($this->SourcecodePath)
         TaskList::Event("SourcecodePath: {$this->SourcecodePath}");
      if ($this->MiscPath)
         TaskList::Event("MiscPath: {$this->MiscPath}");
   }
   
   protected function Run() {
      
      $Proceed = TaskList::Question("Really retarget {$this->ClientFolder}?","Retarget?",array('yes','no','skip'),'skip');
      if ($Proceed == 'no') exit();
      if ($Proceed == 'skip') return;
      
      if ($this->SourcecodePath !== FALSE) {
         // Symlink Applications
         $this->Symlink('applications/dashboard', TaskList::CombinePaths($this->SourcecodePath,'applications/dashboard'));
         $this->Symlink('applications/conversations', TaskList::CombinePaths($this->SourcecodePath,'applications/conversations'));
         $this->Symlink('applications/vanilla', TaskList::CombinePaths($this->SourcecodePath,'applications/vanilla'));
         
         // Symlink bootstrap.php
         $this->Symlink('bootstrap.php', TaskList::CombinePaths($this->SourcecodePath,'bootstrap.php'));
         
         // Symlink config files
         $this->Symlink('conf/bootstrap.before.php', TaskList::CombinePaths($this->SourcecodePath,'conf/bootstrap.before.php'));
         $this->Symlink('conf/config-defaults.php', TaskList::CombinePaths($this->SourcecodePath,'conf/config-defaults.php'));
         $this->Symlink('conf/constants.php', TaskList::CombinePaths($this->SourcecodePath,'conf/constants.php'));
         $this->Symlink('conf/locale.php', TaskList::CombinePaths($this->SourcecodePath,'conf/locale.php'));
         
         // Symlink core folders
         $this->Symlink('js', TaskList::CombinePaths($this->SourcecodePath,'js'));
         $this->Symlink('library', TaskList::CombinePaths($this->SourcecodePath,'library'));
         
         // Symlink all core feature plugins
         $this->Symlink('plugins/HtmlPurifier', TaskList::CombinePaths($this->SourcecodePath,'plugins/HtmlPurifier'));
         $this->Symlink('plugins/Gravatar', TaskList::CombinePaths($this->SourcecodePath,'plugins/Gravatar'));
         $this->Symlink('plugins/VanillaInThisDiscussion', TaskList::CombinePaths($this->SourcecodePath,'plugins/VanillaInThisDiscussion'));
         
         // Copy the new index file
         $this->CopySourceFile('index.php', $this->SourcecodePath);
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
         $this->Symlink('plugins/FileUpload', TaskList::CombinePaths($this->PluginPath,'FileUpload'));
         $this->Symlink('plugins/vfoptions', TaskList::CombinePaths($this->PluginPath,'vfoptions'));
         $this->Symlink('plugins/CustomDomain', TaskList::CombinePaths($this->PluginPath,'CustomDomain'));
         $this->Symlink('plugins/CustomTheme', TaskList::CombinePaths($this->PluginPath,'CustomTheme'));
      }
      
      if ($this->ThemePath !== FALSE) {
         // Symlink all core themes
         $this->Symlink('themes/minalla-yellow', TaskList::CombinePaths($this->ThemePath,'minalla-yellow'));
         $this->Symlink('themes/lightgrunge', TaskList::CombinePaths($this->ThemePath,'light-grunge'));
         $this->Symlink('themes/ivanilla', TaskList::CombinePaths($this->ThemePath,'iVanilla'));
         $this->Symlink('themes/simple', TaskList::CombinePaths($this->ThemePath,'simple'));
         $this->Symlink('themes/rounder', TaskList::CombinePaths($this->ThemePath,'rounder'));
         
         // Replace default theme with smartydefault
         $this->Symlink('themes/default', TaskList::CombinePaths($this->ThemePath,'defaultsmarty'));
      }
   }

}

