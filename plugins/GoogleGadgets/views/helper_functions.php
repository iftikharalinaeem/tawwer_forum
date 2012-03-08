<?php if (!defined('APPLICATION')) exit();

function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt) {
   $CssClass = 'DiscussionRow';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt.' ';
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
   $CssClass .= ($CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $Sender->EventArguments['Discussion'] = &$Discussion;
?>
<div class="<?php echo $CssClass; ?>">
   <div class="Discussion">
      <div class="Title">
         <strong><?php
            echo Anchor(Format::Text($Discussion->Name), Gdn_Url::WebRoot(TRUE).'/discussion/'.$Discussion->DiscussionID.'/'.Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
         ?></strong>
      </div>
      <?php
         $Sender->FireEvent('AfterDiscussionTitle');
      ?>
      <div class="Meta">
         <?php
            echo '<span>';
            echo sprintf(Plural($Discussion->CountComments, '%s comment', '%s comments'), $Discussion->CountComments);
            echo '</span>';
            if ($CountUnreadComments > 0 && $Session->IsValid())
               echo '<strong>',sprintf(Gdn::Translate('%s new'), $CountUnreadComments),'</strong>';
               
            echo '<span>';
            $Last = new stdClass();
            $Last->UserID = $Discussion->LastUserID;
            $Last->Name = $Discussion->LastName;
            printf(Gdn::Translate('Most recent by %1$s %2$s'), UserAnchor($Last), Format::Date($Discussion->LastDate));
            echo '</span>';

            echo Anchor($Discussion->Category, Gdn_Url::WebRoot(TRUE).'/categories/'.$Discussion->CategoryUrlCode, 'Category');
            $Sender->FireEvent('DiscussionMeta');
         ?>
      </div>
   </div>
</div>
<?php
}