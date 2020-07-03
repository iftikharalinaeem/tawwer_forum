<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Search;

use Garden\Http\HttpClient;
use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;

/**
 * Elasticsearch microservice API.
 */
class ElasticServiceClient extends HttpClient {
    /**
     * @var string
     */
    private $token;

    /**
     * ElasticServiceClient constructor.
     *
     * @param string $baseUrl
     * @param HttpHandlerInterface|null $handler
     */
    public function __construct(string $baseUrl = '', HttpHandlerInterface $handler = null) {
        parent::__construct($baseUrl, $handler);
        $this->setThrowExceptions(true);
        $this->setDefaultHeader('Content-Type', 'application/json');
    }

    /**
     * Set api access token
     *
     * @param string $token
     */
    public function setToken(string $token) {
        $this->token = $token;
        $this->setDefaultHeader('Authorization', "Bearer $token");
    }


    /**
     * GET /api/v2/knowledge-categories/{knowledgeCategoryID} using smartID.
     *
     * @param string $paramSmartID
     * @param array $query
     * @return array
     */
    public function getKnowledgeCategoryBySmartID(string $paramSmartID, array $query = []): array {
        $result = $this->get("/api/v2/knowledge-categories/".rawurlencode('$foreignID:'.$paramSmartID));
        $body = $result->getBody();
        return $body;
    }

    /**
     * @return string
     */
    public function getToken(): string {
        return $this->token;
    }

}
