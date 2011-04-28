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
$PluginInfo['vfspoof'] = array(
   'Name' => 'VF.com Remote Spoof',
   'Description' => "This plugin allows Vanilla employees to gain access to hosted forums by logging in with a vf.com administrative user.",
   'Version' => '0.1',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'Hidden' => TRUE
);

class VFSpoofPlugin extends Gdn_Plugin {
   
   public function __construct() {
      
   }
   
   /**
    * Allows you to spoof the admin user if you have admin access in the
    * VanillaForums.com database.
    */
   public function EntryController_Spoof_Create(&$Sender) {
      $Sender->Title('Spoof');
      // $Sender->AddSideMenu('dashboard/user');
      $Sender->Form = new Gdn_Form();
      $Email = $Sender->Form->GetValue('Email', '');
      $Password = $Sender->Form->GetValue('Password', '');
      $UserIDToSpoof = ArrayValue(0, $Sender->RequestArgs, '1');
      if ($Email != '' && $Password != '') {
         // Validate the username & password
         $UserModel = Gdn::UserModel();
         $UserModel->SQL = $this->_GetDatabase()->SQL();
         $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
         if (is_object($UserData) && $UserData->Admin == '1') {
            $Identity = new Gdn_CookieIdentity();
            $Identity->Init(array(
               'Salt' => Gdn::Config('Garden.Cookie.Salt'),
               'Name' => Gdn::Config('Garden.Cookie.Name'),
               'Domain' => Gdn::Config('Garden.Cookie.Domain')
            ));
            $Identity->SetIdentity($UserIDToSpoof, TRUE);
            $this->_CloseDatabase();
            Redirect('settings');
         } else {
            $Sender->Form->AddError('Bad Credentials');
         }
      }
      $Sender->Render('spoof', '', 'plugins/vfspoof');
   }
   
   public function Setup() {
      
   }
   
}