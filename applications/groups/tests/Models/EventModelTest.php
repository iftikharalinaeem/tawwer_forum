<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Groups\Models;

use PHPUnit\Framework\TestCase;
use EventModel;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the event model.
 */
class EventModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @return array
     */
    public static function getAddons(): array {
        return ['vanilla', 'groups'];
    }

    /**
     * Test formatEventDate
     *
     * @param string $dataString
     * @param string $timezone
     * @param array $expected
     * @dataProvider formatEventDateProvider
     */
    public function testFormatEventDate($dataString, $timezone, array $expected) {
        /** @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $config->set('Garden.GuestTimeZone', $timezone);
        $result = EventModel::formatEventDate($dataString);
        $this->AssertSame($expected, $result);
    }

    /**
     * Test formatEventDate error
     */
    public function testInvalidFormatEventDate() {
        $this->expectError();
        EventModel::formatEventDate('dnsfids');
    }

    /**
     * @return array
     */
    public function formatEventDateProvider(): array {
        return [
            'empty dataString' => [null, '', ['', '', '', '']],
            'valid dataString Sao Paulo' => ['2020-03-01 20:00:00',
                'America/Sao_Paulo',
                [
                    'Sunday, March  1, 2020',
                    ' 5:00PM',
                    '17:00',
                    '2020-03-01T17:00:00-03:00'
                ]
            ],
            'valid dataString Detroit' => ['2020-03-01 20:00:00',
                'America/Detroit',
                [
                    'Sunday, March  1, 2020',
                    ' 3:00PM',
                    '15:00',
                    '2020-03-01T15:00:00-05:00'
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->end();

        parent::tearDown();
    }
}
