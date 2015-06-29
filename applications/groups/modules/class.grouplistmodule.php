<?php

/**
 * Groups Application - Group List Module
 *
 */

class GroupListModule extends Gdn_Module {

  public $groups;
  public $id;
  public $title;
  public $emptyMessage;
  public $showMore;
  public $view;

  public function __construct($groups, $id, $title = '', $emptyMessage = '', $showMore = true, $view = '') {
    $this->groups = $groups;
    $this->id = $id;
    $this->title = $title;
    $this->emptyMessage = $emptyMessage;
    $this->showMore = $showMore;
    $this->view = $view ?: c('Vanilla.Discussions.Layout', 'modern');
    $this->_ApplicationFolder = 'groups';
  }

  public function getGroupsInfo($view, $groups, $heading, $emptyMessage = '', $sectionId = '') {

    $groupList['view'] = $view;
    $groupList['emptyMessage'] = $emptyMessage;
    $groupList['title'] = $heading;
    if ($this->showMore) {
      $groupList['moreLink'] = sprintf(T('All %s...'), $heading);
      $groupList['moreUrl'] = '/groups/browse/' . $sectionId;
      $groupList['moreCssClass'] = 'More';
    }

    if ($view == 'table') {
      $groupList['columns'][0]['columnLabel'] = T('Group');
      $groupList['columns'][0]['columnCssClass'] = 'GroupName';
      $groupList['columns'][1]['columnLabel'] = T('Members');
      $groupList['columns'][1]['columnCssClass'] = 'BigCount CountMembers';
      $groupList['columns'][2]['columnLabel'] = T('Discussions');
      $groupList['columns'][2]['columnCssClass'] = 'BigCount CountDiscussions';
      $groupList['columns'][3]['columnLabel'] = T('Latest Post');
      $groupList['columns'][3]['columnCssClass'] = 'BlockColumn LatestPost';
    }

    foreach ($groups as $group) {
      $groupList['items'][] = $this->getGroupInfo($group, $view, true, $sectionId);
    }

    return $groupList;
  }

  public function getGroupInfo($group, $view, $withButtons = true, $sectionId = false) {

    $item['text'] = htmlspecialchars(SliceString(Gdn_Format::PlainText(val('Description', $group), val('Format', $group)), C('Groups.CardDescription.ExcerptLength', 150)));
    $item['textCssClass'] = 'GroupDescription';
    $item['imageSource'] = val('Icon', $group) ? Gdn_Upload::Url(val('Icon', $group)) : C('Groups.DefaultIcon', false);
    $item['imageCssClass'] = 'Group-Icon';
    $item['heading'] = val('Name', $group);
    $item['url'] = GroupUrl($group);
    $item['id'] = 'Group_'.val('GroupID', $group);
    $item['metaCssClass'] = '';

    if ($view != 'table') {
      // 'LastTitle' is only added if JoinRecentPosts function is called on groups
      $attachDiscussionData = val('LastTitle', $group);

      $item['meta']['countDiscussions']['text'] = Plural(val('CountDiscussions', $group), '%s discussion', '%s discussions', number_format(val('CountDiscussions', $group)));
      $item['meta']['countMembers']['text'] = Plural(val('CountMembers', $group), '%s member', '%s members', number_format(val('CountMembers', $group)));

      if ($attachDiscussionData) {
        $item['meta']['lastDiscussion']['text'] = T('Most recent discussion:') . ' ';
        $item['meta']['lastDiscussion']['linkText'] = htmlspecialchars(SliceString(Gdn_Format::Text(val('LastTitle', $group)), 100));
        $item['meta']['lastDiscussion']['url'] = val('LastUrl', $group);

        $item['meta']['lastUser']['text'] = T('by') . ' ';
        $item['meta']['lastUser']['linkText'] = val('LastName', $group);
        $item['meta']['lastUser']['url'] = UserUrl($group, 'Last');

        $item['meta']['lastDate']['text'] = Gdn_Format::Date(val('LastDateInserted', $group));
      }
    }

    if ($withButtons) {
      $item['options'] = getGroupOptions($group, $sectionId);
      $item['buttons'] = getGroupButtons($group);
    }

    if ($view == 'table') {
      $this->getGroupTableItem($item, $group);
    }

    return $item;
  }


  public function getGroupTableItem(&$item, $group) {
    $item['rows']['main']['type'] = 'main';
    $item['rows']['main']['cssClass'] = 'Group-Name';

    $item['rows']['countMembers']['type'] = 'count';
    $item['rows']['countMembers']['number'] = val('CountMembers', $group);
    $item['rows']['countMembers']['cssClass'] = 'CountMembers';

    $item['rows']['countDiscussions']['type'] = 'count';
    $item['rows']['countDiscussions']['number'] = val('CountDiscussions', $group);
    $item['rows']['countDiscussions']['cssClass'] = 'CountDiscussions';

    $item['rows']['lastPost']['type'] = 'lastPost';
    $item['rows']['lastPost']['title'] = val('LastTitle', $group);
    $item['rows']['lastPost']['url'] = val('LastUrl', $group);
    $item['rows']['lastPost']['username'] = val('LastName', $group);
    $item['rows']['lastPost']['userUrl'] = UserUrl($group, 'Last');
    $item['rows']['lastPost']['date'] = val('LastDateInserted', $group);
    $item['rows']['lastPost']['imageSource'] = val('LastPhoto', $group);
    $item['rows']['lastPost']['imageUrl'] = UserUrl($group, 'Last');
  }

  /**
   * Render groups
   *
   * @return type
   */
  public function toString() {
    $this->groups = $this->getGroupsInfo($this->view, $this->groups, $this->title, $this->emptyMessage, $this->id);
    $controller = new Gdn_Controller();
    $controller->setData('list', $this->groups);
    return $controller->fetchView('grouplist', 'modules', 'groups');
  }

}
