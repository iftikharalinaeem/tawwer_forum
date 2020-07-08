<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Cloud\ElasticSearch\Events;

use Garden\Http\HttpResponse;
use Vanilla\Cloud\ElasticSearch\Http\DevElasticHttpConfig;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for our development http config test.
 */
class DevElasticHttpConfigTest extends MinimalContainerTestCase {

    /**
     * Test the middleware that authorizes our requests.
     */
    public function testMiddleware() {
        $this->setConfigs([
            DevElasticHttpConfig::CONFIG_SITE_ID => 100,
            DevElasticHttpConfig::CONFIG_ACCOUNT_ID => 13524,
            DevElasticHttpConfig::CONFIG_API_SECRET => 'supersecret',
        ]);

        $httpClient = new MockHttpClient();
        /** @var DevElasticHttpConfig $config */
        $config = self::container()->get(DevElasticHttpConfig::class);
        $httpClient->addMiddleware([$config, 'requestAuthMiddleware']);
        $httpClient->addMockResponse('/test', new HttpResponse(200, '{}'));

        $response1 = $httpClient->get('/test');
        $authHeader1 = $response1->getRequest()->getHeader('Authorization');

        sleep(1);

        $response2 = $httpClient->get('/test');
        $authHeader2 = $response2->getRequest()->getHeader('Authorization');

        $this->assertNotEquals($authHeader1, $authHeader2, 'Keys are time based');

        $mockTime = 820108800;
        $config->setTime($mockTime);
        $actualResponse = $httpClient->get('/test');
        $actualAuthHeader = $actualResponse->getRequest()->getHeader('Authorization');
        $expectedAuth = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJhY2NvdW50SWQiOjEzNTI'.
            '0LCJzaXRlSWQiOjEwMCwiaWF0Ijo4MjAxMDg4MDAsImV4cCI6ODIwMTA4ODEwfQ.TDq7Z0-I0FExq7R'.
            'jtisaYzAylI0IxchS998jNTIP-BXkTPtw1sf1a1Q42hS6AlA3Qbmtra-CGdhvUCf_N33A-Q';
        $this->assertEquals($expectedAuth, $actualAuthHeader);
    }
}
