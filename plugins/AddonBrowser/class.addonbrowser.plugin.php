<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['AddonBrowser'] = array(
   'Name' => 'Addon Browser',
   'Description' => 'Temporary plugin for browsing all addons from within the dashboard.',
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.14'),
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'Hidden' => TRUE
);

class AddonBrowserPlugin extends Gdn_Plugin {
   /// Properties ///

   public $AddonSiteUrl = 'http://vanilla.local'; //'http://vanillaforums.org';

   /**
    * @var AddonInstaller The installer used to install the addons from the browser.
    */
   public $Installer = NULL;

   /**
    * @var bool Whether or not to show hidden addons.
    */
   public $ShowHidden = TRUE;

   /**
    * @var bool Whether or not to show downloads.
    */
   public $ShowDownloads = TRUE;

   /// Methods ///

   public function  __construct() {
      $this->Installer = new AddonInstaller();
      $this->Installer->Browser = $this;
      parent::__construct();
   }


   protected $_ApplicationManager = NULL;

   /**
    * @return Gdn_ApplicationManager
    */
   public function ApplicationManager() {
      if ($this->_ApplicationManager === NULL) {
         $this->_ApplicationManager = new Gdn_ApplicationManager();
      }
      return $this->_ApplicationManager;
   }

   public function Downloaded($Addon) {
      $Key = $Addon['AddonKey'];
      switch (GetValue('Type', $Addon)) {
         case 'Application':
            return array_key_exists($Key, $this->ApplicationManager()->AvailableApplications());
         case 'Locale':
            return FALSE;
         case 'Plugin':
            return Gdn::PluginManager()->AvailablePlugins($Key) !== FALSE;
         case 'Theme':
            return array_key_exists($Key, $this->ThemeManager()->AvailableThemes());
      }
   }

   public function Enabled($Addon) {
      $Key = $Addon['AddonKey'];
      switch (GetValue('Type', $Addon)) {
         case 'Application':
            return array_key_exists($Key, $this->ApplicationManager()->EnabledApplications());
         case 'Locale':
            return FALSE;
         case 'Plugin':
            return array_key_exists($Key, Gdn::PluginManager()->EnabledPlugins());
         case 'Theme':
            $CurrentTheme = $this->ThemeManager()->EnabledThemeInfo();
            return GetValue('Index', $CurrentTheme) == $Key;
      }
   }

   public function FilterAddon($Addon, $Search, $Options) {
      $Found = FALSE;

      if (isset($Options['Enabled']) && GetValue('Enabled', $Addon) != $Options['Enabled']) {
         return FALSE;
      }

      if (!$this->ShowHidden && $Addon['Hidden'])
         return FALSE;

      if ($Search) {
         $Strings = array(GetValue('Name', $Addon), GetValue('Description', $Addon));
         foreach ($Strings as $String) {
            if (strpos(strtolower($String), strtolower($Search)) !== FALSE)
               return TRUE;
         }
      } else {
         return TRUE;
      }
      return FALSE;
   }

