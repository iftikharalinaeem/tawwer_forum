<?php if (!defined('APPLICATION')) exit(); 

if (!function_exists('DateTile')):
/**
 * Get HTML to display date as a calendar tile block.
 * 
 * @param string $Date
 */
function DateTile($Date) {
   if (is_string($Date)) {
      $Date = new DateTime($Date);
   }
   
   return '
   <span class="DateTile">
      <span class="Month">'.strftime('%b', $Date->getTimestamp()).'</span>
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
      echo '<ul class="NarrowList Discussions DiscussionsBlog">';
      foreach ($Discussions as $Discussion) {
         WriteDiscussionBlog((object)$Discussion, 'Discussion');
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
function WriteDiscussionBlog($Discussion, $Px = 'Discussion') {
?>
<li id="<?php echo "{$Px}_{$Discussion->DiscussionID}"; ?>" class="<?php echo CssClass($Discussion); ?>">
   <span class="Options">
      <?php
      echo BookmarkButton($Discussion);
      echo OptionsList($Discussion);
      ?>
   </span>
   <h3 class="Title"><?php
      echo Anchor(Gdn_Format::Text($Discussion->Name, FALSE), DiscussionUrl($Discussion).($Discussion->CountCommentWatch > 0 ? '#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
   ?></h3>
   <div class="Excerpt"><?php
      echo SliceString(Gdn_Format::Text($Discussion->Body, FALSE), C('Groups.Announcements.ExcerptLength', 240));
   ?></div>
   <div class="Meta">
      <?php   
         $FirstUser = UserBuilder($Discussion, 'First');
         echo '<span class="MItem UserTile">'.
            T('by').' '.
            UserAnchor($FirstUser).' '.
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
   if (is_array($Discussions) && count($Discussions) > 0) {
      include_once(PATH_APPLICATIONS .'/vanilla/views/discussions/helper_functions.php');
      include_once(PATH_APPLICATIONS .'/vanilla/views/modules/helper_functions.php');   
      echo '<ul class="NarrowList Discussions">';
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
function WriteEventList($Events, $Group = NULL, $EmptyMessage = '', $Button = TRUE) {
   $GroupID = GetValue('GroupID', $Group, '');
   if (GroupPermission('Member') && $Button) {
      echo '<div class="Button-Controls">';
      echo ' '.Anchor(T('New Event'), "/event/add/{$GroupID}", 'Button Primary Group-NewEventButton').' ';
      echo '</div>';
   }
   
   if (!$Events)
      WriteEmptyState($EmptyMessage);
   else {
      echo '<ul class="NarrowList DataList-Events">';
      foreach ($Events as $Event) {
         echo '<li>';
         WriteEventCard($Event);
         echo '</li>';
      }
      echo '</ul>';
   }
}
endif;

if (!function_exists('WriteEventCard')) :
/**
 * Write an event card
 * 
 * Optionally write rich listing
 * 
 * @param array $Event
 */
function WriteEventCard($Event) {
   $UTC = new DateTimeZone('UTC');
   $Timezone = new DateTimeZone($Event['Timezone']);
   $DateStarts = new DateTime($Event['DateStarts'], $UTC);
   if (Gdn::Session()->IsValid() && $HourOffset = Gdn::Session()->User->HourOffset)
      $DateStarts->modify("{$HourOffset} hours");
   
   echo '<div class="Event">';
   if (GetValue('Rich', $Event)) {
      
      
      
   } else {
      
      echo DateTile($DateStarts->format('Y-m-d'));
      echo '<h3 class="Event-Title">'.Anchor(Gdn_Format::Text($Event['Name']), EventUrl($Event));
      if ($DateStarts->format('g:ia') != '12:00am')
         echo ' <span class="Event-Time MItem">'.$DateStarts->format('g:ia').'</span>';
      echo '</h3>';

      echo '<div class="Event-Location">'.Gdn_Format::Text($Event['Location']).'</div>';
      echo '<p class="Event-Description">'.SliceParagraph(Gdn_Format::Text($Event['Body']), 100).'</p>';
      
   }
   echo '</div>';
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

if (!function_exists('WriteGroupApplicants')):
function WriteGroupApplicants($Applicants) {
   if (!$Applicants)
      return;
   
   $Group = Gdn::Controller()->Data('Group');
   
   if (!GroupPermission('Leader', $Group))
      return;
   
   echo '<div class="Group-Box Group-Applicants">'.
      '<h2>'.T('Applicants & Invitations').'</h2>'.
      '<ul class="NarrowList Applicants">';
   
   
   foreach ($Applicants as $Row) {
      echo '<li id="GroupApplicant_'.$Row['GroupApplicantID'].'" class="Item">';
         echo UserAnchor($Row);
         echo ' <span class="Aside">';

         switch (strtolower($Row['Type'])) {
            case 'application':
               echo Anchor(T('Approve Applicant', 'Approve'), GroupUrl($Group, 'approve')."?id={$Row['GroupApplicantID']}", 'Button SmallButton Hijack Button-Approve').
               ' '.
               Anchor(T('Deny Applicant', 'Deny'), GroupUrl($Group, 'approve')."?id={$Row['GroupApplicantID']}&value=denied", 'Button SmallButton Hijack Button-Deny');
               break;
            case 'invitation':
               echo Anchor(T('Remove Invitation', 'Remove'), GroupUrl($Group, 'approve')."?id={$Row['GroupApplicantID']}&value=denied", 'Button SmallButton Hijack Button-Deny');
               break;
         }
         
         echo '</span> ';
         
         echo '<p class="Applicant-Reason">'.
            htmlspecialchars($Row['Reason']).
            '</p>';
      echo '</li>';
   }
   
   echo '</ul>'.
      '</div>';
}
   
endif;


if (!function_exists('WriteGroupButtons')) :
/**
 * Output action buttons to join/apply to group.
 * 
 * @param array $Group Optional. Uses data array's Group if none is provided. 
 */
function WriteGroupButtons($Group = NULL) {
   if (!$Group)
      $Group = Gdn::Controller()->Data('Group');
   if (!$Group) 
      return;
   echo '<div class="Group-Buttons">';
   
   if (Gdn::Session()->IsValid() && !GroupPermission('Member', $Group)) {
      if (GroupPermission('Join', $Group)) {
         echo ' '.Anchor(T('Join this Group'), GroupUrl($Group, 'join'), 'Button Primary Group-JoinButton Popup').' ';
      } else {
         echo ' '.Wrap(T('Join this Group'), 'span', array('class' => 'Button Primary Group-JoinButton Disabled', 'title' => GroupPermission('Join.Reason', $Group))).' ';
      }
   }

   if (GroupPermission('Leader', $Group)) {
      echo ' '.Anchor(T('Invite'), GroupUrl($Group, 'invite'), 'Button Primary Group-InviteButton Popup').' ';
   }
      
   $Options = array();
   
   if (GroupPermission('Edit', $Group)) {
      $Options['Edit'] = array('Text' => T('Edit'), 'Url' => GroupUrl($Group, 'edit'));
   }
//   if (GroupPermission('Delete')) {
//      $Options['Delete'] = array('Text' => T('Delete'), 'Url' => GroupUrl($Group, 'delete'));
//   }
   if (GroupPermission('Leave', $Group)) {
      $Options['Leave'] = array('Text' => T('Leave Group'), 'Url' => GroupUrl($Group, 'leave'), 'CssClass' => 'Popup');
   }
   
   if (GroupPermission('Delete', $Group)) {
      $Options['Delete'] = array('Text' => sprintf(T('Delete %s'), T('Group')), 'Url' => GroupUrl($Group, 'delete'), 'CssClass' => 'Popup');
   }

   if (count($Options))
      echo ButtonDropDown($Options, 'Button DropRight Group-OptionsButton', Sprite('SpOptions', 'Sprite16'));
   
   echo '</div>';
}
endif;


if (!function_exists('WriteGroupCards')) :
/**
 * Write a list of groups out as cards.
 * 
 * @param array $Groups
 * @param string $EmptyMessage
 */
function WriteGroupCards($Groups, $EmptyMessage = '') {
   if (!$Groups)
      WriteEmptyState($EmptyMessage);
   else {
      echo '<div class="Cards Cards-Groups">';
      foreach ($Groups as $Group) {
         WriteGroupCard($Group);
      }
      echo '</div>';
   }
}
endif;


if (!function_exists('WriteGroupCard')) :
/**
 * Write a group card
 * 
 * @param array $Group
 * @param bool $WithButtons Optional. Whether to show group management option cog
 */
function WriteGroupCard($Group, $WithButtons = TRUE) {
   echo '<div class="CardWrap"><div class="Group Card">';
      $Url = GroupUrl($Group);
      echo "<a href=\"$Url\" class=\"TextColor\">";
         WriteGroupIcon($Group, 'Group-Icon Card-Icon');
         echo '<h3 class="Group-Name">'.htmlspecialchars($Group['Name']).'</h3>';
      echo '</a>';   
      
      echo '<p class="Group-Description">'.
          htmlspecialchars(SliceString(
            Gdn_Format::PlainText($Group['Description'], $Group['Format']),
            C('Groups.CardDescription.ExcerptLength', 150))).'</p>';
      echo '<div class="Group-Members">'
.              Plural($Group['CountMembers'], '%s member','%s members', number_format($Group['CountMembers']))
          .'</div>';
      
      if ($WithButtons)
         WriteGroupButtons($Group);
   echo '</div></div>';
}
endif;

if (!function_exists('WriteGroupIcon')) :
/**
 * Output group icon image.
 * 
 * @param array $Group Optional. Uses data array's Group if none is provided.
 */
function WriteGroupIcon($Group = FALSE, $Class = 'Group-Icon') {
   if (!$Group)
      $Group = Gdn::Controller()->Data('Group');
   
   $Icon = '';
   if ($Group['Icon'])
      $Icon = $Group['Icon'];
   else
      $Icon = C('Groups.DefaultIcon', '');
      
   if ($Icon)
      echo Img(Gdn_Upload::Url($Icon), array('class' => $Class));
   
}
endif;

if (!function_exists('WriteGroupInfo')) :
function WriteGroupInfo() {
   $c = Gdn::Controller();
   
   $Owner = Gdn::UserModel()->GetID($c->Data('Group.InsertUserID'));
   
   $Info = array(
      'Created' => Gdn_Format::Date($c->Data('Group.DateInserted'), 'html'),
      'Owner' => UserAnchor($Owner),
      'Member Count' => array('Members', $c->Data('Group.CountMembers'))
      );
   
   
   echo '<dl class="Group-Info">';
   foreach ($Info as $Code => $Row) {
      if (is_array($Row)) {
         $Label = T($Code, $Row[0]);
         $Value = $Row[1];
      } else {
         $Label = T($Code);
         $Value = $Row;
      }
      
      echo '<dt>'.$Label.'</dt>';
      echo '<dd>'.$Value.'</dd>';
   }
   echo '</dl>';
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
      echo '<ul class="NarrowList DataList-Groups">';
      foreach ($Groups as $Group) {
         echo '<li class="Item Item-Group">';
         echo Anchor(Gdn_Format::Text($Group['Name']), GroupUrl($Group));
         echo '</li>';
      }
      echo '</ul>';
   }
}
endif;

if (!function_exists('WriteGroupUserHeader')):
function WriteGroupUserHeader($Group) {

}
endif;

if (!function_exists('WriteGroupsTable')) :

function WriteGroupsTable($Groups, $Title) {
   ?>
   <div class="DataTableWrap">
      <table class="DataTable GroupsTable">
         <thead>
            <td class="GroupName"><div class="Wrap"><?php echo $Title; ?></div></td>
            <td class="BigCount CountMembers"><div class="Wrap"><?php echo T('Members'); ?></div></td>
            <td class="BigCount CountDiscussions>"><div class="Wrap"><?php echo T('Discussions'); ?></div></td>
         </thead>
         <tbody>
         <?php foreach ($Groups as $Group): ?>
         <tr>
            <td><div class="Wrap">
               <?php
               echo '<h3 class="Group-Name">'.
                  Anchor(htmlspecialchars($Group['Name']), $Group['Url']).
                  '</h3>';
               ?>
            </div></td>
            <td class="BigCount CountMembers"><div class="Wrap">
               <?php
               echo BigPlural($Group['CountMembers'], '%s member');
               ?>
            </div></td>
            <td class="BigCount CountDiscussions"><div class="Wrap">
               <?php
               echo BigPlural($Group['CountDiscussions'], '%s discussion');
               ?>
            </div></td>
         </tr>
         <?php endforeach; ?>
         </tbody>
      </table>
   </div>
   <?php
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
      echo '<ul class="Cards Cards-Members">';
      foreach ($Members as $Member) {
         echo "<li id=\"Member_{$Member['UserID']}\" class=\"CardWrap\">";
         WriteMemberCard($Member);
         echo '</li>';
      }
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteMemberCard')) :
/**
 * Write a list of members out as cards.
 * 
 * @param array $Members
 */
function WriteMemberCard($Member) {
   $UserID = $Member['UserID'];
   $Group = Gdn::Controller()->Data('Group');
   
   echo '<div class="Card Card-Member">';
   
   echo UserPhoto($Member, array('LinkClass' => 'Card-Icon')).' '.UserAnchor($Member).' ';
   echo Wrap(sprintf(T('Joined %s', 'Joined %s'), Gdn_Format::Date($Member['DateInserted'], 'html')),
      'span', array('class' => 'MItem DateJoined')).' ';
   
   $Options = array();
   
   if (GroupPermission('Moderate') && $Group['InsertUserID'] != $Member['UserID']) {
      if (GroupPermission('Edit')) {
         if ($Member['Role'] == 'Leader')
            $Options['SetRole'] = array('Text' => sprintf(T('Make %s'), T('Member')), 'Url' => GroupUrl($Group, 'setrole')."?userid=$UserID&role=member", 'CssClass' => 'Group-MakeLeader Hijack');
         else
            $Options['SetRole'] = array('Text' => T('Make Leader', 'Leader'), 'Url' => GroupUrl($Group, 'setrole')."?userid=$UserID&role=leader", 'CssClass' => 'Group-MakeLeader Hijack');
      }
      $Options['Remove'] = array('Text' => T('Remove'), 'Url' => GroupUrl($Group, 'removemember')."?userid=$UserID", 'CssClass' => 'Group-RemoveMember Popup');
      
   }
   
   if (count($Options)) {
      echo '<div class="Card-Buttons Group-MemberOptions">';
      echo ButtonDropDown($Options, 'Button DropRight Member-OptionsButton', Sprite('SpOptions', 'Sprite16'));
      echo '</div>';
   }
   
   echo '</div>';
}
endif;


if (!function_exists('WriteMemberGrid')) :
/**
 * Output just the linked user photos in rows.
 * No empty state because there is always at least 1 member.
 * 
 * @param array $Members
 */
function WriteMemberGrid($Members, $More = '') {
   if (is_array($Members)) {
      echo '<div class="PhotoGrid PhotoGridSmall">';
      foreach ($Members as $Member) {
          echo UserPhoto($Member);
      }
      if ($More) {
         
         echo $More;
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