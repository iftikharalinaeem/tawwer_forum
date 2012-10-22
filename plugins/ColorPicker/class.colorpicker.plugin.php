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
$PluginInfo['ColorPicker'] = array(
   'Name' => 'Color Picker',
   'Description' => 'This plugin allows users to edit the css colors on their sites.',
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.2a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/colorpicker',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class ColorPickerPlugin extends Gdn_Plugin {
   /// PROPERTIES ///

   /** @var ColorPickerSettings */
   protected $_Settings = NULL;


   /// METHODS ///

   /** Get the settings object for this plugin.
    *
    * @return ColorPickerSettings
    */
   public function Settings() {
      if ($this->_Settings === NULL) {
         // Lazy load the settings.
         require_once dirname(__FILE__).'/class.colorpickersettings.php';
         $this->_Settings = new ColorPickerSettings();
         $this->_Settings->Parent = $this;
      }
      return $this->_Settings;
   }

   /** Standard plugin setup method. */
   public function Setup() {
      // Make sure the folder for uploaded files exists.
      $UploadPath = PATH_UPLOADS.'/ColorPicker';
      if (!file_exists($UploadPath)) {
         mkdir($UploadPath, 0777, TRUE);
      }

      $this->Settings()->GenerateCustomCss($UploadPath.'/custom.css');
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      $Session = Gdn::Session();

      // Add the color picker.
      if ($Session->IsValid() /* && $Session->GetPreference('Plugins.ColorPicker.EditColors') */) {
         $this->Settings()->EditColors($Sender);
      }
   }
}