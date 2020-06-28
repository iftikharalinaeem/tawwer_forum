<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Garden\Web\Exception\NotFoundException;

/**
 * Test the /api/v2/articles/drafts endpoint.
 */
class ArticleDraftsTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/articles/drafts";

    /** @var string[] Fields to be checked with get/<id>/edit */
    protected $editFields = [
        "attributes",
        "parentRecordID",
    ];

    /** @var string[] An array of field names that are okay to send to patch endpoints. */
    protected $patchFields = [
        "attributes",
        "parentRecordID",
    ];

    /** @var string The name of the primary key of the resource. */
    protected $pk = "draftID";

    /** @var string The singular name of the resource. */
    protected $singular = "draft";

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    /**
     * The endpoint's index URL.
     *
     * @return string
     */
    public function indexUrl() {
        return $this->baseUrl . "?insertUserID=" . $this->api()->getUserID();
    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    public function modifyRow(array $row): array {
        $row["parentRecordID"] = $row["parentRecordID"] === null ? 1 : null;
        $attributes = $row["attributes"] ?? [];
        $format = $body["format"] ?? "markdown";
        $categoryID = intval($attributes["knowledgeCategoryID"] ?? 1);

        $attributes["name"] = md5(time());

        switch ($format) {
            case "markdown":
                $row["format"] = "rich";
                $row["body"] = '[{"insert":"Hello world.\n"}]';
                break;
            default:
                $row["format"] = "markdown";
                $row["body"] = "**Hello world**.";
        }

        $attributes["knowledgeCategoryID"] = ++$categoryID;

        return $row;
    }

    /**
     * Grab values for inserting a new article draft.
     *
     * @return array
     */
    public function record(): array {
        $record = [
            "attributes" => [
                "name" => self::class,
                "knowledgeCategoryID" => 1,
            ],
            "body" => "**Hello world**.",
            "format" => "markdown",
            "excerpt" => "Hello world.",
        ];
        return $record;
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "sphinx", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Prepare knowledge with status "deleted", root category, article (and draft if needed)
     *
     * @param bool $getDraft Flag to create and return "draft" record-array when true
     *                       and "article" record-array when false (default)
     *
     * @return array Record-array of "draft" or "article"
     */
    private function prepareDeletedKnowledgeBase(bool $getDraft = false): array {
        $kb = $this->newKnowledgeBase();

        $article = $this->api()->post(
            '/articles',
            [
                "body" => "Hello. I am a test for articles.",
                "format" => "markdown",
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "en",
                "name" => "Example Article",
                "sort" => 1,
            ]
        )->getBody();

        if ($getDraft) {
            $record = $this->record();
            $record['recordID'] = $article['articleID'];

            $draft = $this->api()->post(
                "{$this->baseUrl}",
                $record
            )->getBody();
        }

        $this->api()->patch(
            "/knowledge-bases/{$kb['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );

        return $getDraft ? $draft : $article;
    }

    /**
     * Create new knowledge base.
     *
     * @return array Knowledge base
     */
    public function newKnowledgeBase(): array {
        $salt = '-'.round(microtime(true) * 1000).rand(1, 1000);
        $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base '.$salt,
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-knowledge-base'.$salt,
        ];
        $kb = $this->api()
            ->post('/knowledge-bases', $record)
            ->getBody();
        return $kb;
    }

    /**
     * Overriding for this method, since this resource has no "edit" that would be different from the normal get-by-id,
     * but the method is used for several other tests.
     *
     * @param array|null $record A record to use for comparison.
     * @return array
     */
    public function testGetEdit($record = null) {
        if ($record === null) {
            $record = $this->record();
            $row = $this->testPost();
        } else {
            $row = $record;
        }

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $body = arrayTranslate($record, $this->editFields);
        $this->assertCamelCase($body);
        $body[$this->pk] = $row[$this->pk];

        return $body;
    }

    /**
     * Test PATCH /resource/<id> with a a single field update.
     *
     * Patch endpoints should be able to update every field on its own.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        $row = $this->testGetEdit();
        $patchRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [$field => $patchRow[$field]]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
        $this->assertSame($patchRow[$field], $newRow[$field]);
    }

    /**
     * {@inheritdoc}
     */
    public function testEditFormatCompat(string $editSuffix = "unused") {
        // Pass in an empty edit suffix. We don't have a get_edit for drafts. Just get.
        parent::testEditFormatCompat("");
    }

    /**
     * Test POST /articles/drafts when knowledge base has status "deleted"
     */
    public function testPostDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $record = $this->record();
        $record['recordID'] = $article['articleID'];
        $this->expectException(NotFoundException::class);

        $r = $this->api()->post(
            "{$this->baseUrl}",
            $record
        );
    }

    /**
     * Test PATCH /articles/drafts/<draftID> when knowledge base has status "deleted"
     */
    public function testPatchDeleted() {
        $draft = $this->prepareDeletedKnowledgeBase(true);

        $record = $this->record();
        $record['recordID'] = $draft['recordID'];

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$draft['draftID']}",
            $record
        );
        $this->assertEquals(200, $r->getStatusCode());
    }

    /**
     * Test GET /articles/drafts/<draftID> when knowledge base has status "deleted"
     */
    public function testGetDeleted() {
        $draft = $this->prepareDeletedKnowledgeBase(true);

        $r = $this->api()->get(
            "{$this->baseUrl}/{$draft['draftID']}"
        );
        $this->assertEquals(200, $r->getStatusCode());
    }

    /**
     * Test GET /articles/drafts when knowledge base has status "deleted"
     */
    public function testIndexDeleted() {
        $draft = $this->prepareDeletedKnowledgeBase(true);
        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            "{$this->baseUrl}",
            ['articleID' => $draft['recordID']]
        );
    }

    /**
     * Test DELETE /articles/drafts when knowledge base has status "deleted"
     */
    public function testDeleteDeleted() {
        $draft = $this->prepareDeletedKnowledgeBase(true);

        $r = $this->api()->delete(
            "{$this->baseUrl}/{$draft['draftID']}"
        );

        $this->assertEquals(204, $r->getStatusCode());
    }
}
