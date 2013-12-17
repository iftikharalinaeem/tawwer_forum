<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class FeaturedModule extends Gdn_Module {

   public $count = 5;

   public function __construct($Sender = '') {
      $this->_ApplicationFolder = 'plugins/featured';
      $this->ClassName = get_class();
   }

   public function GetData() {

      $SQL = Gdn::SQL();
		$Session = Gdn::Session();


      $DiscussionModel = new DiscussionModel();
      $Discussions = $DiscussionModel->GetWhere(array(), 0, $this->count);
      $this->SetData('Discussions', $Discussions);
   }

   public function AssetTarget() {
      //return 'Panel';
      return 'Content';
   }

   /**
    * Returns the xhtml for this module as a fully parsed and rendered string.
    *
    * @return string
    */
   public function FetchView($View = '') {
      require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'discussions', 'Vanilla');
      $this->CountCommentsPerPage = 50;

      $ViewPath = $this->FetchViewLocation('featured_list');
      $String = '';
      ob_start();
      if(is_object($this->_Sender) && isset($this->_Sender->Data)) {
         $Data = $this->_Sender->Data;
      } else {
         $Data = array();
      }
      include ($ViewPath);
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }

   public function ToString() {
      $this->GetData();
      return parent::ToString();
   }
}