<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright 2012 Vanilla Forums Inc
 */
class VanillaBulletinThemeHooks implements Gdn_IPlugin {
   
   public function Setup() { }
   
   /**
    * Add new discussion & conversation buttons to various pages.
    * @param Gdn_Controller $sender;
    */
   public function CategoriesController_Render_Before($sender) {
      if ($sender->RequestMethod != 'index')
         $this->_MovePanel($sender, 'Discussion');
   }
   
   public function DiscussionsController_Render_Before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
   
   public function Gdn_Dispatcher_AppStartup_Handler($sender) {
      $userID = Gdn::Session()->UserID;
      if (!$userID)
         return;
      
      // Verified users can post discussions.
      if (Gdn::Session()->User->Verified)
         return;

      // Moderators can post discussions.
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         return;
      
      $countComments = Gdn::Session()->User->CountComments;
      if ($countComments === NULL) {
         $countComments = Gdn::SQL()
            ->Select('CommentID', 'count', 'CountComments')
            ->From('Comment')
            ->Where('InsertUserID', $userID)
            ->Get()->Value('CountComments', 0);
         
         Gdn::UserModel()->SetField($userID, 'CountComments', $countComments);
      }
   }
   
   public function Base_Render_Before($sender) {
      $sender->SetData('_SERVER_NAME', $_SERVER["SERVER_NAME"]);
      $sender->SetData('_REQUEST_URI', $_SERVER["REQUEST_URI"]);
   }
   public function DraftsController_Render_Before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
  
   public function PostController_Render_Before($sender) {
      $this->_MovePanel($sender, 'Discussion');
   }
   
   private function _MovePanel($sender, $buttonType) {
      if ($buttonType == 'Discussion') {
         // $Sender->AddModule('NewDiscussionModule', 'Content');
      } else if (isset($sender->Assets['Panel'][$buttonType])) {
         $sender->Assets['Content'][$buttonType] = $sender->Assets['Panel'][$buttonType];
         unset($sender->Assets['Content'][$buttonType]);
      }
   }
}