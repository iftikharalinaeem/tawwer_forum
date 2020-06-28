<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\FeatureFlagHelper;
use Vanilla\Knowledge\Models\KnowledgeArticleSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;

/**
 * Class unified search test.
 */
class KnowledgeArticleSearchTypeTest extends SphinxUnifiedSearchKBTest {

    /**
     * Apply some container configuration.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        $container
            ->rule(SearchService::class)
            ->addCall('registerActiveDriver', [new Reference(SphinxSearchDriver::class)])
            ->rule(SphinxSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(KnowledgeArticleSearchType::class)])
        ;
    }

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);
    }

    /**
     * @inheritdoc
     */
    public static function teardownAfterClass(): void {
        FeatureFlagHelper::clearCache();
    }
}
