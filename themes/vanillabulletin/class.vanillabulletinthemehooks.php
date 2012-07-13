<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright 2012 Vanilla Forums Inc
 */
class VanillaBulletinThemeHooks implements Gdn_IPlugin {
   
   public function Setup() { }
   
   /**
    * Add new discussion & conversation buttons to various pages.
    * @param Gdn_Controller $Sender;
    */
   public function CategoriesController_Render_Before($Sender) {
      if ($Sender->RequestMethod != 'index')
         $this->_MovePanel($Sender, 'Discussion');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
      $this->_MovePanel($Sender, 'Discussion');
   }
   
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      $UserID = Gdn::Session()->UserID;
      if (!$UserID)
         return;
      
      // Verified users can post discussions.
      if (Gdn::Session()->User->Verified)
         return;

      // Moderators can post discussions.
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         return;
      
      $CountComments = Gdn::Session()->User->CountComments;
      if ($CountComments === NULL) {
         $CountComments = Gdn::SQL()
            ->Select('CommentID', 'count', 'CountComments')
            ->From('Comment')
            ->Where('InsertUserID', $UserID)
            ->Get()->Value('CountComments', 0);
         
         Gdn::UserModel()->SetField($UserID, 'CountComments', $CountComments);
      }
   }
   
   public function Base_Render_Before($Sender) {
      $Sender->SetData('_SERVER_NAME', $_SERVER["SERVER_NAME"]);
      $Sender->SetData('_REQUEST_URI', $_SERVER["REQUEST_URI"]);
   }
   public function DraftsController_Render_Before($Sender) {
      $this->_MovePanel($Sender, 'Discussion');
   }
  
   public function PostController_Render_Before($Sender) {
      $this->_MovePanel($Sender, 'Discussion');
   }
   
   private function _MovePanel($Sender, $ButtonType) {
      if ($ButtonType == 'Discussion') {
         // $Sender->AddModule('NewDiscussionModule', 'Content');
      } else if (isset($Sender->Assets['Panel'][$ButtonType])) {
         $Sender->Assets['Content'][$ButtonType] = $Sender->Assets['Panel'][$ButtonType];
         unset($Sender->Assets['Content'][$ButtonType]);
      }
   }
}