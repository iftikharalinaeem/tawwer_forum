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

    /**
     * Assert that an API response was a cache hit.
     */
    public function testGroupCategoriesByTopLevel() {
        $cat0 = [
            'name' => '0',
            'knowledgeCategoryID' => 0,
            'parentID' => -1,
        ];
        $cat1 = [
            'name' => '1',
            'knowledgeCategoryID' => 1,
            'parentID' => 0,
        ];
        $cat1_1 = [
            'name' => '1.1',
            'knowledgeCategoryID' => 11,
            'parentID' => 1,
        ];
        $cat1_1_1 = [
            'name' => '1.1.1',
            'knowledgeCategoryID' => 111,
            'parentID' => 11,
        ];
        $cat2 = [
            'name' => '2',
            'knowledgeCategoryID' => 2,
            'parentID' => 0,
        ];
        $cat3 = [
            'name' => '3',
            'knowledgeCategoryID' => 3,
            'parentID' => 0,
        ];
        $cat3_1 = [
            'name' => '3.1',
            'knowledgeCategoryID' => 31,
            'parentID' => 3,
        ];
        $categories = [
            $cat0,
            $cat1,
            $cat1_1,
            $cat1_1_1,
            $cat2,
            $cat3,
            $cat3_1,
        ];

        $collection = new KnowledgeCategoryCollection($categories);

        $this->assertSame(
            [
                [$cat0],
                [$cat1, $cat1_1, $cat1_1_1],
                [$cat2],
                [$cat3, $cat3_1],
            ],
            $collection->groupCategoriesByTopLevel()
        );
    }
}
