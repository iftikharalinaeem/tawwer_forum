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
$PluginInfo['Git'] = array(
   'Description' => 'This plugin draws a line at the end of the page telling you what branch the sourcecode is running.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class GitPlugin extends Gdn_Plugin {

   public function Base_Render_Before(&$Sender) {
      $GitStatus = shell_exec("/usr/local/git/bin/git status");
      $GitBranch = array_pop(explode(' ',array_shift(explode("\n",$GitStatus))));
      
      $GitRevLog = shell_exec("/usr/local/git/bin/git log -n 1");
      $GitRevHash = array_pop(explode(' ',array_shift(explode("\n",$GitRevLog))));
      
      $Sender->GitPlugin_Branch = $GitBranch;
      $Sender->GitPlugin_RevHash = $GitRevHash;
      
      $ViewData = $Sender->FetchView($this->GetView('gitbranch.php'));
      $Sender->AddAsset('Foot', $ViewData, 'GitPluginBar');
   }

   public function Setup() {
      
   }

}