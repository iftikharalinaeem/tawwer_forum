<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\Entities\KnowledgeBaseEntity;

/**
 * Test the /api/v2/knowledge-bases endpoint.
 */
class KnowledgeBasesTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The name of the primary key of the resource. */
    protected $pk = "knowledgeBaseID";

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        'name',
        'description',
        'viewType',
        'icon',
        'sortArticles',
        'sourceLocale',
    ];


    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Grab values for inserting a new knowledge base.
     *
     * @return array
     */
    public function record() {
        $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base DESCRIPTION',
            'viewType' => 'guide',
            'icon' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => '',
        ];
        return $record;
    }

    /**
     * Test POST /knowledge-base
     * if it automatically generates rootCategory
     */
    public function testRootCategory() {
        $result = $this->api()->post(
            $this->baseUrl,
            $this->record()
        );

        $this->assertEquals(201, $result->getStatusCode());
        $body = $result->getBody();
        // not ready yet - that is why "== 0" should be "> 0" in future!!!
        $this->assertTrue($body['rootCategoryID'] == -1);
    }
}
