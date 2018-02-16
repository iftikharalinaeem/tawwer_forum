<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/statuses endpoints.
 */
class StatusesTest extends AbstractResourceTest {

    /** {@inheritdoc} */
    protected $editFields = ['name', 'votingEnabled', 'isDefault'];

    /** {@inheritdoc} */
    protected $testPagingOnIndex = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/statuses';
        $this->patchFields = ['name', 'votingEnabled', 'isDefault'];
        $this->pk = 'statusID';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $row['name'] = md5($row['name']);
        $row['isDefault'] = !$row['isDefault'];
        $row['votingEnabled'] = $row['votingEnabled'];

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        static $total = 0;
        $total++;

        $record = [
            'name' => "Status {$total}",
            'votingEnabled' => true,
            'isDefault' => false
        ];
        return $record;
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'ideation'];
        parent::setUpBeforeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function testGetEdit($record = null) {
        if ($record === null) {
            $record = $this->record();
            $row = $this->testPost($record);
        } else {
            $row = $record;
        }

        $r = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit");

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual(arrayTranslate($record, $this->editFields), $r->getBody());
        $this->assertCamelCase($r->getBody());

        return $r->getBody();
    }
}
