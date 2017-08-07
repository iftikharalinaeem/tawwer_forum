<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2011 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class AutoRoleByEmailPlugin extends Gdn_Plugin {
	/**
    * Add 'Domains' box to Edit Role page.
    */
   public function roleController_beforeRolePermissions_handler($sender) {
      echo '<li class="form-group">
                <div class="label-wrap">'.
                $sender->Form->label('Domains', 'Domains').
                wrap(t('RoleDomainInfo', "Assign new users to this role if their email is from one of these domains (space-separated)."), 'div', ['class' => 'info']).
                '</div>'.
                $sender->Form->textBoxWrap('Domains', ['MultiLine' => true]).
            '</li>';
	}

   /**
    * If new user's email is @domain, add to special role.
    */
   public function userModel_beforeInsertUser_handler($sender) {
      // Get new user's email domain
      $email = $sender->EventArguments['InsertFields']['Email'];
      list($junk, $domain) = explode('@', $email);

      // Any roles assigned?
      $roleModel = new RoleModel();
      $roleData = $roleModel->SQL->getWhereLike('Role', ['Domains' => $domain]);
      foreach ($roleData->result() as $result) {
         // Confirm it wasn't a sloppy match
         //print_r($Result);
         $domainList = explode(' ', $result->Domains);
         if (in_array($domain, $domainList)) {
            // Add the role to the user
            $sender->EventArguments['InsertFields']['Roles'][] = $result->RoleID;
         }
      }
   }

   /**
    * One time on enable.
    */
   public function setup() {
      $this->structure();

      // Backwards compatibility with 0.1
      if (c('Plugins.AutoRoleByEmail.Domain', FALSE)) {
         $roleModel = new RoleModel();
         $roleModel->update(
            ['Domains' => c('Plugins.AutoRoleByEmail.Domain')],
            ['Name' => c('Plugins.AutoRoleByEmail.Role')]
         );
         removeFromConfig('Plugins.AutoRoleByEmail.Domain');
         removeFromConfig('Plugins.AutoRoleByEmail.Role');
      }
   }

   /**
    * Add 'Domains' column to Role table.
    */
   public function structure() {
      Gdn::structure()->table('Role')
         ->column('Domains', 'text', NULL)
         ->set();
   }
}
