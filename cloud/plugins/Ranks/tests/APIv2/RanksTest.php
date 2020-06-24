<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use RankModel;

/**
 * Test the /api/v2/ranks endpoints.
 */
class RanksTest extends AbstractResourceTest {

    /** @var string */
    protected $baseUrl = '/ranks';

    /** @var array */
    protected $patchFields = ['name', 'userTitle', 'level', 'notificationBody', 'cssClass', 'criteria', 'abilities'];

    /** @var string */
    protected $pk = 'rankID';

    /** @var bool */
    protected  $testPagingOnIndex = false;

    /**
     * {@inheritdoc}
     */
    public function generateIndexRows() {
        $result = parent::generateIndexRows();
        RankModel::refreshCache();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row) {
        $textFields = ['name', 'userTitle', 'notificationBody', 'cssClass'];
        foreach ($textFields as $textField) {
            if (array_key_exists($textField, $row)) {
                $row[$textField] = md5($row[$textField]);
            }
        }

        if (array_key_exists('criteria', $row)) {
            $row['criteria'] = [
                'time' => '1 year',
                'permission' => 'community.moderate'
            ];
        }
        if (array_key_exists('abilities', $row)) {
            $row['abilities'] = [
                'signature' => false,
                'editTimeout' => 0,
            ];
        }

        return $row;
    }

    /**
     * Provide data for testing renamed fields.
     *
     * @return array
     */
    public function provideRenamedFields() {
        $result = [
            'ActivityLinks' => ['abilities', 'linksActivity', false, 'ActivityLinks'],
            'Avatars' => ['abilities', 'avatar', false, 'Avatars'],
            'Body' => ['schema', 'notificationBody', 'Notification Body', 'Body'],
            'CommentLinks' => ['abilities', 'linksPosts', false, 'CommentLinks'],
            'ConversationLinks' => ['abilities', 'linksConversations', false, 'ConversationLinks'],
            'EditContentTimeout' => ['abilities', 'editTimeout', -1, 'EditContentTimeout'],
            'Label' => ['schema', 'userTitle', 'User Title', 'Label'],
            'Locations' => ['abilities', 'location', false, 'Locations'],
            'MeAction' => ['abilities', 'meActions', false, 'MeAction'],
            'PermissionRole' => ['abilities', 'roleID', 8, 'PermissionRole'],
            'CountPosts' => ['criteria', 'posts', 999, 'CountPosts'],
            'Signatures' => ['abilities', 'signature', false, 'Signatures'],
            'SignatureMaxNumberImages' => ['abilities', 'signatureImages', -1, 'SignatureMaxNumberImages'],
            'SignatureMaxLength' => ['abilities', 'signatureLength', -1, 'SignatureMaxLength'],
            'Titles' => ['abilities', 'title', false, 'Titles']
        ];
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        $result = [
            'name' => 'Test Rank',
            'userTitle' => 'User Title',
            'level' => 3,
            'notificationBody' => 'Notification Body',
            'cssClass' => 'css-class',
            'criteria' => [
                'time' => '2 weeks',
                'posts' => 10,
            ],
            'abilities' => [
                'signature' => true,
                'avatar' => true
            ]
        ];
        return $result;
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['ranks', 'vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        RankModel::refreshCache();
        $result = parent::testIndex();
        return $result;
    }

    /**
     * Test renaming of fields.
     *
     * @param string $type
     * @param string $field
     * @param mixed $value
     * @param string $expectedName
     * @dataProvider provideRenamedFields
     */
    public function testFieldRenaming($type, $field, $value, $expectedName) {
        $row = $this->testPost();
        $id = $row[$this->pk];

        switch ($type) {
            case 'abilities':
                $patch = ['abilities' => [$field => $value]];
                break;
            case 'criteria':
                $patch = ['criteria' => [$field => $value]];
                break;
            default:
                $patch = [$field => $value];
        }
        $updatedRow = $this->api()->patch(
            "{$this->baseUrl}/{$id}",
            $patch
        )->getBody();

        $rank = static::container()->get(RankModel::class)->getID($id);

        switch ($type) {
            case 'abilities':
                $expected = $updatedRow['abilities'][$field];
                $actual = $rank['Abilities'][$expectedName];
                $actual = $actual === 'yes' ? true : ($actual === 'no' ? false : $actual);
                break;
            case 'criteria':
                $expected = $updatedRow['criteria'][$field];
                $actual = $rank['Criteria'][$expectedName];
                break;
            default:
                $expected = $updatedRow[$field];
                $actual = $rank[$expectedName];
        }
        $this->assertSame($expected, $actual);
    }
}
