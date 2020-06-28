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

    /**
     * Test that explicit sorting works.
     */
    public function testSorts() {
        /** @var \DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(\DiscussionModel::class);
        $discussion7_1 = $discussionModel->save([
            'Name' => '3 sort test 7 1 relevance',
            'Body' => '7 1',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00"
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => '1 sort test 7 2 relevance important',
            'Body' => '7 2',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00"
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => '2 sort test 7 3 relevance',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-03 12:00:00"
        ]);

        $this->sphinxReindex();

        $this->assertSearchResultIDs([
            'query' => 'sort test',
            'sort' => 'dateInserted'
        ], [$discussion7_3, $discussion7_2, $discussion7_1]);

        $this->assertSearchResultIDs([
            'query' => 'sort test',
            'sort' => '-dateInserted'
        ], [$discussion7_1, $discussion7_2, $discussion7_3]);

        $this->assertSearchResultIDs([
            'query' => 'sort test relevance important',
            'sort' => 'relevance'
        ], [$discussion7_2, $discussion7_3, $discussion7_1]);

        $this->assertSearchResultIDs([
            'query' => 'sort test relevance important',
        ], [$discussion7_2, $discussion7_3, $discussion7_1]);
    }

    /**
     * @return array
     */
    public function provideSorts(): array {
        return [];
    }
}
