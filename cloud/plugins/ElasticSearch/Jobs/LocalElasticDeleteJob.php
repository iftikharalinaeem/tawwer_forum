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
 * Local job for handling deleting of records from elasticsearch.
 */
class LocalElasticDeleteJob extends AbstractLocalElasticJob {

    /** @var string[] */
    private $elasticIDs;

    /** @var string */
    private $indexName;

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        try {
            // Remove the records from elastic.
            $response = $this->elasticClient->deleteDocuments($this->indexName, $this->elasticIDs);
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
            'elasticIDs:a' => 's',
            'indexName:s',
        ]);

        $message = $schema->validate($message);

        $this->elasticIDs = $message['elasticIDs'];
        $this->indexName = $message['indexName'];
    }
}
