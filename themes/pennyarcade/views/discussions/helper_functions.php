<?php if (!defined('APPLICATION')) exit();

// WriteDiscussion() is completely custom for the PA theme.
function WriteDiscussion($Discussion, &$Sender, &$Session, $Alt2) {
   static $Alt = FALSE;
   $CssClass = 'Item';
   $CssClass .= $Discussion->Bookmarked == '1' ? ' Bookmarked' : '';
   $CssClass .= $Alt ? ' Alt ' : '';
   $Alt = !$Alt;
   $CssClass .= $Discussion->Announce == '1' ? ' Announcement' : '';
   $CssClass .= $Discussion->Dismissed == '1' ? ' Dismissed' : '';
   $CssClass .= $Discussion->InsertUserID == $Session->UserID ? ' Mine' : '';
   $CssClass .= ($Discussion->CountUnreadComments > 0 && $Session->IsValid()) ? ' New' : '';
   $DiscussionUrl = '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 && C('Vanilla.Comments.AutoOffset') && $Session->UserID > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '');
   $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
   $Sender->EventArguments['Discussion'] = &$Discussion;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $First = UserBuilder($Discussion, 'First');
   $Last = UserBuilder($Discussion, 'Last');
   if (is_null($Last->UserID)) {
      $Last = $First;
      $Discussion->LastDate = $Discussion->FirstDate;
   }
   
   $Sender->FireEvent('BeforeDiscussionName');
   $CountCommentsPerPage = GetValue('CountCommentsPerPage', $Sender);
   if (!$CountCommentsPerPage) {
      $CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
      $Sender->CountCommentsPerPage = $CountCommentsPerPage;
   }
   $CountPages = ceil($Discussion->CountComments / $CountCommentsPerPage);
   $LastPageUrl = '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/p'.$CountPages.'/#Comment_'.$Discussion->LastCommentID;
   
   $DiscussionName = Gdn_Format::Text($Discussion->Name);
   if ($DiscussionName == '')
      $DiscussionName = T('Blank Discussion Topic');
      
   $Sender->EventArguments['DiscussionName'] = &$DiscussionName;

   static $FirstDiscussion = TRUE;
   if (!$FirstDiscussion)
      $Sender->FireEvent('BetweenDiscussion');
   else
      $FirstDiscussion = FALSE;
      
   $Discussion->CountComments--;
   if ($Discussion->CountComments < 0)
      $Discussion->CountComments = 0;
?>
<li class="<?php echo $CssClass; ?>">
   <?php
   $Sender->FireEvent('BeforeDiscussionContent');
   WriteOptions($Discussion, $Sender, $Session);
         
   if ($Sender->CanEditDiscussions) {
      if (!property_exists($Sender, 'CheckedDiscussions'))
         $Sender->CheckedDiscussions = $Session->GetAttribute('CheckedDiscussions', array());

      $ItemSelected = in_array($Discussion->DiscussionID, $Sender->CheckedDiscussions);
      echo '<div class="Administration"><input type="checkbox" name="DiscussionID[]" value="'.$Discussion->DiscussionID.'"'.($ItemSelected?' checked="checked"':'').' /></div>';
   }
   ?>
   <table>
      <tr>
         <td class="DiscussionName"><?php
            echo Anchor($DiscussionName, $DiscussionUrl, 'Title');
            if ($CountPages > 1) {
               echo '<div class="MiniPager">
                  <div class="MiniPagerArrow"></div>';
                  if ($CountPages < 5) {
                     for ($i = 0; $i < $CountPages; $i++) {
                        WritePageLink($Discussion, $i+1);
                     }
                  } else {
                     WritePageLink($Discussion, 1);
                     WritePageLink($Discussion, 2);
                     echo '<span class="Elipsis">...</span>';
                     WritePageLink($Discussion, $CountPages-1);
                     WritePageLink($Discussion, $CountPages);
                  }
               echo '</div>';
            }
         ?></td>
         <td class="User FirstUser">
            <div class="Wrap">
            <?php
               echo UserPhoto($First, 'PhotoLink');
               echo UserAnchor($First, 'UserLink');
               echo '<span class="CommentDate">'.Gdn_Format::Date($Discussion->FirstDate).'</span>';
            ?>
            </div>
         </td>
         <td class="User LastUser">
            <div class="Wrap">
            <?php
            if ($Last) {
               echo Anchor('<span class="PaSprite GreenArrow"></span>', $LastPageUrl, 'LastPage');
               echo UserPhoto($Last, 'PhotoLink');
               echo UserAnchor($Last, 'UserLink');
               echo '<span class="CommentDate">'.Gdn_Format::Date($Discussion->LastDate).'</span>';
            } else {
               echo '&nbsp;';
            }
            ?>
            </div>
         </td>
         <td class="Count CountComments"><div class="Wrap"><?php echo Gdn_Format::BigNumber($Discussion->CountComments); ?></div></td>
         <td class="Count CountViews"><?php echo Gdn_Format::BigNumber($Discussion->CountViews); ?></td>
      </tr>
   </table>
</li>
<?php
}

function WritePageLink($Discussion, $PageNumber) {
   echo Anchor($PageNumber, '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/p'.$PageNumber);
}

