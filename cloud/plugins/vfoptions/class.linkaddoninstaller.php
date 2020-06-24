<?php

class LinkAddonInstaller extends AddonInstaller {
   public $BasePath = '';

   public $Browser = NULL;

   public function __construct($BasePath = '') {
      $this->BasePath = $BasePath;
      $this->Browser = Gdn::PluginManager()->GetPluginInstance('AddonBrowserPlugin');
   }

   public function Download($Slug) {
      $Slug = strtolower($Slug);
      $SlugAddon = $this->AnalyzeSlug($Slug);

      // Figure out the path to the addon.
      $SourcePath = $this->BasePath.'/'.$Slug;

      // Get the addon info.
      $Addon = UpdateModel::AnalyzeAddon($SourcePath);

      // Get the remote info for the addon.
      $Addon = $this->Browser->Rest($this->Browser->AddonSiteUrl.'/addon.json?id='.urlencode($Slug));
      if (!$Addon)
         throw new Gdn_UserException("Could not find the information for the addon: $Addon", 400);

      if ($Addon['Type'] == 'Core')
         throw new Gdn_UserException("The application does not support downloading addons of type core.");

      // Link the file.
      $Folder = $Addon['AddonKey'];
      if ($Addon['AddonTypeID'] == ADDON_TYPE_APPLICATION)
         $Folder = strtolower($Folder);

      $InstallPath = PATH_ROOT.$SlugAddon['BasePath'].'/'.$Folder;


      if (file_exists($InstallPath)) {
         throw new Gdn_UserException(T('The addon has already been downloaded.'), 400);
      }

      $Result = symlink($SourcePath, $InstallPath);

      if (!$Result) {
         throw new Gdn_UserException(T('Could not link the addon.'), 400);
      }
   }
}