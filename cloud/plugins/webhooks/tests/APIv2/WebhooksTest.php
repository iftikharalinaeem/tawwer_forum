<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

/**
 * Test /api/v2/webhooks endpoint.
 */
class WebhooksTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/webhooks";

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = ["events", "name", "secret", "status", "url"];

    /** @var array The patch fields. */
    protected $patchFields = ['status', 'name', 'url', 'secret', 'events'];

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
                case 'events':
                    if (in_array("discussion", $value)) {
                        $value = ["comment"];
                    } else {
                        $value = ["discussion"];
                    }
                    break;
                case 'status':
                    $value = $value === "active" ? "disabled" : "active";
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
            'name' => 'webhooktest',
            'url' => 'http://webhook.test',
            'secret' => '123456789abcdefghijk',
            'status' => 'active',
            'events' => ['comment', 'discussion'],
        ];
        return $record;
    }
}
