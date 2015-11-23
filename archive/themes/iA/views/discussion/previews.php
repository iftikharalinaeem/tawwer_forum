<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Advertisement TopAdvertisement">
   advertisement
</div>
<ul class="PreviewList Discussion">
   <?php
   $CurrentComment = $this->CommentData->NextRow();
   foreach ($this->DiscussionData->Result() as $CurrentDiscussion) {
      echo '<li class="Item Comment" id="CommentsFor_'.$CurrentDiscussion->DiscussionID.'">';
      $Counter = 0;
      if ($CurrentDiscussion->CountComments > 1) {
         while (is_object($CurrentComment) && $CurrentComment->DiscussionID == $CurrentDiscussion->DiscussionID) {
            $CommentsFound = TRUE;
            $Counter++;
            if ($Counter <= 2) {
               $Author = UserBuilder($CurrentComment, 'Insert');
               ?>
               <div class="CommentPreview" id="Comment_<?php echo $CurrentComment->CommentID; ?>">
                  <?php echo UserPhoto($Author); ?>
                  <div class="Message"><?php echo SliceString(Gdn_Format::Text($CurrentComment->Body), 150); ?></div>
               </div>
               <?php
            }
            $CurrentComment = $this->CommentData->NextRow();
         }
      } else {
         echo 'No replies yet...';
      }
         
      if ($Session->IsValid()) {
         echo '<a class="CommentLink" href="#">'.T('Write a comment').'</a>';
         echo $this->FetchView('comment', 'post', 'vanilla');
      }

      echo '</li>';
   }
   ?>
</ul>