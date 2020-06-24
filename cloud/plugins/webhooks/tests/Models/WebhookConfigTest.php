<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Webhooks\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Webhooks\Library\WebhookConfig;

/**
 * Test basic capabilities of the WebhookConfig class.
 */
class WebhookConfigTest extends TestCase {

    /**
     * Provide data for configuring the instance through the constructor.
     *
     * @return array
     */
    public function provideConstructorData(): array {
        $result = [
            "Valid Row" => [
                [
                    "webhookID" => 1,
                    "url" => "https://vanillaforums.com",
                    "secret" => "abc123",
                ],
                true,
            ],
            "Invalid Row" => [
                [
                    "url" => "https://vanillaforums.com",
                ],
                false,
            ],
        ];

        return $result;
    }

    /**
     * Verify ability to setup a config instance, or not, based on a webhook row.
     *
     * @param array $row
     * @param bool $isValid
     * @return void
     * @dataProvider provideConstructorData
     */
    public function testConstructor(array $row, bool $isValid): void {
        if ($isValid === false) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("Invalid webhook row.");
        }

        $config = new WebhookConfig($row);

        $this->assertEquals($row["secret"], $config->getSecret());
        $this->assertEquals($row["url"], $config->getUrl());
        $this->assertEquals($row["webhookID"], $config->getWebhookID());
    }
}
