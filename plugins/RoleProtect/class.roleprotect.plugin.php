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
   'Version' => '1.0b',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.18b'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => array('Garden.Roles.Selective' => 0),
   'SettingsUrl' => '/settings/pennyarcade',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class RoleProtectPlugin extends Gdn_Plugin {
   
   public function __construct() {
      
   }
   
   
   public function UserController_BeforeUserAdd_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      $RoleData = $Sender->EventArguments['RoleData'];
      
      // Roles the logged-in user can modify
      $EditableRoleData = array();
      
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
      }
      
      // Format EditableRoleData into a nice ASSOC array
      $EditableRoleData = array_flip($EditableRoleData);
      $EditableRoles = array();
      foreach ($EditableRoleData as $EditableRoleID => $Trash)
         $EditableRoles[$EditableRoleID] = GetValue($EditableRoleID, $RoleData);
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($EditableRoles)) return;
      
      // We might only have a subset of roles available. Apply that subset
      
      $Sender->EventArguments['RoleData'] = $EditableRoles;
   }
   
   public function UserController_BeforeUserEdit_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      $RoleData = $Sender->EventArguments['RoleData'];
      
      // Roles the logged-in user can modify
      $EditableRoleData = array();
      
      // Roles that, if present in the target user, protect him from  being edited
      $ProtectedRoleData = array();
      
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
         $EditableRoles[$EditableRoleID] = GetValue($EditableRoleID, $RoleData);
      
      // Format ProtectedRoleData into a nice ASSOC array
      $ProtectedRoleData = array_flip($ProtectedRoleData);
      $ProtectedRoles = array();
      foreach ($ProtectedRoleData as $ProtectedRoleID => $Trash)
         $ProtectedRoles[$ProtectedRoleID] = GetValue($ProtectedRoleID, $RoleData);
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($EditableRoles) && !sizeof($ProtectedRoles)) return;
      
      // Get all the roles of the user we're trying to edit
      $TheirRoles = $Sender->EventArguments['UserRoleData'];
      foreach ($TheirRoles as $TheirRoleID => $TheirRoleName) {
         if (array_key_exists($TheirRoleID, $ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to edit this user.");
         }
      }
      
      // If we get here, we're not prevented from modifying this user, but we might
      // still only have a subset of their roles. Apply that subset
      
      $Sender->EventArguments['RoleData'] = $EditableRoles;
   }
   
   public function UserController_BeforeUserDelete_Handler($Sender) {
      
      // If this user is here, they have Account Edit. If they also haver 'Moderator'
      // then we have to take special care, otherwise just proceed as normal without
      // modifying anything.
      if (!Gdn::Session()->CheckPermission('Garden.Roles.Selective'))
         return;
      
      $RoleData = $Sender->EventArguments['RoleData'];
      
      // Roles that, if present in the target user, protect him from  being edited
      $ProtectedRoleData = array();
      
      // Loop over the logged-in user's roles
      $MyRoleData = Gdn::UserModel()->GetRoles(Gdn::Session()->UserID)->Result();
      $RoleIDs = ConsolidateArrayValuesByKey($MyRoleData, 'RoleID');
      $RoleNames = ConsolidateArrayValuesByKey($MyRoleData, 'Name');
      $MyRoles = ArrayCombine($RoleIDs, $RoleNames);
      foreach ($MyRoles as $RoleID => $RoleName) {
         $ProtectedRolesList = C("Plugins.RoleProtect.{$RoleID}.Protected", NULL);
         if (!is_null($ProtectedRolesList)) {
            $ProtectedRolesList = explode(',', $ProtectedRolesList);
            if (is_array($ProtectedRolesList) && sizeof($ProtectedRolesList))
               $ProtectedRoleData = array_merge($ProtectedRoleData, $ProtectedRolesList);
         }
      }
      
      // Format ProtectedRoleData into a nice ASSOC array
      $ProtectedRoleData = array_flip($ProtectedRoleData);
      $ProtectedRoles = array();
      foreach ($ProtectedRoleData as $ProtectedRoleID => $Trash)
         $ProtectedRoles[$ProtectedRoleID] = GetValue($ProtectedRoleID, $RoleData);
      
      // Nothing configured for this role - allow all operations
      if (!sizeof($ProtectedRoles)) return;
      
      // Get all the roles of the user we're trying to edit
      $TheirRoles = $Sender->EventArguments['UserRoleData'];
      foreach ($TheirRoles as $TheirRoleID => $TheirRoleName) {
         if (array_key_exists($TheirRoleID, $ProtectedRoles)) {
            // Short circuit rendering, we can't edit this person
            throw new Exception("You do not have permission to delete this user.");
         }
      }

   }
   
}
   