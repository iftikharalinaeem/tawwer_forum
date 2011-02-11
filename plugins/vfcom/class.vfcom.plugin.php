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
$PluginInfo['vfcom'] = array(
   'Name' => 'VanillaForums.com Hosting',
   'Description' => "This plugin applies modifications needed for vanilla to support multiple webhead technology.",
   'Version' => '1.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class VfcomPlugin extends Gdn_Plugin {
   
   public function __construct() {
      
   }
   
   public function Gdn_Upload_GetUrls_Handler($Sender, $Args) {
      
      $VfcomClient = C('VanillaForums.SiteName', NULL);
      if (is_null($VfcomClient)) return;
      
      $VfcomHostname = C('VanillaForums.Hostname', 'vanillaforums.com');
      $Args['Urls'][''] = $FinalURL = sprintf('http://%s.static.%s/uploads',
         $VfcomClient,
         $VfcomHostname
      );

   }
   
   public function Setup() {
      
   }
   
}