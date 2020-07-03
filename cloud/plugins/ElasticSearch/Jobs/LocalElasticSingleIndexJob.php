<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Local job for handling updating individual records in elasticsearch.
 */
class LocalElasticSingleIndexJob extends AbstractLocalElasticJob {

    /** @var string */
    private $apiUrl;

    /** @var array */
    private $apiParams;

    /** @var string */
    private $indexName;

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        try {
            // Fetch the record.
            $recordResponse = $this->vanillaClient->get($this->apiUrl, $this->apiParams);
            $recordBody = $recordResponse->getBody();

            throw new \Exception('Exception');
            // Insert the record body into elasticsearch.
            $response = $this->elasticClient->indexDocuments($this->indexName, "{$this->indexName}ID", [$recordBody]);

            $debug = true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            return JobExecutionStatus::error();
        }

        return JobExecutionStatus::complete();
    }

    /**
     * @inheritdoc
     */
    public function setMessage(array $message) {
        $schema = Schema::parse([
            'apiUrl:s',
            'apiParams:o?',
            'indexName:s',
        ]);

        $message = $schema->validate($message);

        $this->apiUrl = $message['apiUrl'];
        $this->indexName = $message['indexName'];
        $this->apiParams = $message['apiParams'] ?? [];
    }
}
