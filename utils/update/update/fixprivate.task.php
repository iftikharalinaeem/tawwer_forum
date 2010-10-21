<?php

class FixlawedTask extends Task {

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
            $SourceCodeFolder = TaskList::Input("Enter new sourcecode version for selected clients, or 'no' to skip", "Sourcecode Version", "unstable");
            $SourceCodePath = sprintf('/srv/www/source/%s/',$SourceCodeFolder);
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
      
      TaskList::MajorEvent("New targets:");
      if ($this->VanillaPath)
         TaskList::Event("Vanilla Path: {$this->VanillaPath}");
      if ($this->MiscPath)
         TaskList::Event("Misc Path: {$this->MiscPath}");
   }
   
   protected function Run() {
      if ($this->VanillaPath !== FALSE) {
         // Symlink Applications
         $this->Symlink('plugins/PrivateCommunity');
         $this->Symlink('plugins/PrivateCommunity', TaskList::CombinePaths($this->PluginPath,'PrivateCommunity'));
      }
   }

}

