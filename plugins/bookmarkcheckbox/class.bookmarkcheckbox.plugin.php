<?php if (!defined('APPLICATION')) exit();

class BookmarkCheckboxPlugin extends Gdn_Plugin {
   
   /** 
    * Add the bookmark checkbox to the comment form.
    */
   public function DiscussionController_AfterBodyField_Handler($Sender) {
      $this->addCheckBox($Sender);
   }
   
   public function PostController_DiscussionFormOptions_Handler($Sender) {
      $this->addCheckBox($Sender);
   }
   
   private function addCheckBox($Sender) {
      if (Gdn::Session()->IsValid()) {
         // Is this discussion currently bookmarked?
         $Attributes = array(
             'value' => '1', 
             'checked' => 'checked'
         );

         echo $Sender->Form->CheckBox('Bookmarked', T('Bookmark this discussion'), $Attributes).'</li>';
      }
   }
   
   /** 
    * Save the bookmark value on comment form postback 
    */
   public function PostController_AfterCommentSave_Handler($Sender) {
      $Discussion = &$Sender->EventArguments['Discussion'];
      
      if (is_object($Discussion)) {
         $CurrentState = GetValue('Bookmarked', $Discussion);
         $FormState = $Sender->Form->GetFormValue('Bookmarked');
         
          // Only bookmark, don't unbookmark
         if ($FormState && $CurrentState != $FormState) {
            $Discussion->Bookmarked = $Sender->DiscussionModel->BookmarkDiscussion($Discussion->DiscussionID, Gdn::Session()->UserID);
         }
      }
   }
}
