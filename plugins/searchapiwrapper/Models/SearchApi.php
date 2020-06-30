<?php
/**
 * @author Alexandre (Daazku) Chouinard
 */

namespace Vanilla\Inf\Search;

use Exception;
use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;

/**
 * Class SearchApi
 */
class SearchApi {

    const INDEX_ALIAS_ARTICLE = '{{idx:article}}';
    const INDEX_ALIAS_ARTICLE_REVISION = '{{idx:articleRevision}}';
    const INDEX_ALIAS_CATEGORY = '{{idx:category}}';
    const INDEX_ALIAS_COMMENT = '{{idx:comment}}';
    const INDEX_ALIAS_DISCUSSION = '{{idx:discussion}}';
    const INDEX_ALIAS_GROUP = '{{idx:group}}';
    const INDEX_ALIAS_USER = '{{idx:user}}';

    const INDEX_ARTICLE = 'article';
    const INDEX_ARTICLE_REVISION = 'articleRevision';
    const INDEX_CATEGORY =  'category';
    const INDEX_COMMENT = 'comment';
    const INDEX_DISCUSSION = 'discussion';
    const INDEX_GROUP = 'group';
    const INDEX_USER =  'user';

    const INDEX_TO_ALIAS_MAP = [
        self::INDEX_ARTICLE => self::INDEX_ALIAS_ARTICLE,
        self::INDEX_ARTICLE_REVISION => self::INDEX_ALIAS_ARTICLE_REVISION,
        self::INDEX_CATEGORY => self::INDEX_ALIAS_CATEGORY,
        self::INDEX_COMMENT => self::INDEX_ALIAS_COMMENT,
        self::INDEX_DISCUSSION => self::INDEX_ALIAS_DISCUSSION,
        self::INDEX_GROUP => self::INDEX_ALIAS_GROUP,
        self::INDEX_USER => self::INDEX_ALIAS_USER,
    ];

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var AbstractSearchApiInformationProvider
     */
    private $configurationProvider;

    /**
     * SearchApi constructor.
     *
     * @param HttpClient $httpClient
     * @param AbstractSearchApiInformationProvider $searchApiConfigurationProvider
     */
    public function __construct(HttpClient $httpClient, AbstractSearchApiInformationProvider $searchApiConfigurationProvider) {
        $httpClient->setBaseUrl($searchApiConfigurationProvider->getBaseUrl());
        $httpClient->setDefaultHeaders([
            'content-type' => 'application/json',
        ]);

        $this->httpClient = $httpClient;
        $this->configurationProvider = $searchApiConfigurationProvider;
    }

    /**
     * Get Authorization header
     *
     * @return string[]
     *
     * @throws Exception When something goes wrong.
     */
    private function getAuthorizationHeader() {
        return [
            'Authorization' => 'Bearer '.$this->configurationProvider->getAuthJWT(),
        ];
    }

    /**
     * Handle response properly.
     *
     * @param HttpResponse $response
     * @return array Microservice JSON decoded response
     *
     * @throws HttpResponseException When something goes wrong.
     */
    private function handleResponse(HttpResponse $response): array {
        if (!$response->isSuccessful()) {
            throw new HttpResponseException($response, $response->getRawBody());
        }

        return $response->getBody();
    }

    /**
     * Issue a search query to the microservice.
     *
     * @param array $indexesAlias
     * @param array $searchPayload
     * @return array Microservice JSON decoded response (contains ES result)
     *
     * @throws Exception When something goes wrong.
     */
    public function search(array $indexesAlias, array $searchPayload): array {
        $body = [
            'indexesAlias' => $indexesAlias,
            'searchPayload' => $searchPayload,
        ];
        $headers = $this->getAuthorizationHeader();

        return $this->handleResponse(
            $this->httpClient->post('search', $body, $headers)
        );
    }

    /**
     * Send documents to be indexed by the microservice.
     *
     * @param string $indexAlias
     * @param string $documentIdField
     * @param array $documents
     *
     * @return array Microservice JSON decoded response (contains ES result)
     *
     * @throws Exception When something goes wrong.
     */
    public function index(string $indexAlias, string $documentIdField, array $documents): array {
        $body = [
            'indexAlias' => $indexAlias,
            'documentIdField' => $documentIdField,
            'documents' => $documents,
        ];
        $headers = $this->getAuthorizationHeader();

        return $this->handleResponse(
            $this->httpClient->post('documents', $body, $headers)
        );
    }

    /**
     * Delete documents.
     *
     * @param string $indexAlias
     * @param array $documentsId
     *
     * @return array Microservice JSON decoded response (contains ES result)
     *
     * @throws Exception When something goes wrong.
     */
    public function delete(string $indexAlias, array $documentsId): array {
        $body = [
            'indexAlias' => $indexAlias,
            'documentsId' => $documentsId,
        ];
        $headers = $this->getAuthorizationHeader();

        return $this->handleResponse(
            $this->httpClient->request('DELETE', 'documents', $body, $headers)
        );
    }
}
