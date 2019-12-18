<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

use Garden\Http\HttpClient;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * Execute the webhook event queue.
 */
class WebhookEvent implements \Vanilla\Scheduler\Job\LocalJobInterface {

    /** @var string Url of the event. */
    protected $eventUrl;

    /** @var  string Event name. */
    protected $eventName;

    /** @var object The user executing the ping event. */
    protected $eventUser;

    /** @var string Event action. */
    protected $eventAction;

    /** @var HttpClient */
    protected $httpClient;

    /** @var  string Event Secret. */
    protected $eventSecret;

    /**
     * Initial job setup.
     *
     * @param WebhookModel $webhookModel
     */
    public function __construct(HttpClient $httpClient) {
       $this->httpClient = $httpClient;
    }

    /**
     * Execute all queued items.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $body = $this->getRequestBody();
        if (!is_array($body)) {
            return JobExecutionStatus::failed();
        }
        $bodyEncoded = base64_encode(json_encode($body));
        $signature = hash_hmac("sha256", $bodyEncoded, $this->eventSecret);
        $headers = [
            'X-Vanilla-Event' => $this->eventName,
            'X-Vanilla-ID' => $this->eventAction,
            'X-Vanilla-Signature' => $signature
        ];
        $this->httpClient->setDefaultHeaders($headers);
        $result = $this->httpClient->post($this->eventUrl, $body);
        $status = $result->getStatusCode();
        return $status === 200 ? JobExecutionStatus::complete() :JobExecutionStatus::failed();
    }

    /**
     * Set the job's Message
     *
     * @param array $message The webhook event message.
     */
    public function setMessage(array $message) {
        $this->eventUrl = $message['url'];
        $this->eventAction = $message['action'];
        $this->eventUser = $message['user'];
        $this->eventName = $message['name'];
        $this->eventSecret = $message['secret'];
    }

    /**
     * Construct the request body.
     *
     * @return array|bool
     */
    private function getRequestBody() {
        $uuid = $this->generateUuid();
        $requestBody = false;
        if (is_string($uuid)) {
            $sender = [
                'userID' =>$this->eventUser->UserID,
                'name' => $this->eventUser->Name,
                'photoURL' => $this->eventUser->PhotoUrl,
                'dataLastActive' => $this->eventUser->DataLastActive
            ];
            $requestBody = [
                'action' => $this->eventAction,
                'ping' =>  $uuid,
                'sender' => $sender,
                'site' => [
                    'siteID' => 0
                ]
            ];
        }
        return $requestBody;
    }

    /**
     * Set job priority
     *
     * @param JobPriority $priority
     * @return void
     */
    public function setPriority(JobPriority $priority) {
    }

    /**
     * Set job execution delay
     *
     * @param int $seconds
     * @return void
     */
    public function setDelay(int $seconds) {
    }

    /**
     * Generate a Uuid.
     *
     * @return \Ramsey\Uuid\UuidInterface|string The uuid string.
     * @return UnsatisfiedDependencyException Dependency not met.
     */
    private function generateUuid() {
        try {
        $uuid = Uuid::uuid1();
        $uuid = $uuid->toString();
        } catch (UnsatisfiedDependencyException $e) {
            echo 'Missing dependancy exception ' . $e->getMessage();
        }
        return $uuid;
    }
}
