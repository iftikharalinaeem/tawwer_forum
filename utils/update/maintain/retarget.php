<?php

class RetargetTask extends Task {

   protected $SourcecodePath;

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $SourceCodeFolder = TaskList::Input("Enter new sourcecode location for selected clients?", "Sourcecode Folder", "vanilla_source");
      $this->SourcecodePath = sprintf('/srv/www/%s/',$SourceCodeFolder);
   }
   
   protected function Run() {
      $Proceed = TaskList::Question("Really retarget {$this->ClientFolder}'s sourcecodepath to {$this->SourcecodePath}?","Retarget?",array('yes','no','skip'),'skip');
      if ($Proceed == 'no') exit();
      if ($Proceed == 'skip') return;
      
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

}

