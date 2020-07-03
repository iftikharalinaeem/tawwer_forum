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
use Vanilla\Utility\ModelUtils;

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
        $type = $event->getType();
        [$recordType, $recordID] = $event->getRecordTypeAndID();

        if ($recordType === null || $recordID === null) {
            // We can't handle this event. It is likely malformed.
            trigger_error('Could not handle resourceEvent with no recordID or recordType', E_USER_NOTICE);
            return $event;
        }

        switch ($event->getAction()) {
            case ResourceEvent::ACTION_INSERT:
            case ResourceEvent::ACTION_UPDATE:
                $apiUrl = "/api/v2/{$recordType}s/$recordID";
                $this->scheduler->addJob(
                    LocalElasticSingleIndexJob::class,
                    [
                        'apiUrl' => $apiUrl,
                        'apiParams' => [
                            'expand' => [ModelUtils::EXPAND_CRAWL],
                        ],
                        'indexName' => $recordType,
                    ]
                );
                break;
            case ResourceEvent::ACTION_DELETE:
                $this->scheduler->addJob(
                    LocalElasticDeleteJob::class,
                    [
                        'elasticIDs' => [$recordID],
                        'indexName' => $recordType,
                    ]
                );
                break;
        }

        return $event;
    }
}
