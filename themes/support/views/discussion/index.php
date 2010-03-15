<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CurrentOffset = $this->Offset;
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));
if ($this->Pager->FirstPage()) {
?>
<div class="Discussion FirstComment <?php echo $this->Discussion->Category; ?>">
   <?php
   $Comment = $this->CommentData->FirstRow();
   $Author = UserBuilder($Comment, 'Insert');
   $this->EventArguments['Comment'] = &$Comment;
   $this->Options = '';
   $IsFirstComment = $Comment->CommentID == $this->Discussion->FirstCommentID;
   
   if ($IsFirstComment
      && ($Session->UserID == $Comment->InsertUserID
      || $Session->CheckPermission('Vanilla.Discussions.Edit', $this->Discussion->CategoryID)))
   {
      // User can edit the discussion topic/first comment
      $this->Options .= '<li>'.Anchor('Edit', '/vanilla/post/editdiscussion/'.$Comment->DiscussionID, 'EditDiscussion').'</li>';
   } else if ($Session->UserID == $Comment->InsertUserID
      || $Session->CheckPermission('Vanilla.Comments.Edit', $this->Discussion->CategoryID))
   {
      // User can edit the comment
      $this->Options .= '<li>'.Anchor('Edit', '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment').'</li>';
   }
   
   if ($IsFirstComment) {
      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $this->Discussion->CategoryID))
         $this->Options .= '<li>'.Anchor($this->Discussion->Announce == '1' ? 'Unannounce' : 'Announce', 'vanilla/discussion/announce/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $this->Discussion->CategoryID))
         $this->Options .= '<li>'.Anchor($this->Discussion->Sink == '1' ? 'Unsink' : 'Sink', 'vanilla/discussion/sink/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $this->Discussion->CategoryID))
         $this->Options .= '<li>'.Anchor($this->Discussion->Closed == '1' ? 'Reopen' : 'Close', 'vanilla/discussion/close/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $this->Discussion->CategoryID))
         $this->Options .= '<li>'.Anchor('Delete Discussion', 'vanilla/discussion/delete/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</li>';
   } else {
      // Delete comment
      if ($Session->CheckPermission('Vanilla.Comments.Delete', $this->Discussion->CategoryID))
         $this->Options .= '<li>'.Anchor('Delete', 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($this->SelfUrl), 'DeleteComment') . '</li>';
   }
   
   // Allow plugins to add options
   $this->FireEvent('CommentOptions');
   
   if ($this->Options != '') {
      ?>
   <ul class="Options">
      <li><strong><?php echo Gdn::Translate('Options'); ?></strong>
         <ul>
            <?php echo $this->Options; ?>
         </ul>
      </li>
   </ul>
      <?php
   }
   echo UserPhoto($Author, 'AuthorPhoto');
   ?>
   <div class="Title"><?php
      if (Gdn::Config('Vanilla.Categories.Use') === TRUE) {
         echo Anchor($this->Discussion->Category, 'categories/'.$this->Discussion->CategoryID.'/'.Format::Url($this->Discussion->Category));
         echo '<span>&bull;</span>';
      }
      echo Format::Text($this->Discussion->Name);
   ?></div>
   <div class="Meta">
      <span class="Author">
         <?php
         echo UserAnchor($Author);
         ?>
      </span>
      <span class="Created">
         <?php
         echo Format::Date($Comment->DateInserted);
         ?>
      </span>
      <span class="Permalink">
         <?php echo Anchor(Gdn::Translate('Permalink'), '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID, 'Permalink', array('name' => 'Item_'.$CurrentOffset)); ?>
      </span>
      <?php
      $this->FireEvent('AfterCommentMeta');
      ?>
   </div>
   <div class="Body"><?php echo Format::To($Comment->Body, $Comment->Format); ?></div>
   <?php
   $this->FireEvent('AfterCommentBody');


   if ($Session->IsValid()) {
      $Message = '';
      switch ($this->Discussion->Category) {
         case 'Question':
            $Message = 'I have this question, too!';
            break;
         case 'Idea':
            $Message = 'I have this problem, too!';
            break;
         case 'Problem':
            $Message = 'I like this idea!';
            break;
         case 'Kudos':
            $Message = 'I agree!';
            break;
      }
      if ($Message != '') {
         echo '<div class="Voter">';
         if ($this->Discussion->Bookmarked == '0') {
            echo Anchor(
               $Message,
               '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
               'Vote',
               array('title' => Gdn::Translate($Message))
            );

            if ($this->Discussion->Category == 'Question')
               echo '<span>
                  <strong>Notify me when someone answers.</strong>
                  The more people who ask this question, the more it will get noticed.
               </span>';
            elseif ($this->Discussion->Category == 'Idea')
               echo '<span>
                  <strong>Tell me when this idea gets some attention.</strong>
                  The more people who like this idea, the more it will get noticed.
               </span>';
            elseif ($this->Discussion->Category == 'Problem')
               echo '<span>
                  <strong>Tell me when this problem gets fixed.</strong>
                  The more people who have this problem, the more it will get noticed.
               </span>';
         } else {
            echo '<span>';
            if ($this->Discussion->Category == 'Question')
               echo 'You will be notified when this question gets answered.';
            elseif ($this->Discussion->Category == 'Idea')
               echo 'You will be notified when this question gets attention.';
            elseif ($this->Discussion->Category == 'Problem')
               echo 'You will be notified when this problem is solved.';

            echo ' '.Anchor(
               'Stop following this topic.',
               '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
               'UnVote',
               array('title' => Gdn::Translate('Stop following this topic.'))
            );
            echo '</span>';
         }
         echo '</div>';
      }
   }

   
   
   ?>
</div>
<?php
}
echo $this->Pager->ToString('less');
echo $this->RenderAsset('DiscussionBefore');
?>
<ul id="Discussion">
   <?php
   $this->FireEvent('BeforeCommentsRender');
   // Only prints individual comment list items
   $CommentData = $this->CommentData->Result();
   foreach ($CommentData as $Comment) {
      ++$CurrentOffset;
      if ($CurrentOffset != 1) {
         $this->CurrentComment = $Comment;
         WriteComment($Comment, $this, $Session, $CurrentOffset);
      }
   }
   ?>
</ul>
<?php

if($this->Pager->LastPage()) {
   $this->AddDefinition('DiscussionID', $this->Data['Discussion']->DiscussionID);
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(is_null($LastCommentID) || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', $this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}

echo $this->Pager->ToString('more');

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="CommentOption Closed">
      <?php echo Gdn::Translate('This discussion has been closed.'); ?>
   </div>
   <?php
} else {
   if ($Session->IsValid()) {
      echo $this->FetchView('comment', 'post');
   } else {
      ?>
      <div class="CommentOption">
         <?php echo Gdn::Translate('Want to take part in this discussion? Click one of these:'); ?>
         <?php echo Anchor(Gdn::Translate('Sign In'), Gdn::Authenticator()->SignInUrl($this->SelfUrl), 'Button'.(Gdn::Config('Garden.SignIn.Popup') ? ' SignInPopup' : '')); ?> 
         <?php
            $Url = Gdn::Authenticator()->RegisterUrl($this->SelfUrl);
            if(!empty($Url))
               echo Anchor(Gdn::Translate('Apply for Membership'), $Url, 'Button');
         ?>
      </div>
      <?php 
   }
}
?>
<div class="Back">
   <?php echo Anchor(Gdn::Translate('Back to Discussions'), '/discussions'); ?>
</div>