<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/badges endpoints.
 */
class BadgesTest extends AbstractResourceTest {

    /** @var int */
    protected static $recordCounter = 0;

    /** @var bool */
    protected $incrementRecordCounterOnCall = false;

    /** {@inheritdoc} */
    protected $baseUrl = '/badges';

    /** {@inheritdoc} */
    protected $editFields = ['name', 'key', 'body', 'photoUrl', 'points', 'class', 'classLevel', 'enabled'];

    /** {@inheritdoc} */
    protected $patchFields = ['name', 'key', 'body', 'photoUrl', 'points', 'class', 'classLevel', 'enabled'];

    /** {@inheritdoc} */
    protected $pk = 'badgeID';

    /** {@inheritdoc} */
    protected $singular = 'badge';


    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'conversations', 'badges'];
        parent::setupBeforeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->incrementRecordCounterOnCall = false;
        static::$recordCounter++;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        if ($this->incrementRecordCounterOnCall) {
            static::$recordCounter++;
        }

        $count = static::$recordCounter;
        $name = "Test Badge $count";
        $record = [
            'name' => $name,
            'key' => "test-badge-$count",
            'body' => "$name description",
            'photoUrl' => null,
            'points' => 1,
            'class' => 'testbadge',
            'classLevel' => $count,
            'enabled' => true,
        ];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        // We need this since badges cannot have the same slug and this test will create 5 of them.
        $this->incrementRecordCounterOnCall = true;
        parent::testIndex();
    }
}
