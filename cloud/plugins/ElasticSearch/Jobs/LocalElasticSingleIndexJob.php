<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Utility\ArrayUtils;

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
        $recordResponse = $this->vanillaClient->get($this->apiUrl, $this->apiParams);
        $recordBody = $recordResponse->getBody();

        $documents = $recordBody;
        if (ArrayUtils::isAssociative($documents)) {
            $documents = [$documents];
        }
        try {
            // Insert the record body into elasticsearch.
            $response = $this->elasticClient->indexDocuments(
                $this->indexName,
                "{$this->indexName}ID",
                $documents
            );
        } catch (\Exception $e) {
            logException($e);
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
