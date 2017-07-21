<?php if (!defined('APPLICATION')) exit();

if (!function_exists('DateTile')):
    /**
     * Get HTML to display date as a calendar tile block.
     *
     * @param string $date
     */
    function DateTile($date) {
        if (is_string($date)) {
            $date = new DateTime($date);
        }

        return '
        <span class="DateTile">
            <span class="Month">'.strftime('%b', $date->getTimestamp()).'</span>
            <span class="Day">'.$date->format('j').'</span>
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
     $options = [];
     if (GroupPermission('Edit', $group)) {
          $options['Edit'] = ['Text' => sprintf(t('Edit %s'), t('Group')), 'Url' => GroupUrl($group, 'edit')];
     }
     if (GroupPermission('Leave', $group)) {
          $options['Leave'] = ['Text' => t('Leave Group'), 'Url' => GroupUrl($group, 'leave'), 'CssClass' => 'Popup'];
     }
     if (GroupPermission('Leader', $group)) {
          $options['Delete'] = ['Text' => sprintf(t('Delete %s'), t('Group')), 'Url' => GroupUrl($group, 'delete'), 'CssClass' => 'Popup'];
     }
     if (GroupPermission('Leader', $group)) {
          $options['Invite'] = ['Text' => t('Invite Members'), 'Url' => GroupUrl($group, 'invite'), 'CssClass' => 'js-invite-members'];
     }
     if (GroupPermission('Leader', $group)) {
          $options['Members'] = ['Text' => t('Manage Members'), 'Url' => GroupUrl($group, 'members')];
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
     $groupModel = new GroupModel();
     $buttons = [];
     if (Gdn::session()->isValid() && !GroupPermission('Member', $group) && GroupPermission('Join', $group) && !$groupModel->getApplicantType(val('GroupID', $group))) {
          $joinButton['text'] = t('Join');
          $joinButton['url'] = GroupUrl($group, 'join');
          $joinButton['cssClass'] = 'Popup';
          $buttons[] = $joinButton;
     }
     if (Gdn::session()->isValid() && ($groupModel->getApplicantType(val('GroupID', $group))) === 'Invitation') {
          $acceptButton['text'] = t('Join');
          $acceptButton['url'] = GroupUrl($group, 'inviteaccept');
          $acceptButton['cssClass'] = 'Hijack';
          $declineButton['text'] = t('Decline');
          $declineButton['url'] = GroupUrl($group, 'invitedecline');
          $declineButton['cssClass'] = 'Hijack';
          $buttons[] = $acceptButton;
          $buttons[] = $declineButton;
     }
     return $buttons;
}
endif;

if (!function_exists('writeDiscussionList')):
/**
 * Renders a list of discussions in the same format as in the vanilla application.
 *
 * @param Controller $sender The sending object.
 * @param string $type The type of listing, 'announcements' or 'discussions'.
 * @param string $emptyMessage The message to render if no discussions exist.
 * @param string $title The title of the discussion list.
 * @param string $layout The layout type, either 'modern' or 'table'.
 */
function writeDiscussionList($sender, $type = 'discussions', $emptyMessage = '', $title = '', $layout = '') {
     if (!$layout) {
          $layout = c('Vanilla.Discussions.Layout', 'modern');
     }
     // Force discussions if bad type is sent
     if (!in_array($type, ['announcements', 'discussions'])) {
          trace('Wrong type argument sent.');
          $type = 'discussions';
     }
     ?>
     <div class="Group-Box Group-<?php echo $title; ?> Section-DiscussionList">
          <div class="PageControls">
                <h2 class="H"><?php echo $title; ?></h2>
                <?php
                if ($type == 'announcements' && GroupPermission('Moderate')) {
                     echo '<div class="Button-Controls">';
                     echo anchor(sprintf(t('New Announcement')), GroupUrl($sender->data('Group'), 'announcement'), 'Button Primary');
                     echo '</div>';
                } else if ($type == 'discussions' && (GroupPermission('Member') || GroupPermission('Moderate'))) {
                     echo '<div class="Button-Controls">';
                     echo Gdn_Theme::module('NewDiscussionModule', ['CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$sender->data('Group.GroupID')]);
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
                if ($type == 'discussions' && $sender->data('Discussions')->result()) {
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
  writeDiscussionList($sender, 'announcements', $emptyMessage, t('Announcements'));
  $sender->setData('Discussions', $bak);
}
endif;

if (!function_exists('WriteEmptyState')):
/**
 * Output a generically formatted "empty state" message box.
 *
 * @param string $message HTML.
 */
function WriteEmptyState($message) {
    if ($message)
        echo Wrap($message, 'p', ['class' => 'EmptyMessage']);
}
endif;

if (!function_exists('WriteGroupBanner')) :
/**
 * Output optional group banner as a div background image to allow dynamic page resizing.
 */
function WriteGroupBanner($group = []) {
    if (!$group) {
      $group = Gdn::controller()->data('Group');
    }

    if (val('Banner', $group)) {
        echo wrap('', 'div', [
            'class' => 'Group-Banner',
            'style' => 'background-image: url("'.Gdn_Upload::url(val('Banner', $group)).'");']
        );
    }
}
endif;

if (!function_exists('WriteGroupButtons')) :
/**
 * Output action buttons to join/apply to group.
 *
 * @param array $group Optional. Uses data array's Group if none is provided.
 */
function WriteGroupButtons($group = null) {
    if (!$group)
        $group = Gdn::Controller()->Data('Group');
    if (!$group)
        return;
    echo '<div class="Group-Buttons">';

    if (Gdn::Session()->IsValid() && !GroupPermission('Member', $group)) {
        if (GroupPermission('Join', $group)) {
            echo ' '.Anchor(T('Join this Group'), GroupUrl($group, 'join'), 'Button Primary Group-JoinButton Popup').' ';
        } else {
            echo ' '.Wrap(T('Join this Group'), 'span', ['class' => 'Button Primary Group-JoinButton Disabled', 'title' => GroupPermission('Join.Reason', $group)]).' ';
        }
    }

    if (GroupPermission('Leader', $group)) {
        echo ' '.Anchor(T('Invite'), GroupUrl($group, 'invite'), 'Button Primary Group-InviteButton Popup').' ';
    }

    $options = [];

    if (GroupPermission('Edit', $group)) {
        $options['Edit'] = ['Text' => T('Edit'), 'Url' => GroupUrl($group, 'edit')];
    }
//    if (GroupPermission('Delete')) {
//        $Options['Delete'] = array('Text' => T('Delete'), 'Url' => GroupUrl($Group, 'delete'));
//    }
    if (GroupPermission('Leave', $group)) {
        $options['Leave'] = ['Text' => T('Leave Group'), 'Url' => GroupUrl($group, 'leave'), 'CssClass' => 'Popup'];
    }

    if (GroupPermission('Leader', $group)) {
        $options['Delete'] = ['Text' => sprintf(T('Delete %s'), T('Group')), 'Url' => GroupUrl($group, 'delete'), 'CssClass' => 'Popup'];
    }

    if (count($options))
        echo ButtonDropDown($options, 'Button DropRight Group-OptionsButton', Sprite('SpOptions', 'Sprite16'));

    echo '</div>';
}
endif;

if (!function_exists('writeGroupOptions')):
/**
 * Renders the options menu for a group in a cog (typically for group in a group list).
 *
 * @param array $options The options to render.
 */
function writeGroupOptions($options = []) {
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
function writeGroupOptionsButton($options = []) {
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
 * @param array $groups
 * @param string $emptyMessage
 */
function WriteGroupCards($groups, $emptyMessage = '') {
    if (!$groups)
        WriteEmptyState($emptyMessage);
    else {
        echo '<div class="Cards Cards-Groups">';
        foreach ($groups as $group) {
            WriteGroupCard($group);
        }
        echo '</div>';
    }
}
endif;

if (!function_exists('WriteGroupCard')) :
/**
 * Write a group card
 *
 * @param array $group
 * @param bool $withButtons Optional. Whether to show group management option cog
 */
function WriteGroupCard($group, $withButtons = true) {
    echo '<div class="CardWrap"><div class="Group Card">';
        $url = GroupUrl($group);
        echo "<a href=\"$url\" class=\"TextColor\">";
            WriteGroupIcon($group, 'Group-Icon Card-Icon');
            echo '<h3 class="Group-Name">'.htmlspecialchars($group['Name']).'</h3>';
        echo '</a>';

        echo '<p class="Group-Description">'.
             htmlspecialchars(SliceString(
                Gdn_Format::PlainText($group['Description'], $group['Format']),
                C('Groups.CardDescription.ExcerptLength', 150))).'</p>';
        echo '<div class="Group-Members">'
.                  Plural($group['CountMembers'], '%s member','%s members', number_format($group['CountMembers']))
             .'</div>';

        if ($withButtons)
            WriteGroupButtons($group);
    echo '</div></div>';
}
endif;

if (!function_exists('WriteGroupIcon')) :
/**
 * Output group icon image.
 *
 * @param array $Group Optional. Uses data array's Group if none is provided.
 */
function WriteGroupIcon($group = false, $class = 'Group-Icon', $addChangeIconLink = false) {
    if (!$group) {
         $group = Gdn::Controller()->Data('Group');
    }
    if (val('Icon', $group)) {
         $icon = val('Icon', $group);
    } else {
         $icon = c('Groups.DefaultIcon', '');
    }
    if ($icon) {
         $output = '';
         if ($addChangeIconLink && GroupPermission('Edit', val('GroupID', $group))) {
              $output .= '';
              $output .= anchor('<span class="icon icon-camera"></span>'.t('Change Icon'), GroupUrl($group, 'groupicon'), 'ChangePicture');
         }
         $output .= img(Gdn_Upload::Url($icon), ['class' => $class]);

         echo wrap($output, 'div', ['class' => 'Photo PhotoWrap PhotoWrapLarge Group-Icon-Big-Wrap']);
    }
}
endif;

if (!function_exists('WriteGroupList')) :
/**
 * Write a list of groups out as a list.
 *
 * @param array $groups
 */
function WriteGroupList($groups) {
    if (is_array($groups)) {
        echo '<ul class="NarrowList DataList-Groups">';
        foreach ($groups as $group) {
            echo '<li class="Item Item-Group">';
            echo Anchor(Gdn_Format::Text($group['Name']), GroupUrl($group));
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
 * @param array $members
 */
function WriteMemberGrid($members, $more = '') {
    if (is_array($members)) {
        echo '<div class="PhotoGrid PhotoGridSmall">';
        foreach ($members as $member) {
             echo UserPhoto($member, val('Role', $member));
        }
        if ($more) {

            echo $more;
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
 * @param array $members
 */
function WriteMemberSimpleList($members) {
    if (is_array($members)) {
        echo '<ul class="Group-MemberList">';
        foreach ($members as $member) {
            echo '<li class="Group-Member UserTile">'.UserPhoto($member).' '.UserAnchor($member)."</li>\n";
        }
        echo '</ul>';
    }
}
endif;
