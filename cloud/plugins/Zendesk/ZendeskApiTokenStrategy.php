<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ZendeskApiTokenStrategy.
 */
class ZendeskApiTokenStrategy implements ZendeskAuthenticationStrategy {

    /**
     * @var string
     */
    private $apiToken;
    /**
     * @var string
     */
    private $apiUser;

    /**
     * Setup Properties.
     *
     * @param string $apiToken
     * @param string $apiUser
     */
    public function __construct(
        string $apiToken,
        string $apiUser
    ) {
        $this->apiToken = $apiToken;
        $this->apiUser = $apiUser;
    }

    /**
     * Define authorization method for API token.
     *
     * @return string
     */
    public function getAuthenticationHeader(): string {
        return 'Authorization: Basic '.base64_encode($this->apiUser.'/token:'.$this->apiToken);
    }
}
