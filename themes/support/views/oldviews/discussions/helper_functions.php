<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   // Questions are "Unanswered" or "Answered"
   $Discussion->State = $Discussion->State == '' ? 'Needs Answer' : $Discussion->State;

   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch - 1;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   if ($Discussion->State != '')
      $CssClass .= ' '.Gdn_Format::AlphaNumeric($Discussion->State);
      
   $CountVotes = 0;
   if (is_numeric($Discussion->Score) && $Discussion->Score > 0)
      $CountVotes = $Discussion->Score;
   
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $First = UserBuilder($Discussion, 'First');
   $FirstPhoto = UserPhoto($First);
   $Last = UserBuilder($Discussion, 'Last');
?>
<li class="<?php echo $CssClass; ?>">
   <?php
   // Answers
   $Css = 'StatBox AnswersBox';
   if ($Discussion->CountComments > 1)
      $Css .= ' HasAnswersBox';
      
   echo Wrap(
      // Anchor(
      Wrap(T('Answers')) . ($Discussion->CountComments - 1)
      // ,'/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '')
      // )
      , 'div', array('class' => $Css));
   
   // Views
   echo Wrap(
      // Anchor(
      Wrap(T('Views')) . $Discussion->CountViews
      // , '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '')
      // )
      , 'div', array('class' => 'StatBox ViewsBox'));

   // Follows
   $Title = T($Discussion->Bookmarked == '1' ? 'Undo Follow' : 'Follow');
   if ($Session->IsValid()) {
      echo Wrap(Anchor(
         Wrap(T('Follows')) . $Discussion->CountBookmarks,
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         '',
         array('title' => $Title)
      ), 'div', array('class' => 'StatBox FollowsBox'));
   } else {
      echo Wrap(Wrap(T('Follows')) . $Discussion->CountBookmarks, 'div', array('class' => 'StatBox FollowsBox'));
   }

   // Votes
   if ($Session->IsValid()) {
      echo Wrap(Anchor(
         Wrap(T('Votes')) . $CountVotes,
         '/vanilla/discussion/votediscussion/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         '',
         array('title' => T('Vote'))
      ), 'div', array('class' => 'StatBox VotesBox'));
   } else {
      echo Wrap(Wrap(T('Votes')) . $CountVotes, 'div', array('class' => 'StatBox VotesBox'));
   }

/*
   if ($FirstPhoto != '') {
   ?>
      <div class="Photo"><?php echo $FirstPhoto; ?></div>
   <?php }
*/
?>   
   <div class="ItemContent Discussion">
      <?php echo Anchor(
         Gdn_Format::Text($Discussion->Name),
         '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name)
         // .($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '')
         , 'Title');
      ?>
      <?php $Sender->FireEvent('AfterDiscussionTitle'); ?>
      <div class="Meta">
         <?php
            // echo Wrap(UserAnchor($First), 'b');
            
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo Wrap(sprintf(T('%s new'), $CountUnreadComments), 'strong');
            
            if ($Discussion->CountComments == 1)
               echo Wrap(Gdn_Format::Date($Discussion->LastDate));
            else
               echo Wrap(sprintf(T('Most recent %1$s %2$s'), UserAnchor($Last), Gdn_Format::Date($Discussion->LastDate)));
               
            WriteOptions($Discussion, $Sender, $Session);
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}

function WriteFilterTabs(&$Sender) {
   $Session = Gdn::Session();
   $Title = property_exists($Sender, 'Category') && is_object($Sender->Category) ? $Sender->Category->Name : T('All Discussions');
   $Bookmarked = T('Following');
   $MyDiscussions = T('Mine');
   // $MyDrafts = T('My Drafts');
   $CountBookmarks = 0;
   $CountDiscussions = 0;
   // $CountDrafts = 0;
   if ($Session->IsValid()) {
      $CountBookmarks = $Session->User->CountBookmarks;
      $CountDiscussions = $Session->User->CountDiscussions;
      $CountDrafts = $Session->User->CountDrafts;
   }
   if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
      $Bookmarked .= '<span>'.$CountBookmarks.'</span>';            

   if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
      $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';            
/*
   if (is_numeric($CountDrafts) && $CountDrafts > 0)
      $MyDrafts .= '<span>'.$CountDrafts.'</span>';
*/    
   ?>
<div class="Tabs DiscussionsTabs">
   <ul>
      <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All Questions'), 'discussions'); ?></li>
      <?php if ($CountDiscussions > 0 || $Sender->RequestMethod == 'popular') { ?>
      <li<?php echo $Sender->RequestMethod == 'popular' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Popular'), '/discussions/popular', 'PopularDiscussions'); ?></li>
      <?php } ?>
      <?php if ($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') { ?>
      <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions'); ?></li>
      <?php } ?>
      <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
      <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo Anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks'); ?></li>
      <?php } ?>
      <?php
      /*
      if ($CountDrafts > 0 || $Sender->ControllerName == 'draftscontroller') {
      ?>
      <li<?php echo $Sender->ControllerName == 'draftscontroller' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDrafts, '/drafts', 'MyDrafts'); ?></li>
      <?php }
      */
      ?>
   </ul>
   <?php
   if (property_exists($Sender, 'Category') && is_object($Sender->Category)) {
      ?>
      <div class="SubTab">↳ <?php echo $Sender->Category->Name; ?></div>
      <?php
   }
   ?>
</div>
   <?php
}

/**
 * Render options that the user has for this discussion.
 */
function WriteOptions($Discussion, &$Sender, &$Session) {
   if ($Session->IsValid() && $Sender->ShowOptions) {
      // Dismiss an announcement
      if ($Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         echo ' '.Anchor(T('Dismiss'), 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'Option DismissAnnouncement');
/*      
      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Discussion->CategoryID))
         echo ' '.Anchor(T($Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'Option AnnounceDiscussion');

*/
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Discussion->CategoryID))
         echo ' '.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'Option EditDiscussion');

/*
      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Discussion->CategoryID))
         echo ' '.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Option SinkDiscussion');

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $Discussion->CategoryID))
         echo ' '.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Option CloseDiscussion');

*/      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
         echo ' '.Anchor(T('Delete'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Option DeleteDiscussion');
      
      // Allow plugins to add options
      $Sender->FireEvent('DiscussionOptions');
   }
}