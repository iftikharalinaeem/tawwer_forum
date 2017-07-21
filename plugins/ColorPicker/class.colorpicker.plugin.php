<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

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
      $uploadPath = PATH_UPLOADS.'/ColorPicker';
      if (!file_exists($uploadPath)) {
         mkdir($uploadPath, 0777, TRUE);
      }

      $this->Settings()->GenerateCustomCss($uploadPath.'/custom.css');
   }

   /**
    *
    * @param Gdn_Controller $sender
    */
   public function Base_Render_Before($sender) {
      $session = Gdn::Session();

      // Add the color picker.
      if ($session->IsValid() /* && $Session->GetPreference('Plugins.ColorPicker.EditColors') */) {
         $this->Settings()->EditColors($sender);
      }
   }
}
