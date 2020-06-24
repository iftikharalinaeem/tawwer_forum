<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Vanilla\Sphinx;

use Garden\Container\Reference;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Search\GlobalSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;

/**
 * Tests for sphinx search API with the service feature flag.
 */
class NewSphinxSearchTest extends \SphinxSearchTest {

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        FeatureFlagHelper::clearCache();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);
        self::container()
            ->rule(SearchService::class)
            ->addCall('registerActiveDriver', ['driver' => new Reference(SphinxSearchDriver::class)])
            ->rule(SphinxSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(GlobalSearchType::class)])
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
