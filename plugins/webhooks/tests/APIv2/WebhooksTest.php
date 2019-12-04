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
        $postedRecord = $this->testpost();
        $patchFields = ['active' => 0, 'name' => 'testpatch', 'url' => 'http://webhook.patch'];
        $r = $this->api()->patch("{$this->baseUrl}/{$postedRecord[$this->pk]}", $patchFields);
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual($patchFields, $r->getBody());
        return $r->getBody();
    }

    /**
     * This method is not implemented.
     *
     * @param null $record
     * @return array|void
     */
    public function testGetEdit($record = null) {
        $this->assertTrue(true);
    }

    /**
     * This method is not implemented.
     *
     * @param null $record
     * @return array|void
     */
    public function testGetEditFields($record = null) {
        $this->assertTrue(true);
    }

    /**
     * Test PATCH /resource/<id> with a a single field update.
     *
     * @param string $field The name of the field to patch.
     */
    public function testPatchSparse($field = null) {
        $this->patchFields = ['name', 'active', 'url', 'events'];
        $row = $this->testpost();
        $field = 'name';
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
     * This method is not implemented.
     *
     * @param null $record
     * @return array|void
     */
    public function testIndex($record = null) {
        $this->assertTrue(true);
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
