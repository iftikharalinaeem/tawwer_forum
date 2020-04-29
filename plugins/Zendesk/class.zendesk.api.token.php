<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ZendeskApiToken.
 */
class ZendeskApiToken extends Zendesk {

    /**
     * Setup Properties.
     *
     * @param IZendeskHttpRequest $curlRequest Curl Request Object.
     * @param string $url Url to API.
     * @param string $apiToken
     * @param string $apiUser
     */
    public function __construct(
        IZendeskHttpRequest $curlRequest,
        $url,
        string $apiToken,
        string $apiUser
    ) {
        parent::__construct($curlRequest, $url, '');
        $this->apiToken = $apiToken;
        $this->apiUser = $apiUser;
    }

    /**
     * Define Authorization header.
     * Set Authorization: Basic by using ApiUser and ApiToken.
     */
    protected function defineAuthorizationHeader() {
        $this->curl->setOption(
            CURLOPT_HTTPHEADER,
            ['Content-type: application/json', 'Authorization: Basic '.base64_encode($this->apiUser.'/token:'.$this->apiToken)]
        );
    }
}
