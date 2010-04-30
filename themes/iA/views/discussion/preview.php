<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CurrentDiscussionID = 0;
$New = FALSE;
$First = TRUE;
?>
<div class="Advertisement TopAdvertisement">
   advertisement
</div>
<ul class="PreviewList Discussion">
   <?php
   $Counter = 0;
   $Loop = 0;
   $CurrentDiscussionKey = 0;
   $DiscussionIDs = $this->DiscussionIDs;
   $Open = FALSE;
   foreach ($this->CommentData->Result() as $Comment) {
      while (array_key_exists($CurrentDiscussionKey, $DiscussionIDs) && $DiscussionIDs[$CurrentDiscussionKey] != $Comment->DiscussionID) {
         if ($Open)
            echo '</li>';
         
         echo '<li class="Item Comment" id="CommentsFor_'.$DiscussionIDs[$CurrentDiscussionKey].'">No replies yet...</li>';
         if ($Open)
            echo '<li class="Item Comment" id="CommentsFor_'.$Comment->DiscussionID.'">';
         
         $CurrentDiscussionKey++;
      }

      if ($Loop == 0) {
         $Open = TRUE;
         echo '<li class="Item Comment" id="CommentsFor_'.$Comment->DiscussionID.'">';
      }
         
      $Loop++;
      $New = $Comment->DiscussionID != $CurrentDiscussionID;
      if ($New)
         $Counter = 0;

      $Counter++;
      if ($Counter <= 2) {
         $CurrentDiscussionID = $Comment->DiscussionID;
         $Author = UserBuilder($Comment, 'Insert');
         if ($New && !$First) {
            if ($Session->IsValid()) {
               echo '<a class="CommentLink" href="#">'.T('Write a comment').'</a>';
               echo $this->FetchView('comment', 'post', 'vanilla');
            }
            echo '</li>';
            echo '<li class="Item Comment" id="CommentsFor_'.$Comment->DiscussionID.'">';
         }
         ?>
            <div class="CommentPreview" id="Comment_<?php echo $Comment->CommentID; ?>">
               <?php echo UserPhoto($Author); ?>
               <div class="Message"><?php echo SliceString(Gdn_Format::Text($Comment->Body), 150); ?></div>
            </div>
      <?php
      }
      $First = FALSE;
   }
   ?>
   </li>
</ul>