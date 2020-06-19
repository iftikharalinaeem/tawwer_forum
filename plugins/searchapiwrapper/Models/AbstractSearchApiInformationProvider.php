<?php

namespace Vanilla\Inf\Search;

use Firebase\JWT\JWT;

/**
 * Provide necessary information
 *
 * Class AbstractSearchApiInformationProvider
 */
abstract class AbstractSearchApiInformationProvider
{
    /**
     * The base URL to the API.
     *
     * @return string
     */
    public abstract function getBaseUrl(): string;

    /**
     * The secret used to encrypt the JWT.
     *
     * @return string
     */
    protected abstract function getSecret(): string;

    /**
     * Returns information needed by the API.
     *
     * Refer to the API documentation to determine what is needed here.
     * The last time this docblock was updated it was "siteId" and "accountId"
     *
     * @return array
     */
    protected abstract function getTokenPayload(): array;

    /**
     * Return the auth JWT.
     *
     * It has a TTL of 10 seconds so it should be crafted as late as possible :).
     *
     * @return string
     */
    public final function getAuthJWT()
    {
        $timestamp = time();
        $fullPayload = $this->getTokenPayload() + [
            'iat' => $timestamp,
            'exp' => $timestamp + 10,
        ];
        return JWT::encode($fullPayload, $this->getSecret(), 'HS512');
    }
}
