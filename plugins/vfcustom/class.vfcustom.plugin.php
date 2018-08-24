<?php

/**
 * @copyright 2010-2018 Vanilla Forums Inc
 * @license Proprietary
 */

$PluginInfo['vfcustom'] = array(
    'Name' => 'Custom Domain',
    'Description' => 'Make your Vanilla Forum accessible from a different domain.',
    'Version' => '2.1.3',
    'MobileFriendly' => true,
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'Icon' => 'custom_domain.png',
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com'
);

use Garden\Http\HttpClient;

/**
 * Custom Domain Plugin
 *
 * This plugin allows VanillaForums.com customers to enable access to their site
 * from a custom domain pointing at our servers.
 *
 * Changes:
 *  2.0     Compatibility with Infrastructure
 *  2.1     Improvement to UI
 *  2.1.1   Fix resolve detection
 *  2.1.2   Fix the fix
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @package infrastructure
 * @subpackage vfcustom
 * @since 1.0
 */
class VfCustomPlugin extends Gdn_Plugin {

    public function __construct() {

    }

    /**
     * Add Custom Domain link to panel
     *
     * @param Gdn_Controller $Sender
     */
    public function base_getAppSettingsMenuItems_handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->addLink('Appearance', 'Custom Domain', 'settings/customdomain', 'Garden.Settings.Manage');
    }

    /**
     * Virtual Controller Dispatcher
     *
     * @param Gdn_Controller $sender
     */
    public function settingsController_customdomain_create($sender) {
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Creates a "Custom Domain" upgrade offering screen where users can purchase
     * & implement a custom domain.
     *
     * @param Gdn_Controller $sender
     */
    public function controller_index($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('settings/customdomain');
        $sender->Form = new Gdn_Form();

        $sender->title('Custom Domain Name');

        $client = Infrastructure::client();
        $sender->setData('name', $client);

        $context = Infrastructure::getContext($client, false);
        $site = val('site', $context);
        $sender->setData('site', $site);

        $clientLookup = `host -t A -T {$client}`;
        $matched = preg_match('`has address ([\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})`im',$clientLookup,$matches);
        if ($matched) {
            $clientIP = $matches[1];
        }
        $sender->setData('clientIP', $clientIP);

        $sender->setData('steps', true);
        $sender->setData('attempt', $sender->Form->isPostBack());

        if (stristr(Gdn::request()->getValue('HTTP_REFERER'), 'customdomain/remove')) {
            $sender->setData('attempt', true);
            $sender->setData('removed', true);
        }

        if ($sender->Form->isPostBack()) {
            try {
                $requestedDomain = trim(strtolower($sender->Form->getValue('CustomDomain')));

                $this->checkAvailable($requestedDomain);
                $this->checkConfiguration($requestedDomain);
                $this->setCustomDomain($requestedDomain);
                $site['domain'] = $requestedDomain;
                $sender->setData('site', $site);
            } catch (Exception $ex) {
                $sender->setData('failed', true);
                $sender->setData('errorType', get_class($ex));
                $sender->setData('errorText', $ex->getMessage());
                $sender->Form->addError($ex);
            }
        }

        $sender->render('customdomain', '', 'plugins/vfcustom');
    }

    /**
     * Test request pathway
     *
     * @param $sender
     */
    public function controller_validate($sender, $args) {
        safeHeader("Content-Type: text/plain", true);

        $suppliedKey = $args[0];
        $key = Gdn::installationID();
        if ($key != $suppliedKey) {
            exit;
        }

        echo $this->getValidationCode();
        exit;
    }

    /**
     * Get validation code
     *
     * Generate the expected code for HTTP domain validation.
     *
     * @return string
     */
    private function getValidationCode() {
        $key = Gdn::installationID();
        $secret = Gdn::installationSecret();
        $hashSecret = hash_hmac('sha256', $key, $secret);

        return sprintf("%d-%s", Infrastructure::siteID(), $hashSecret);
    }

    /**
     * Check whether the domain is pointing at us
     *
     * @param $domain
     * @return bool
     * @throws OriginValidationException
     */
    private function checkConfiguration($domain) {
        $key = Gdn::installationID();
        $uri = url("/settings/customdomain/validate/{$key}", false);

        // Prepare softserve respone
        $softRequest = paths($domain, $uri);
        $softResponse = $this->getValidationCode();
        Infrastructure::prepareSoftServe($softRequest, $softResponse);

        // Test softserve response
        $client = new HttpClient(paths('http://', $domain));
        $response = $client->get($uri);

        $rawsponse = $response->getRawBody();
        if ($rawsponse != $this->getValidationCode()) {
            throw new OriginValidationException("The origin did not response with the required validation code.");
        }

        return true;
    }

    /**
     * Check whether the domain is available
     *
     * @param $domain
     * @return bool
     * @throws CommunicationErrorException
     * @throws IllegalDomainException
     * @throws UnavailableDomainException
     */
    private function checkAvailable($domain) {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new InvalidDomainException("The domain you requested is invalid");
        }

        $context = Infrastructure::context();
        $site = val('site', $context);
        $siteID = val('siteid', $site);

        $illegalDomains = array(
            'vanillaforums.com',
            'vanilladev.com'
        );
        foreach ($illegalDomains as $illegalDomain) {
            $regexIllegalDomain = str_replace('.', '\.', $illegalDomain);
            if (preg_match("/(?:.*\.)?{$regexIllegalDomain}/i", $domain)) {
                throw new IllegalDomainException("The domain you requested is prohibited");
            }
        }

        $domainAvailableQuery = Communication::orchestration('/site/checkdomain')
            ->method('get')
            ->parameter('siteid', $siteID)
            ->parameter('domain', $domain);
        $domainAvailable = $domainAvailableQuery->send();

        if (!$domainAvailableQuery->responseClass('2xx')) {
            throw new CommunicationErrorException("Problem verifying domain availability. Please contact support.", $domainAvailableQuery);
        }

        $available = val('available', $domainAvailable, false);
        if (!$available) {
            throw new UnavailableDomainException("The domain you requested is currently in use");
        }

        return true;
    }

    public function controller_remove($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('settings/customdomain');
        $sender->Form = new Gdn_Form();

        $context = Infrastructure::context();
        $site = val('site', $context);
        $siteID = val('siteid', $site);

        $removeDomainQuery = Communication::orchestration('/site/domain')
                ->method('delete')
                ->parameter('siteid', $siteID);
        $removedomain = $removeDomainQuery->send();

        if ($removeDomainQuery->responseClass('2xx')) {
            $cookieDomain = valr('authenticate', $removedomain);
            $this->authenticate($cookieDomain);
        }

        sleep(1);

        redirect(url('settings/customdomain'));
    }

    /**
     * Set a site's custom domain on Orchestration
     *
     * @param $domain
     * @return bool
     * @throws CommunicationErrorException
     */
    protected function setCustomDomain($domain) {
        $context = Infrastructure::context();
        $site = val('site', $context);
        $siteID = val('siteid', $site);

        $setDomainQuery = Communication::orchestration('/site/domain')
                ->method('post')
                ->parameter('siteid', $siteID)
                ->parameter('domain', $domain);
        $setdomain = $setDomainQuery->send();

        if (!$setDomainQuery->responseClass('2xx')) {
            throw new CommunicationErrorException("Failed to set custom domain. Please contact support.", $setDomainQuery);
        }

        // Re authenticate
        $cookieDomain = valr('authenticate', $setdomain);
        $this->authenticate($cookieDomain);

        return true;
    }

    /**
     * Re-authenticates a user with the current configuration.
     *
     * When the custom domain is changed, the current cookie will likely not be valid. This method applies a new cookie
     * with the new custom domain.
     *
     * @param $cookieDomain
     */
    private function authenticate($cookieDomain) {
        // If there was a request to reauthenticate (ie. we've been shifted to a custom domain and the user needs to reauthenticate)
        $identity = new Gdn_CookieIdentity();
        $identity->init([
            'Salt' => Gdn::config('Garden.Cookie.Salt'),
            'Name' => Gdn::config('Garden.Cookie.Name'),
            'Domain' => $cookieDomain
        ]);
        $identity->setIdentity(Gdn::session()->UserID, true);
    }

    /**
     * No setup required.
     */
    public function setup() {

    }

}

class InvalidDomainException extends Exception {}
class OriginValidationException extends Exception {}
class IllegalDomainException extends Exception {}
class UnavailableDomainException extends Exception {}
class AddressMismatchDomainException extends Exception {}
class RecordConfigurationException extends Exception {}