// WriteFilterTabs() is modified to include a heading row for the discussion list. Check bottom of function for changes.
function WriteFilterTabs(&$Sender) {
   $Session = Gdn::Session();
   $Title = property_exists($Sender, 'Category') && is_object($Sender->Category) ? $Sender->Category->Name : T('All Discussions');
   $Bookmarked = T('My Bookmarks');
   $MyDiscussions = T('My Discussions');
   $MyDrafts = T('My Drafts');
   $CountBookmarks = 0;
   $CountDiscussions = 0;
   $CountDrafts = 0;
   if ($Session->IsValid()) {
      $CountBookmarks = $Session->User->CountBookmarks;
      $CountDiscussions = $Session->User->CountDiscussions;
      $CountDrafts = $Session->User->CountDrafts;
   }
   if ($CountBookmarks === NULL) {
      $Bookmarked .= '<span class="Popin" rel="'.Url('/discussions/UserBookmarkCount').'">-</span>';
   } elseif (is_numeric($CountBookmarks) && $CountBookmarks > 0)
      $Bookmarked .= '<span>'.$CountBookmarks.'</span>';

   if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
      $MyDiscussions .= '<span>'.$CountDiscussions.'</span>';

   if (is_numeric($CountDrafts) && $CountDrafts > 0)
      $MyDrafts .= '<span>'.$CountDrafts.'</span>';
      
   ?>
<div class="Tabs DiscussionsTabs">
   <ul>
      <?php $Sender->FireEvent('BeforeDiscussionTabs'); ?>
      <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo Anchor(T('All Discussions'), 'discussions'); ?></li>
      <?php $Sender->FireEvent('AfterAllDiscussionsTab'); ?>
      <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
      <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo Anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks'); ?></li>
      <?php
         $Sender->FireEvent('AfterBookmarksTab');
      }
      if ($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') {
      ?>
      <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions'); ?></li>
      <?php
      }
      if ($CountDrafts > 0 || $Sender->ControllerName == 'draftscontroller') {
      ?>
      <li<?php echo $Sender->ControllerName == 'draftscontroller' ? ' class="Active"' : ''; ?>><?php echo Anchor($MyDrafts, '/drafts', 'MyDrafts'); ?></li>
      <?php
      }
      $Sender->FireEvent('AfterDiscussionTabs');
      ?>
   </ul>
   <?php
   $DescendantData = GetValue('DescendantData', $Sender->Data);
   $Category = GetValue('Category', $Sender->Data);
   if ($DescendantData && $Category) {
      echo '<div class="SubTab"><span class="BreadCrumb FirstCrumb"> &rarr; </span>';
      foreach ($DescendantData->Result() as $Descendant) {
         // Ignore the root node
         if ($Descendant->CategoryID > 0) {
            echo Anchor(Gdn_Format::Text($Descendant->Name), '/categories/'.$Descendant->UrlCode);
            echo '<span class="BreadCrumb"> &rarr; </span>';
         }
      }
      echo $Category->Name;
      echo '</div>';
   }
   if (!property_exists($Sender, 'CanEditDiscussions'))
      $Sender->CanEditDiscussions = $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', 'any');
   
   if ($Sender->CanEditDiscussions) {
   ?>
   <div class="Administration">
      <input type="checkbox" name="Toggle" />
   </div>
   <?php } ?>
</div>
<?php // BEGIN CUSTOM THEME CHANGES ?>
<table class="DiscussionHeading">
   <tr>
      <td class="DiscussionName">Thread</td>
      <td class="User FirstUser"><div class="Wrap">Original Post</div></td>
      <td class="User LastUser"><div class="Wrap">Most Recent Post</div></td>
      <td class="Count CountComments"><div class="Wrap">Replies</div></td>
      <td class="Count CountViews">Views</td>
   </tr>
</table>
   <?php // END CUSTOM THEME CHANGES
}

// DID NOT MODIFY WriteOptions() for PA theme.
function WriteOptions($Discussion, &$Sender, &$Session) {
   if ($Session->IsValid() && $Sender->ShowOptions) {
      echo '<div class="Options">';
      $Sender->Options = '';
      
      // Dismiss an announcement
      if (C('Vanilla.Discussions.Dismiss', 1) && $Discussion->Announce == '1' && $Discussion->Dismissed != '1')
         $Sender->Options .= '<li>'.Anchor(T('Dismiss'), 'vanilla/discussion/dismissannouncement/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'DismissAnnouncement') . '</li>';
      
      // Edit discussion
      if ($Discussion->FirstUserID == $Session->UserID || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Edit'), 'vanilla/post/editdiscussion/'.$Discussion->DiscussionID, 'EditDiscussion') . '</li>';

      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'DeleteDiscussion') . '</li>';
      
      // Allow plugins to add options
      $Sender->FireEvent('DiscussionOptions');
      
      if ($Sender->Options != '') {
      ?>
         <div class="ToggleFlyout OptionsMenu">
            <div class="MenuTitle"><?php echo T('Options'); ?></div>
            <ul class="Flyout MenuItems">
               <?php echo $Sender->Options; ?>
            </ul>
         </div>
      <?php
      }
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
      echo '</div>';
   }
}