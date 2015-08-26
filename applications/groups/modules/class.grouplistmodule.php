<?php

/**
 * Groups Application - Group List Module
 *
 */

class GroupListModule extends Gdn_Module {

    public $sender;
    public $groups;
    public $id;
    public $title;
    public $emptyMessage;
    public $cssClass;
    public $showMore;
    public $view;

    public function __construct($sender, $groups, $id, $title = '', $emptyMessage = '', $cssClass = '', $showMore = true, $view = '') {
        $this->sender = $sender;
        $this->groups = $groups;
        $this->id = $id;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->cssClass = $cssClass;
        $this->showMore = $showMore;
        $this->view = $view ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->_ApplicationFolder = 'groups';
    }

    public function __get($name) {
        $name = lcfirst($name);

        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __set($name, $value) {

    }

    public function getGroupsInfo($view, $groups, $heading, $emptyMessage = '', $cssClass = '', $sectionId = '') {

        $groupList['view'] = $view;
        $groupList['emptyMessage'] = $emptyMessage;
        $groupList['title'] = $heading;
        $groupList['cssClass'] = $cssClass;

        if ($this->showMore) {
            $groupList['moreLink'] = sprintf(t('All %s...'), $heading);
            $groupList['moreUrl'] = url('/groups/browse/'.$sectionId);
            $groupList['moreCssClass'] = 'More';
        }

        if ($view == 'table') {
            $groupList['columns'][0]['columnLabel'] = t('Group');
            $groupList['columns'][0]['columnCssClass'] = 'GroupName';
            $groupList['columns'][1]['columnLabel'] = t('Members');
            $groupList['columns'][1]['columnCssClass'] = 'BigCount CountMembers';
            $groupList['columns'][2]['columnLabel'] = t('Discussions');
            $groupList['columns'][2]['columnCssClass'] = 'BigCount CountDiscussions';
            $groupList['columns'][3]['columnLabel'] = t('Latest Post');
            $groupList['columns'][3]['columnCssClass'] = 'BlockColumn LatestPost';
        }

        foreach ($groups as $group) {
            $groupList['items'][] = $this->getGroupInfo($group, $view, true, $sectionId);
        }

        return $groupList;
    }

    public function getGroupInfo($group, $view, $withButtons = true, $sectionId = false) {
        $item['text'] = htmlspecialchars(sliceString(Gdn_Format::plainText(val('Description', $group), val('Format', $group)), c('Groups.CardDescription.ExcerptLength', 150)));
        $item['textCssClass'] = 'GroupDescription';
        $item['imageSource'] = val('Icon', $group) ? Gdn_Upload::url(val('Icon', $group)) : C('Groups.DefaultIcon', false);
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
                $groupModel = new GroupModel();
                if ($groupModel->CheckPermission('View', val('GroupID', $group))) {
                    $item['meta']['lastDiscussion']['text'] = t('Most recent discussion:') . ' ';
                    $item['meta']['lastDiscussion']['linkText'] = htmlspecialchars(sliceString(Gdn_Format::text(val('LastTitle', $group)), 100));
                    $item['meta']['lastDiscussion']['url'] = url(val('LastUrl', $group));
                }

                $item['meta']['lastUser']['text'] = t('by') . ' ';
                $item['meta']['lastUser']['linkText'] = val('LastName', $group);
                $item['meta']['lastUser']['url'] = userUrl($group, 'Last');

                $item['meta']['lastDate']['text'] = Gdn_Format::date(val('LastDateInserted', $group));
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
        $item['rows']['lastPost']['userUrl'] = userUrl($group, 'Last');
        $item['rows']['lastPost']['date'] = val('LastDateInserted', $group);
        $item['rows']['lastPost']['imageSource'] = val('LastPhoto', $group);
        $item['rows']['lastPost']['imageUrl'] = userUrl($group, 'Last');
    }

    /**
     * Render groups
     *
     * @return type
     */
    public function toString() {
        $this->sender->EventArguments['view'] = &$this->view;
        $this->sender->fireEvent('beforeGenerateGroupList');

        $this->groups = $this->getGroupsInfo($this->view, $this->groups, $this->title, $this->emptyMessage, $this->cssClass, $this->id);
        $this->sender->setData('list', $this->groups);
//        if (!in_array($this->view, array('table', 'list'))) {
//            $this->view = 'list';
//        }
//        $view = 'groups_'.$this->view;

        return $this->sender->fetchView('grouplist', 'modules', 'groups');
    }

}
