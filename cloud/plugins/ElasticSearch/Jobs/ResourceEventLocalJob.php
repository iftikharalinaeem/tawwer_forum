<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Events\ResourceEvent;
use Garden\Http\HttpClient;
use Garden\Web\Exception\ServerException;
use Vanilla\Cloud\ElasticSearch\Http\ElasticHttpClient;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use VanillaTests\InternalClient;

class ResourceEventLocalJob implements LocalJobInterface {

    /** @var ElasticHttpClient */
    private $elasticClient;

    /** @var ResourceEvent */
    private $resourceEvent;

    /** @var HttpClient */
    private $vanillaClient;

    /**
     *
     * @param ElasticHttpClient $elasticClient
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $config
     */
    public function __construct(ElasticHttpClient $elasticClient, InternalClient $internalClient, ConfigurationInterface $config) {
        $this->elasticClient = $elasticClient;

        // Make an internal http client.
        $internalClient->setUserID($config->get('Garden.SystemUserID'));
        $internalClient->setThrowExceptions(true);
        $this->vanillaClient = $internalClient;
    }


    public function run(): JobExecutionStatus {
        $normalizedRecord = $this->resourceEvent->getNormalizedRecordPayload();
        $recordType = $normalizedRecord['recordType'] ?? null;
        $recordID = $normalizedRecord['recordID'] ?? null;

        if ($recordType === null || $recordID === null) {
            trigger_error('Could index a record without a recordType or recordID', E_USER_NOTICE);
            return JobExecutionStatus::invalid();
        }

        $indexName = "${recordType}s";
        // Fetch the record.
        $recordResponse = $this->vanillaClient->get("/$indexName/$recordID", ['expand' => ['crawl']]);
        $recordBody = $recordResponse->getBody();

        // Insert the record body into elasticsearch.
        $response = $this->elasticClient->indexDocuments($indexName, "${recordType}ID", [$recordBody]);

        $test = true;
    }

    public function setMessage(array $message) {
        if (!($message['resourceEvent'] instanceof ResourceEvent)) {
            throw new ServerException('Unable to queue a ResourceEventJob without a ResourceEvent');
        }
        $this->resourceEvent = $message['resourceEvent'];
    }

    public function setPriority(JobPriority $priority) {
        // TODO: Implement setPriority() method.
    }

    public function setDelay(int $seconds) {
        // TODO: Implement setDelay() method.
    }


}
