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
$PluginInfo['PennyArcade'] = array(
   'Name' => 'Penny-Arcade Customizations',
   'Description' => 'This plugin applies Penny-Arcade specific customizations to Vanilla core.',
   'Version' => '1.0.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class PennyArcadePlugin extends Gdn_Plugin {
   
   protected $MaxCategoryLength = 80;
   protected $MaxDiscussionLength = 90;

   public function __construct() {
      
   }
   
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
      $DiscussionName = GetValue('Name',$Sender->EventArguments['FormPostValues']);
      if (strlen($DiscussionName) > $this->MaxDiscussionLength)
         $Sender->Validation->AddValidationResult('Name','@'.sprintf(T('Discussion Title cannot be longer than %d characters'), $this->MaxDiscussionLength));
   }
   
   public function CategoryModel_BeforeSaveCategory_Handler($Sender) {
      $CategoryName = GetValue('Name',$Sender->EventArguments['FormPostValues']);
      if (strlen($CategoryName) > $this->MaxCategoryLength)
         $Sender->Validation->AddValidationResult('Name','@'.sprintf(T('Category Name cannot be longer than %d characters'), $this->MaxCategoryLength));
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      
   }
   
}

if (!function_exists('ValidateUsername')) {
   function ValidateUsername($Value, $Field = '') {
      return ValidateRegex(
         $Value,
         "/^([\d\w_ \*~]{3,30})?$/siu"
      );
   }
}