<?php if (!defined('APPLICATION')) exit(); 

if (!function_exists('DateTile')):
/**
 * Get HTML to display date as a calendar tile block.
 * 
 * @param string $Date
 */
function DateTile($Date) {
   if (is_string($Date))
      $Date = new DateTime($Date);
   
   return '
   <span class="DateTile">
      <span class="Month">'.$Date->format('M').'</span>
      <span class="Day">'.$Date->format('j').'</span>
   </span>';
}
endif;


if (!function_exists('WriteDiscussionBlogList')):
/**
 * Output discussions in expanded format with excerpts.
 * 
 * @param array $Discussions 
 * @param string $EmptyMessage What to show when there's no content.
 */
function WriteDiscussionBlogList($Discussions, $EmptyMessage = '') {
   if (!$Discussions)
      WriteEmptyState($EmptyMessage);
   
   if (is_array($Discussions)) {
      include_once(PATH_APPLICATIONS .'/vanilla/views/discussions/helper_functions.php');
      //include_once(PATH_APPLICATIONS .'/vanilla/views/modules/helper_functions.php');   
      echo '<ul class="DataList Discussions DiscussionsBlog">';
      foreach ($Discussions as $Discussion) {
         WriteDiscussionBlog((object)$Discussion, 'Group');
      }
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteDiscussionBlog')):
/**
 * Output discussions in expanded format with excerpts.
 * 
 * @param object $Discussion
 * @param string $Px 
 */
function WriteDiscussionBlog($Discussion, $Px = 'Bookmark') {
?>
<li id="<?php echo "{$Px}_{$Discussion->DiscussionID}"; ?>" class="<?php echo CssClass($Discussion); ?>">
   <span class="Options">
      <?php
      echo BookmarkButton($Discussion);
      ?>
   </span>
   <div class="Title"><?php
      echo Anchor(Gdn_Format::Text($Discussion->Name, FALSE), DiscussionUrl($Discussion).($Discussion->CountCommentWatch > 0 ? '#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
   ?></div>
   <div class="Excerpt"><?php
      echo SliceString(Gdn_Format::Text($Discussion->Body, FALSE), C('Groups.Announcements.ExcerptLength', 240));
   ?></div>
   <div class="Meta">
      <?php   
         $First = new stdClass();
         $First->UserID = $Discussion->InsertUserID;
         $First->Name = $Discussion->FirstName;
         echo '<span class="MItem UserTile">'.
            UserPhoto($First).' '.
            UserAnchor($First).' '.
            Gdn_Format::Date($Discussion->DateInserted, 'html').'</span>';   
      ?>
   </div>
</li>
<?php
}
endif;

if (!function_exists('WriteDiscussionList')):
/**
 * Output discussions in a compact list.
 * 
 * @param array $Discussions 
 * @param string $EmptyMessage What to show when there's no content.
 */
function WriteDiscussionList($Discussions, $EmptyMessage = '') {
   if (!$Discussions)
      WriteEmptyState($EmptyMessage);
   
   //if (C('Vanilla.Discussions.Layout') == 'table')
   //WriteDiscussionRow($Discussion, $this, $Session, $Alt);
   if (is_array($Discussions)) {
      include_once(PATH_APPLICATIONS .'/vanilla/views/discussions/helper_functions.php');
      include_once(PATH_APPLICATIONS .'/vanilla/views/modules/helper_functions.php');   
      echo '<ul class="DataList Discussions">';
      foreach ($Discussions as $Discussion) {
         WriteModuleDiscussion((object)$Discussion, 'Group');
      }
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteEmptyState')):
/**
 * Output a generically formatted "empty state" message box.
 * 
 * @param string $Message HTML.
 */
function WriteEmptyState($Message) {
   if ($Message)
      echo Wrap($Message, 'p', array('class' => 'EmptyMessage'));
}
endif;

if (!function_exists('WriteEventList')) :
/**
 * Output an HTML list of events or an empty state message.
 * 
 * @param array $Events 
 * @param string $EmptyMessage What to show when there's no content.
 */   
function WriteEventList($Events, $Group = NULL, $EmptyMessage = '') {
   if (!$Events)
      WriteEmptyState($EmptyMessage);
   
   if (is_array($Events)) {
      $GroupID = GetValue('GroupID', $Group, '');
      if (GroupPermission('Member')) {
         echo ' '.Anchor(T('New Event'), Url("/event/add/{$GroupID}"), 'Button Primary Group-NewEventButton').' ';
      }
      echo '<ul class="DataList DataList-Events">';
      foreach ($Events as $Event) {
         $DateStarts = new DateTime($Event['DateStarts']);
         echo 
         '<li class="Event">
            '.DateTile($DateStarts->format('Y-m-d')).'
            <h3 class="Event-Title">'.Gdn_Format::Text($Event['Name']).' <span class="Event-Time MItem">'.$DateStarts->format('g:ia').'</span></h3>
            
            <div class="Event-Location">'.Gdn_Format::Text($Event['Location']).'</div>
            <p class="Event-Description"'.SliceParagraph(Gdn_Format::Text($Event['Body']), 100).'</p>
         </li>';
      }
      echo '</ul>';
   }   
}
endif;


if (!function_exists('WriteGroupBanner')) :
/**
 * Output optional group banner as a div background image to allow dynamic page resizing.
 */  
function WriteGroupBanner() {
   $Group = Gdn::Controller()->Data('Group');
   
   if ($Group['Banner']) {
      echo Wrap('', 'div', array(
         'class' => 'Group-Banner',
         'style' => 'background-image: url("'.Gdn_Upload::Url($Group['Banner']).'");')
      );
   }
}
endif;


if (!function_exists('WriteGroupButtons')) :
/**
 * Output action buttons to join/apply to group.
 */
function WriteGroupButtons($Group = FALSE) {
   if (!$Group)
      $Group = Gdn::Controller()->Data('Group');
   
   echo '<div class="Group-Buttons">';
   
   if (GroupPermission('Join'))
      echo ' '.Anchor(T('Join This Group'), GroupUrl($Group, 'join'), 'Button BigButton Primary Group-JoinButton Popup').' ';
      
      
   $Options = array();
   
   if (GroupPermission('Edit')) {
      $Options['Edit'] = array('Text' => T('Edit'), 'Url' => GroupUrl($Group, 'edit'));
   }
//   if (GroupPermission('Delete')) {
//      $Options['Delete'] = array('Text' => T('Delete'), 'Url' => GroupUrl($Group, 'delete'));
//   }
   if (GroupPermission('Leave')) {
      $Options['Leave'] = array('Text' => T('Leave Group'), 'Url' => GroupUrl($Group, 'leave'), 'CssClass' => 'Popup');
   }

   if (count($Options))
      echo ButtonDropDown($Options, 'Button BigButton DropRight Group-OptionsButton', Sprite('SpOptions', 'Sprite16'));
   
   echo '</div>';
}
endif;


if (!function_exists('WriteGroupCards')) :
/**
 * Write a list of groups out as cards.
 * 
 * @param array $Groups
 */
function WriteGroupCards($Groups, $EmptyMessage = '') {
   if (!$Groups)
      WriteEmptyState($EmptyMessage);
   
   if (is_array($Groups)) {
      echo '<div class="Cards Cards-Groups">';
      foreach ($Groups as $Group) {
         echo '<div class="Group Card">';
            echo '<h3 class="Group-Name">'.Anchor(Gdn_Format::Text($Group['Name']), GroupUrl($Group)).'</h3>';
            WriteGroupIcon($Group);
            echo '<p class="Group-Description">'.Gdn_Format::Text($Group['Description']).'</p>';
            WriteGroupButtons($Group);
         echo '</div>';
      }
      echo '</div>';
   }
}
endif;


if (!function_exists('WriteGroupIcon')) :
/**
 * Output group icon image.
 */
function WriteGroupIcon($Group = FALSE) {
   if (!$Group)
      $Group = Gdn::Controller()->Data('Group');
   
   if ($Group['Icon'])
      echo Img(Gdn_Upload::Url($Group['Icon']), array('class' => 'Group-Icon'));
}
endif;


if (!function_exists('WriteGroupList')) :
/**
 * Write a list of groups out as a list.
 * 
 * @param array $Groups
 */
function WriteGroupList($Groups) {
   
   
   if (is_array($Groups)) {
      echo '<ul class="DataList DataList-Groups">';
      foreach ($Groups as $Group) {
         echo '<li class="Item Item-Group">';
         echo Anchor(Gdn_Format::Text($Group['Name']), GroupUrl($Group));
         echo '</li>';
      }
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteMemberCards')) :
/**
 * Write a list of members out as cards.
 * 
 * @param array $Members
 */
function WriteMemberCards($Members) {
   if (!$Members)
      echo WriteEmptyState(T("GroupMembersEmpty", "No one has joined yet. Spread the word!"));
   
   if (is_array($Members)) {
      foreach ($Members as $Member) {
         echo Gdn_Format::Text($Member['Name']).', ';
      } 
   }
}
endif;


if (!function_exists('WriteMemberGrid')) :
/**
 * Output just the linked user photos in rows.
 * No empty state because there is always at least 1 member.
 * 
 * @param array $Members
 */
function WriteMemberGrid($Members) {
   if (is_array($Members)) {
      echo '<div class="PhotoGrid PhotoGridSmall">';
      foreach ($Members as $Member) {
          echo UserPhoto($Member);
      }
      echo '</div>';
   }
}
endif;


if (!function_exists('WriteMemberSimpleList')) :
/**
 * Output just the photo and linked username in a column.
 * No empty state because there is always at least 1 member.
 * 
 * @param array $Members
 */
function WriteMemberSimpleList($Members) {   
   if (is_array($Members)) {
      echo '<ul class="Group-MemberList">';
      foreach ($Members as $Member) {
         echo '<li class="Group-Member UserTile">'.UserPhoto($Member).' '.UserAnchor($Member)."</li>\n";
      }
      echo '</ul>';
   }
}
endif;