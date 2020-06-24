<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Tests for endpoints including subcategories.
 */
class KnowledgeIncludeSubcategoriesTest extends KbApiTestCase {

    /**
     * Test that the article index includes subcategories.
     */
    public function testArticleSubcategories() {
        $this->createKnowledgeBase();
        $cat1 = $this->createCategory();
        $this->createArticle();
        $cat1_1 = $this->createCategory();
        $this->createArticle();
        $this->createArticle();
        $this->createArticle();
        $this->createArticle();

        $response = $this->api()->get('/articles', ['knowledgeCategoryID' => $cat1['knowledgeCategoryID']]);
        $this->assertCount(1, $response->getBody());

        $response = $this->api()->get('/articles', [
            'knowledgeCategoryID' => $cat1['knowledgeCategoryID'],
            'includeSubcategories' => true,
        ]);
        $this->assertCount(5, $response->getBody());

        $response = $this->api()->get('/articles', [
            'knowledgeCategoryID' => $cat1_1['knowledgeCategoryID'],
            'includeSubcategories' => true,
        ]);
        $this->assertCount(4, $response->getBody());
    }

    /**
     * Test that the article index includes subcategories.
     */
    public function testHelpCenterNav() {
        $kb = $this->createKnowledgeBase([
            'viewType' => KnowledgeBaseModel::TYPE_HELP,
            'sortArticles' => KnowledgeBaseModel::ORDER_NAME,
        ]);
        $cat1 = $this->createCategory();
        $ar1 = $this->createArticle();
        $ar2 = $this->createArticle();
        $ar3 = $this->createArticle();
        $ar4 =$this->createArticle();
        $cat1_1 = $this->createCategory();
        $ar5 = $this->createArticle();
        $ar6 = $this->createArticle();

        $kbID = $kb['knowledgeBaseID'];
        $response = $this->api()->get("/knowledge-bases/$kbID/navigation-flat")->getBody();
        // 3 category and ONLY 5 articles.
        $this->assertCount(8, $response);
    }
}
