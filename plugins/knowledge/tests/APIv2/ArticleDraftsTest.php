<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\ArticleDraft;

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
            "excerpt" => "**Hello world**.",
        ];
        return $record;
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
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
     * Test ArticleDraft::getExcerpt with few scenarios
     *
     * @param string $body Plain body to truncate
     * @param string $excerpt Expected excerpt to be returned
     * @dataProvider provideExcerpts
     */
    public function testGetExcerpt(string $body, string $excerpt) {
        $res = ArticleDraft::getExcerpt($body);
        $this->assertEquals($excerpt, $res);
    }

    /**
     * Data provider for testGetExcerpt
     *
     * @return array
     */
    public function provideExcerpts(): array {
        return [
            'Short body' => [
                'Test body just few words. Nothing to truncate.',
                'Test body just few words. Nothing to truncate.'
            ],
            'Short but dirty body' => [
                "Test body just few words. But few      spaces. And few \n \n \n new lines to truncate.",
                'Test body just few words. But few spaces. And few new lines to truncate.'
            ],
            'Long body' => [
                '123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 '
                .'1st line was 100 characters long '
                .'123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 '
                .'2nd line was 100 characters too '
                .'123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 '
                .'Now we have enough to truncate '
                ,
                '123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 '
                .'1st line was 100 characters long '
                .'123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789 '
                .'2nd line was 100 characters too '
                .'123456789 123456789 123456789 123456789 123456789 123456789…'
            ]
        ];
    }
}
