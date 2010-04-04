<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   // Questions are "Unanswered" or "Answered"
   if ($Discussion->Category == 'Question' && $Discussion->State == '')
      $Discussion->State = 'Needs Answer';

   // Ideas are "Suggested", "Planned", "Not Planned" or "Completed"
   if ($Discussion->Category == 'Idea' && $Discussion->State == '')
      $Discussion->State = 'Suggested';
   
   // Problems are "Unsolved" or "Solved"
   if ($Discussion->Category == 'Problem' && $Discussion->State == '')
      $Discussion->State = 'Unsolved';
            
   
   $CssClass = 'DiscussionRow';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= ' ' . $Discussion->Category;
   if ($Discussion->State != '')
      $CssClass .= ' '.Format::AlphaNumeric($Discussion->State);
      
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
            $Score = 0;
            if (is_numeric($Discussion->Score) && $Discussion->Score > 0)
               $Score = $Discussion->Score;
               
            $Title = Gdn::Translate($Discussion->Bookmarked == '1' ? 'Undo Vote' : 'Vote');
            echo Anchor(
               '<span class="Star">'
                  .Img('themes/support/design/pixel.png', array('alt' => $Title))
               .'</span>'
               .'<span class="Votes">'.$Score.'</span>',
               '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
               'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
               array('title' => $Title)
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
               <li><strong><?php echo Gdn::Translate('Options'); ?></strong>
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
         echo Anchor(Format::Text($Discussion->Name), '/discussion/'.$Discussion->DiscussionID.'/'.Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'Title');
      ?></strong>
      <?php
         $Sender->FireEvent('AfterDiscussionTitle');
      ?>
      <div class="Meta">
         <span><b>
         <?php
            echo UserAnchor($First);
            echo '</b>';
            switch($Discussion->Category) {
               case 'Question':
                  echo ' asked';
                  break;
               case 'Idea':
                  echo ' suggested';
                  break;
               case 'Problem':
                  echo ' reported';
                  break;
               case 'Kudos':
                  echo ' thanked';
                  break;
               default:
                  echo ' posted';
                  break;
            }
            echo '</span>';
            echo '<span>';
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(Plural($CountUnreadComments, '%s new comment', '%s new comments'), $CountUnreadComments),'</strong>';
            else 
               printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
            echo '</span>';
            echo '<span>';
            printf(Gdn::Translate($Discussion->CountComments > 0 ? 'Most recent by %1$s %2$s' : 'Posted by %2$s'), UserAnchor($Last), Format::Date($Discussion->LastDate));
            echo '</span>';
            
            if ($Discussion->State != '')
               echo '<span class="State">'.$Discussion->State.'</span>';
               
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</li>
<?php
}