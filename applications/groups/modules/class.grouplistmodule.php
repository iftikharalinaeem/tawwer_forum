<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupListModule
 *
 * Consolidates the data and renders the view for a group list. Group lists populate the /groups and /groups/browse views.
 */
class GroupListModule extends Gdn_Module {

     /** @var array The groups to render. (An array of group arrays.)*/
     protected $groups;

     /** @var string The group list's unique identifier or endpoint slug ('mine', 'popular', 'new', etc.). */
     public $id;

     /** @var string The group list's title (i.e., 'My Groups'). */
     protected $title;

     /** @var string The message to display if there are no groups. */
     protected $emptyMessage;

     /** @var string A css class to add to the group list container. */
     protected $cssClass;

     /** @var bool Whether to provide a link to see all of the group list's contents. */
     protected $showMore;

     /** @var string The layout type, either 'modern' or 'table'. */
     protected $layout;

     /** @var bool Whether the latest post is attached to a group item. */
     protected $attachDiscussions;

     /** @var bool The latest post type ('Discussion' or 'Comment'). */
     protected $lastPostType;

     /**
      * Construct the GroupListModule object.
      *
      * @param array $groups The groups to render. (An array of group arrays.)
      * @param string $id The group list's unique identifier or endpoint slug ('mine', 'popular', 'new', etc.).
      * @param string $title The group list's title (i.e., 'My Groups').
      * @param string $emptyMessage The message to display if there are no groups.
      * @param string $cssClass A css class to add to the group list container.
      * @param bool $showMore Whether to provide a link to see all of the group list's contents.
      * @param string $layout The layout type, either 'modern' or 'table'.
      */
     public function __construct($groups = [], $id = 'groups', $title = '', $emptyMessage = '', $cssClass = 'GroupList', $showMore = true, $layout = '') {
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
      * @param string $sectionId The group list's unique identifier or endpoint slug ('mine', 'popular', 'new', etc.).
      * @return array A group list data array.
      */
     protected function getGroupsInfo($layout, $groups, $heading, $emptyMessage, $cssClass, $sectionId) {

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
                $this->addEmpty($groupList['items'], 'lastPost');
          }

          return $groupList;
     }

