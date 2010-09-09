<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VFOrgThemeHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }
   
   public function DiscussionsController_Render_Before(&$Sender) {
      $Form = Gdn::Factory('Form');
      $Form->InputPrefix = '';
      $SearchForm = '<div class="SearchForm">'
      .$Form->Open(array('action' => Url('/search'), 'method' => 'get'))
		.$Form->TextBox('Search')
		.$Form->Button('Search', array('Name' => ''))
		.$Form->Close()
      .'</div>';

      $Sender->AddAsset('Content', $SearchForm, 'Content');
   }
   
}