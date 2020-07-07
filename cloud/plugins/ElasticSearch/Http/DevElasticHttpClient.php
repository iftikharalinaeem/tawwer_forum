<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Garden\Http\HttpResponse;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\InternalClient;

/**
 * Dev implementation of the elastic http client.
 *
 * Instead of passing along pointers for indexes to be queued, the client will resolve pointers immediately and index syncronously.
 */
class DevElasticHttpClient extends AbstractElasticHttpClient {

    /** @var InternalClient */
    private $vanillaClient;

    /**
     * @inheritdoc
     */
    public function __construct(DevElasticHttpConfig $elasticConfig, InternalClient $vanillaClient, ConfigurationInterface $config) {
        parent::__construct($elasticConfig);
        // Make an internal http client.
        $vanillaClient->setBaseUrl('');
        $vanillaClient->setUserID($config->get('Garden.SystemUserID'));
        $vanillaClient->setThrowExceptions(true);
        $this->vanillaClient = $vanillaClient;
    }

    /**
     * Implementation that resolves pointeres immediately, rather than on in a deferred job.
     *
     * @inheritdoc
     */
    public function indexDocuments(string $indexName, string $documentIdField, array $apiPointer): HttpResponse {
        $recordResponse = $this->vanillaClient->get($apiPointer['apiUrl'], $apiPointer['apiParams']);
        $recordBody = $recordResponse->getBody();

        $documents = $recordBody;
        if (ArrayUtils::isAssociative($documents)) {
            $documents = [$documents];
        }

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
}
