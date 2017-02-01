<?php

if (!defined('APPLICATION'))
    exit();

/**
 * Rackspace API Common Functions
 *
 * Rackspace API functionality common to all Rackspace services.
 *    Credentials management
 *    Services token negotiation
 *    Services token storage/retrieval
 *
 * $Configuration['Rackspace']['API']['Account']['Vanilla']['Name'] = 'Vanilla';
 * $Configuration['Rackspace']['API']['Account']['Vanilla']['APIUser'] = 'navvywavvy';
 * $Configuration['Rackspace']['API']['Account']['Vanilla']['APIKey'] = 'c849e446e0cc138b4d8308b1ce1665b9';
 * $Configuration['Rackspace']['API']['Account']['Vanilla']['Gateway'] = 'US';
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @version 1.1
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package Api
 * @since 1.0
 */
class RackspaceAPI {

    const GATEWAY_US = 'https://auth.api.rackspacecloud.com/v1.1';
    const GATEWAY_UK = 'https://lon.auth.api.rackspacecloud.com/v1.1';

    protected static $Accounts;
    protected $Rackspace;
    protected $AccountName;
    protected $AccountAPIUser;
    protected $AccountAPIKey;
    protected $AccountGateway;
    protected $Services;
    protected $ServicesToken;
    protected $Service;
    protected $PreferredRegion;
    protected $CacheCredentials;

    public function __construct() {
        $this->Rackspace = new ProxyRequest(FALSE, array(
            'ConnectTimeout' => 5,
            'Timeout' => 60,
            'Redirects' => TRUE,
            'SSLNoVerify' => TRUE,
            'PreEncodePost' => TRUE,
            'Cookies' => FALSE
        ));

        $this->Services = NULL;
        $this->ServicesToken = NULL;
        $this->Context('public');
    }

    /**
     * Set the service catalog to use
     *
     * @param string $Service
     * @throws RackspaceAPIException
     * @throws RackspaceAPIUnknownServiceException
     * @return string
     */
    public function Service($Service = NULL) {
        if (!is_null($Service)) {
            $Service = strtolower(str_replace(' ', '', $Service));
            if (is_null($this->Services))
                throw new RackspaceAPIException("Services not yet loaded");

            if (!array_key_exists($Service, $this->Services))
                throw new RackspaceAPIUnknownServiceException("Service '{$Service}' is not available");

            $this->Service = $Service;
        }
        return $this->Service;
    }

    /**
     * Return the parsed services catalog
     *
     * @return array
     */
    public function Services() {
        return $this->Services;
    }

    /**
     * Authenticate to a specific account
     *
     * @param string $AccountName
     */
    public function Account($AccountName) {
        $Account = RackspaceAPI::GetAccount($AccountName);
        if (!$Account)
            throw new RackspaceAPINoCredentialsException("Unable to find credentials for '{$AccountName}'");

        $this->AccountName = $Account['Name'];
        $this->AccountAPIUser = $Account['APIUser'];
        $this->AccountAPIKey = $Account['APIKey'];
        $this->AccountGateway = strtoupper($Account['Gateway']);

        $this->Authenticate();
    }

    /**
     * Set a preference for a certain regional endpoint
     *
     * @param string $Region
     * @return string
     */
    public function PreferredRegion($Region = NULL) {
        if (!is_null($Region))
            $this->PreferredRegion = strtolower($Region);
        return $this->PreferredRegion;
    }

    /**
     * Set the exection context
     *
     * @param string $Context
     * @return string
     */
    public function Context($Context = NULL) {
        if (!is_null($Context)) {
            $Opts = array(
                'internal' => 'internal',
                'public' => 'public'
            );
            $this->Context = GetValue($Context, $Opts, 'public');
        }
        return empty($this->Context) ? 'public' : $this->Context;
    }

    /**
     * Get a Gateway URL
     *
     * @param string $GatewayLocation Location, either 'US' or 'UK'
     */
    protected static function GetGateway($GatewayLocation) {
        if ($GatewayLocation == 'US')
            return RackspaceAPI::GATEWAY_US;

        if ($GatewayLocation == 'UK')
            return RackspaceAPI::GATEWAY_UK;

        throw new RackspaceAPIBadGatewayException("No such gateway '{$GatewayLocation}'");
    }

    /**
     * Load account information
     *
     * @param string $AccountName Rackspace account to load
     * @return array Account Information
     */
    protected static function GetAccount($AccountName) {
        if (!is_array(self::$Accounts))
            self::$Accounts = array();
        if (!array_key_exists($AccountName, self::$Accounts)) {
            $Account = C("Rackspace.API.Account.{$AccountName}", FALSE);
            self::$Accounts[$AccountName] = $Account;
        }
        return GetValue($AccountName, self::$Accounts);
    }

    public static function AddAccount($AccountName, $Account) {
        if (!is_array(self::$Accounts))
            self::$Accounts = array();
        self::$Accounts[$AccountName] = $Account;
    }

