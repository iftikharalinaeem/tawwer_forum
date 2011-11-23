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
$PluginInfo['RoleProtect'] = array(
   'Name' => 'Role Protection',
   'Description' => 'Prevents certain privileged roles from escalating their permissions or deleting other privileged users.',
   'Version' => '1.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.18b'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => array('Garden.Roles.Selective' => 0),
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class RoleProtectPlugin extends Gdn_Plugin {
   
   protected $Roles;
   protected $EditableRoles;
   protected $ProtectedRoles;

   public function __construct() {
      $RoleModel = new RoleModel();
      $this->Roles = $RoleModel->GetArray();
      $RoleModel = NULL;
   }
   
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender) {
      
      // Roles the logged-in user can modify
      $EditableRoleData = $this->EditableRoles = array();
      
      // Roles that, if present in the target user, protect him from  being edited
      $ProtectedRoleData = $this->ProtectedRoles = array();
      
      if (!Gdn::Session()->IsValid()) return;
      
      // Loop over the logged-in user's roles
      $MyRoleData = Gdn::UserModel()->GetRoles(Gdn::Session()->UserID)->Result();
      $RoleIDs = ConsolidateArrayValuesByKey($MyRoleData, 'RoleID');
      $RoleNames = ConsolidateArrayValuesByKey($MyRoleData, 'Name');
      $MyRoles = ArrayCombine($RoleIDs, $RoleNames);
      foreach ($MyRoles as $RoleID => $RoleName) {
         $EditableRolesList = C("Plugins.RoleProtect.{$RoleID}.CanAffect", NULL);
         if (!is_null($EditableRolesList)) {
            $EditableRolesList = explode(',', $EditableRolesList);
            if (is_array($EditableRolesList) && sizeof($EditableRolesList))
               $EditableRoleData = array_merge($EditableRoleData, $EditableRolesList);
         }
         
         $ProtectedRolesList = C("Plugins.RoleProtect.{$RoleID}.Protected", NULL);
         if (!is_null($ProtectedRolesList)) {
            $ProtectedRolesList = explode(',', $ProtectedRolesList);
            if (is_array($ProtectedRolesList) && sizeof($ProtectedRolesList))
               $ProtectedRoleData = array_merge($ProtectedRoleData, $ProtectedRolesList);
         }
      }
      
      // Format EditableRoleData into a nice ASSOC array
      $EditableRoleData = array_flip($EditableRoleData);
      $EditableRoles = array();
      foreach ($EditableRoleData as $EditableRoleID => $Trash)
         $EditableRoles[$EditableRoleID] = GetValue($EditableRoleID, $this->Roles);
      $this->EditableRoles = $EditableRoles;
      
      // Format ProtectedRoleData into a nice ASSOC array
      $ProtectedRoleData = array_flip($ProtectedRoleData);
      $ProtectedRoles = array();
      foreach ($ProtectedRoleData as $ProtectedRoleID => $Trash)
         $ProtectedRoles[$ProtectedRoleID] = GetValue($ProtectedRoleID, $this->Role);
      $this->ProtectedRoles = $ProtectedRoles;
   }
   
   public function UserController_BeforeUserAdd_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($this->EditableRoles)) return;
      
      // We might only have a subset of roles available. Apply that subset
      $Sender->EventArguments['RoleData'] = $this->EditableRoles;
   }
   
   public function UserController_BeforeUserEdit_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($this->EditableRoles) && !sizeof($this->ProtectedRoles)) return;
      
      // Get all the roles of the user we're trying to edit
      $TheirRoles = $Sender->EventArguments['UserRoleData'];
      foreach ($TheirRoles as $TheirRoleID => $TheirRoleName) {
         if (array_key_exists($TheirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to edit this user.");
         }
      }
      
      // If we get here, we're not prevented from modifying this user, but we might
      // still only have a subset of their roles. Apply that subset
      
      $Sender->EventArguments['RoleData'] = $this->EditableRoles;
   }
   
   public function UserController_BeforeUserDelete_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($this->ProtectedRoles)) return;
      
      // Get all the roles of the user we're trying to edit
      $TheirRoles = $Sender->EventArguments['UserRoleData'];
      foreach ($TheirRoles as $TheirRoleID => $TheirRoleName) {
         if (array_key_exists($TheirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to delete this user.");
         }
      }

   }
   
   public function CheckRolePermission($TargetUserID) {
      
      // Get all the roles of the user we're trying to edit
      $TheirRoleData = Gdn::UserModel()->GetRoles($TargetUserID)->Result();
      $TheirRoleIDs = ConsolidateArrayValuesByKey($TheirRoleData, 'RoleID');
      $TheirRoleNames = ConsolidateArrayValuesByKey($TheirRoleData, 'Name');
      $TheirRoles = ArrayCombine($TheirRoleIDs, $TheirRoleNames);
      
      foreach ($TheirRoles as $TheirRoleID => $TheirRoleName) {
         if (array_key_exists($TheirRoleID, $this->ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            return FALSE;
         }
      }
      
      return TRUE;
   }
   
}
   