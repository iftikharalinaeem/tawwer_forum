<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Reference;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;

/**
 * Class unified search test
 */
class KnowledgeArticleSearchTypeTest extends SphinxUnifiedSearchKBTest {

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);
        self::container()->rule(SearchService::class)
            ->addCall('registerActiveDriver', [new Reference(SphinxSearchDriver::class)]);
        self::container()->rule(SphinxSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(DiscussionSearchType::class)])
            ->addCall('registerSearchType', [new Reference(CommentSearchType::class)]);
    }

    /**
     * @inheritdoc
     */
    public static function teardownAfterClass(): void {
        FeatureFlagHelper::clearCache();
    }
}
