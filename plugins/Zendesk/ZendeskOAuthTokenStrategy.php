<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ZendeskOAuthTokenStrategy.
 */
class ZendeskOAuthTokenStrategy implements ZendeskAuthenticationStrategy {

    /**
     * @var string
     */
    private $apiToken;
    /**
     * @var string
     */
    private $accessToken;

    /**
     * Setup Properties.
     *
     * @param string $accessToken
     */
    public function __construct(string $accessToken) {
        $this->accessToken = $accessToken;
    }

    /**
     * Define authorization method for OAuthToken.
     *
     * @return string
     */
    public function getAuthentication(): string {
        return 'Authorization: Bearer '.$this->accessToken;
    }
}