     /**
      * Adds a column type to an item row if it does not exist.
      * Ensures the cell is generated even if it has no data.
      *
      * @param array $items The group items.
      * @param string $column The column type to add to the group items.
      */
     public function addEmpty(&$items, $column) {
          foreach ($items as &$item) {
                if (!isset($item['rows'][$column])) {
                     $item['rows'][$column]['type'] = $column;
                }
          }
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
     protected function getGroupInfo($group, $layout, $withOptions, $sectionId) {
          $item['text'] = htmlspecialchars(sliceString(Gdn_Format::plainText(val('Description', $group), val('Format', $group)), c('Groups.CardDescription.ExcerptLength', 150)));
          $item['textCssClass'] = 'GroupDescription';
          $item['imageSource'] = val('Icon', $group) ? Gdn_Upload::url(val('Icon', $group)) : c('Groups.DefaultIcon', false);
          $item['imageCssClass'] = 'Group-Icon';
          $item['heading'] = val('Name', $group);
          $item['url'] = groupUrl($group);
          $item['id'] = 'Group_'.val('GroupID', $group);
          $item['metaCssClass'] = '';

          // 'LastTitle' is only added if JoinRecentPosts function is called on groups
          $attachDiscussionData = val('LastTitle', $group);

          $item['meta']['countDiscussions']['text'] = plural(val('CountDiscussions', $group), '%s discussion', '%s discussions', number_format(val('CountDiscussions', $group)));
          $item['meta']['countDiscussions']['count'] = val('CountDiscussions', $group);
          $item['meta']['countDiscussions']['cssClass'] = 'DiscussionCount MItem-Count';
          $item['meta']['countMembers']['text'] = plural(val('CountMembers', $group), '%s member', '%s members', number_format(val('CountMembers', $group)));
          $item['meta']['countMembers']['count'] = val('CountMembers', $group);
          $item['meta']['countMembers']['cssClass'] = 'MemberCount MItem-Count';
          $item['meta']['countMembersNumber']['text'] = val('CountMembers', $group);
          $item['meta']['countMembersNumber']['count'] = val('CountMembers', $group);
          $item['meta']['countMembersNumber']['cssClass'] = 'Hidden DiscussionCountNumber Number MItem-Count';
          $item['meta']['countDiscussionsNumber']['text'] = val('CountDiscussions', $group);
          $item['meta']['countDiscussionsNumber']['count'] = val('CountDiscussions', $group);
          $item['meta']['countDiscussionsNumber']['cssClass'] = 'Hidden MemberCountNumber Number MItem-Count';

          $groupPrivacy = $group["Privacy"];


          if ($groupPrivacy === "Private") {
                $privacyIconLabel = t("Private Group");
                $item['meta']['privacy']['text'] = $privacyIconLabel;
                $item['meta']['privacy']['icon'] = <<<EOT
                    <span class="Title-Icon" title="$privacyIconLabel">
                        <span class="sr-only">$privacyIconLabel</span>
                            <svg aria-hidden="true" class="icon Title-PrivateIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14.877 18.934">
                                <title>$privacyIconLabel</title>
                                <path fill="currentColor" d="M14.088,6.761H12.172V4.06A4.016,4.016,0,0,0,8.2,0H6.76A4.015,4.015,0,0,0,2.705,3.96v2.8H.788A.788.788,0,0,0,0,7.55H0V18.145a.788.788,0,0,0,.787.789h13.3a.789.789,0,0,0,.789-.789V7.546A.79.79,0,0,0,14.088,6.761ZM4.06,4.051A2.678,2.678,0,0,1,6.706,1.344H8.117a2.676,2.676,0,0,1,2.7,2.648h0a.581.581,0,0,1,0,.059v2.71H4.06Zm4.057,9.335v2.842H6.76V13.386a1.827,1.827,0,0,1-1.217-1.217A1.957,1.957,0,0,1,6.657,9.636h0l.1-.037a2.011,2.011,0,0,1,1.352,3.788Z"/>
                            </svg>
                        </span>
EOT;
          }
          if ($groupPrivacy === "Secret") {
                $secretIconLabel = t("Secret Group");
                $item['meta']['privacy']['text'] = $secretIconLabel;
                $item['meta']['privacy']['icon'] = <<<EOT
                    <span class="Title-Icon" title="$secretIconLabel">
                        <span class="sr-only">$secretIconLabel</span>
                        <svg aria-hidden="true" class="icon Title-SecretIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24.028 13.984">
                            <title>$secretIconLabel</title>
                            <path fill="currentColor" d="M23.7,1.171C23.344.026,19.46-.079,16.927.037s-4.919,2.634-4.919,2.634S9.892.152,7.358.037.709.026.354,1.171A16.709,16.709,0,0,0,.068,6.094c.134,1.144,3.2,7.832,7.023,7.889s3.361-1.593,4.917-1.651c1.556.058,1.1,1.709,4.918,1.651s6.888-6.745,7.022-7.889A16.7,16.7,0,0,0,23.7,1.171ZM10.157,8.84c-.421.6-3.644.75-4.574.326A3.237,3.237,0,0,1,3.77,5.7c.1-.638-.013-1.407,3.9-.934C10.622,5.121,10.157,8.84,10.157,8.84Zm8.276.326c-.93.424-4.153.273-4.574-.326,0,0-.462-3.716,2.482-4.072,3.918-.473,3.805.3,3.9.934A3.237,3.237,0,0,1,18.433,9.166Z"/>
                        </svg>
                    </span>
EOT;
          }


          $groupModel = new GroupModel();
          if ($attachDiscussionData && $groupModel->checkPermission('View', val('GroupID', $group))) {
                $this->attachDiscussions = true;
                $this->lastPostType = 'Comment';
                if (val('NoComment', $group)) {
                     $this->lastPostType = 'Discussion';
                }
                $item['meta']['lastDiscussion']['text'] = sprintf(t('%s: %s'), t('Most recent discussion'), null);
                $item['meta']['lastDiscussion']['linkText'] = htmlspecialchars(sliceString(Gdn_Format::text(val('LastTitle', $group)), 100));
                $item['meta']['lastDiscussion']['url'] = url(val('LastUrl', $group));
                $item['meta']['lastUser']['text'] = t('by') . ' ';
                $item['meta']['lastUser']['linkText'] = val('Last'.$this->lastPostType.'Name', $group);
                $item['meta']['lastUser']['url'] = userUrl($group, 'Last'.$this->lastPostType);
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
     protected function getGroupItemTableData(&$item, $group) {
          $item['rows']['main']['type'] = 'main';
          $item['rows']['main']['cssClass'] = 'Group-Name';

          $item['rows']['countMembers']['type'] = 'count';
          $item['rows']['countMembers']['number'] = val('CountMembers', $group);
          $item['rows']['countMembers']['cssClass'] = 'CountMembers';

          $item['rows']['countDiscussions']['type'] = 'count';
          $item['rows']['countDiscussions']['number'] = val('CountDiscussions', $group);
          $item['rows']['countDiscussions']['cssClass'] = 'CountDiscussions';

          $attachDiscussionData = val('LastTitle', $group);
          $groupModel = new GroupModel();
          if ($attachDiscussionData && $groupModel->checkPermission('View', val('GroupID', $group))) {
                $item['rows']['lastPost']['type'] = 'lastPost';
                $item['rows']['lastPost']['title'] = $item['meta']['lastDiscussion']['linkText'];
                $item['rows']['lastPost']['url'] = $item['meta']['lastDiscussion']['url'];
                $item['rows']['lastPost']['username'] = $item['meta']['lastUser']['linkText'];
                $item['rows']['lastPost']['userUrl'] = $item['meta']['lastUser']['url'];
                $item['rows']['lastPost']['date'] = $item['meta']['lastDate']['text'];
                $item['rows']['lastPost']['imageSource'] = val('Last'.$this->lastPostType.'Photo', $group);
                $item['rows']['lastPost']['imageUrl'] = userUrl($group, 'Last'.$this->lastPostType);
          }
     }

     /**
      * Renders the group list.
      *
      * @return string HTML view
      */
     public function toString() {
          require_once Gdn::controller()->fetchViewLocation('group_functions', 'Group', 'groups');
          if (!$this->groups) {
                $controller = Gdn::controller();
                $this->groups = val('Groups', $controller->Data);
          }
          if (!$this->groups) {
                return '';
          }
          $groupList = $this->getGroupsInfo($this->layout, $this->groups, $this->title, $this->emptyMessage, $this->cssClass, $this->id);
          $controller = new Gdn_Controller();
          $controller->setData('list', $groupList);
          $controller->fireEvent('groupList');
          return $controller->fetchView($this->getView(), 'modules', 'groups');
     }

}
