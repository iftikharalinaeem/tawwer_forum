<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Models;

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\Knowledge\KnowledgeStructure;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\NavigationCacheProcessor;
use Vanilla\Site\TranslationModel;
use Vanilla\TranslationsApi\Models\TranslationPropertyModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Tests for the navigation cache.
 */
class NavigationCacheTest extends KbApiTestCase {

    protected static $enabledLocales = ['vf_fr' => 'fr', 'vf_es' => 'es', 'vf_ru' => 'ru'];
    protected static $addons = ['vanilla', 'translationsapi', 'knowledge'];

    /**
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        $container
            ->rule(ArticleModel::class)
            ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
            ->rule(ArticleRevisionModel::class)
            ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
            ->rule(KnowledgeCategoryModel::class)
            ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
            ->rule(KnowledgeBaseModel::class)
            ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
            ->rule(TranslationPropertyModel::class) // If the plugins not enabled it doesn't matter, since just a rule.
            ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
        ;
    }

    /**
     * Setup the cache.
     */
    public function setUp(): void {
        parent::setUp();

        self::container()->setInstance(\Gdn_Cache::class, new \Gdn_Dirtycache());
    }

    /**
     * Assert that an API response was a cache hit.
     *
     * @param array $kb
     * @param array $params
     * @param string|null $message
     * @param bool $expected
     */
    private function assertCached(array $kb, array $params = [], string $message = '', bool $expected = true) {
        $kbID = $kb['knowledgeBaseID'];
        $tail = ($params['tree'] ?? false) ? 'navigation-tree' : 'navigation-flat';
        $response = $this->api()->get("/knowledge-bases/$kbID/$tail", $params);

        $this->assertSame($expected ? '1' : '0', $response->getHeader('X-App-Cache-Hit') ?: '0', $message);
    }

    /**
     * Assert that an API response was a cache miss.
     *
     * @param array $kb
     * @param array $params
     * @param string|null $message
     */
    private function assertNotCached(array $kb, array $params = [], string $message = '') {
        $this->assertCached($kb, $params, $message, false);
    }

    /**
     * Test that caching flat clears the cache.
     */
    public function testCacheFlat() {
        $kb = $this->createKnowledgeBase();
        $this->createCategory(['name' => 'cat1']);
        $this->createArticle(['name' => 'article1']);

        $this->assertNotCached($kb);

        // Call again
        $this->assertCached($kb);
    }

    /**
     * Test that navigations are cached independantly.
     */
    public function testIndependentCaches() {
        $kb = $this->createKnowledgeBase();
        $this->createCategory(['name' => 'cat2']);
        $this->createArticle(['name' => 'article2']);
        $kb2 = $this->createKnowledgeBase();
        $this->createCategory(['name' => 'cat2']);
        $this->createArticle(['name' => 'article2']);

        $this->assertNotCached($kb);
        $this->assertNotCached($kb, ['tree' => true]);
        $this->assertNotCached($kb, ['tree' => true, 'locale' => 'fr']);
        $this->assertNotCached($kb2);
    }

    /**
     * Test that article actions clear the cache.
     */
    public function testArticleCacheClearing() {
        $kb = $this->createKnowledgeBase();
        $category = $this->createCategory(['name' => 'cat1']);
        $article = $this->createArticle(['name' => 'article1']);

        $articleID = $article['articleID'];

        $this->assertNotCached($kb);
        $this->assertCached($kb);

        $this->createArticle();
        $this->assertNotCached($kb, [], "Creating a new article clears the cache");

        $this->api()->patch("/articles/$articleID", ['name' => 'articleModif']);
        $this->assertNotCached($kb, [], "Modifying an article clears the cache");
    }

    /**
     * Test that category actions clear the cache.
     */
    public function testCategoryCacheClearing() {
        $kb = $this->createKnowledgeBase();
        $category = $this->createCategory(['name' => 'cat1']);
        $article = $this->createArticle(['name' => 'article1']);

        $categoryID = $category['knowledgeCategoryID'];

        $this->assertNotCached($kb);
        $this->assertCached($kb);

        $this->createCategory();
        $this->assertNotCached($kb, [], "Creating a new category clears the cache");

        $this->api()->patch("/knowledge-categories/$categoryID", ['name' => 'categoryModif']);
        $this->assertNotCached($kb, [], "Modifying a category clears the cache");
    }

    /**
     * Test that kb actions clear the cache.
     */
    public function testKnowledgeBaseCacheClearing() {
        $kb = $this->createKnowledgeBase();
        $category = $this->createCategory(['name' => 'cat1']);
        $article = $this->createArticle(['name' => 'article1']);

        $kbID = $category['knowledgeBaseID'];

        $this->assertNotCached($kb);
        $this->assertCached($kb);

        $this->createKnowledgeBase();
        $this->assertNotCached($kb, [], "Creating a new knowledge base clears the cache");

        $this->api()->patch("/knowledge-bases/$kbID", ['name' => 'kbModif']);
        $this->assertNotCached($kb, [], "Modifying a knowledge base clears the cache");
    }

    /**
     * Test that translation actions clear the cache.
     */
    public function testTranslationsCacheClear() {
        $kb = $this->createKnowledgeBase();
        $category = $this->createCategory(['name' => 'cat1']);
        $article = $this->createArticle(['name' => 'article1']);

        $this->assertNotCached($kb);
        $this->assertCached($kb);

        $this->api()->patch('/translations/kb', [[
            'recordType' => 'knowledgeCategory',
            'recordID' => $article['knowledgeCategoryID'],
            'locale' => 'fr',
            'propertyName' => 'name',
            'translation' => 'translated-fr',
        ]]);
        $this->assertNotCached($kb, [], "Patching a translation resource clears the cache.");
    }

    /**
     * Test that utility update clears the cache.
     */
    public function testUtilityUpdateClearCache() {
        $kb = $this->createKnowledgeBase();
        $category = $this->createCategory(['name' => 'cat1']);
        $article = $this->createArticle(['name' => 'article1']);

        $this->assertNotCached($kb);
        $this->assertCached($kb);

        /** @var KnowledgeStructure $plugin */
        $structure = self::container()->get(KnowledgeStructure::class);
        $structure->run();

        $this->assertNotCached($kb);
    }
}
