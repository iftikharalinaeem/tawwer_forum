<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= $Discussion->Closed == '1' ? ' Closed' : '';
   $CountUnreadComments = $Discussion->CountUnreadComments;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
?>
<li class="<?php echo $CssClass; ?>">
   <?php WriteOptions($Discussion, $Sender, $Session); ?>
   <div class="ItemContent Discussion">
      <?php echo Anchor(Wrap($Discussion->DiscussionID, 'span').Gdn_Format::Text($Discussion->Name), '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'Title'); ?>
      <?php $Sender->FireEvent('AfterDiscussionTitle'); ?>
      <div class="Meta">
         <?php if ($Discussion->Closed == '1') { ?>
         <span class="Closed"><?php echo T('Closed'); ?></span>
         <?php } ?>
         <span><?php printf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments); ?></span>
         <?php
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(T('%s new'), $CountUnreadComments),'</strong>';
         ?>
         <span><?php
            if ($Discussion->LastCommentID != '')
               printf(T('Most recent by %1$s %2$s'), UserAnchor($Last), Gdn_Format::Date($Discussion->LastDate));
            else
               printf(T('Started by %1$s %2$s'), UserAnchor($First), Gdn_Format::Date($Discussion->FirstDate));
         ?></span>
         <span><?php echo Anchor($Discussion->Category, '/categories/'.$Discussion->CategoryUrlCode, 'Category'); ?></span>
         <?php $Sender->FireEvent('DiscussionMeta'); ?>
      </div>
   </div>
</li>
<?php
}

function WriteFilterTabs(&$Sender) {
   $Session = Gdn::Session();
   $Title = property_exists($Sender, 'Category') && is_object($Sender->Category) ? $Sender->Category->Name : T('All Issues');
   $Bookmarked = T('Following');
   $MyDiscussions = T('My Issues');
   $CountBookmarks = 0;
   $CountDiscussions = 0;
   if ($Session->IsValid()) {
      $CountBookmarks = $Session->User->CountBookmarks;
      $CountDiscussions = $Session->User->CountDiscussions;
   }
   if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
      $Bookmarked .= '<span>'.$CountBookmarks.'</span>';            

   if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
      $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';            
      
   ?>
<div class="Tabs DiscussionsTabs">
   <ul>
      <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Open Issues'), 'discussions'); ?></li>
      <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
      <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo Anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks'); ?></li>
      <?php
      }
      if ($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') {
      ?>
      <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions'); ?></li>
      <?php
      }
      ?>
      <li<?php echo $Sender->RequestMethod == 'closed' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('Closed Issues'), '/discussions/closed', 'ClosedDiscussions'); ?></li>
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
      echo '<div class="Options">';
      // Follow link
      $Title = T($Discussion->Bookmarked == '1' ? 'Unfollow' : 'Follow');
      echo Anchor(
         '<span class="Star">'
            .Img('applications/dashboard/design/images/pixel.png', array('alt' => $Title))
         .'</span>',
         '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
         'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => $Title)
      );
      
      $Sender->Options = '';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

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
      echo '</div>';
   }
}