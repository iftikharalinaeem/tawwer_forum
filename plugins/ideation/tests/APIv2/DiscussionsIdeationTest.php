<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use AttachmentModel;
use CategoryModel;
use StatusModel;

/**
 * Test managing ideas with the /api/v2/discussions endpoint.
 */
class DiscussionsIdeationTest extends AbstractAPIv2Test {

    /** @var int Category ID associated with an idea category. */
    private static $categoryID;

    /** @var string[] */
    private $patchFields = ['statusID', 'statusNotes'];

    /**
     * Assert an array has all necessary idea fields.
     *
     * @param array $discussion
     */
    private function assertIsIdea($discussion) {
        $this->assertIsArray($discussion);

        $this->assertArrayHasKey('type', $discussion);
        $this->assertEquals('idea', $discussion['type']);

        $this->assertArrayHasKey('attributes', $discussion);
        $this->assertArrayHasKey('idea', $discussion['attributes']);

        $this->assertArrayHasKey('statusID', $discussion['attributes']['idea']);
        $this->assertArrayHasKey('status', $discussion['attributes']['idea']);
        $this->assertArrayHasKey('statusNotes', $discussion['attributes']['idea']);
        $this->assertArrayHasKey('type', $discussion['attributes']['idea']);

        $this->assertIsArray($discussion['attributes']['idea']['status']);
        $this->assertArrayHasKey('name', $discussion['attributes']['idea']['status']);
        $this->assertArrayHasKey('state', $discussion['attributes']['idea']['status']);
    }

    /**
     * Modify idea metadata for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    private function modifyRowIdea(array $row) {
        $row['statusID']++;
        $row['statusNotes'] = md5($row['statusNotes']);

        return $row;
    }

    /**
     * Provide the patch fields in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function providePatchFields() {
        $r = [];
        foreach ($this->patchFields as $field) {
            $r[$field] = [$field];
        }
        return $r;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'ideation'];
        parent::setupBeforeClass();

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get('CategoryModel');
        static::$categoryID = $categoryModel->save([
            'Name' => 'Test Idea Category',
            'UrlCode' => 'test-idea-category',
            'InsertUserID' => self::$siteInfo['adminUserID'],
            'IdeationType' => 'up'
        ]);
    }

    /**
     * Test /discussion/<id> includes idea metadata.
     */
    public function testGetIdea() {
        $row = $this->testPostIdea();
        $discussionID = $row['discussionID'];

        $response = $this->api()->get("discussions/{$discussionID}");

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsIdea($body);

        return $body;
    }

    /**
     * Verify an idea can be created with the discussions endpoint.
     */
    public function testPostIdea() {
        $record = [
            'categoryID' => static::$categoryID,
            'name' => 'Test Idea',
            'body' => 'Hello world!',
            'format' => 'markdown'
        ];
        $response = $this->api()->post('discussions/idea', $record);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertEquals('idea', $body['type']);

        $this->assertTrue(is_int($body['discussionID']));
        $this->assertTrue($body['discussionID'] > 0);

        $this->assertRowsEqual($record, $body);

        return $body;
    }

    /**
     * Verify ideas can be queried from the discussions index.
     */
    public function testDiscussionsIndexIdea() {
        $indexPosts = 5;
        for ($i = 1; $i <= $indexPosts; $i++) {
            $this->testPostIdea();
        }

        // Add a regular discussion to ensure it's filtered out.
        $this->api()->post('discussions', [
            'categoryID' => 1,
            'name' => 'Test Discussion',
            'body' => 'Hello world!',
            'format' => 'markdown'
        ]);

        $response = $this->api()->get('discussions', ['type' => 'idea']);
        $this->assertEquals(200, $response->getStatusCode());

        $ideas = $response->getBody();
        $this->assertNotEmpty($ideas);
        foreach ($ideas as $idea) {
            $this->assertIsIdea($idea);
        }
    }

    /**
     * Test PATCH /discussions/<id>/idea with a full overwrite.
     */
    public function testPatchIdeaFull() {
        /** @var AttachmentModel $attachmentModel */
        $attachmentModel = self::container()->get(AttachmentModel::class);
        /** @var StatusModel $statusModel */
        $statusModel = self::container()->get(StatusModel::class);

        $row = $this->testGetIdea();
        $discussionID = $row['discussionID'];
        $idea = $row['attributes']['idea'];
        $new = array_intersect_key($this->modifyRowIdea($idea), $this->providePatchFields());

        $response = $this->api()->patch(
            "discussions/{$discussionID}/idea",
            $new
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertRowsEqual($new, $body);

        $attachment = $attachmentModel->getWhere([
            'Type' => 'status',
            'ForeignID' => "d-{$discussionID}"
        ])->firstRow(\DATASET_TYPE_ARRAY);
        $newStatus = $statusModel->getID($new['statusID'], \DATASET_TYPE_ARRAY);
        $this->assertEquals($newStatus['Name'], $attachment['StatusName']);
        $this->assertEquals($new['statusNotes'], $attachment['StatusNotes']);

        return $body;
    }

    /**
     * Test PATCH /discussions/<id>/idea with a a single field update.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        /** @var AttachmentModel $attachmentModel */
        $attachmentModel = self::container()->get(AttachmentModel::class);

        $row = $this->testGetIdea();
        $discussionID = $row['discussionID'];
        $idea = $row['attributes']['idea'];
        $new = array_intersect_key($this->modifyRowIdea($idea), $this->providePatchFields());

        $r = $this->api()->patch(
            "discussions/{$discussionID}/idea",
            [$field => $new[$field]]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("discussions/{$discussionID}");
        $this->assertSame($new[$field], $newRow['attributes']['idea'][$field]);

        $attachment = $attachmentModel->getWhere([
            'Type' => 'status',
            'ForeignID' => "d-{$discussionID}"
        ])->firstRow(\DATASET_TYPE_ARRAY);
        switch ($field) {
            case 'statusID':
                /** @var StatusModel $statusModel */
                $statusModel = self::container()->get(StatusModel::class);
                $newStatus = $statusModel->getID($new['statusID'], \DATASET_TYPE_ARRAY);
                $this->assertEquals($newStatus['Name'], $attachment['StatusName']);
                break;
            case 'statusNotes':
                $this->assertEquals($new['statusNotes'], $attachment['StatusNotes']);
        }
    }

    /**
     * An idea that is in a non idea category should return an invalid idea attribute.
     */
    public function testIdeaInNonIdeaCategory() {
        $record = [
            'categoryID' => static::$categoryID,
            'name' => 'Test Idea',
            'body' => 'Hello world!',
            'format' => 'markdown'
        ];
        $response = $this->api()->post('discussions/idea', $record);

        // Switch the idea to another category.
        $this->api()->patch("/discussions/{$response['discussionID']}", ['categoryID' => 123]);
        $discussion = $this->api()->get("/discussions/{$response['discussionID']}");

        $this->assertSame(0, $discussion['attributes']['idea']['statusID']);
        $this->assertSame("Invalid", $discussion['attributes']['idea']['status']['name']);
    }
}
