<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class PromotedGroupsModule
 *
 * Outputs a list of new, popular, updated or 'my' groups.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @package groups
 */
class PromotedGroupsModule extends Gdn_Module {

    /** @var int The max number of groups to retrieve. */
    protected $limit = 3;

    /** @var string The type of groups to return. Must be a key in $promoteTypes. */
    protected $promoteType;

    /** @var bool Whether to attach data from the group's last discussion to the group. */
    protected $attachLastDiscussion = false;

    /** @var array The categories of promoted types and data associated with them.
     */
    protected $promoteTypes = [
        'popular' => ['title' => 'Popular Groups', 'url' => '/groups/browse/popular', 'orderBy' => 'CountMembers'],
        'new' => ['title' => 'New Groups', 'url' => '/groups/browse/new', 'orderBy' => 'DateInserted'],
        'updated' => ['title' => 'Recently Updated Groups', 'url' => '/groups/browse/updated', 'orderBy' => 'DateLastComment'],
        'mine' => ['title' => 'My Groups', 'url' => '/groups/browse/mine', 'orderBy' => 'DateLastComment']
    ];

    /** @var string The title to be rendered for the module. */
    protected $title;

    /** @var string The url for the 'show more' link. */
    protected $url;

    /** @var string The field to order the group results by. */
    protected $orderBy;

    /**
     * Construct the PromotedGroupsModule object.
     *
     * @param string $promoteType $promoteType
     */
    public function __construct($promoteType = 'popular') {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
        $this->setView('promotedgroups');
        $this->setPromoteType($promoteType);
    }

    /**
     * Sets the title.
     *
     * @param string $title The title to be rendered for the module.
     * @return PromotedGroupsModule $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Sets the max number of groups to retrieve.
     *
     * @param int $limit The max number of groups to retrieve.
     * @return PromotedGroupsModule $this
     */
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets whether to attach data from the group's last discussion to the group.
     *
     * @param boolean $attachLastDiscussion Whether to attach data from the group's last discussion to the group.
     * @return PromotedGroupsModule $this
     */
    public function setAttachLastDiscussion($attachLastDiscussion) {
        $this->attachLastDiscussion = $attachLastDiscussion;
        return $this;
    }

    /**
     * Sets the promote type and its related properties.
     *
     * @param string $promoteType The type of groups to return. Must be a key in $promoteTypes.
     * @return PromotedGroupsModule $this
     */
    public function setPromoteType($promoteType) {
        if (!array_key_exists($promoteType, $this->promoteTypes)) {
            $this->setData('ErrorMessage', t('No such groups listing.'));
        } else {
            $this->promoteType = $promoteType;

            // explicitly set the properties.
            $this->title = $this->promoteTypes[$this->promoteType]['title'];
            $this->url = $this->promoteTypes[$this->promoteType]['url'];
            $this->orderBy = $this->promoteTypes[$this->promoteType]['orderBy'];
        }
        return $this;
    }

    /**
     * Retrieve the groups for this module.
     *
     * @param string $promoteType
     * @param string $limit
     * @param string $attachLastDiscussion
     * @param string $orderBy
     * @return array|null
     */
    private function getData($promoteType, $limit, $attachLastDiscussion, $orderBy) {
        //get groups
        $groupModel = new GroupModel();
        if ($promoteType === 'mine' && Gdn::session()->UserID > 0) {
            $groups = $groupModel->getByUser(Gdn::session()->UserID, $orderBy, 'desc', $limit);
        }
        else {
            $groups = $groupModel->get($orderBy, 'desc', $limit)->resultArray();
        }
        if ($attachLastDiscussion) {
            $groupModel->joinRecentPosts($groups);
        }
        return $groups;
    }

    /**
     * Renders the promoted groups list.
     *
     * @return string HTML view
     */
    public function toString() {
        $groups = $this->getData($this->promoteType, $this->limit, $this->attachLastDiscussion, $this->orderBy);
        $groupList = new GroupListModule($groups, $this->promoteType, $this->title, t("There aren't any groups yet."), 'groups-'.$this->promoteType);
        $groupList->setView($this->getView());
        return $groupList->toString();
    }

}