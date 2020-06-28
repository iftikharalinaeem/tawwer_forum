<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test deleted articles from the articles API.
 */
class ArticleDeletedTest extends KbApiTestCase {

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "sphinx", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Test GET /articles/<id> when knowledge base has status "deleted"
     */
    public function testGetDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            "/articles/{$article['articleID']}"
        );
    }

    /**
     * Test POST /articles when knowledge base has status "deleted"
     */
    public function testPostDeleted() {
        $kb = $this->createKnowledgeBase();
        $record = $this->createArticle();

        $this->api()->patch(
            "/knowledge-bases/{$kb['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );
        $this->expectException(NotFoundException::class);
        $this->createArticle();
    }

    /**
     * Test GET /articles/<id>/edit when knowledge base has status "deleted"
     */
    public function testGetEditDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            "/articles/{$article['articleID']}/edit"
        );
    }

    /**
     * Test PATCH /articles/<id> when knowledge base has status "deleted".
     */
    public function testPatchDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->patch(
            "/articles/{$article['articleID']}",
            ['name' => 'Patched test article']
        );
    }

    /**
     * Test PUT /articles/<id>/react when knowledge base has status "deleted".
     */
    public function testPutReactHelpfulDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->put(
            "/articles/{$article['articleID']}/react",
            ['helpful' => 'yes']
        );
    }

    /**
     * Test PATCH /articles/<id>/status when knowledge base has status "deleted".
     */
    public function testPatchStatusDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->patch(
            "/articles/{$article['articleID']}/status",
            ['status' => 'deleted']
        );
    }

    /**
     * Prepare knowledge with status "deleted", root category and article
     *
     * @return array Record-array of "draft" or "article"
     */
    private function prepareDeletedKnowledgeBase() {
        $kb = $this->createKnowledgeBase();
        $article = $this->createArticle();

        $this->api()->patch(
            "/knowledge-bases/{$kb['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );

        return $article;
    }
}
