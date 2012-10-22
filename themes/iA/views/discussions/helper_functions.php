<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
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
         <?php
         echo Gdn_Format::To($Discussion->Body, $Discussion->Format);
         ?>
      </div>
      <div class="Meta">
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
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID))
         echo '<div class="OptionButton">'.Anchor(T('Remove'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Delete').'</div>';

      echo '</div>';
   }
}