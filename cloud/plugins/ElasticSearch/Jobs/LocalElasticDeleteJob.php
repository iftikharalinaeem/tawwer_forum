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
            $body = $response->getBody();

            $this->handleFailedDeletes($body);
        } catch (\Exception $e) {
            logException($e);
            return JobExecutionStatus::error();
        }

        return JobExecutionStatus::complete();
    }

    /**
     * Handle failed deletes in the elastic response.
     *
     * In this local job we just log the failed items.
     * Hosted queue variations should attempt to requeue not found deletes, (because they may not have been indexed yet).
     *
     * @param array $elasticResponse The elasticsearch response.
     */
    private function handleFailedDeletes(array $elasticResponse) {
        $failedItemIDs = [];
        $notFoundItemIDs = [];

        $items = $elasticResponse['items'] ?? [];
        foreach ($items as $item) {
            $delete = $item['delete'] ?? null;
            if (!$delete) {
                continue;
            }

            $itemID = $delete['_id'];
            $deleteStatus = $delete['status'] ?? null;
            if ($deleteStatus === 404) {
                $notFoundItemIDs[] = $itemID;
            }

            if ($deleteStatus !== 201) {
                $failedItemIDs[] = $itemID;
            }
        }

        $failedCount = count($failedItemIDs);
        if ($failedCount > 0) {
            errorLog("Failed to delete $failedCount item(s) from index {$this->indexName}: " . implode(", ", $failedItemIDs));

            $notFoundCount = count($notFoundItemIDs);
            if ($notFoundCount > 0) {
                errorLog("$notFoundCount item(s) not found to delete from index {$this->indexName}: " . implode(", ", $notFoundItemIDs));
            }
        }
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
