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
$PluginInfo['BoagWorldCompat'] = array(
   'Name' => 'Boagworld Compatibility Plugin',
   'Description' => 'Converts bbcode quotes to html quotes.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class BoagWorldCompatPlugin extends Gdn_Plugin {

   public function Base_BeforeCommentBody_Handler($Sender) {
		$Object = GetValue('Object', $Sender->EventArguments);
		if (is_object($Object) && strpos($Object->Body, '[/quote]') > 0) {
			$Object->Format = 'BBCode';
			$Sender->EventArguments['Object'] = $Object;
		}
	}
	
   public function OnDisable() { }
   public function Setup() { }
	
}