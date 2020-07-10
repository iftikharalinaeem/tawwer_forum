<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Aws\CloudFront\Exception\Exception;
use Garden\Http\HttpResponse;
use Vanilla\Cloud\ElasticSearch\LocalElasticSiteIndexJob;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Http\InternalClient;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;

/**
 * Dev implementation of the elastic http client.
 *
 * Instead of passing along pointers for indexes to be queued, the client will resolve pointers immediately and index syncronously.
 */
class DevElasticHttpClient extends AbstractElasticHttpClient {

    /** @var InternalClient */
    private $vanillaClient;

    /** @var \Gdn_Request */
    private $request;

    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * @inheritdoc
     */
    public function __construct(
        DevElasticHttpConfig $elasticConfig,
        InternalClient $vanillaClient,
        ConfigurationInterface $config,
        \Gdn_Request $request,
        SchedulerInterface $scheduler
    ) {
        parent::__construct($elasticConfig);
        // Make an internal http client.
        $vanillaClient->setBaseUrl('');
        $vanillaClient->setUserID($config->get('Garden.SystemUserID'));
        $vanillaClient->setThrowExceptions(true);
        $this->vanillaClient = $vanillaClient;
        $this->request = $request;
        $this->scheduler = $scheduler;
    }

    /**
     * @inheritdoc
     */
    public function indexDocuments(string $indexName, string $documentIdField, array $documents): HttpResponse {


        $body = [
            'indexAlias' => $this->convertIndexNameToAlias($indexName),
            'documentIdField' => $documentIdField,
            'documents' => $documents,
        ];

        return $this->post('/documents', $body);
    }


    /**
     * @inheritdoc
     */
    public function deleteDocuments(string $indexName, array $documentIDs): HttpResponse {
        $body = [
            'indexAlias' => $this->convertIndexNameToAlias($indexName),
            'documentsId' => $documentIDs, // This name is what is used in the API. Typo?
        ];
        return $this->deleteWithBody('/documents', $body);
    }

    /**
     * @return HttpResponse
     */
    public function triggerFullSiteIndex(): HttpResponse {
        $slip = $this->scheduler->addJob(
            LocalElasticSiteIndexJob::class,
            ['resourceApiUrl' => $this->request->getSimpleUrl('/api/v2/resources')."?crawlable=true"],
            JobPriority::low(),
            0
        );

        $slipID = $slip->getId();

        return new HttpResponse(
            202,
            ['Content-Type' => 'application/json'],
            json_encode(['slipID' => $slipID, 'extendedStatus' => $slip->getExtendedStatus()])
        );
    }

    /**
     * @inheritdoc
     */
    public function documentsFieldMassUpdate(string $indexName, array $searchPayload, array $updates): HttpResponse {
        throw new Exception('Not implemented yet');
    }
}