   public function GetLocalAddons($Search, $Page = 'p1', $Options = array()) {
      $Addons = array();

      // Get the plugins.
      $Plugins = Gdn::PluginManager()->AvailablePlugins();
      foreach ($Plugins as $Index => $Plugin) {
         $Addon = (array)$Plugin;
         $Addon['Type'] = 'Plugin';
         $Addon['AddonKey'] = $Index;
         $Addon['Enabled'] = isset(Gdn::PluginManager()->EnabledPlugins[$Index]);
         $Addon['Downloaded'] = TRUE;
         $Addon['SettingsUrl'] = GetValue('SettingsUrl', $Plugin);
         if (!GetValue('Name', $Addon))
            $Addon['Name'] = $Index;

         $Slug = AddonSlug($Addon);
         if ($this->FilterAddon($Addon, $Search, $Options))
            $Addons[] = $Addon;
      }

      // Get the applications.
      $ApplicationManager = $this->ApplicationManager();
      $Applications = $ApplicationManager->AvailableVisibleApplications();
      foreach ($Applications as $Index => $Application) {
         $Addon = (array)$Application;
         $Addon['Type'] = 'Application';
         $Addon['AddonKey'] = $Index;
         $Addon['Enabled'] = array_key_exists($Index, $ApplicationManager->EnabledApplications());
         $Addon['Downloaded'] = TRUE;
         $Addon['SettingsUrl'] = GetValue('SettingsUrl', $Application);
         if (!GetValue('Name', $Addon))
            $Addon['Name'] = $Index;

         $Slug = AddonSlug($Addon);
         if ($this->FilterAddon($Addon, $Search, $Options))
            $Addons[] = $Addon;
      }

      // Get the themes.
      $ThemeManager = $this->ThemeManager();
      $Themes = $ThemeManager->AvailableThemes();
      foreach ($Themes as $Index => $Theme) {
         $Addon = (array)$Theme;
         $Addon['Type'] = 'Theme';
         $Addon['AddonKey'] = $Index;
         $Addon['Enabled'] = GetValue('Folder', $Theme) == $ThemeManager->CurrentTheme();
         $Addon['Downloaded'] = TRUE;
         if (GetValue('Folder', $Theme))
            $Addon['InstallPath'] = PATH_THEMES.'/'.$Theme['Folder'];

         if (isset($Theme['Options'])) {
            $Addon['SettingsUrl'] = Url('/dashboard/settings/themeoptions');
         }
         if (isset($Theme['ScreenshotUrl']))
            $Addon['IconUrl'] = $Theme['ScreenshotUrl'];
         if (!GetValue('Name', $Addon))
            $Addon['Name'] = $Index;

         $Slug = AddonSlug($Addon);
         if ($this->FilterAddon($Addon, $Search, $Options))
            $Addons[] = $Addon;
      }

      // Sort the addons.
      self::SortAddons($Addons);
      $TotalAddons = count($Addons);

      // Paginate the array.
      list($Offset, $Limit) = OffsetLimit($Page, 20);
      $Addons = array_slice($Addons, $Offset, $Limit);

      // Check to see which addons can be removed.
      foreach ($Addons as &$Addon) {
         if (!isset($Addon['InstallPath'])) {
            $Addon['CanRemove'] = FALSE;
         } else {
            $Addon['CanRemove'] = TRUE; //is_writable($Addon['InstallPath']);
         }
      }

      return array($Addons, $TotalAddons);
   }

   public function GetRemoteAddons($Search, $Page = 'p1', $Options = array()) {
      $Query = (array)$Options;
      if ($Search)
         $Query['Form/Keywords'] = $Search;
      $Query['Types'] = 'application,plugin,theme,locale';
      $Query['checked'] = 'checked';
      $Query['page'] = $Page;

      $Url = $this->AddonSiteUrl.'/addon/browse.json?'.http_build_query($Query);

      $Result = $this->Rest($Url);
      $Addons = GetValue('Addons', $Result, array());
      $TotalAddons = GetValue('TotalAddons', $Result, count($Addons));

      // Add local properties to the collection.
      foreach ($Addons as &$Addon) {
         $Addon['Enabled'] = $this->Enabled($Addon);
         $Addon['Downloaded'] = $this->Downloaded($Addon);
         $Addon['CanRemove'] = FALSE;
      }

      return array($Addons, $TotalAddons);
   }

   public function Rest($Url) {
      $Response = file_get_contents($Url);
      $Data = json_decode($Response, TRUE);
      return $Data;
   }

   public static function SortAddons(&$Array) {
      // Make sure every addon has a name.
      foreach ($Array as $Key => $Value) {
         $Name = GetValue('Name', $Value, $Key);
         SetValue('Name', $Array[$Key], $Name);
      }
      uasort($Array, array('SettingsController', 'CompareAddonName'));
   }

