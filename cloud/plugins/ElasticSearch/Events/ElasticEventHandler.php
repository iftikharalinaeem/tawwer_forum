<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Events\ResourceEvent;
use Vanilla\Cloud\ElasticSearch\Http\ElasticHttpClient;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\Library\WebhookConfig;

/**
 * Event handler for turning dispatched events into elastic-search updates.
 */
class ElasticEventHandler {

    /** @var ElasticHttpClient */
    private $elasticClient;

    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * DI.
     *
     * @param ElasticHttpClient $elasticClient
     * @param SchedulerInterface $scheduler
     */
    public function __construct(ElasticHttpClient $elasticClient, SchedulerInterface $scheduler) {
        $this->elasticClient = $elasticClient;
        $this->scheduler = $scheduler;
    }


    /**
     * Dispatch resource events to the relevant webhooks.
     *
     * @param ResourceEvent $event
     * @return ResourceEvent
     */
    public function handleResourceEvent(ResourceEvent $event): ResourceEvent {
//        $this->scheduler->addJob(
//            ResourceEventLocalJob::class,
//            [ 'resourceEvent' => $event ]
//        );

        /** @var ResourceEventLocalJob $job */
        $job = \Gdn::getContainer()->get(ResourceEventLocalJob::class);
        $job->setMessage(['resourceEvent' => $event]);
        $job->run();

        return $event;
    }

}
