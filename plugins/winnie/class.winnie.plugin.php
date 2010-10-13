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
$PluginInfo['winnie'] = array(
   'Name' => 'Winnie',
   'Description' => 'And I.....',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class WinniePlugin extends Gdn_Plugin {

	public function DiscussionController_AfterCommentFormat_Handler($Sender) {
		$Obj = GetValue('Object', $Sender->EventArguments);
		$Obj->FormatBody = str_replace('{winnie}', '<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" WIDTH="306" HEIGHT="450"><PARAM NAME="movie" VALUE="http://www.11235813.com/08/winniereprise.swf"><PARAM NAME="quality" VALUE="high"><PARAM NAME="bgcolor" VALUE="#FFFFFF"><EMBED src="http://www.11235813.com/08/winniereprise.swf" quality="high" bgcolor="#FFFFFF"  WIDTH="306" HEIGHT="450" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"></EMBED></OBJECT>', $Obj->FormatBody);
		$Sender->EventArguments['Object'] = $Obj;
	}

   public function OnDisable() {
		// Do nothing
   }

   public function Setup() {
      // Do nothing
   }
}