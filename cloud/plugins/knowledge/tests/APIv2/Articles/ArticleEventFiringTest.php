<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Garden\Events\ResourceEvent;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Knowledge\Models\ArticleModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test that various article actions trigger resource events.
 */
class ArticleEventFiringTest extends KbApiTestCase {

    use EventSpyTestTrait;

    /**
     * Test that creating a new article fires an event.
     */
    public function testNewArticleEvent() {
        $this->createKnowledgeBase();
        $this->createArticle([
            'name' => 'New Article',
        ]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_INSERT, [
                'name' => 'New Article',
                'articleID' => 1,
                'locale' => 'en',
            ]),
        ], ['name', 'articleID', 'locale']);
    }

    /**
     * Test that updating an article triggers an update event.
     */
    public function testUpdateArticleEvent() {
        $this->createKnowledgeBase();
        $article = $this->createArticle([
            'name' => 'New Article',
        ]);

        $this->api()->patch("/articles/{$article['articleID']}", [
            'name' => 'Updated Name',
        ]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Updated Name',
                'articleID' => 1,
                'locale' => 'en',
            ]),
        ], ['name', 'articleID', 'locale']);
    }

    /**
     * Test that creating a new locale variant for an article triggers a new article event.
     */
    public function testNewLocaleArticleEvent() {
        $this->createKnowledgeBase([
            'siteSectionGroup' => 'mockSiteSectionGroup-1',
        ]);
        $article = $this->createArticle([
            'name' => 'New Article',
        ]);

        $this->api()->patch("/articles/{$article['articleID']}", [
            'name' => 'Name in Fr',
            'body' => 'Hello world',
            'format' => TextFormat::FORMAT_KEY,
            'locale' => 'fr'
        ]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_INSERT, [
                'name' => 'Name in Fr',
                'articleID' => 1,
                'locale' => 'fr',
            ]),
        ], ['name', 'articleID', 'locale']);
    }

    /**
     * Test that setting a status for an article sends an update for all article locale variants.
     */
    public function testPatchStatus() {
        $this->createKnowledgeBase([
            'siteSectionGroup' => 'mockSiteSectionGroup-1',
        ]);
        $article = $this->createArticle([
            'name' => 'Name in En',
        ]);

        $this->api()->patch("/articles/{$article['articleID']}", [
            'name' => 'Name in Fr',
            'body' => 'Hello world',
            'format' => TextFormat::FORMAT_KEY,
            'locale' => 'fr'
        ]);

        $this->clearDispatchedEvents();

        $this->api()->patch("/articles/{$article['articleID']}/status", ['status' => ArticleModel::STATUS_DELETED]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in En',
                'locale' => 'en',
                'status' => ArticleModel::STATUS_DELETED,
            ]),
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in Fr',
                'locale' => 'fr',
                'status' => ArticleModel::STATUS_DELETED,
            ]),
        ], ['articleID', 'locale', 'status'], true);

        $this->clearDispatchedEvents();
        $this->api()->patch("/articles/{$article['articleID']}/status", ['status' => ArticleModel::STATUS_PUBLISHED]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in En',
                'locale' => 'en',
                'status' => ArticleModel::STATUS_PUBLISHED,
            ]),
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in Fr',
                'locale' => 'fr',
                'status' => ArticleModel::STATUS_PUBLISHED,
            ]),
        ], ['articleID', 'locale', 'status'], true);
    }

    /**
     * Test that setting the article as featured dispatches a change for all article locale variations.
     */
    public function testPutFeatured() {
        $this->createKnowledgeBase([
            'siteSectionGroup' => 'mockSiteSectionGroup-1',
        ]);
        $article = $this->createArticle([
            'name' => 'Name in En',
        ]);

        $this->api()->patch("/articles/{$article['articleID']}", [
            'name' => 'Name in Fr',
            'body' => 'Hello world',
            'format' => TextFormat::FORMAT_KEY,
            'locale' => 'fr'
        ]);

        $this->clearDispatchedEvents();

        $this->api()->put("/articles/{$article['articleID']}/featured", ['featured' => true]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in En',
                'locale' => 'en',
                'featured' => true,
            ]),
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in Fr',
                'locale' => 'fr',
                'featured' => true,
            ]),
        ], ['name', 'locale', 'featured'], true);

        $this->clearDispatchedEvents();
        $this->api()->put("/articles/{$article['articleID']}/featured", ['featured' => false]);

        $this->assertEventsDispatched([
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in En',
                'locale' => 'en',
                'featured' => false,
            ]),
            $this->expectedResourceEvent('article', ResourceEvent::ACTION_UPDATE, [
                'name' => 'Name in Fr',
                'locale' => 'fr',
                'featured' => false,
            ]),
        ], ['name', 'locale', 'featured'], true);
    }
}
