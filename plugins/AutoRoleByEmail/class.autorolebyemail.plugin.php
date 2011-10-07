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
   'Description' => 'Assigns new members from a specific email domain to a role (in addition to default role).',
   'Version' => '0.1',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'matt@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class AutoRoleByEmailPlugin extends Gdn_Plugin {
	/**
	 * Hacky setup for Hubspot.
	 */
   public function Setup() {
      SaveToConfig('Plugins.AutoRoleByEmail.Domain', 'hubspot.com');
      SaveToConfig('Plugins.AutoRoleByEmail.Role', 'Hubspotter');
   }
   
   /**
    * If new user's email is @domain, add to special role.
    */
   public function UserModel_BeforeInsertUser_Handler($Sender) {
      $Domain = C('Plugins.AutoRoleByEmail.Domain', FALSE);
      $Role = C('Plugins.AutoRoleByEmail.Role', FALSE);
      $Email = $Sender->EventArguments['InsertFields']['Email'];
      $EscapedDomain = str_replace('.', '\.', $Domain);
      if ($Domain && $Role && preg_match('/@'.$EscapedDomain.'$/', $Email)) {
         $RoleModel = new RoleModel();
         $RoleID = $RoleModel->GetWhere(array('Name' => $Role))->FirstRow()->RoleID;
         $Sender->EventArguments['InsertFields']['Roles'][] = $RoleID;
      }
   }
}