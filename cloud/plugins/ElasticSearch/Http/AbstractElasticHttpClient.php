<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Web\Exception\HttpException;

/**
 * Http client for accessing an infrastructure elasticsearch instance.
 *
 * This client does not have access to the full elastic search API.
 * Instead it has access to a subset, currently defined here.
 *
 * @link https://github.com/vanilla/dev-inter-ops/issues/57
 *
 * All methods in this wrapper use the vanilla index names, and will be automatically mapped to aliases.
 *
 * Eg. `discussions` as an alias name will be passed to the service as `{{idx:discussions}}`.
 * This alias will be resolved to the service as something like: `vf_ACCOUNTID_SITEID_discussions`.
 */
abstract class AbstractElasticHttpClient extends HttpClient {

    const SCOPE_ACCOUNT = 'account';
    const SCOPE_HUB = 'hub';
    const SCOPE_SITE = 'site';

    const SCOPES = [
        self::SCOPE_ACCOUNT,
        self::SCOPE_HUB,
        self::SCOPE_SITE,
    ];

    /** @var AbstractElasticHttpConfig */
    private $clientConfig;

    /**
     * DI.
     *
     * @param AbstractElasticHttpConfig $clientConfig
     */
    public function __construct(AbstractElasticHttpConfig $clientConfig) {
        parent::__construct($clientConfig->getBaseUrl());
        $this->clientConfig = $clientConfig;

        $this->throwExceptions = true;
        $this->setDefaultHeaders([
            'content-type' => 'application/json',
        ]);

        // Add a middleware to JWT auths are created at the correct time.
        $this->addMiddleware([$clientConfig, 'requestAuthMiddleware']);
    }

    /**
     * Send documents to be indexed by the microservice.
     *
     * @param string $indexName The index to index into.
     * @param string $documentIdField The field in the document to use as elastics `_id`.
     * @param array $documents An array of documents to index in elasticsearch.
     *
     * @return HttpResponse Http response (contains ES result).
     *
     * @throws HttpException When something goes wrong.
     */
    abstract public function indexDocuments(string $indexName, string $documentIdField, array $documents): HttpResponse;

    /**
     * Trigger a full-site index.
     *
     * @return HttpResponse
     */
    abstract public function triggerFullSiteIndex(): HttpResponse;

    /**
     * Update multiple documents with some specific fields based on the an elasticsearch query.
     *
     * @param string $indexName The name of the index.
     * @param array $searchPayload An elastic search payload.
     * @param array $updates Array of [$field => $value].
     *
     * @return HttpResponse
     */
    abstract public function documentsFieldMassUpdate(string $indexName, array $searchPayload, array $updates): HttpResponse;

    /**
     * Delete documents from elasticsearch.
     *
     * @param string $indexName The index to delete from.
     * @param array $documentIDs The documents `_id`s to delete.
     *
     * @return HttpResponse Http response (contains ES response).
     *
     * @throws HttpException When something goes wrong.
     */
    abstract public function deleteDocuments(string $indexName, array $documentIDs): HttpResponse;

    /**
     * Search some documents.
     *
     * @param array $indexNames The index names to search.
     * @param array $searchPayload An elasticsearch query payload.
     * This should be the request body of the _search elastic endpoint.
     * {@link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-search.html#search-search-api-request-body}.
     * Query is in the `query` subfield.
     * {@link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html}
     * @param string $scope The search scope.
     * One of {@link AbstractElasticHttpClient::SCOPES}.
     *
     * @return HttpResponse
     *
     * @throws HttpException When something goes wrong.
     */
    public function searchDocuments(array $indexNames, array $searchPayload, string $scope = self::SCOPE_SITE): HttpResponse {
        $indexAliases = array_map([$this, 'convertIndexNameToAlias'], $indexNames);
        $body = [
            'indexesAlias' => $indexAliases,
            'scope' => $scope,
            'searchPayload' => $searchPayload,
        ];
        return $this->post('/search', $body);
    }

    /**
     * Send a DELETE request to the API with a body instead of params.
     *
     * @param string $uri The URL or path of the request.
     * @param array $body The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    protected function deleteWithBody(string $uri, array $body = [], array $headers = [], array $options = []): HttpResponse {
        return $this->request(HttpRequest::METHOD_DELETE, $uri, $body, $headers, $options);
    }

    /**
     * Convert an index name into an alias.
     *
     * @param string $indexName
     *
     * @return string
     */
    protected function convertIndexNameToAlias(string $indexName): string {
        return "{{idx:".trim($indexName)."}}";
    }
}
