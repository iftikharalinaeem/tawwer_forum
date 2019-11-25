<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Emailer\Tests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `EmailLogModel` class.
 */
class EmailLogModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * Run with just the emailer.
     *
     * @return array
     */
    public static function getAddons(): array {
        return ['emailer'];
    }

    /**
     * Reproduces a regression bug with the email log insert from the router.
     */
    public function testEmailLogInsertWithNullBody() {
        $data = [
            'MessageID' => 'foo',
            'ReplyTo' => 'bar@example.com',
            'Body' => null,
            'Format' => 'Html',
        ];

        /* @var \EmailLogModel $model */
        $model = $this->container()->get(\EmailLogModel::class);

        $r = $model->insert($data);
        $this->assertNotEmpty($r);
    }
}
