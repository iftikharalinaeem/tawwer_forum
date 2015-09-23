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

if (!function_exists('getGroupOptions')):
/**
 * Compiles the options for a group given a user's permissions.
 *
 * @param array $group The group to get options for.
 * @return array The options for the group that the user can access.
 */
function getGroupOptions($group, $sectionId = 'home') {
    $options = array();
    if (GroupPermission('Edit', $group)) {
        $options['Edit'] = array('Text' => t('Edit Group'), 'Url' => GroupUrl($group, 'edit'));
    }
    if (GroupPermission('Leave', $group)) {
        $options['Leave'] = array('Text' => t('Leave Group'), 'Url' => GroupUrl($group, 'leave'), 'CssClass' => 'Popup');
    }
    if (GroupPermission('Delete', $group)) {
        $options['Delete'] = array('Text' => sprintf(t('Delete %s'), t('Group')), 'Url' => GroupUrl($group, 'delete'), 'CssClass' => 'Popup');
    }
    if (GroupPermission('Leader', $group)) {
        $options['Invite'] = array('Text' => t('Invite Members'), 'Url' => GroupUrl($group, 'invite'), 'CssClass' => 'Popup');
    }
    return $options;
}
endif;

if (!function_exists('getGroupButtons')):
/**
 * Compiles the buttons for a group given the user's membership and permissions.
 *
 * @param array $group The group to get buttons for.
 * @return array The buttons for the group.
 */
function getGroupButtons($group) {
    $buttons = array();
    if (Gdn::session()->isValid() && !GroupPermission('Member', $group) && GroupPermission('Join', $group)) {
        $joinButton['text'] = t('Join');
        $joinButton['url'] = GroupUrl($group, 'join');
        $joinButton['cssClass'] = 'Popup';
        $buttons[] = $joinButton;
    }
    return $buttons;
}
endif;

if (!function_exists('writeDiscussionList')):
/**
 * Renders a list of discussions in the same format as in the vanilla application.
 *
 * @param Controller $sender The sending object.
 * @param string $emptyMessage The message to render if no discussions exist.
 * @param string $title The title of the discussion list.
 * @param string $layout The layout type, either 'modern' or 'table'.
 */
function writeDiscussionList($sender, $emptyMessage = '', $title = 'Discussions', $layout = '') {
    if (!$layout) {
        $layout = c('Vanilla.Discussions.Layout', 'modern');
    }
    ?>
    <div class="Group-Box Group-<?php echo $title; ?> Section-DiscussionList">
        <div class="PageControls">
            <h2 class="H"><?php echo $title; ?></h2>
            <?php
            if ($title == 'Announcements' && GroupPermission('Moderate')) {
                echo '<div class="Button-Controls">';
                echo anchor(sprintf(t('New Announcement')), GroupUrl($sender->data('Group'), 'announcement'), 'Button Primary');
                echo '</div>';
            } else if ($title == 'Discussions' && GroupPermission('Member')) {
                echo '<div class="Button-Controls">';
                echo Gdn_Theme::module('NewDiscussionModule', array('CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$sender->data('Group.GroupID')));
                echo '</div>';
            }
            echo '</div>';
            $sender->EventArguments['layout'] = &$layout;
            $sender->EventArguments['title'] = &$title;
            $sender->fireEvent('beforeDiscussionList');
            if (!$sender->data('Discussions')->result()) {
                echo '<div class="EmptyMessage">'.$emptyMessage.'</div>';
            } else {
                if ($layout == 'table') {
                    include_once($sender->fetchViewLocation('table_functions', 'discussions', 'vanilla'));
                    include_once($sender->fetchViewLocation('helper_functions', 'discussions', 'vanilla'));
                    writeDiscussionTable();
                } else if ($layout == 'modern') {
                    include_once($sender->fetchViewLocation('helper_functions', 'discussions', 'vanilla')); ?>
                    <ul class="DataList <?php echo $title; ?>">
                        <?php foreach($sender->Data('Discussions') as $discussion) {
                            writeDiscussion($discussion, $sender, Gdn::session());
                        }?>
                    </ul> <?php
                }
            }
            if ($title == 'Discussions' && $sender->data('Discussions')->result()) {
                echo '<div class="MoreWrap">'.
                        anchor(t('All Discussions'), GroupUrl($sender->Data('Group'), 'discussions')).
                    '</div>';
            }
            ?>
     </div>
<?php
}
endif;

if (!function_exists('writeAnnouncementList')):
/**
 * Renders a list of announcements.
 *
 * @param Controller $sender The sending object.
 * @param $emptyMessage The message to render if not announcements exist.
 */
function writeAnnouncementList($sender, $emptyMessage) {
  $bak = $sender->data('Discussions');
  $sender->setData('Discussions', $sender->data('Announcements'));
  $sender->setData('Announcements', false);
  writeDiscussionList($sender, $emptyMessage, t('Announcements'));
  $sender->setData('Discussions', $bak);
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

if (!function_exists('WriteGroupBanner')) :
/**
 * Output optional group banner as a div background image to allow dynamic page resizing.
 */
function WriteGroupBanner($group = array()) {
   if (!$group) {
     $group = Gdn::controller()->data('Group');
   }

   if (val('Banner', $group)) {
      echo wrap('', 'div', array(
         'class' => 'Group-Banner',
         'style' => 'background-image: url("'.Gdn_Upload::url(val('Banner', $group)).'");')
      );
   }
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

if (!function_exists('writeGroupOptions')):
/**
 * Renders the options menu for a group in a cog (typically for group in a group list).
 *
 * @param array $options The options to render.
 */
function writeGroupOptions($options = array()) {
    if (empty($options)) {
        return;
    }
    echo ' <span class="ToggleFlyout OptionsMenu">';
    echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
    echo sprite('SpFlyoutHandle', 'Arrow');
    echo '<ul class="Flyout MenuItems" style="display: none;">';
    foreach ($options as $code => $option) {
        echo wrap(anchor($option['Text'], $option['Url'], val('CssClass', $option, $code)), 'li');
    }
    echo '</ul>';
    echo '</span>';
}
endif;

if (!function_exists('writeGroupOptionsButton')):
/**
 * Renders the options menu for a group in a button dropdown format (typically for the group banner).
 *
 * @param array $options The options to render.
 */
function writeGroupOptionsButton($options = array()) {
    if (empty($options)) {
        return;
    }
    echo ' <div class="GroupOptions OptionsMenu ButtonGroup">';
    echo '<ul class="Dropdown MenuItems">';
    foreach ($options as $code => $option) {
        echo wrap(anchor($option['Text'], $option['Url'], val('CssClass', $option, $code)), 'li');
    }
    echo '</ul>';
    echo '<a class="NavButton Handle Button GroupOptionsTitle" title="'.t('Options').'">'.t('Options').' '.
        sprite('Sprite', 'SpDropdownHandle').'</a>';
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
function WriteGroupIcon($group = FALSE, $class = 'Group-Icon') {
   if (!$group) {
       $group = Gdn::Controller()->Data('Group');
   }
   if (val('Icon', $group)) {
       $icon = val('Icon', $group);
   } else {
       $icon = c('Groups.DefaultIcon', '');
   }
   if ($icon) {
       echo img(Gdn_Upload::Url($icon), array('class' => $class));
   }
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
