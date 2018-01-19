<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/groups endpoints.
 */
class GroupsTest extends AbstractResourceTest {

    /** @var int */
    protected static $recordCounter = 0;

    /** @var bool */
    protected $incrementRecordCounterOnCall = false;

     /** {@inheritdoc} */
    protected $formattedFields = ['body', 'description'];

    /** {@inheritdoc} */
    protected $baseUrl = '/groups';

    /** {@inheritdoc} */
    protected $editFields = ['description', 'name', 'format', 'privacy', 'bannerUrl', 'iconUrl'];

    /** {@inheritdoc} */
    protected $patchFields = ['description', 'name', 'format', 'privacy', 'bannerUrl', 'iconUrl'];

    /** {@inheritdoc} */
    protected $pk = 'groupID';

    /** {@inheritdoc} */
    protected $singular = 'group';


    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'conversations', 'groups'];
        parent::setupBeforeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
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
        $name = "Test Group $count";
        $record = [
            'name' => $name,
            'description' => "$name description",
            'format' => 'Markdown',
            'privacy' => 'public',
            'bannerUrl' => null,
            'iconUrl' => 'https://example.com/image.jpg',
        ];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        // We need this since groups cannot have the same name and this test will create 5 of them.
        $this->incrementRecordCounterOnCall = true;
        parent::testIndex();
    }
}
