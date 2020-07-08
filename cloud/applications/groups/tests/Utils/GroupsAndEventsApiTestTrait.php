<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Groups\Utils;

use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Http\InternalClient;

/**
 * @method InternalClient api()
 */
trait GroupsAndEventsApiTestTrait {

    /** @var int|null */
    protected $lastInsertedGroupID = null;

    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /** @var int|null */
    protected $lastInsertedEventID = null;

    /** @var int|null */
    protected $lastInsertedParentID = null;

    /** @var string|null */
    protected $lastInsertedParentType = null;

    /**
     * Clear local info between tests.
     */
    public function setUpGroupsAndEventsApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedEventID = null;
        $this->lastInsertedGroupID = null;
        $this->lastInsertedParentID = null;
    }

    /**
     * Create a knowledge base.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createGroup(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $params = $overrides + [
                'name' => 'Group' . $salt,
                'description' => 'Hello group description',
                'format' => TextFormat::FORMAT_KEY,
                'iconUrl' => null,
                'bannerUrl' => null,
                'privacy' => 'public',
            ];
        $result = $this->api()->post('/groups', $params)->getBody();
        $this->lastInsertedGroupID = $result['groupID'];
        $this->lastInsertedParentID = $result['groupID'];
        $this->lastInsertedParentType = 'group';
        return $result;
    }

    /**
     * Have the current user join a group.
     *
     * @param array $overrides
     *
     * @return array
     */
    public function joinGroup(array $overrides = []): array {
        $groupID = $overrides['groupID'] ?? $this->lastInsertedGroupID;

        if ($groupID === null) {
            throw new \Exception('Could not join a group because none was specified.');
        }

        $result = $this->api()->post("/groups/$groupID/join")->getBody();
        $this->clearGroupMemoryCache();
        return $result;
    }

    /**
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createCategory(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $name = "Test Category $salt";
        $categoryID = $overrides['parentCategoryID'] ?? $this->lastInsertedCategoryID;

        $params = $overrides + [
                'customPermissions' => false,
                'displayAs' => 'discussions',
                'parentCategoryID' => $categoryID,
                'name' => $name,
                'urlCode' => slugify($name)
            ];
        $result = $this->api()->post('/categories', $params)->getBody();
        $this->lastInsertedCategoryID = $result['categoryID'];
        $this->lastInsertedParentID = $result['categoryID'];
        $this->lastInsertedParentType = 'category';
        return $result;
    }

    /**
     * Create an article.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createEvent(array $overrides = []): array {
        $parentID = $overrides['parentRecordID'] ?? $this->lastInsertedParentID;
        $parentType = $overrides['parentRecordType'] ?? $this->lastInsertedParentType;

        if ($parentID === null || $parentType === null) {
            throw new \Exception('Could not insert a test event because no parent was specified.');
        }

        $params = $overrides + [
                'name' => 'Test Event',
                'dateStarts' => new \DateTime(),
                'format' => TextFormat::FORMAT_KEY,
                'body' => 'Hello Event',
                'location' => 'A Location',
                'parentRecordID' => $parentID,
                'parentRecordType' => $parentType,
            ];
        $result = $this->api()->post('/events', $params)->getBody();
        $this->lastInsertedEventID = $result['eventID'];
        return $result;
    }

    /**
     * Clear out existing events.
     *
     * @param string $parentRecordType
     * @param array $options
     */
    public function clearEvents($parentRecordType = '', $options = []) {
        /** @var \EventModel $model */
        $model = \Gdn::getContainer()->get(\EventModel::class);
        $where = [
            "parentRecordType" =>  $parentRecordType
        ];

        $where = array_merge($where, $options);

        $model->delete($where);
    }

    /**
     * Clear the in memory cache of group permissions.
     */
    protected function clearGroupMemoryCache() {
        /** @var \GroupModel $model */
        $model = \Gdn::getContainer()->get(\GroupModel::class);
        $model->resetCachedPermissions();
    }
}
