<?php

/**
 * Groups Application - Group List Module
 *
 */

class MemberListModule extends Gdn_Module {

  public $members;
  public $group;
  public $title;
  public $emptyMessage;
  public $view;

  public function __construct($members, $group, $title = '', $emptyMessage = '', $view = '') {
    $this->members = $members;
    $this->group = $group;
    $this->title = $title;
    $this->emptyMessage = $emptyMessage;
    $this->view = $view ?: c('Vanilla.Discussions.Layout', 'modern');
    $this->_ApplicationFolder = 'groups';
  }

  public function getMemberOptions($member, $group) {

  }


  public function getMemberButtons($member, $group) {
    $userId = val('UserID', $member);
    $buttons = array();
    if (GroupPermission('Moderate') && (val('InsertUserID', $group) != $userId)) {
      if (GroupPermission('Edit')) {
        if (val('Role', $member) == 'Leader') {
          $makeMember['text'] = sprintf(T('Make %s'), T('Member'));
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

  public function getMembersInfo($view, $members, $group, $heading, $emptyMessage = '', $sectionId = '') {

    $memberList['view'] = $view;
    $memberList['emptyMessage'] = $emptyMessage;
    $memberList['title'] = $heading;

    if ($view == 'table') {
      $memberList['columns'][0]['columnLabel'] = t('User');
      $memberList['columns'][0]['columnCssClass'] = 'UserName';
      $memberList['columns'][1]['columnLabel'] = t('Join Date');
      $memberList['columns'][1]['columnCssClass'] = 'JoinDate';
      $memberList['columns'][2]['columnLabel'] = '';
      $memberList['columns'][2]['columnCssClass'] = 'Buttons';
    }

    foreach ($members as $member) {
      $memberList['items'][] = $this->getMemberInfo($member, $group, $view, true, $sectionId);
    }

    return $memberList;
  }

  public function getMemberInfo($member, $group, $view, $withButtons = true, $sectionId = false) {

    if ($view != 'table') {
      $item['buttons'] = $this->getMemberButtons($member, $group);
    }

    $item['heading'] = Gdn_Format::text(val('Name', $member));
    $item['url'] = userUrl($member);
    $item['imageSource'] = userPhotoUrl($member);
    $item['imageUrl'] = userUrl($member);
    $item['metaCssClass'] = '';

    if ($view != 'table') {
      $item['meta']['joinDate']['text'] = sprintf(T('Joined %s', 'Joined %s'), Gdn_Format::date(val('DateInserted', $member), 'html'));
      $item['meta']['joinDate']['cssClass'] = 'JoinDate';
    }

//    $item['options'] = $this->getMemberOptions($member, $group);

    if ($view == 'table') {
      $this->getMemberTableItem($item, $member, $group);
    }

    return $item;
  }


  public function getMemberTableItem(&$item, $member, $group) {
    $item['rows']['main']['type'] = 'main';
    $item['rows']['main']['cssClass'] = 'UserName';

    $item['rows']['joinDate']['type'] = 'default';
    $item['rows']['joinDate']['text'] = Gdn_Format::Date(val('DateInserted', $member), 'html');
    $item['rows']['joinDate']['cssClass'] = 'JoinDate';

    $item['rows']['buttons']['type'] = 'buttons';
    $item['rows']['buttons']['buttons'] = $this->getMemberButtons($member, $group);
    $item['rows']['buttons']['cssClass'] = 'pull-right';
  }

  /**
   * Render members
   *
   * @return type
   */
  public function toString() {
    $this->members = $this->getMembersInfo($this->view, $this->members, $this->group, $this->title, $this->emptyMessage);
    $controller = new Gdn_Controller();
    $controller->setData('list', $this->members);
    return $controller->fetchView('memberlist', 'modules', 'groups');
    return '';
  }
}
