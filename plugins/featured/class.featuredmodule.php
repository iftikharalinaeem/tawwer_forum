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
   public $count = 6;
   public $reactionType = 'Feature';

   public function __construct($Sender = '') {
      $this->_ApplicationFolder = 'plugins/featured';
      $this->ClassName = get_class();
   }

   public function GetData() {
      $DiscussionModel = new DiscussionModel();
      $Discussions = new Gdn_DataSet(array(), DATASET_TYPE_ARRAY);

      if (class_exists('ReactionModel')) {
         $ReactionType = ReactionModel::ReactionTypes($this->reactionType);;
         if ($ReactionType) {
            $TagID = $ReactionType['TagID'];

            // Get the IDs of the discussions that have been featured.
            $DiscussionIDs = Gdn::SQL()->GetWhere('UserTag', array(
               'RecordType' => 'Discussion-Total',
               'TagID' => $TagID,
               'Total >' => 0
               ), 'DateInserted', 'desc', $this->count)->ResultArray();
            $DiscussionIDs = array_column($DiscussionIDs, 'RecordID');
            if (!empty($DiscussionIDs)) {
               $DiscussionData = $DiscussionModel->GetWhere(array('d.DiscussionID' => $DiscussionIDs, 'Announce' => 'all'))->ResultArray();
               $DiscussionData = Gdn_DataSet::Index($DiscussionData, 'DiscussionID');

               // Make sure the result is ordered by the date they were featured.
               $Result = array();
               foreach ($DiscussionIDs as $ID) {
                  if (isset($DiscussionData[$ID]))
                     $Result[] = $DiscussionData[$ID];
               }
               $Discussions = new Gdn_DataSet($Result, DATASET_TYPE_ARRAY);
            }
         }
      } else {
         $Discussions = $DiscussionModel->GetWhere(array(), 0, $this->count);
      }
      $this->SetData('Discussions', $Discussions);
   }

   public function AssetTarget() {
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
      include $ViewPath;
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }

   public function ToString() {
      $this->GetData();
      return parent::ToString();
   }
}