<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class RoleProtectPlugin extends Gdn_Plugin {

   protected $Roles;
   protected $EditableRoles;
   protected $ProtectedRoles;

   public function __construct() {
      $roleModel = new RoleModel();
      $this->Roles = $roleModel->GetArray();
      $roleModel = NULL;
   }

   public function Gdn_Dispatcher_BeforeDispatch_Handler($sender) {

      // Roles the logged-in user can modify
      $editableRoleData = $this->EditableRoles = [];

      // Roles that, if present in the target user, protect him from  being edited
      $protectedRoleData = $this->ProtectedRoles = [];

      if (!Gdn::Session()->IsValid()) return;

      // Loop over the logged-in user's roles
      $myRoleData = Gdn::UserModel()->GetRoles(Gdn::Session()->UserID)->Result();
      $roleIDs = array_column($myRoleData, 'RoleID');
      $roleNames = array_column($myRoleData, 'Name');
      $myRoles = ArrayCombine($roleIDs, $roleNames);
      foreach ($myRoles as $roleID => $roleName) {
         $editableRolesList = C("Plugins.RoleProtect.{$roleID}.CanAffect", NULL);
         if (!is_null($editableRolesList)) {
            $editableRolesList = explode(',', $editableRolesList);
            if (is_array($editableRolesList) && sizeof($editableRolesList))
               $editableRoleData = array_merge($editableRoleData, $editableRolesList);
         }

         $protectedRolesList = C("Plugins.RoleProtect.{$roleID}.Protected", NULL);
         if (!is_null($protectedRolesList)) {
            $protectedRolesList = explode(',', $protectedRolesList);
            if (is_array($protectedRolesList) && sizeof($protectedRolesList))
               $protectedRoleData = array_merge($protectedRoleData, $protectedRolesList);
         }
      }

      // Format EditableRoleData into a nice ASSOC array
      $editableRoleData = array_flip($editableRoleData);
      $editableRoles = [];
      foreach ($editableRoleData as $editableRoleID => $trash)
         $editableRoles[$editableRoleID] = GetValue($editableRoleID, $this->Roles);
      $this->EditableRoles = $editableRoles;

      // Format ProtectedRoleData into a nice ASSOC array
      $protectedRoleData = array_flip($protectedRoleData);
      $protectedRoles = [];
      foreach ($protectedRoleData as $protectedRoleID => $trash)
         $protectedRoles[$protectedRoleID] = GetValue($protectedRoleID, $this->Role);
      $this->ProtectedRoles = $protectedRoles;
   }

   public function UserController_BeforeUserAdd_Handler($sender) {

      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;

      // Nothing configured for this role - allow all operations
      if (!sizeof($this->EditableRoles)) return;

      // We might only have a subset of roles available. Apply that subset
      $sender->EventArguments['RoleData'] = $this->EditableRoles;
   }

   public function UserController_BeforeUserEdit_Handler($sender) {

      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;

      // Nothing configured for this role - allow all operations
      if (!sizeof($this->EditableRoles) && !sizeof($this->ProtectedRoles)) return;

      // Get all the roles of the user we're trying to edit
      $theirRoles = $sender->EventArguments['UserRoleData'];
      foreach ($theirRoles as $theirRoleID => $theirRoleName) {
         if (array_key_exists($theirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to edit this user.");
         }
      }

      // If we get here, we're not prevented from modifying this user, but we might
      // still only have a subset of their roles. Apply that subset

      $sender->EventArguments['RoleData'] = $this->EditableRoles;
   }

   public function UserController_BeforeUserDelete_Handler($sender) {

      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;

      // Nothing configured for this role - allow all operations
      if (!sizeof($this->ProtectedRoles)) return;

      // Get all the roles of the user we're trying to edit
      $theirRoles = $sender->EventArguments['UserRoleData'];
      foreach ($theirRoles as $theirRoleID => $theirRoleName) {
         if (array_key_exists($theirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to delete this user.");
         }
      }

   }

   public function CheckRolePermission($targetUserID) {

      // Get all the roles of the user we're trying to edit
      $theirRoleData = Gdn::UserModel()->GetRoles($targetUserID)->Result();
      $theirRoleIDs = array_column($theirRoleData, 'RoleID');
      $theirRoleNames = array_column($theirRoleData, 'Name');
      $theirRoles = ArrayCombine($theirRoleIDs, $theirRoleNames);

      foreach ($theirRoles as $theirRoleID => $theirRoleName) {
         if (array_key_exists($theirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            return FALSE;
         }
      }

      return TRUE;
   }

}
