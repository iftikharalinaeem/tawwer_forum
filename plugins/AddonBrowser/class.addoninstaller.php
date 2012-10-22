<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class AddonInstaller {
   /**
    * @var AddonBrowserPlugin
    */
   public $Browser = NULL;

   public function AnalyzeSlug($Slug) {
      $Addon = array();

      $SlugParts = explode('-', $Slug);
      $Addon['AddonKey'] = GetValue(0, $SlugParts);

      $Types = array('core', 'application', 'locale', 'plugin', 'theme');
      $Type = strtolower(GetValue(1, $SlugParts, ''));
      if (!in_array($Type, $Types)) {
         throw new Exception("Unrecognized addon type: $Type.", 400);
      }
      $Addon['Type'] = ucfirst($Type);
      switch ($Type) {
         case 'core':
            $Addon['BasePath'] = '/';
            break;
         case 'application':
            $Addon['BasePath'] = '/applications';
            break;
         case 'locale':
            $Addon['BasePath'] = '/locales';
            break;
         case 'plugin':
            $Addon['BasePath'] = '/plugins';
            break;
         case 'theme':
            $Addon['BasePath'] = '/themes';
            break;
      }
      return $Addon;
   }

   public function Download($Slug) {
      // Get the remote info for the addon.
      $Addon = $this->Browser->Rest($this->Browser->AddonSiteUrl.'/addon.json?id='.urlencode($Slug));
      if (!$Addon)
         throw new Gdn_UserException("Could not find the information for the addon: $Addon", 400);

      if ($Addon['Type'] == 'Core')
         throw new Gdn_UserException("The application does not support downloading addons of type core.");

      // Download the file.
      $DownloadUrl = $this->Browser->AddonSiteUrl."/get/{$Addon['Slug']}.zip";
      $DestPath = PATH_UPLOADS."/addons/downloaded/{$Addon['Slug']}.zip";
      if (!file_exists(pathinfo($DestPath, PATHINFO_DIRNAME)))
         mkdir(pathinfo($DestPath, PATHINFO_DIRNAME), 0777, TRUE);
      copy($DownloadUrl, $DestPath);

      // Extract the file to the appropriat place.
      $SlugAddon = $this->AnalyzeSlug($Slug);
      $InstallPath = PATH_ROOT.$SlugAddon['BasePath'];
      $this->Extract($DestPath, $InstallPath);
   }

   public function Enable($Slug) {
      $Addon = $this->AnalyzeSlug($Slug);
      
      if ($Addon['Type'] == 'Core')
         throw new Exception('Enabling addons of type core is not supported.', 400);

      if (!$this->Downloaded($Addon)) {
         $this->Download($Slug);
      }

      $AddonKey = $Addon['AddonKey'];
      switch (strtolower($Addon['Type'])) {
         case 'application':
            $ApplicationManager = new Gdn_ApplicationManager();
            $ApplicationManager->EnableApplication($AddonKey);
            break;
         case 'locale':
            $LocaleModel = new LocaleModel();
            $LocaleModel->EnabledLocalePack($AddonKey);
            break;
         case 'plugin':
            $PluginManager = Gdn::PluginManager();
            $PluginManager->EnablePlugin($AddonKey, NULL);
            break;
         case 'theme':
            $ThemeManager = new Gdn_ThemeManager();
            $ThemeManager->EnableTheme($AddonKey);
            break;
      }
   }

   public function Extract($ZipPath, $DestFolder) {
      $ZipArchive = new ZipArchive();

      $ZipArchive->open($ZipPath);
      $ZipArchive->extractTo($DestFolder);
   }

   public function Disable($Slug) {
      $Addon = $this->AnalyzeSlug($Slug);

      if ($Addon['Type'] == 'Core')
         throw new Exception('Disabling addons of type core is not supported.', 400);

      if (!$this->Downloaded($Addon)) {
         throw new Gdn_UserException('You don\'t seem to have this addon so there\'s no need to disable it.', 400);
      }

      $AddonKey = $Addon['AddonKey'];
      switch (strtolower($Addon['Type'])) {
         case 'application':
            $ApplicationManager = new Gdn_ApplicationManager();
            $ApplicationManager->DisableApplication($AddonKey);
            break;
         case 'locale':
            $LocaleModel = new LocaleModel();
            $LocaleModel->DisableLocalePack($AddonKey);
            break;
         case 'plugin':
            $PluginManager = Gdn::PluginManager();
            $PluginManager->DisablePlugin($AddonKey, NULL);
            break;
         case 'theme':
            $ThemeManager = new Gdn_ThemeManager();
            $ThemeManager->DisableTheme($AddonKey);
            break;
      }
   }

   public function Downloaded($Addon) {
      switch (strtolower($Addon['Type'])) {
         case 'core':
            return TRUE;
         case 'application':
         case 'locale':
         case 'plugin':
            return $this->_FolderExists(PATH_ROOT."{$Addon['BasePath']}/{$Addon['AddonKey']}");
         case 'theme':
            // Themes don't have to have the same folder as their keys.
            $ThemeManager = new Gdn_ThemeManager();
            $Themes = $ThemeManager->AvailableThemes();
            return ArrayKeyExistsI($Addon['AddonKey'], $Themes);
      }
   }

   protected function _FolderExists($Path) {
      // First check to see if the folder exists the quick way.
      if (file_exists($Path))
         return TRUE;

      // The folder might exist, but we are on a case-sensitive os.
      $Folder = strrchr($Path, '/');
      $BasePath = substr($Path, 0, -strln($Folder));

      $Paths = (array)glob($Path.'/*', GLOB_ONLYDIR);
      foreach ($Paths as $Path2) {
         if (StringEndsWith($Path2, $Folder))
            return TRUE;
      }
      return FALSE;
   }
}