   protected $_ThemeManager = NULL;
   /**
    * @return Gdn_ThemeManager
    */
   public function ThemeManager() {
      if ($this->_ThemeManager === NULL)
         $this->_ThemeManager = new Gdn_ThemeManager();
      return $this->_ThemeManager;
   }

   public static function CompareAddonName($A, $B) {
      return strcasecmp(GetValue('Name', $A), GetValue('Name', $B));
   }

   /// Event Handlers ///

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function SettingsController_Addons_Create($Sender, $Args = array()) {
      $Sender->Title('Addons');
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('dashboard/settings/addons');
      $Sender->AddCssFile('addonbrowser.css', 'plugins/AddonBrowser');

      $Sections = array('enabled' => 'Enabled', 'downloaded' => 'Downloaded', 'browse' => 'Browse');
      if (!$this->ShowDownloads)
         unset($Sections['downloaded']);

      $Section = strtolower(GetValue(0, $Args));
      if (!array_key_exists($Section, $Sections))
         $Section = 'enabled';
      $Sender->SetData('Section', $Section);
      $Sender->SetData('Sections', $Sections);

      $this->FireEvent('AddonSettings');

      // Perform an action if applicable.
      $Action = strtolower(GetValue(1, $Args));
      if ($Action && in_array($Action, array('enable', 'disable', 'download'))) {
         if (Gdn::Session()->ValidateTransientKey($Sender->Request->Get('TransientKey'))) {
            $Slug = $Sender->Request->Get('Slug');
            try {
               switch ($Action) {
                  case 'enable':
                     $this->Installer->Enable($Slug);
                     break;
                  case 'disable':
                     $this->Installer->Disable($Slug);
                     break;
                  case 'download':
                     $this->Installer->Download($Slug);
                     break;
               }
            } catch (Exception $Ex) {
               $Sender->Form->AddError($Ex);
            }
         } else {
            $Sender->Form->AddError('Could not validate the transient key.');
         }
      }

      Gdn::PluginManager()->AvailablePlugins(NULL, TRUE);

      $Page = $Sender->Request->Get('Page', 'p1');

      // Get the data for the addons.
      switch($Section) {
         case 'browse':
           list($Addons, $TotalAddons) = $this->GetRemoteAddons($Sender->Request->Get('Search'), $Page);
           break;
         case 'enabled':
           list($Addons, $TotalAddons) = $this->GetLocalAddons($Sender->Request->Get('Search'), $Page, array('Enabled' => TRUE));
           break;
        default:
           list($Addons, $TotalAddons) = $this->GetLocalAddons($Sender->Request->Get('Search'), $Page);
           break;
      }
      $Sender->SetData('Addons', $Addons);

      // Build a pager
		$PagerFactory = new Gdn_PagerFactory();
		$Pager = $PagerFactory->GetPager('Pager', $this);
		$Pager->MoreCode = '›';
		$Pager->LessCode = '‹';
		$Pager->ClientID = 'Pager';
      list($Offset, $Limit) = OffsetLimit($Page, 20);
		$Pager->Configure(
			$Offset,
			$Limit,
			$TotalAddons,
			"/settings/addons/$Section?Page={Page}"
		);
		$Sender->SetData('_Pager', $Pager);

      $Query = $Sender->Request->Get();
      unset($Query['Slug'], $Query['TransientKey']);
      $Sender->SetData('_Query', http_build_query($Query));
      $Sender->SetData('_ShowDownloads', $this->ShowDownloads);

      $Sender->Render('Addons', '', 'plugins/AddonBrowser');
   }

   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Add-ons', T('Browse Addons'), 'dashboard/settings/addons', 'Garden.Settings.Manage');
   }
}

function AddonSlug($Addon) {
   $Result = strtolower($Addon['AddonKey']).'-'.strtolower($Addon['Type']);
   return $Result;
}