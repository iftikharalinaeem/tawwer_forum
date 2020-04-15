<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

use Garden\Http\HttpRequest;
use Gdn_Session as SessionInterface;
use PHPUnit\Framework\TestCase;
use Vanilla\Contracts\Models\UserProviderInterface;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Utility\StringUtils;
use Vanilla\Webhooks\Jobs\LogDeliveryJob;
use Vanilla\Webhooks\Library\EventScheduler;
use Vanilla\Webhooks\Mocks\MockDiscussionEvent;
use VanillaTests\BootstrapTrait;

/**
 * Test basic capabilities of EventScheduler.
 */
class EventSchedulerTest extends TestCase {

    use BootstrapTrait;

    /** @var EventScheduler */
    private $eventScheduler;

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var SessionInterface */
    private $session;

    /**
     * {@inheritDoc}
     */
    public function setup(): void {
        $this->scheduler = self::container()->get(SchedulerInterface::class);
        $this->session = self::container()->get(SessionInterface::class);
        $this->userProvider = self::container()->get(UserProviderInterface::class);

        $this->eventScheduler = new EventScheduler(
            $this->scheduler,
            $this->userProvider,
            $this->session
        );
    }

    /**
     * Verify job configuration message generate from an event and a webhook config.
     *
     * @return void
     */
    public function testGenerateJobMessage(): void {
        $event = new MockDiscussionEvent(
            MockDiscussionEvent::ACTION_INSERT,
            ["foo" => "bar"]
        );
        $body = [
            "action" => "mockdiscussion_insert",
            "payload" => $event->getPayload(),
            "sender" => $this->userProvider->getGeneratedFragment(\UserModel::GENERATED_FRAGMENT_KEY_GUEST),
            "site" => [
                "siteID" => 0,
            ],
        ];
        $deliveryID = uniqid("delivery");
        $webhook = new WebhookConfig([
            "secret" => "abc123",
            "url" => "https://vanillaforums.com",
            "webhookID" => 1,
        ]);
        $json = StringUtils::jsonEncodeChecked($body, \JSON_UNESCAPED_SLASHES);
        $signature = "sha1=".hash_hmac("sha1", $json, $webhook->getSecret());

        $result = $this->eventScheduler->generateJobMessage($event, $webhook, $deliveryID);

        $this->assertEquals([
            "body" => $json,
            "feedbackJob" => LogDeliveryJob::class,
            "feedbackMessage" => [
                "webhookDeliveryID" => $deliveryID,
                "webhookID" => $webhook->getWebhookID(),
            ],
            "headers" => [
                "Content-Type" => "application/json",
                "X-Vanilla-Event" => $event->getType(),
                "X-Vanilla-ID" => $deliveryID,
                "X-Vanilla-Signature" => $signature,
            ],
            "method" => HttpRequest::METHOD_POST,
            "uri" => $webhook->getUrl(),
        ], $result);
    }

    /**
     * Verify toggling internal hosted-queue flag.
     *
     * @return void
     */
    public function testHostedQueueToggle(): void {
        foreach ([true, false] as $value) {
            $this->eventScheduler->useHostedQueue($value);
            $this->assertEquals($value, $this->eventScheduler->shouldUseHostedQueue());
        }
    }
}
