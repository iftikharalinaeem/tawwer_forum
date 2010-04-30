<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CountUnreadComments = $Discussion->CountUnreadComments;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $Last = UserBuilder($Discussion, 'Last');
   $First = UserBuilder($Discussion, 'First');
   $FirstPhoto = UserPhoto($First);
?>
<li class="<?php echo $CssClass; ?>" id="Discussion_<?php echo $Discussion->DiscussionID; ?>">
   <?php WriteOptions($Discussion, $Sender, $Session); ?>
   <?php if ($FirstPhoto != '') { ?>
      <div class="Photo"><?php echo $FirstPhoto; ?></div>
   <?php } ?>   
   <div class="ItemContent Discussion">
      <?php echo UserAnchor($First, 'Name Title'); ?>
      <div class="Excerpt">
         <?php echo Gdn_Format::To($Discussion->Body, $Discussion->Format); ?>
      </div>
      <div class="Meta">
         <?php
         /*
         if ($Discussion->Announce == '1') { ?>
         <span class="Announcement"><?php echo T('Announcement'); ?></span>
         <?php } ?>
         <?php if ($Discussion->Closed == '1') { ?>
         <span class="Closed"><?php echo T('Closed'); ?></span>
         <?php } ?>
         <span><?php printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments); ?></span>
         <?php
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(T('%s new'), $CountUnreadComments),'</strong>';
         ?>
         <span><?php printf(T('Most recent by %1$s %2$s'), UserAnchor($Last), Gdn_Format::Date($Discussion->LastDate)); ?></span>
         <span><?php echo Anchor($Discussion->Category, '/categories/'.$Discussion->CategoryUrlCode, 'Category'); ?></span>
         <?php
         */
         ?>
         <span><?php echo Anchor(Gdn_Format::Date($Discussion->FirstDate), '/discussion/'.$Discussion->DiscussionID.($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionUrl'); ?></span>
         <?php $Sender->FireEvent('DiscussionMeta'); ?>
      </div>
   </div>
</li>
<?php
}

/**
 * Render options that the user has for this discussion.
 */
function WriteOptions($Discussion, &$Sender, &$Session) {
   if ($Session->IsValid() && $Sender->ShowOptions) {
      echo '<div class="Options">';
      // Bookmark link
      $Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
      echo Anchor(
         '<span class="Star">'
            .Img('applications/dashboard/design/images/pixel.png', array('alt' => $Title))
         .'</span>',
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => $Title)
      );
      
      $Sender->Options = '';
/*
      Facebook has no fancy options!
      
      // Dismiss an announcement
      if ($Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         $Sender->Options .= '<li>'.Anchor(T('Dismiss'), 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
      // Allow plugins to add options
      $Sender->FireEvent('DiscussionOptions');
      
      if ($Sender->Options != '') {
      ?>
         <ul class="Options">
            <li>
               <strong><?php echo T('Options'); ?></strong>
               <ul>
                  <?php echo $Sender->Options; ?>
               </ul>
            </li>
         </ul>
      <?php
      }
*/
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
         echo '<div class="OptionButton">'.Anchor(T('Remove'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Delete').'</div>';


      echo '</div>';
   }
}