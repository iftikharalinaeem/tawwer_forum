<?php
/**
 * Groups Application - Group List Module
 *
 */

/**
 * Class GroupListModule
 *
 * Consolidates the data and renders the view for a group list. Group lists populate the /groups and /groups/browse views.
 */
class GroupListModule extends Gdn_Module {

    /**
     * @var array The groups to render. (An array of group arrays.)
     */
    private $groups;
    /**
     * @var string The group list's unique endpoint slug ('mine', 'popular', 'new', etc.).
     */
    private $id;
    /**
     * @var string The group list's title (i.e., 'My Groups').
     */
    private $title;
    /**
     * @var string The message to display if there are no groups.
     */
    private $emptyMessage;
    /**
     * @var string A css class to add to the group list container.
     */
    private $cssClass;
    /**
     * @var bool Whether to provide a link to see all of the group list's contents.
     */
    private $showMore;
    /**
     * @var string The layout type, either 'modern' or 'table'.
     */
    private $layout;
    /**
     * @var bool Whether the latest post is attached to a group item.
     */
    private $attachDiscussions;

    /**
     * Construct the GroupListModule object.
     *
     * @param array $groups The groups to render. (An array of group arrays.)
     * @param string $id The group list's unique endpoint slug ('mine', 'popular', 'new', etc.).
     * @param string $title The group list's title (i.e., 'My Groups').
     * @param string $emptyMessage The message to display if there are no groups.
     * @param string $cssClass A css class to add to the group list container.
     * @param bool $showMore Whether to provide a link to see all of the group list's contents.
     * @param string $layout The layout type, either 'modern' or 'table'.
     */
    public function __construct($groups, $id, $title = '', $emptyMessage = '', $cssClass = '', $showMore = true, $layout = '') {
        $this->groups = $groups;
        $this->id = $id;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->cssClass = $cssClass;
        $this->showMore = $showMore;
        $this->layout = $layout ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->setView('grouplist');
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Collect and organize the data for the group list.
     *
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param array $groups The groups to render. (An array of group arrays.)
     * @param string $heading The group list's title (i.e., 'My Groups').
     * @param string $emptyMessage The message to display if there are no groups.
     * @param string $cssClass A css class to add to the group list container.
     * @param string $sectionId The group list's unique endpoint slug ('mine', 'popular', 'new', etc.).
     * @return array A group list data array.
     */
    private function getGroupsInfo($layout, $groups, $heading, $emptyMessage, $cssClass, $sectionId) {

        $groupList['layout'] = $layout;
        $groupList['emptyMessage'] = $emptyMessage;
        $groupList['title'] = $heading;
        $groupList['cssClass'] = $cssClass;

        if ($this->showMore) {
            $groupList['moreLink'] = sprintf(t('All %s...'), $heading);
            $groupList['moreUrl'] = url('/groups/browse/'.$sectionId);
            $groupList['moreCssClass'] = 'More';
        }

        if ($layout == 'table') {
            $groupList['columns'][0]['columnLabel'] = t('Group');
            $groupList['columns'][0]['columnCssClass'] = 'GroupName';
            $groupList['columns'][1]['columnLabel'] = t('Members');
            $groupList['columns'][1]['columnCssClass'] = 'BigCount CountMembers';
            $groupList['columns'][2]['columnLabel'] = t('Discussions');
            $groupList['columns'][2]['columnCssClass'] = 'BigCount CountDiscussions';
        }

        foreach ($groups as $group) {
            $groupList['items'][] = $this->getGroupInfo($group, $layout, true, $sectionId);
        }

        if ($this->attachDiscussions && $layout == 'table') {
            $groupList['columns'][3]['columnLabel'] = t('Latest Post');
            $groupList['columns'][3]['columnCssClass'] = 'BlockColumn LatestPost';
        }

        return $groupList;
    }

    /**
     * Collect and organize the data for a group item in the group list.
     *
     * @param array $group The group item.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withOptions Whether to add a the group options to the group item.
     * @param string $sectionId The group list's unique endpoint slug.
     * @return array A data array representing a group item in a group list.
     */
    private function getGroupInfo($group, $layout, $withOptions, $sectionId) {
        $item['text'] = htmlspecialchars(sliceString(Gdn_Format::plainText(val('Description', $group), val('Format', $group)), c('Groups.CardDescription.ExcerptLength', 150)));
        $item['textCssClass'] = 'GroupDescription';
        $item['imageSource'] = val('Icon', $group) ? Gdn_Upload::url(val('Icon', $group)) : C('Groups.DefaultIcon', false);
        $item['imageCssClass'] = 'Group-Icon';
        $item['heading'] = val('Name', $group);
        $item['url'] = GroupUrl($group);
        $item['id'] = 'Group_'.val('GroupID', $group);
        $item['metaCssClass'] = '';

        // 'LastTitle' is only added if JoinRecentPosts function is called on groups
        $attachDiscussionData = val('LastTitle', $group);

        $item['meta']['countDiscussions']['text'] = Plural(val('CountDiscussions', $group), '%s discussion', '%s discussions', number_format(val('CountDiscussions', $group)));
        $item['meta']['countDiscussions']['count'] = val('CountDiscussions', $group);
        $item['meta']['countMembers']['text'] = Plural(val('CountMembers', $group), '%s member', '%s members', number_format(val('CountMembers', $group)));
        $item['meta']['countMembers']['count'] = val('CountMembers', $group);

        if ($attachDiscussionData) {
            $this->attachDiscussions = true;
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

        if ($withOptions) {
            $item['options'] = getGroupOptions($group, $sectionId);
            $item['buttons'] = getGroupButtons($group);
        }

        if ($layout == 'table') {
            $this->getGroupItemTableData($item, $group);
        }

        return $item;
    }


    /**
     * Adds the row data for a group item in a table layout group list.
     *
     * @param array $item The working group item for a group list.
     * @param array $group The group array we're parsing.
     */
    private function getGroupItemTableData(&$item, $group) {
        $item['rows']['main']['type'] = 'main';
        $item['rows']['main']['cssClass'] = 'Group-Name';

        $item['rows']['countMembers']['type'] = 'count';
        $item['rows']['countMembers']['number'] = val('CountMembers', $group);
        $item['rows']['countMembers']['cssClass'] = 'CountMembers';

        $item['rows']['countDiscussions']['type'] = 'count';
        $item['rows']['countDiscussions']['number'] = val('CountDiscussions', $group);
        $item['rows']['countDiscussions']['cssClass'] = 'CountDiscussions';

        $attachDiscussionData = val('LastTitle', $group);
        if ($attachDiscussionData) {
            $item['rows']['lastPost']['type'] = 'lastPost';
            $item['rows']['lastPost']['title'] = val('LastTitle', $group);
            $item['rows']['lastPost']['url'] = val('LastUrl', $group);
            $item['rows']['lastPost']['username'] = val('LastName', $group);
            $item['rows']['lastPost']['userUrl'] = userUrl($group, 'Last');
            $item['rows']['lastPost']['date'] = val('LastDateInserted', $group);
            $item['rows']['lastPost']['imageSource'] = val('LastPhoto', $group);
            $item['rows']['lastPost']['imageUrl'] = userUrl($group, 'Last');
        }
    }

    /**
     * Renders the group list.
     *
     * @return string HTML view
     */
    public function toString() {
        require_once Gdn::Controller()->fetchViewLocation('group_functions', 'Group', 'groups');
        $this->groups = $this->getGroupsInfo($this->layout, $this->groups, $this->title, $this->emptyMessage, $this->cssClass, $this->id);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->groups);
        return $controller->fetchView($this->getView(), 'modules', 'groups');
    }

}
