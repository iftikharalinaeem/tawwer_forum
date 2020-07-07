<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Polls\Models\PollSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;
use Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\CategoryAndDiscussionApiTestTrait;

/**
 * Class PollSearchTypeTest
 */
class PollSearchTypeTest extends AbstractAPIv2Test {
    use SphinxTestTrait, CategoryAndDiscussionApiTestTrait;

    protected static $addons = ['vanilla', 'advancedsearch', 'sphinx', 'polls'];

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);
        saveToConfig('Plugins.Sphinx.UseDeltas', true);

        self::container()
            ->rule(SearchService::class)
            ->addCall('registerActiveDriver', [new \Garden\Container\Reference(SphinxSearchDriver::class)])
            ->rule(SphinxSearchDriver::class)
            ->addCall('registerSearchType', [new \Garden\Container\Reference(DiscussionSearchType::class)])
            ->addCall('registerSearchType', [new \Garden\Container\Reference(PollSearchType::class)])
        ;
    }

    /**
     * Clear info between tests.
     */
    public function setUp(): void {
        parent::setUp();
    }
    /**
     * @inheritdoc
     */
    public static function teardownAfterClass(): void {
        FeatureFlagHelper::clearCache();
    }

    /**
     * Test searching for Poll types
     */
    public function testSearchPollType() {
        /** @var PollsApiController $pollsApiController */
        $pollsApiController = Gdn::getContainer()->get(PollsApiController::class);
        $category = $this->createCategory([
            'name' => 'Polls Category',
            'urlCode' => 'pollstest',
        ]);

        $discussion1 = $this->createDiscussion([
            'name' => 'Polls test discussion1',
            'body' => 'Polls Test',
            'format' => 'markdown',
            'categoryID' => $category['categoryID'],
        ]);

        $pollsApiController->post([
            'name' => 'Poll 1',
            'discussionID' => $discussion1['discussionID']
        ]);

        $discussion2 = $this->createDiscussion([
            'name' => 'Polls test discussion2',
            'body' => 'Polls Test',
            'format' => 'markdown',
            'categoryID' => $category['categoryID'],
        ]);

        $pollsApiController->post([
            'name' => 'Poll 2',
            'discussionID' => $discussion2['discussionID']
        ]);

        $discussion = $this->createDiscussion([
            'categoryID' => $category['categoryID'],
            'name' => 'Not A Poll',
            'body' => 'Discussion'
        ]);
        self::SphinxReindex();

        $params = [
            'query' => 'Polls',
            'recordTypes' => ['discussion'],
            'types' => ['poll'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());
        $results = $response->getBody();
        $this->assertEquals(2, count($results));
    }

    /**
     * Test searching for Poll and Discussions Types..
     */
    public function testSearchPollTypeWithDiscussion() {
        /** @var PollsApiController $pollsApiController */
        $pollsApiController = Gdn::getContainer()->get(PollsApiController::class);
        $this->resetTable('Category');
        $this->resetTable('Discussion');

        $category = $this->createCategory([
            'name' => 'Polls Category',
            'urlCode' => 'pollstest',
        ]);

        $discussion1 = $this->createDiscussion([
            'name' => 'Polls test discussion1',
            'body' => 'Polls Test',
            'format' => 'markdown',
            'categoryID' => $category['categoryID'],
        ]);

        $pollsApiController->post([
            'name' => 'Poll 1',
            'discussionID' => $discussion1['discussionID']
        ]);


        $discussion2 = $this->createDiscussion([
            'name' => 'Polls test discussion2',
            'body' => 'Polls Test',
            'format' => 'markdown',
            'categoryID' => $category['categoryID'],
        ]);

        $pollsApiController->post([
            'name' => 'Poll 2',
            'discussionID' => $discussion2['discussionID']
        ]);

        $discussion = $this->createDiscussion([
            'categoryID' => $category['categoryID'],
            'name' => 'Not A Poll',
            'body' => 'Discussion'
        ]);


        self::SphinxReindex();

        $params = [
            'query' => 'Polls',
            'recordTypes' => ['discussion'],
            'types' => ['poll', 'discussion'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(3, count($results));
    }
}
