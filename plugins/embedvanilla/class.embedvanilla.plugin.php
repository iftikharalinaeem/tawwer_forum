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
$PluginInfo['embedvanilla'] = array(
   'Name' => 'Embed Vanilla',
   'Description' => "Embed Vanilla allows you to embed your Vanilla forum within another application like WordPress, Drupal, or some custom website you've created.",
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class EmbedVanillaPlugin extends Gdn_Plugin {
   
	public function Base_Render_Before($Sender) {
		$Sender->AddJsFile('plugins/embedvanilla/local.js');

		// Record the remote source using the embed feature.
		$RemoteUrl = C('Plugins.EmbedVanilla.RemoteUrl');
		if (!$RemoteUrl) {
			$RemoteUrl = GetIncomingValue('remote');
			if ($RemoteUrl)
				SaveToConfig('Plugins.EmbedVanilla.RemoteUrl', $RemoteUrl);
		}

		// Report the remote url to redirect to if not currently embedded.
		if (!IsSearchEngine() && $RemoteUrl && ($Sender->MasterView == 'default' || $Sender->MasterView == ''))
			$Sender->AddDefinition('RemoteUrl', $RemoteUrl);

	}
	
   public function Setup() {
      // Nothing to do here!
   }

}