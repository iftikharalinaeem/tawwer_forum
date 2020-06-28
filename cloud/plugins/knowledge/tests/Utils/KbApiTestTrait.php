<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Utils;

use Garden\Http\HttpResponse;
use Vanilla\Formatting\Formats\TextFormat;

/**
 * Test case with utilities for knowledge.
 */
trait KbApiTestTrait {

    /** @var int|null */
    protected $lastInsertedKbID = null;

    /** @var int|null */
    protected $lastInsertedCategoryID = null;

    /** @var HttpResponse */
    protected $lastResponse;

    /**
     * Clear local info between tests.
     */
    public function setUpKbApiTestTrait(): void {
        $this->lastInsertedCategoryID = null;
        $this->lastInsertedKbID = null;
        $this->lastResponse = null;
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
        $this->lastResponse = $this->api()->post('/knowledge-bases', $params);
        $result = $this->lastResponse->getBody();
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
        $this->lastResponse = $this->api()->post('/knowledge-categories', $params);
        $result = $this->lastResponse->getBody();
        $this->lastInsertedCategoryID = $result['knowledgeCategoryID'];
        return $result;
    }

    /**
     * Create an article.
     *
     * @param array $overrides Fields to override on the insert.
     * @param array $extraLocales
     *
     * @return array
     */
    public function createArticle(array $overrides = [], array $extraLocales = []): array {
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
        $this->lastResponse = $this->api()->post('/articles', $params);
        $article = $this->lastResponse->getBody();

        foreach ($extraLocales as $locale) {
            $this->api()->patch("/articles/" . $article["articleID"], [
                'locale' => $locale,
                'name' => $article['name'] . ' ' . $locale,
                'body' => $article['body'] . ' ' . $locale,
                'format' => 'markdown',
            ]);
        }

        return $article;
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
