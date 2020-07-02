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
class ElasticHttpClient extends HttpClient {

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
        $this->addMiddleware(function (HttpRequest $request, callable $next) use ($clientConfig) : HttpResponse {
            $request->setHeader('Authorization', 'Bearer '.$clientConfig->getAuthJWT());
            return $next($request);
        });
    }

    /**
     * Search some documents.
     *
     * @param array $indexNames The index names to search.
     * @param array $searchPayload An elasticsearch query payload.
     * This should be the request body of the _search elastic endpoint.
     * {@link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-search.html#search-search-api-request-body}.
     * Query is in the `query` subfield.
     * {@link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html}
     *
     * @return HttpResponse
     *
     * @throws HttpException When something goes wrong.
     */
    public function searchDocuments(array $indexNames, array $searchPayload): HttpResponse {
        $indexAliases = array_map([$this, 'convertIndexNameToAlias'], $indexNames);
        $body = [
            'indexesAlias' => $indexAliases,
            'searchPayload' => $searchPayload,
        ];
        return $this->post('/search', $body);
    }

    /**
     * Send documents to be indexed by the microservice.
     *
     * @param string $indexName
     * @param string $documentIdField
     * @param array $documents
     *
     * @return HttpResponse Http response (contains ES result).
     *
     * @throws HttpException When something goes wrong.
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
     * Delete documents.
     *
     * @param string $indexName
     * @param array $documentIDs
     *
     * @return HttpResponse Http response (contains ES result).
     *
     * @throws HttpException When something goes wrong.
     */
    public function deleteDocuments(string $indexName, array $documentIDs): HttpResponse {
        $body = [
            'indexAlias' => $this->convertIndexNameToAlias($indexName),
            'documentsId' => $documentIDs, // This name is what is used in the API. Typo?
        ];
        return $this->post('/documents', $body);
    }

    /**
     * Convert an index name into an alias.
     *
     * @param string $indexName
     *
     * @return string
     */
    private function convertIndexNameToAlias(string $indexName): string {
        return "{{idx:".trim($indexName)."}}";
    }
}
