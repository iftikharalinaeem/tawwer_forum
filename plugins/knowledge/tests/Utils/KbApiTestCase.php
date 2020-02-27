<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Utils;

use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test case with utilities for knowledge.
 */
class KbApiTestCase extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'knowledge'];

    /** @var int|null */
    protected $lastInsertedKbID = null;

    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /**
     * Clear local info between tests.
     */
    public function setUp(): void {
        parent::setUp();

        $this->lastInsertedCategoryID = null;
        $this->lastInsertedKbID = null;
    }

    /**
     * Create a knowledge base.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createKnowledgeBase(array $overrides = []): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $params = $overrides + [
                'name' => 'test-kb',
                'urlCode' => 'test-kb-unique' . $salt,
                'viewType' => 'guide',
                'sortArticles' => 'manual',
                'description' => 'Hello world',
            ];
        $result = $this->api()->post('/knowledge-bases', $params)->getBody();
        $this->lastInsertedKbID = $result['knowledgeBaseID'];
        $this->lastInsertedCategoryID = $result['rootCategoryID'];
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
        $categoryID = $overrides['parentID'] ?? $this->lastInsertedCategoryID;

        if ($categoryID === null) {
            throw new \Exception('Could not insert a test category because a parent knowledgeCategoryID was not available');
        }

        $params = $overrides + [
                'name' => 'test-category',
                'parentID' => $this->lastInsertedCategoryID,
            ];
        $result = $this->api()->post('/knowledge-categories', $params)->getBody();
        $this->lastInsertedCategoryID = $result['knowledgeCategoryID'];
        return $result;
    }

    /**
     * Create an article.
     *
     * @param array $overrides Fields to override on the insert.
     *
     * @return array
     */
    public function createArticle(array $overrides = []): array {
        $categoryID = $overrides['knowledgeCategoryID'] ?? $this->lastInsertedCategoryID;

        if ($categoryID === null) {
            throw new \Exception('Could not insert a test article because a knowledgeCategoryID was not available');
        }

        $params = $overrides + [
                'name' => 'Hello article',
                'body' => 'Hello article',
                'format' => TextFormat::FORMAT_KEY,
                'locale' => 'en',
                'knowledgeCategoryID' => $categoryID,
            ];
        return $this->api()->post('/articles', $params)->getBody();
    }

    /**
     * Truncate all KB tables.
     */
    public function truncateTables() {
        $tables = ['knowledgeBase', 'knowledgeCategory', 'article', 'articleRevision'];

        foreach ($tables as $table) {
            \Gdn::sql()->truncate($table);
        }
    }

    /**
     * Assert that items in 2 arrays are in the same order (articleID checking only).
     *
     * @param array $expectedRowOrder
     * @param array $actualRowOrder
     */
    protected function assertArticleOrder(array $expectedRowOrder, array $actualRowOrder) {
        /**
         * Convert an array of rows into an articleID stirng.
         *
         * @param array $rows
         * @return string
         */
        $transformToArticleIDString = function (array $rows) {
            $ids = [];
            foreach ($rows as $row) {
                $ids[] = $row['articleID'] . '--' . $row['name'];
            }
            return '[' . implode(', ', $ids) . ']';
        };

        $this->assertEquals($transformToArticleIDString($expectedRowOrder), $transformToArticleIDString($actualRowOrder));
    }
}
