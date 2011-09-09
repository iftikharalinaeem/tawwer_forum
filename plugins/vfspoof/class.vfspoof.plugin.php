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
   'Version' => '1.0',
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
    * Opens a connection to the VanillaForums.com database.
    */
   private $_Database = FALSE;
   private function _GetDatabase() {
      if (!is_object($this->_Database)) {
         $this->_Database = new Gdn_Database(array(
            'Name' => C('VanillaForums.Database.Name', 'vfcom'),
            'Host' => C('VanillaForums.Database.Host', C('Database.Host')),
            'User' => C('VanillaForums.Database.User', C('Database.User')),
            'Password' => C('VanillaForums.Database.Password', C('Database.Password'))
         ));
      }
         
      return $this->_Database;
   }
   private function _CloseDatabase() {
      if (is_object($this->_Database)) {
         $this->_Database->CloseConnection();
         $this->_Database = FALSE;
      }
   }
   
   /// Event Handlers ///
   
   /**
    * @param Gdn_Controller $Sender 
    * @since 1.0 Added the ability to spoof with an ?access_token= querystring.
    */
   public function Base_BeforeControllerMethod_Handler($Sender) {
      // Don't spoof unless this is an api request.
      if (Gdn::Controller()->DeliveryType() != DELIVERY_TYPE_DATA) {
         return;
      }
      
      if ($AccessToken = Gdn::Request()->Get('access_token')) {
         if ($AccessToken == C('VanillaForums.AccessToken', 'e121e1c40183fc8428fa7b08657d4b1b')) {
            Gdn::Session()->Start(Gdn::UserModel()->GetSystemUserID(), FALSE, FALSE);
         }
      }
   }
   
   /**
    * Allows you to spoof the admin user if you have admin access in the
    * VanillaForums.com database.
    * @param Gdn_Controller $Sender
    */
   public function EntryController_VfSpoof_Create($Sender) {
      $Sender->Title('Spoof');
      // $Sender->AddSideMenu('dashboard/user');
      $Sender->Form = new Gdn_Form();
      $Email = $Sender->Form->GetValue('Email', '');
      $Password = $Sender->Form->GetValue('Password', '');
      $UserIDToSpoof = GetValue(0, $Sender->RequestArgs, FALSE);
      
      if ($Email != '' && $Password != '') {
         
         // Validate the username & password
         $UserModel = new UserModel(); // don't pollute the old user model
         $UserModel->SQL = $this->_GetDatabase()->SQL();
         $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
         $this->_CloseDatabase();
         
         $RemoteIsAdmin = GetValue('Admin', $UserData, FALSE);
         if ($RemoteIsAdmin > 0) {
            if ($UserIDToSpoof === FALSE)
               $UserIDToSpoof = Gdn::UserModel()->GetSystemUserID();
            
            Gdn::Session()->Start($UserIDToSpoof, TRUE);
            
            $UserIsAdmin = GetValue("Admin", Gdn::Session()->User, FALSE);
            if ($UserIsAdmin > 0)
               Redirect('/settings');
            else
               Redirect('/');
         } else {
            $Sender->Form->AddError('Bad Credentials');
         }
      }
      $Sender->Render('spoof', '', 'plugins/vfspoof');
   }
   
   public function Setup() {
      
   }
   
}