    /**
     * Authenticate to the Authentication Service Gateway
     *   - Get a token
     *   - Get services catalog
     *
     * Response may be cached.
     *
     * @param string $Gateway
     * @param string $ApiUser
     * @param string $ApiKey
     * @param boolean $LastAttempt
     * @return string Services Token
     */
    protected function Authenticate($LastAttempt = FALSE) {
        $DataSource = 'unknown';
        $this->ServicesToken = NULL;
        $this->Services = NULL;
        $AuthCache = NULL;

        if ($this->CacheCredentials()) {
            $AuthCacheKey = $this->CacheKey($this->AccountGateway, $this->AccountAPIUser, $this->AccountAPIKey);

            // Check memcached
            $AuthCache = Gdn::Cache()->Get($AuthCacheKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => TRUE
            ));
        }

        // If memcached failed, ask Rackspace for a token
        if (!$this->CacheCredentials() || $AuthCache === Gdn_Cache::CACHEOP_FAILURE) {
            // Get Gateway URL
            $GatewayURL = RackspaceAPI::GetGateway($this->AccountGateway);
            $GatewayAuth = CombinePaths(array($GatewayURL, "auth"));

            $RequestArguments = array('credentials' => array(
                    'username' => $this->AccountAPIUser,
                    'key' => $this->AccountAPIKey
            ));
            $RequestBody = json_encode($RequestArguments);

            // Get token from Rackspace
            $AuthResponse = $this->Rackspace->Request(array(
                'URL' => $GatewayAuth,
                'Method' => 'POST',
                'PreEncodePost' => FALSE
                    ), $RequestBody, NULL, array(
                'Content-Type' => 'application/json'
            ));

            if (!$this->Rackspace->ResponseClass('20x'))
                throw new RackspaceAPIBadCredentialsException("Received error ({$this->Rackspace->ResponseStatus}) from authentication service", $AuthResponse, $this->Rackspace->ResponseStatus);

            // Parse JSON data into array
            $DataSource = 'api';
            $AuthData = json_decode($AuthResponse, TRUE);
            $AuthData = GetValue('auth', $AuthData);
        } else {
            $DataSource = 'cache';
            $AuthData = $AuthCache;
        }

        if (!$AuthData || !is_array($AuthData)) {
            $this->ExpireToken($AuthCacheKey);
            throw new RackspaceAPIGatewayException("Could not parse service catalog response from '{$DataSource}'");
        }

        // Parse token
        $TokenData = GetValueR('token', $AuthData);
        $TokenID = GetValue('id', $TokenData);
        $TokenExpiry = GetValue('expires', $TokenData);
        $TokenExpiryDateTime = strtotime($TokenExpiry);
        $TokenTTL = $TokenExpiryDateTime - time();

        // Token is expired, or nearly expired. Get a new one.
        if ($TokenTTL < 30) {
            $this->ExpireToken();

            // Try again
            if (!$LastAttempt)
                return $this->Authenticate(TRUE);

            throw new RackspaceAPIExpiredTokenException("Could not re-issue authentication token, after retry");
        }

        // Store token
        $this->ServicesToken = $TokenID;

        // Enumerate services
        $ServiceCatalog = GetValue('serviceCatalog', $AuthData);
        $this->Services = array();
        foreach ($ServiceCatalog as $Service => $ServiceData) {
            $ServiceName = strtolower(str_replace(' ', '', $Service));
            $this->Services[$ServiceName] = $ServiceData;
        }

        // Store token
        if ($DataSource == 'api' && $this->CacheCredentials()) {
            $CacheOp = Gdn::Cache()->Store($AuthCacheKey, $AuthData, array(
                Gdn_Cache::FEATURE_NOPREFIX => TRUE,
                Gdn_Cache::FEATURE_EXPIRY => floor($TokenTTL * 0.9)   // Keep tokens for 90% of their life
            ));
        }

