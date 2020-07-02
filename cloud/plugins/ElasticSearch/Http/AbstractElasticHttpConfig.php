<?php
/**
 * @author Alexandre (Daazku) Chouinard
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Exception;
use Firebase\JWT\JWT;

/**
 * Provide necessary information
 *
 * Class AbstractSearchApiInformationProvider
 */
abstract class AbstractElasticHttpConfig {
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
        $timestamp = time();
        $fullPayload = $this->getTokenPayload() + [
            'iat' => $timestamp,
            'exp' => $timestamp + 10,
        ];

        $var = JWT::encode($fullPayload, $this->getSecret(), 'HS512');
        return $var;
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
