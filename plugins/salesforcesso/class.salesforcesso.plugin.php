<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class SalesForceSSOPlugin
 *
 * Expose the functionality of the core class Gdn_OAuth2 to create SSO workflow specific to Salesforce.
 */

class SalesForceSSOPlugin extends Gdn_OAuth2 {

    protected $userProfileURL = null;

    /**
     * Set the key for saving OAuth settings in GDN_UserAuthenticationProvider
     */
    public function __construct() {
        $this->setProviderKey('salesforcesso');
        $this->settingsView = 'plugins/settings/salesforcesso';
        $this->requestAccessTokenParams = ['scope' => null];
    }


    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User profile from provider.
     */
    public function getProfile() {
        $uri = $this->requireVal('id', $this->accessTokenResponse, 'token request resonse');
        $params = array(
            'access_token' => $this->accessToken()
        );
        // Request the profile from the Authentication Provider
        $rawProfile = $this->api($uri, 'GET', $params);

        // Translate the keys of the profile sent to match the keys we are looking for.
        $profile = $this->translateProfileResults($rawProfile);

        // Log the results when troubleshooting.
        $this->log('getProfile API call', ['ProfileUrl' => $uri, 'Params' => $params, 'RawProfile' => $rawProfile, 'Profile' => $profile]);
        return $profile;
    }
}
