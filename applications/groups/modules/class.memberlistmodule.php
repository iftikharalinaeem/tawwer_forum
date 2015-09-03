<?php

/**
 * Groups Application - Member List Module
 *
 */

class MemberListModule extends Gdn_Module {

    /**
     * @var array The members to render.
     */
    public $members;
    /**
     * @var array The group that the members are associated with.
     */
    public $group;
    /**
     * @var string The member section title (i.e., 'Leaders' or 'Members').
     */
    public $title;
    /**
     * @var string The message to display if there are no members.
     */
    public $emptyMessage;
    /**
     * @var string The layout type, either 'modern' or 'table'.
     */
    public $layout;
    /**
     * @var bool Whether to add the 'leader', 'member' and 'remove' buttons to member items.
     */
    public $withButtons;

  /**
   * Construct the MemberListModule object.
   *
   * @param array $members The members to render.
   * @param array $group The group that the members are associated with.
   * @param string $title The member section title (i.e., 'Leaders' or 'Members').
   * @param string $emptyMessage The message to display if there are no members.
   * @param string $layout The layout type, either 'modern' or 'table'.
   * @param bool $withButtons Whether to add the 'leader', 'member' and 'remove' buttons to member items.
   */
    public function __construct($members, $group, $title = '', $emptyMessage = '', $layout = '', $withButtons = true) {
        $this->members = $members;
        $this->group = $group;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->layout = $layout ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->_ApplicationFolder = 'groups';
        $this->withButtons = $withButtons;
    }

    /**
     * Compiles the data for the buttons for a member item.
     *
     * @param array $member The member item.
     * @param array $group The group that the member is associated with.
     * @return array The member buttons.
     */
    public function getMemberButtons($member, $group) {
        $userId = val('UserID', $member);
        $buttons = array();
        if (GroupPermission('Moderate') && (val('InsertUserID', $group) != $userId)) {
            if (GroupPermission('Edit')) {
                if (val('Role', $member) == 'Leader') {
                    $makeMember['text'] = sprintf(t('Make %s'), t('Member'));
                    $makeMember['url'] = GroupUrl($group, 'setrole')."?userid=$userId&role=member";
                    $makeMember['cssClass'] = 'Group-MakeMember Hijack';

                    $buttons[] = $makeMember;
                } else {
                    $makeLeader['text'] = t('Make Leader', 'Leader');
                    $makeLeader['url'] = GroupUrl($group, 'setrole')."?userid=$userId&role=leader";
                    $makeLeader['cssClass'] = 'Group-Leader Hijack';

                    $buttons[] = $makeLeader;
                }
            }
            $remove['text'] = t('Remove');
            $remove['url'] = GroupUrl($group, 'removemember')."?userid=$userId";
            $remove['cssClass'] = 'Group-RemoveMember Popup';

            $buttons[] = $remove;
        }
        return $buttons;
    }

    /**
     * Collect and organize the data for the member list.
     *
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param array $members The members to render.
     * @param array $group The group that the members are associated with.
     * @param string $title The applicant section title.
     * @param string $emptyMessage The message to display if there are no applicants.
     * @param bool $withButtons Whether to add the 'leader', 'member' and 'remove' to member items.
     * @return array A member list data array.
     */
    public function getMembersInfo($layout, $members, $group, $title, $emptyMessage, $withButtons) {

        $memberList['layout'] = $layout;
        $memberList['emptyMessage'] = $emptyMessage;
        $memberList['title'] = $title;

        if ($layout == 'table') {
            $memberList['columns'][0]['columnLabel'] = t('User');
            $memberList['columns'][0]['columnCssClass'] = 'UserName';
            $memberList['columns'][1]['columnLabel'] = t('Join Date');
            $memberList['columns'][1]['columnCssClass'] = 'JoinDate';
            $memberList['columns'][2]['columnLabel'] = '';
            $memberList['columns'][2]['columnCssClass'] = 'Buttons';
        }

        foreach ($members as $member) {
            $memberList['items'][] = $this->getMemberInfo($member, $group, $layout, $withButtons);
        }

        return $memberList;
    }

    /**
     * Collect and organize the data for a member item in the member list.
     *
     * @param array $member The member item.
     * @param array $group The group that the member is associated with.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withButtons Whether to add the 'leader', 'member' and 'remove' buttons to member items.
     * @return array A data array representing a member item in a member list.
     */
    public function getMemberInfo($member, $group, $layout, $withButtons) {

        $item['heading'] = Gdn_Format::text(val('Name', $member));
        $item['url'] = userUrl($member);
        $item['imageSource'] = userPhotoUrl($member);
        $item['imageUrl'] = userUrl($member);
        $item['metaCssClass'] = '';

        if ($layout != 'table') {
            $item['meta']['joinDate']['text'] = sprintf(T('Joined %s', 'Joined %s'), Gdn_Format::date(val('DateInserted', $member), 'html'));
            $item['meta']['joinDate']['cssClass'] = 'JoinDate';
        }
        if ($layout == 'table') {
            $this->getMemberTableItem($item, $member, $group, $withButtons);
        }
        elseif ($withButtons) {
            $item['buttons'] = $this->getMemberButtons($member, $group);
        }

        return $item;
    }


  /**
   * Adds the row data for a member item in a table layout member list.
   *
   * @param array $item The working member item for a member list.
   * @param array $member The member array we're parsing.
   * @param array $group The group that the member is associated with.
   * @param bool $withButtons Whether to add the 'leader', 'member' and 'remove' buttons to member items.
   */
    public function getMemberTableItem(&$item, $member, $group, $withButtons) {
        $item['rows']['main']['type'] = 'main';
        $item['rows']['main']['cssClass'] = 'UserName';

        $item['rows']['joinDate']['type'] = 'default';
        $item['rows']['joinDate']['text'] = Gdn_Format::date(val('DateInserted', $member), 'html');
        $item['rows']['joinDate']['cssClass'] = 'JoinDate';

        if ($withButtons) {
            $item['rows']['buttons']['type'] = 'buttons';
            $item['rows']['buttons']['buttons'] = $this->getMemberButtons($member, $group);
            $item['rows']['buttons']['cssClass'] = 'pull-right';
        }
    }

    /**
     * Renders the member list.
     *
     * @return string HTML view
     */
    public function toString() {
        $this->members = $this->getMembersInfo($this->layout, $this->members, $this->group, $this->title, $this->emptyMessage, $this->withButtons);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->members);
        return $controller->fetchView('memberlist', 'modules', 'groups');
    }
}
