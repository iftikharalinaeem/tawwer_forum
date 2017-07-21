<?php if (!defined('APPLICATION')) exit();

class BookmarkCheckboxPlugin extends Gdn_Plugin {
   
   /** 
    * Add the bookmark checkbox to the comment form.
    */
   public function DiscussionController_AfterBodyField_Handler($sender) {
      $this->addCheckBox($sender);
   }
   
   public function PostController_DiscussionFormOptions_Handler($sender) {
      $this->addCheckBox($sender);
   }
   
   private function addCheckBox($sender) {
      if (Gdn::Session()->IsValid()) {
         // Is this discussion currently bookmarked?
         $attributes = [
             'value' => '1', 
             'checked' => 'checked'
         ];

         echo $sender->Form->CheckBox('Bookmarked', T('Bookmark this discussion'), $attributes).'</li>';
      }
   }
   
   /** 
    * Save the bookmark value on comment form postback 
    */
   public function PostController_AfterCommentSave_Handler($sender) {
      $discussion = &$sender->EventArguments['Discussion'];
      
      if (is_object($discussion)) {
         $currentState = GetValue('Bookmarked', $discussion);
         $formState = $sender->Form->GetFormValue('Bookmarked');
         
          // Only bookmark, don't unbookmark
         if ($formState && $currentState != $formState) {
            $discussion->Bookmarked = $sender->DiscussionModel->BookmarkDiscussion($discussion->DiscussionID, Gdn::Session()->UserID);
         }
      }
   }
}
