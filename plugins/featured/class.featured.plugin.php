<?php if(!defined('APPLICATION')) die();

class FeaturedPlugin extends Gdn_Plugin {

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      if (class_exists('ReactionModel')) {
         $rm = new ReactionModel();
         $rm->DefineReactionType(['UrlCode' => 'Feature', 'Name' => 'Feature', 'Sort' => '0', 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
         'Description' => 'Feature a discussion.', 'Permission' => 'Garden.Curation.Manage', 'RecordTypes' => ['discussion']], 'Featured');
      }
   }
}