        return TRUE;
    }

    /**
     * Whether to use Garden memcaching for tokens
     *
     * @param boolean $CacheCredentials
     * @return boolean
     */
    public function CacheCredentials($CacheCredentials = NULL) {
        if (!is_null($CacheCredentials))
            $this->CacheCredentials = (bool)$CacheCredentials;

        return (bool)$this->CacheCredentials;
    }

    /**
     * Create a cache key from account data
     *
     * @param string $Gateway
     * @param string $ApiUser
     * @param string $ApiKey
     * @return string
     */
    protected function CacheKey($Gateway, $ApiUser, $ApiKey) {
        $ApiHash = md5(implode('-', array($ApiUser, $ApiKey)));
        $AuthCacheKey = FormatString("rackspace.api.{gateway}-{apihash}.auth", array(
            'gateway' => $Gateway,
            'apihash' => $ApiHash
        ));

        return $AuthCacheKey;
    }

    /**
     * Remove the current auth token from cache
     *
     */
    protected function ExpireToken() {
        if ($this->CacheCredentials()) {
            $AuthCacheKey = $this->CacheKey($this->AccountGateway, $this->AccountAPIUser, $this->AccountAPIKey);
            Gdn::Cache()->Remove($AuthCacheKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => TRUE
            ));
        }

        $this->ServicesToken = NULL;
        $this->Services = NULL;
    }

    /**
     * Perform a request againt the active service endpoint
     *
     * @param string $Url
     * @param string $Method
     * @param type $Parameters
     * @param type $Options
     */
    public function Request($Method, $Url, $Options = NULL, $Parameters = NULL, $Headers = NULL, $Files = NULL) {

        $DefaultOptions = array(
            'Retry' => 1,
            'AutoToken' => TRUE,
            'SaveAs' => FALSE,
            'Binary' => FALSE
        );

        if (!is_array($Options))
            $Options = array();
        $Options = array_merge($DefaultOptions, $Options);

        $ServicerootURL = $this->GetUrl();
        $RequestUrl = CombinePaths(array($ServicerootURL, $Url));

        if (!is_array($Headers))
            $Headers = array();

        if ($Options['AutoToken'])
            $Headers['X-Auth-Token'] = $this->ServicesToken;

        $RequestOptions = array();
        if ($Options['SaveAs'] !== FALSE)
            $RequestOptions['SaveAs'] = $Options['SaveAs'];
        if ($Options['Binary'])
            $RequestOptions['TransferMode'] = 'binary';

        $RequestOptions = array_merge($RequestOptions, array(
            'URL' => $RequestUrl,
            'Method' => $Method
        ));

        $Response = $this->Rackspace->Request(
                $RequestOptions, $Parameters, $Files, $Headers);

        // Token probably expired
        if ($this->Rackspace->ResponseClass('401')) {
            $Retries = GetValue('Retry', $Options);
            if ($Retries) {
                $this->ExpireToken();
                $Options['Retry'] --;
                return $this->Request($Method, $Url, $Parameters, $Options);
            }
        }

        if (!$this->Rackspace->ResponseClass('2xx'))
            throw new RackspaceAPICommunicationException("Received error code {$this->Rackspace->ResponseStatus} while communicating with API", array(
        'Options' => $RequestOptions,
        'Headers' => $Headers,
        'Response' => $Response
            ), $this->Rackspace->ResponseStatus);

        if ($Response) {
            $Format = GetValue('format', $Parameters, FALSE);

            if ($Format == 'json')
                $Response = @json_decode($Response, TRUE);

            if ($Format == 'xml')
                $Response = simplexml_load_string($Response);
        }

        return $Response;
    }

    /**
     * Get the best URL for the current service
     *
     * @return string
     * @throws RackspaceAPIUnknownServiceException
     * @throws RackspaceAPIException
     */
    protected function GetUrl() {
        $Service = GetValue($this->Service, $this->Services, NULL);
        if (empty($Service))
            throw new RackspaceAPIUnknownServiceException("Service '{$this->Service}' is not defined");

        $PreferredRegion = $this->PreferredRegion();
        $Endpoint = NULL;
        foreach ($Service as $ServiceEndpoint) {
            $EndpointRegion = strtolower(GetValue('region', $ServiceEndpoint, NULL));
            if (!empty($PreferredRegion) && !empty($EndpointRegion)) {
                if ($EndpointRegion == $PreferredRegion) {
                    $Endpoint = $ServiceEndpoint;
                    break;
                }
            }
        }

        if (!$Endpoint)
            $Endpoint = array_shift($Service);

        // Determine Endpoint URL
        $PreferredContext = $this->Context();
        $ContextURL = array();

        $InternalURL = GetValue('internalURL', $Endpoint, FALSE);
        if ($InternalURL)
            $ContextURL['internal'] = $InternalURL;

        $PublicURL = GetValue('publicURL', $Endpoint, FALSE);
        if ($PublicURL)
            $ContextURL['public'] = $PublicURL;

        $PreferredURL = GetValue($PreferredContext, $ContextURL, FALSE);
        if (!empty($PreferredURL))
            return $PreferredURL;
        if (!empty($PublicURL))
            return $PublicURL;

        throw new RackspaceAPIException("No services URL available for '{$this->Service}'");
    }

}

class RackspaceAPIException extends Exception {

    protected $TroubleMessage = NULL;

    public function __construct($Message, $Response = NULL, $Code = 500) {
        parent::__construct($Message, $Code);

        if (!is_array($Response)) {
            $JsonResponse = json_decode($Response, true);
            if ($JsonResponse)
                $Response = array_pop($JsonResponse);
        }

        $this->TroubleMessage = GetValueR('message', $Response, print_r($Response, TRUE));
    }

    public function getTroubleMessage() {
        return $this->TroubleMessage;
    }

    public function __toString() {
        return get_class($this) . ": [" . $this->getCode() . "] " . $this->getMessage() . " - " . $this->getTroubleMessage();
    }

}

class RackspaceAPINoCredentialsException extends RackspaceAPIException {

}

class RackspaceAPIGatewayException extends RackspaceAPIException {

}

class RackspaceAPIBadCredentialsException extends RackspaceAPIException {

}

class RackspaceAPIBadGatewayException extends RackspaceAPIException {

}

class RackspaceAPIExpiredTokenException extends RackspaceAPIException {

}

class RackspaceAPIUnknownServiceException extends RackspaceAPIException {

}

class RackspaceAPICommunicationException extends RackspaceAPIException {

}

class RackspaceAPIHashMismatchException extends RackspaceAPIException {

}
