<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright 2012 Vanilla Forums Inc
 */
class VanillaBulletinThemeHooks implements Gdn_IPlugin {
   
   public function setup() { }
   
   /**
    * Add new discussion & conversation buttons to various pages.
    * @param Gdn_Controller $sender;
    */
   public function categoriesController_render_before($sender) {
      if ($sender->RequestMethod != 'index')
         $this->_MovePanel($sender, 'Discussion');
   }
   
   public function discussionsController_render_before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
   
   public function gdn_Dispatcher_AppStartup_Handler($sender) {
      $userID = Gdn::session()->UserID;
      if (!$userID)
         return;
      
      // Verified users can post discussions.
      if (Gdn::session()->User->Verified)
         return;

      // Moderators can post discussions.
      if (Gdn::session()->checkPermission('Garden.Moderation.Manage'))
         return;
      
      $countComments = Gdn::session()->User->CountComments;
      if ($countComments === NULL) {
         $countComments = Gdn::sql()
            ->select('CommentID', 'count', 'CountComments')
            ->from('Comment')
            ->where('InsertUserID', $userID)
            ->get()->value('CountComments', 0);
         
         Gdn::userModel()->setField($userID, 'CountComments', $countComments);
      }
   }
   
   public function base_render_before($sender) {
      $sender->setData('_SERVER_NAME', $_SERVER["SERVER_NAME"]);
      $sender->setData('_REQUEST_URI', $_SERVER["REQUEST_URI"]);
   }
   public function draftsController_render_before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
  
   public function postController_render_before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
   
   private function _MovePanel($sender, $buttonType) {
      if ($buttonType == 'Discussion') {
         // $Sender->addModule('NewDiscussionModule', 'Content');
      } else if (isset($sender->Assets['Panel'][$buttonType])) {
         $sender->Assets['Content'][$buttonType] = $sender->Assets['Panel'][$buttonType];
         unset($sender->Assets['Content'][$buttonType]);
      }
   }
}