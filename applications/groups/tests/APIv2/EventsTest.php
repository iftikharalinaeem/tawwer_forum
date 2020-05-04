<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/events endpoints.
 */
class EventsTest extends AbstractResourceTest {

    /** @var array */
    protected static $groups;

    /** @var int */
    protected static $testCount = 0;

    /** {@inheritdoc} */
    protected $baseUrl = '/events';

    /** {@inheritdoc} */
    protected $editFields = ['groupID', 'parentRecordID', 'parentRecordType', 'name', 'body', 'format', 'location', 'dateStarts', 'dateEnds'];

    /** {@inheritdoc} */
    protected $patchFields = ['groupID', 'parentRecordID', 'parentRecordType', 'name', 'body', 'format', 'location', 'dateStarts', 'dateEnds'];

    /** {@inheritdoc} */
    protected $pk = 'eventID';

    /** {@inheritdoc} */
    protected $singular = 'event';


    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$groups = [];
        self::$addons = ['vanilla', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \GroupsApiController $groupsAPIController */
        $groupsAPIController = static::container()->get('GroupsApiController');

        for ($i = 0; $i < 2; $i++) {
            $groupTxt = uniqid(__CLASS__." ");
            self::$groups[] = $groupsAPIController->post([
                'name' => $groupTxt,
                'description' => $groupTxt,
                'format' => 'markdown',
                'privacy' => 'public',
            ]);
        }

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);

        $session->end();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();
        static::$testCount++;
    }

    /**
     * @inheritdoc
     */
    public function indexUrl() {
        $indexUrl = $this->baseUrl;
        $indexUrl .= '?'.http_build_query(['groupID' => self::$groups[0]['groupID']]);
        return $indexUrl;
    }

    /**
     * @inheritdoc
     */
    public function modifyRow(array $row) {
        $row = parent::modifyRow($row);

        // Assign the event to the other group.
        if (isset($row['groupID'])) {
            $row['groupID'] = self::$groups[1]['groupID'];
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $count = self::$testCount;
        $name = "Test Event $count";
        $record = [
            'groupID' => self::$groups[0]['groupID'],
            'parentRecordID' => self::$groups[0]['groupID'],
            'parentRecordType' => \GroupModel::RECORD_TYPE,
            'name' => $name,
            'body' => "$name description",
            'format' => 'markdown',
            'location' => 'Somewhere',
            'dateStarts' => date(\DateTime::RFC3339),
            'dateEnds' => date(\DateTime::RFC3339, now() + 36000),
        ];

        return $record;
    }
}
