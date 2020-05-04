<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Groups\Utils;

use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\InternalClient;
use VanillaTests\SiteTestTrait;

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
    public function setUp(): void {
        parent::setUp();

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
     * Create a category.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createCategory(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $name = "Test Category $salt";
        $categoryID = $overrides['parentID'] ?? $this->lastInsertedCategoryID;

        if ($categoryID === null) {
            throw new \Exception('Could not insert a test category because a parent knowledgeCategoryID was not available');
        }

        $params = $overrides + [
                'customPermissions' => false,
                'displayAs' => 'discussions',
                'parentCategoryID' => null,
                'name' => $name,
                'slug' => slugify($name)
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
     * Truncate all KB tables.
     */
    public function truncateTables() {
        $tables = ['group', 'event', 'category'];

        foreach ($tables as $table) {
            \Gdn::sql()->truncate($table);
        }
    }
}
