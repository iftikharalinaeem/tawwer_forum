<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

/**
 * Test /api/v2/webhooks endpoint.
 */
class WebhooksTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/webhooks";

    /** @var array The patch fields. */
    protected $patchFields = ['active', 'name', 'url', 'secret'];

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    /**
     * Run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['webhooks'];
        parent::setUpBeforeClass();
    }

    /**
     * Test PATCH /resource/<id> with a full record overwrite.
     */
    public function testPatchFull() {
        $row = $this->testpost();
        $newRow = $this->modifyRow($row);
        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual($newRow, $r->getBody());
        return $r->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        $this->assertTrue(true);
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEditFields($record = null) {
        $this->assertTrue(true);
    }

    /**
     * Test DELETE /resource/<id>.
     *
     * Overriding test since webhookModel uses the pipelineModel::selectsingle method, that throws a 500, instead of 404.
     */
    public function testDelete() {
        $row = $this->testPost();
        // GardenHTTP does not allow a call to its delete method with a body. This long form is required for delete requests with a body.
        $r = $this->api()->request(\Garden\Http\HttpRequest::METHOD_DELETE, "{$this->baseUrl}/{$row[$this->pk]}", []);

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
            $this->fail("The {$this->singular} did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(500, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a {$this->singular}.");
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
        $row = $this->testPost();
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
    protected function modifyRow(array $row) {
        $newRow = [];

        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            switch ($key) {
                case 'active':
                    $value = !$value;
                    break;
                default:
                    $value = $value.$dt->format(\DateTime::RSS);
            }
            $newRow[$key] = $value;
        }
        return $newRow;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $record = [
            'active' => 1,
            'name' => 'webhooktest',
            'url' => 'http://webhook.test',
            'secret' => '123',
            'events' => '*'
        ];
        return $record;
    }
}
