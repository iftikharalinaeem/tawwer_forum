<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Vanilla\HostedJob\Job\HostedJobInterface;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Queue up an HTTP request for remote execution.
 */
class RemoteRequestJob implements HostedJobInterface {

    /** @var int */
    private $delay = 0;

    /** @var array */
    protected $message;

    /** @var JobPriority */
    private $priority;

    /**
     * Setup the job.
     */
    public function __construct() {
        $this->priority = JobPriority::normal();
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage(array $message) {
        $this->message = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(): array {
        return $this->message;
    }

    /**
     * {@inheritDoc}
     */
    public function getJobType(): string {
        return "\\HostedQueue\\Addons\\HttpRequest\\HttpRequestJob";
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

    /**
     * {@inheritDoc}
     */
    public function getPriority(): JobPriority {
        return JobPriority::high();
    }

    /**
     * {@inheritDoc}
     */
    public function getDelay(): int {
        return $this->delay;
    }
}
