<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Gdn_Session as SessionInterface;
use UserModel;
use Vanilla\InjectableInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Webhooks\Library\Client;

/**
 * Execute the webhook event queue.
 */
abstract class WebhookEvent implements LocalJobInterface, InjectableInterface {

    /** @var string */
    protected $action;

    /** @var int */
    protected $delay;

    /** @var string Url of the event. */
    protected $webhookUrl;

    /** @var string Event action. */
    protected $eventAction;

    /** @var Client */
    protected $client;

    /** @var JobPriority */
    protected $priority;

    /** @var SessionInterface */
    protected $session;

    /** @var UserModel */
    protected $userModel;

    /** @var  string Event Secret. */
    protected $webhookSecret;

    /**
     * Get default webhook event data.
     *
     * @return array
     */
    protected function defaultData(): array {
        return [
            "action" => $this->action,
            "sender" => $this->userModel->getFragmentByID($this->session->UserID),
            "site" => $this->site(),
        ];
    }

    /**
     * Get the type of this event.
     *
     * @return string
     */
    abstract protected function getEventType(): string;

    /**
     * Get type-specific data for this event.
     *
     * @return array
     */
    abstract protected function getData(): array;

    /**
     * Execute all queued items.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $defaultData = $this->defaultData();
        $data = $this->getData();

        $this->client->sendEvent(
            $this->webhookUrl,
            $defaultData + $data,
            $this->getEventType(),
            $this->webhookSecret
        );
        return JobExecutionStatus::complete();
    }

    /**
     * Initial job setup.
     *
     * @param HttpClient $httpClient
     * @param SessionInterface $session
     */
    public function setDependencies(Client $client, SessionInterface $session, UserModel $userModel) {
        $this->client = $client;
        $this->session = $session;
        $this->userModel = $userModel;
    }

    /**
     * Setup the job.
     *
     * @param array $message The webhook event message.
     */
    public function setMessage(array $message) {
        $this->setAction($message["action"]);
        $this->setWebhookUrl($message["url"]);
        $this->setWebhookSecret($message["secret"]);
    }

    /**
     * {@inheritDoc}
     */
    public function setDelay(int $seconds) {
        $this->delay = $seconds;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(JobPriority $priority) {
        $this->priority = $priority;
    }

    /**
     * Set the event action identifier.
     *
     * @param string $action
     * @return self
     */
    protected function setAction(string $action): self {
        $this->action = $action;
        return $this;
    }

    /**
     * Set the shared secret for signinf the webhook event payload.
     *
     * @param string $webhookSecret
     * @return self
     */
    protected function setWebhookSecret(string $webhookSecret): self {
        $this->webhookSecret = $webhookSecret;
        return $this;
    }

    /**
     * Set the webhook URL.
     *
     * @param string $webhookUrl
     * @return self
     */
    protected function setWebhookUrl(string $webhookUrl): self {
        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException("Invalid webhook URL.");
        }
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    /**
     * Get site details to include alongside event data.
     *
     * @return array
     */
    protected function site(): array {
        if (class_exists('\Infrastructure')) {
            $result = ["siteID" => \Infrastructure::site("siteid")];
        } else {
            $result = ["siteID" => 0];
        }
        return $result;
    }
}
