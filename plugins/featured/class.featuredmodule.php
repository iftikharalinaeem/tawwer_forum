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

   public function __construct($sender = '') {
      $this->_ApplicationFolder = 'plugins/featured';
      $this->ClassName = get_class();
   }

   public function getData() {
      $discussionModel = new DiscussionModel();
      $discussions = new Gdn_DataSet([], DATASET_TYPE_ARRAY);

      if (class_exists('ReactionModel')) {
         $reactionType = ReactionModel::reactionTypes($this->reactionType);;
         if ($reactionType) {
            $tagID = $reactionType['TagID'];

            // Get the IDs of the discussions that have been featured.
            $discussionIDs = Gdn::sql()->getWhere('UserTag', [
               'RecordType' => 'Discussion-Total',
               'TagID' => $tagID,
               'Total >' => 0
               ], 'DateInserted', 'desc', $this->count)->resultArray();
            $discussionIDs = array_column($discussionIDs, 'RecordID');
            if (!empty($discussionIDs)) {
               $discussionData = $discussionModel->getWhere(['d.DiscussionID' => $discussionIDs, 'Announce' => 'all'])->resultArray();
               $discussionData = Gdn_DataSet::index($discussionData, 'DiscussionID');

               // Make sure the result is ordered by the date they were featured.
               $result = [];
               foreach ($discussionIDs as $iD) {
                  if (isset($discussionData[$iD]))
                     $result[] = $discussionData[$iD];
               }
               $discussions = new Gdn_DataSet($result, DATASET_TYPE_ARRAY);
            }
         }
      } else {
         $discussions = $discussionModel->getWhere([], 0, $this->count);
      }
      $this->setData('Discussions', $discussions);
   }

   public function assetTarget() {
      return 'Content';
   }

   /**
    * Returns the xhtml for this module as a fully parsed and rendered string.
    *
    * @return string
    */
   public function fetchView($View = '') {
      require_once Gdn::controller()->fetchViewLocation('helper_functions', 'discussions', 'Vanilla');
      $this->CountCommentsPerPage = 50;

      $ViewPath = $this->fetchViewLocation('featured_list');
      $String = '';
      ob_start();
      include $ViewPath;
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }

   public function toString() {
      $this->getData();
      return parent::toString();
   }
}