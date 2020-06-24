<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class PorterRunnerMeta
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class PorterRunnerMeta {
    /* @var string */
    protected $key = null;
    /* @var int|null */
    protected $scheduled = null;
    /* @var int|null */
    protected $finished = null;
    /* @var JobExecutionStatus */
    protected $status = null;
    /* @var string|null */
    protected $jobId = null;
    /* @var bool */
    protected $done = null;

    /**
     * PorterRunnerMeta constructor.
     *
     * @param string $key
     * @param int $scheduled
     * @param int $finished
     * @param JobExecutionStatus $status
     * @param string|null $jobId
     * @param bool $done
     */
    public function __construct(
        string $key,
        int $scheduled = null,
        int $finished = null,
        JobExecutionStatus $status = null,
        string $jobId = null,
        bool $done = false
    ) {
        $this->key = $key;
        $this->scheduled = $scheduled;
        $this->finished = $finished;
        $this->status = $status ?? JobExecutionStatus::unknown();
        $this->jobId = $jobId;
        $this->done = $done;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @return PorterRunnerMeta
     */
    public function stateIntended(): PorterRunnerMeta {
        $this->scheduled = time();
        $this->finished = null;
        $this->status = JobExecutionStatus::intended();
        $this->done = false;

        return $this;
    }

    /**
     * State Done
     *
     * @param JobExecutionStatus $status
     * @return PorterRunnerMeta
     */
    public function stateDone(JobExecutionStatus $status): PorterRunnerMeta {
        $this->finished = time();
        $this->status = $status;
        $this->done = true;

        return $this;
    }

    /**
     * Get Scheduled
     *
     * @return int
     */
    public function getScheduled(): ?int {
        return $this->scheduled;
    }

    /**
     * @return int
     */
    public function getFinished(): ?int {
        return $this->finished;
    }

    /**
     * @return JobExecutionStatus|null
     */
    public function getStatus(): ?JobExecutionStatus {
        return $this->status;
    }

    /**
     * Set Status
     *
     * @param JobExecutionStatus $status
     * @return PorterRunnerMeta
     */
    public function setStatus(JobExecutionStatus $status): PorterRunnerMeta {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJobId(): ?string {
        return $this->jobId;
    }

    /**
     * Set Job Id
     *
     * @param string $jobId
     * @return PorterRunnerMeta
     */
    public function setJobId(string $jobId): PorterRunnerMeta {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDone(): bool {
        return $this->done;
    }

    /**
     * @return array
     */
    public function getInfo(): array {
        return [
            'scheduled' => $this->scheduled ? date('c', $this->scheduled) : '-',
            'finished' => $this->finished ? date('c', $this->finished) : '-',
            'done' => $this->done,
            'jobId' => $this->jobId,
            'status' => $this->status->getStatus(),
            'scheduled_to_now' => $this->scheduled ? time() - $this->scheduled : null,
            'finished_to_now' => $this->finished ? time() - $this->finished : null,
            'execution_time' => $this->scheduled && $this->finished ? $this->finished - $this->scheduled : null,
        ];
    }
}
