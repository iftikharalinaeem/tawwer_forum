<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2011 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['AutoRoleByEmail'] = array(
   'Name' => 'Auto-Role By Email',
   'Description' => 'Adds new users to roles based on their email domain (in addition to default role).',
   'Version' => '1.0',
   'SettingsUrl' => '/settings/registration',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'matt@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class AutoRoleByEmailPlugin extends Gdn_Plugin {
	/**
    * Add 'Domains' box to Edit Role page.
    */
   public function RoleController_BeforeRolePermissions_Handler($Sender) {
      echo Wrap($Sender->Form->Label('Domains', 'Domains') .
         Wrap(T('RoleDomainInfo', "Assign new users to this role if their email is from one of these domains (space-separated)."), 'div', array('class' => 'Info')) .
         $Sender->Form->TextBox('Domains', array('MultiLine' => TRUE)), 'li');
	}
   
   /**
    * If new user's email is @domain, add to special role.
    */
   public function UserModel_BeforeInsertUser_Handler($Sender) {
      // Get new user's email domain
      $Email = $Sender->EventArguments['InsertFields']['Email'];
      list($Junk, $Domain) = explode('@', $Email);
      
      // Any roles assigned?
      $RoleModel = new RoleModel();
      $RoleData = $RoleModel->SQL->GetWhereLike('Role', array('Domains' => $Domain));
      foreach ($RoleData->Result() as $Result) {
         // Confirm it wasn't a sloppy match
         $DomainList = explode(' ', $Result->Domains);
         if (in_array($Domain, $DomainList)) {
            // Add the role to the user
            $Sender->EventArguments['InsertFields']['Roles'][] = $Result->RoleID;
         }
      }
   }
   
   /**
    * One time on enable.
    */
   public function Setup() {      
      $this->Structure();
         
      // Backwards compatibility with 0.1
      if (C('Plugins.AutoRoleByEmail.Domain', FALSE)) {
         $RoleModel = new RoleModel();
         $RoleModel->Update(
            array('Domains' => C('Plugins.AutoRoleByEmail.Domain')), 
            array('Name' => C('Plugins.AutoRoleByEmail.Role'))
         );
         RemoveFromConfig('Plugins.AutoRoleByEmail.Domain');
         RemoveFromConfig('Plugins.AutoRoleByEmail.Role');
      }
   }
   
   /**
    * Add 'Domains' column to Role table.
    */
   public function Structure() {
      Gdn::Structure()->Table('Role')
         ->Column('Domains', 'text', NULL)
         ->Set();  
   }
}