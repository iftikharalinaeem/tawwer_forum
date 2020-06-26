<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Models;

use Vanilla\Knowledge\Models\KnowledgeCategoryCollection;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Tests for the navigation cache.
 */
class KnowledgeCategoryCollectionTest extends KbApiTestCase {
    const CAT_0 = [
        'name' => '0',
        'knowledgeCategoryID' => 0,
        'parentID' => -1,
    ];
    const CAT_1 = [
        'name' => '1',
        'knowledgeCategoryID' => 1,
        'parentID' => 0,
    ];
    const CAT_1_1 = [
        'name' => '1.1',
        'knowledgeCategoryID' => 11,
        'parentID' => 1,
    ];
    const CAT_1_1_1 = [
        'name' => '1.1.1',
        'knowledgeCategoryID' => 111,
        'parentID' => 11,
    ];
    const CAT_2 = [
        'name' => '2',
        'knowledgeCategoryID' => 2,
        'parentID' => 0,
    ];
    const CAT_3 = [
        'name' => '3',
        'knowledgeCategoryID' => 3,
        'parentID' => 0,
    ];
    const CAT_3_1 = [
        'name' => '3.1',
        'knowledgeCategoryID' => 31,
        'parentID' => 3,
    ];

    const ALL_CATEGORIES = [
        self::CAT_0,
        self::CAT_1,
        self::CAT_1_1,
        self::CAT_1_1_1,
        self::CAT_2,
        self::CAT_3,
        self::CAT_3_1,
    ];

    /**
     * Assert that an API response was a cache hit.
     */
    public function testGroupCategoriesByTopLevel() {
        $collection = new KnowledgeCategoryCollection(self::ALL_CATEGORIES);

        $this->assertSame(
            [
                [self::CAT_0],
                [self::CAT_1, self::CAT_1_1, self::CAT_1_1_1],
                [self::CAT_2],
                [self::CAT_3, self::CAT_3_1],
            ],
            $collection->groupCategoriesByTopLevel()
        );
    }

    /**
     * Assert that an API response was a cache hit.
     */
    public function testGetForParentID() {
        $collection = new KnowledgeCategoryCollection(self::ALL_CATEGORIES);

        $this->assertSame(
            [self::CAT_1_1, self::CAT_1_1_1],
            $collection->getWithChildren(self::CAT_1_1['knowledgeCategoryID'])
        );

        $this->assertSame(
            [self::CAT_1, self::CAT_1_1, self::CAT_1_1_1],
            $collection->getWithChildren(self::CAT_1['knowledgeCategoryID'])
        );

        $this->assertSame(
            [self::CAT_2],
            $collection->getWithChildren(self::CAT_2['knowledgeCategoryID'])
        );
    }
}
