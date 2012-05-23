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
$PluginInfo['BanHammer'] = array(
   'Name' => 'Ban Hammer',
   'Description' => 'Adds a "Ban User" button to the user profile pages. Administrators can assign who gets to see & use this button by assigning "Allow Ban" permissions to roles of their choice.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
	'RegisterPermissions' => array('Garden.Ban.Allow')
);

class BanHammerPlugin extends Gdn_Plugin {

	public function ProfileController_AfterAddSideMenu_Handler($Sender) {
		$SideMenu = GetValue('SideMenu', $Sender->EventArguments);
		$Session = Gdn::Session();
		if ($SideMenu && $Session->CheckPermission('Garden.Ban.Allow') && $Session->UserID != $Sender->User->UserID)
			$SideMenu->AddLink('Options', T($this->_IsBanned($Sender->User->UserID) ? 'Unban User' : 'Ban User'), '/profile/ban/'.$Sender->User->UserID.'/'.Gdn::Session()->TransientKey(), FALSE, array('class' => 'BanHammer'));
	}
	
	public function ProfileController_Ban_Create($Sender) {
		$UserID = GetValue(0, $Sender->RequestArgs);
		$Username = 'user_banned';
		$TransientKey = GetValue(1, $Sender->RequestArgs);
		$Session = Gdn::Session();
		if ($Session->ValidateTransientKey($TransientKey) && $Session->CheckPermission('Garden.Ban.Allow')) {
			$UserModel = Gdn::UserModel();
			$UserModel->SaveRoles($UserID, array($this->_IsBanned($UserID) ? C('Plugins.BanHammer.UnbanRoleID', 8) : C('Plugins.BanHammer.BanRoleID', 1)), TRUE);
			$Username = Gdn::UserModel()->Get($UserID)->Name;
		}
		// Redirect back to the user profile
		Redirect(Url(UserUrl(array('UserID' => $UserID, 'Name' => $Username)), TRUE));
	}
	
	public function ProfileController_Render_Before($Sender) {
		$Sender->Head->AddString('
<style type="text/css">
	li.BanHammer a {
		color: #f00;
		font-weight: bold;
	}
</style>
<script type="text/javascript">
	// confirm ban
   $("li.BanHammer a").popup({
      confirm: true,
      followConfirm: true
   });
</script>
');
	}
	
	private function _IsBanned($UserID) {
		$Is = FALSE;
		$RoleData = Gdn::UserModel()->GetRoles($UserID);
		if ($RoleData) {
			foreach ($RoleData->Result() as $Role) {
				if (is_object($Role)) {
					if ($Role->RoleID == C('Plugins.BanHammer.BanRoleID', 1))
						$Is = TRUE;
				}

			}
		}
		return $Is;
	}

   public function OnDisable() {
		// Do nothing
   }
   public function Setup() {
      // Do nothing
   }
}