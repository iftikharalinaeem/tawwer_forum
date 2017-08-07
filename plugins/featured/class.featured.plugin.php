<?php if(!defined('APPLICATION')) die();

class FeaturedPlugin extends Gdn_Plugin {

   public function setup() {
      $this->structure();
   }

   public function structure() {
      if (class_exists('ReactionModel')) {
         $rm = new ReactionModel();
         $rm->defineReactionType(['UrlCode' => 'Feature', 'Name' => 'Feature', 'Sort' => '0', 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
         'Description' => 'Feature a discussion.', 'Permission' => 'Garden.Curation.Manage', 'RecordTypes' => ['discussion']], 'Featured');
      }
   }
}
