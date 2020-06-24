<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Jobs;

use Garden\Web\Exception\ClientException;
use Vanilla\HostedJob\Job\HostedFeedbackInterface;
use Vanilla\KnowledgePorterRunner\Utility\PorterRunnerMetaDao;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class FeedbackJob.
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class FeedbackJob implements HostedFeedbackInterface {

    /** @var PorterRunnerMetaDao */
    protected $metaDao;

    /**
     * FeedbackJob constructor.
     *
     * @param PorterRunnerMetaDao $metaDao
     */
    public function __construct(PorterRunnerMetaDao $metaDao) {
        $this->metaDao = $metaDao;
    }

    /**
     * Execute
     *
     * @param array $message
     * @return mixed|string
     * @throws ClientException For malformed message.
     */
    public function execute(array $message = []) {
        if (!isset($message['jobKey']) || !isset($message['result'])) {
            throw new ClientException('Malformed message', 400);
        }

        $jobMeta = $this->metaDao->get($message['jobKey']);
        $jobMeta->stateDone(JobExecutionStatus::looseStatus($message['result']));
        $this->metaDao->save($jobMeta);

        return $jobMeta->getInfo();
    }
}
