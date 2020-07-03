<?php
/**
 * @author Alexandre (Daazku) Chouinard
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Exception;
use Firebase\JWT\JWT;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Provide necessary information
 *
 * Class AbstractSearchApiInformationProvider
 */
abstract class AbstractElasticHttpConfig {

    /** @var int */
    private $time;

    /**
     * The base URL to the API.
     *
     * @return string
     */
    abstract public function getBaseUrl(): string;

    /**
     * Return the auth JWT.
     *
     * It has a TTL of 10 seconds so it should be crafted as late as possible :).
     *
     * @return string
     *
     * @throws Exception When something goes wrong.
     */
    final public function getAuthJWT() {
        $timestamp = $this->getTime();
        $fullPayload = $this->getTokenPayload() + [
            'iat' => $timestamp,
            'exp' => $timestamp + 10,
        ];

        $var = JWT::encode($fullPayload, $this->getSecret(), 'HS512');
        return $var;
    }

    /**
     * @return int
     */
    public function getTime(): int {
        return $this->time ?? time();
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void {
        $this->time = $time;
    }

    /**
     * An HTTP middleware function for applying authentication to elasticsearch requests.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     */
    final public function requestAuthMiddleware(HttpRequest $request, callable $next): HttpResponse {
        $request->setHeader('Authorization', 'Bearer '.$this->getAuthJWT());
        return $next($request);
    }

    /**
     * Returns information needed by the API.
     *
     * Refer to the API documentation to determine what is needed here.
     * The last time this docblock was updated it was "siteId" and "accountId"
     *
     * @return array
     */
    abstract protected function getTokenPayload(): array;

    /**
     * The secret used to encrypt the JWT.
     *
     * @return string
     */
    abstract protected function getSecret(): string;
}
