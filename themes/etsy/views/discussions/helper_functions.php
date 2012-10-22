<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'DiscussionRow';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= ' ' . $Discussion->Category;
   $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
?>
<li class="<?php echo $CssClass; ?>">
   <?php
   echo UserPhoto($First, 'AuthorPhoto');
      if ($Sender->ShowOptions) {
      ?>
      <div class="Options">
         <?php
            // Build up the options that the user has for each discussion
            if ($Session->IsValid()) {
               // Bookmark link
               echo Anchor(
                  '<span>*</span>',
                  '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
                  'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
                  array('title' => T($Discussion->Bookmarked == '1' ? 'Undo Vote' : 'Vote'))
               );
               
               $Sender->Options = '';
               
               // Dismiss an announcement
               // if ($Discussion->Announce == '1' && $Discussion->Dismissed != '1')
               //    $Sender->Options .= '<li>'.Anchor('Dismiss', 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
               
               // Edit discussion
               if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor('Edit', 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';
      
               // Announce discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Announce == '1' ? 'Unannounce' : 'Announce', 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';
      
               // Sink discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Sink == '1' ? 'Unsink' : 'Sink', 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';
      
               // Close discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Close', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor($Discussion->Closed == '1' ? 'Reopen' : 'Close', 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
               
               // Delete discussion
               if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
                  $Sender->Options .= '<li>'.Anchor('Delete', 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
               
               // Allow plugins to add options
               $Sender->FireEvent('DiscussionOptions');
               
               if ($Sender->Options != '') {
               ?>
               <ul class="Options">
                  <li><strong><?php echo T('Options'); ?></strong>
                     <ul>
                        <?php echo $Sender->Options; ?>
                     </ul>
                  </li>
               </ul>
               <?php
               }
            }          
         ?>
      </div>
      <?php
      }
      ?>
   <div class="Discussion">
      <strong><?php
         echo Anchor(Gdn_Format::Text($Discussion->Name), '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
      ?></strong>
      <?php
         $Sender->FireEvent('AfterDiscussionTitle');
      ?>
      <div class="Meta">
         <span><b>
         <?php
            echo UserAnchor($First);
            echo '</b>';
            echo '</span>';
            echo '<span>';
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(Plural($CountUnreadComments, '%s new comment', '%s new comments'), $CountUnreadComments),'</strong>';
            else 
               printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
            echo '</span>';
            echo '<span>';
            printf(T($Discussion->CountComments > 0 ? 'Most recent by %1$s %2$s' : 'Posted by %2$s'), UserAnchor($Last), Gdn_Format::Date($Discussion->LastDate));
            echo '</span>';

            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}