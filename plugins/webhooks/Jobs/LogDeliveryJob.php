<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Webhooks\Models\WebhookDeliveryModel;

/**
 * Record an event delivery attempt to a webhook.
 */
class LogDeliveryJob implements LocalJobInterface {

    /** @var int */
    private $delay;

    /** @var array */
    private $delivery;

    /** @var JobPriority */
    private $priority;

    /** @var WebhookDeliveryModel */
    private $deliveryModel;

    /**
     * Setup the basic job.
     *
     * @param WebhookDeliveryModel $deliveryModel
     */
    public function __construct(WebhookDeliveryModel $deliveryModel) {
        $this->deliveryModel = $deliveryModel;
    }

    /**
     * Get a schema for validating the job message parameters.
     *
     * @return Schema
     */
    private function messageSchema(): Schema {
        $schema = Schema::parse([
            "webhookDeliveryID" => [
                "type" => "string",
            ],
            "webhookID" => [
                "type" => "integer",
            ],
            "requestHeaders" => [
                "type" => "string",
            ],
            "requestBody" => [
                "type" => "string",
            ],
            "requestDuration" => [
                "allowNull" => true,
                "type" => "integer",
            ],
            "responseHeaders" => [
                "type" => "string",
            ],
            "responseBody" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "responseCode" => [
                "allowNull" => true,
                "type" => "integer",
            ],
        ]);

        return $schema;
    }

    /**
     * {@inheritDoc}
     */
    public function run(): JobExecutionStatus {
        if (!is_array($this->delivery)) {
            return JobExecutionStatus::abandoned();
        }

        $this->deliveryModel->insert($this->delivery);
        return JobExecutionStatus::complete();
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage(array $message) {
        $message = $this->messageSchema()->validate($message);
        $this->delivery = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(JobPriority $priority) {
        $this->priority = $priority;
    }

    /**
     * {@inheritDoc}
     */
    public function setDelay(int $seconds) {
        $this->delay = $seconds;
    }
}
