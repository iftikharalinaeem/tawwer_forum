<?php

class NewforumTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
      
      $this->RootPath = FALSE;
      $this->VanillaPath = FALSE;
      $this->MiscPath = FALSE;
      $this->PluginPath = FALSE;
      $this->ThemePath = FALSE;
   }
   
   public function Init() {
      $SourceCodeVersion = $this->C('VFCom.DeploymentVersion',FALSE);
      if ($SourceCodeVersion === FALSE) return;
      
      $SourceCodePath = sprintf('/srv/www/source/%s/',$SourceCodeVersion);
      if (is_dir($SourceCodePath))
         $this->RootPath = $SourceCodePath;
   }
   
   protected function Run() {
      if (is_dir($this->ClientRoot)) {
         TaskList::Event('client already exists.');
         exit();
      }
   
      // Create the client vhost folder
      TaskList::Mkdir($this->ClientRoot);
      
      // Create subfolders
      $this->Mkdir('applications');
      $this->Mkdir('cache');
      $this->Mkdir('cache/Smarty');
      $this->Mkdir('cache/Smarty/cache');
      $this->Mkdir('cache/Smarty/compile');
      $this->Mkdir('cache/HtmlPurifier');
      $this->Mkdir('conf');
      $this->Mkdir('plugins');
      $this->Mkdir('themes');
      $this->Mkdir('uploads');
      
      $this->TaskList->ExecTask('filesystem', $this->ClientFolder, $this->ClientInfo);
      
      // Create empty client config file
      $this->Touch('conf/config.php');
   }

}

