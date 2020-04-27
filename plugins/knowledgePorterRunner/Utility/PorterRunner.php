<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Garden\Web\Exception\ClientException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\KnowledgePorterRunner\Jobs\HostedJob;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Class PorterRunner
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class PorterRunner {
    private const JOB_TIMEOUT = 3600;

    /** @var ConfigurationInterface */
    protected $config;

    /** @var SchedulerInterface */
    protected $scheduler;

    /** @var PorterRunnerMetaDao */
    protected $metaDao;

    /**
     * PorterRunner constructor.
     *
     * @param ConfigurationInterface $config
     * @param SchedulerInterface $scheduler
     * @param PorterRunnerMetaDao $metaDao
     */
    public function __construct(ConfigurationInterface $config, SchedulerInterface $scheduler, PorterRunnerMetaDao $metaDao) {
        $this->config = $config;
        $this->scheduler = $scheduler;
        $this->metaDao = $metaDao;
    }

    /**
     * Schedule Porter
     *
     * @throws ClientException On missing parameters.
     */
    public function schedulePorter(): array {
        $result = [];

        $runnerConfigs = $this->config->get('Plugins.KnowledgePorterRunner.config', null);
        if ($runnerConfigs === null || !is_array($runnerConfigs)) {
            throw new ClientException("Configuration is missing or it is not an array");
        }

        foreach ($runnerConfigs as $runnerKey => $runnerConfig) {
            if (!is_array($runnerConfig['source']['domain'])) {
                $runnerConfig['source']['domain'] = [$runnerConfig['source']['domain']];
            }

            foreach ($runnerConfig['source']['domain'] as $domain) {
                $jobConfig = $runnerConfig;

                // The `foreignIDPrefix` prefix can come encoded into the domain. Ex: `vanillaforums.com=vanilla`
                // Note: It is done in this way to keep compatibility with the `multi-import.sh` part of the `knowledge-porter`
                $domainTokens = explode('=', $domain);
                if (count($domainTokens) > 2) {
                    throw new ClientException("Malformed `source.domain` configuration");
                }

                $jobConfig['source']['domain'] = $domainTokens[0];
                if (isset($domainTokens[1])) {
                    $jobConfig['source']['foreignIDPrefix'] = str_replace('{prefix}', $domainTokens[1], $jobConfig['source']['foreignIDPrefix']);
                }

                $jobKey = $runnerKey.'-'.str_replace("/", "_", $jobConfig['source']['domain']);

                if (!isset($result[$jobKey])) {
                    $jobMeta = $this->metaDao->get($jobKey);

                    if ($jobMeta->isDone() || time() > $jobMeta->getScheduled() + self::JOB_TIMEOUT) {
                        try {
                            $jobMeta->stateIntended();
                            $slip = $this->scheduler->addJob(HostedJob::class, ['jobKey' => $jobKey, 'config' => $jobConfig], JobPriority::low(), 0);
                            $jobMeta->setJobId($slip->getId());
                            $slipStatus = $slip->getStatus();
                            if (!$slipStatus->is(JobExecutionStatus::stackExecutionError())) {
                                $jobMeta->setStatus($slipStatus);
                            } else {
                                $jobMeta->stateDone(JobExecutionStatus::invalid());
                            }
                        } finally {
                            $this->metaDao->save($jobMeta);
                        }
                    }
                    $result[$jobKey] = $jobMeta->getInfo();
                } else {
                    $result[$jobKey.'-'.uniqid()] = [
                        'error' => "Malformed configuration. Duplicated source domain `$domain`",
                    ];
                }
            }
        }

        return $result;
    }
}
