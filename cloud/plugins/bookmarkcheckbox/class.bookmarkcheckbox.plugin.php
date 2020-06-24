<?php if (!defined('APPLICATION')) exit();

class BookmarkCheckboxPlugin extends Gdn_Plugin {
   
   /** 
    * Add the bookmark checkbox to the comment form.
    */
   public function discussionController_afterBodyField_handler($sender) {
      $this->addCheckBox($sender);
   }
   
   public function postController_discussionFormOptions_handler($sender) {
      $this->addCheckBox($sender);
   }
   
   private function addCheckBox($sender) {
      if (Gdn::session()->isValid()) {
         // Is this discussion currently bookmarked?
         $attributes = [
             'value' => '1', 
             'checked' => 'checked'
         ];

         echo $sender->Form->checkBox('Bookmarked', t('Bookmark this discussion'), $attributes).'</li>';
      }
   }
   
   /** 
    * Save the bookmark value on comment form postback 
    */
   public function postController_afterCommentSave_handler($sender) {
      $discussion = &$sender->EventArguments['Discussion'];
      
      if (is_object($discussion)) {
         $currentState = getValue('Bookmarked', $discussion);
         $formState = $sender->Form->getFormValue('Bookmarked');
         
          // Only bookmark, don't unbookmark
         if ($formState && $currentState != $formState) {
            $discussion->Bookmarked = $sender->DiscussionModel->bookmarkDiscussion($discussion->DiscussionID, Gdn::session()->UserID);
         }
      }
   }
}
