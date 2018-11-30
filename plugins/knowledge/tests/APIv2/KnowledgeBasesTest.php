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
        'type',
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
     * Grab values for inserting a new knowledge category.
     *
     * @return array
     */
    public function record() {
        $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base DESCRIPTION',
            'type' => 'guide',
            'icon' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => '',
        ];
        return $record;
    }

    public function testKnowledgeBaseEntity() {
        $kb = new KnowledgeBaseEntity(['name'=>'test']);
        $kb->name = 'Test Knowledge Baser Name';
        $this->assertEquals('Test Knowledge Baser Name', $kb->name);
        $this->expectException(\TypeError::class);
        $kb->name = ['Test Knowledge Baser Name'];
        $kb->description = 'Knowledge Baser TestDescription';
        $this->assertEquals('Knowledge Baser TestDescription', $kb->description);
        $this->expectException(\TypeError::class);
        $kb->description = 123;

        //$this->assertEquals('Test Knowledge Baser Name', $kb->name);
    }

}
