<?php

/**
 * Rackspace API Common Functions
 *
 * Rackspace API functionality common to all Rackspace services.
 *    Credentials management
 *    Services token negotiation
 *    Services token storage/retrieval
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @version 1.1
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package infrastructure
 * @since 1.0
 */
class Rackspace {

    /**
     * HTTP Interface
     * @var ProxyRequest
     */
    protected $Rackspace;
    protected $AuthURL;
    protected $AccountAPIUser;
    protected $AccountAPIKey;
    protected $AccountID;
    protected $Services;
    protected $ServicesToken;
    protected $Service;
    protected $PreferredRegion;
    protected $CacheCredentials;

    public function __construct($AuthURL, $Identity) {
        $this->Rackspace = new RackspaceProxyRequest(false, array(
            'ConnectTimeout' => 15,
            'Timeout' => 60,
            'Redirects' => true,
            'SSLNoVerify' => true,
            'PreEncodePost' => true,
            'Cookies' => false
        ));

        $this->AuthURL = $AuthURL;
        $this->AccountAPIUser = val('username', $Identity);
        $this->AccountAPIKey = val('apiKey', $Identity);

        $this->Services = null;
        $this->ServicesToken = null;

        $this->PreferredRegion(val('region', $Identity));
        $this->Context(val('context', $Identity, 'public'));
        $this->Authenticate();
    }

    /**
     * Set the service catalog to use
     *
     * @param string $Service
     * @throws RackspaceAPIException
     * @throws RackspaceAPIUnknownServiceException
     * @return string
     */
    public function Service($Service = null) {
        if (!is_null($Service)) {
            $Service = strtolower(str_replace(' ', '', $Service));
            if (is_null($this->Services)) {
                throw new RackspaceAPIException("Services not yet loaded");
            }

            if (!array_key_exists($Service, $this->Services)) {
                throw new RackspaceAPIUnknownServiceException("Service '{$Service}' is not available");
            }

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
     * Set a preference for a certain regional endpoint
     *
     * @param string $Region
     * @return string
     */
    public function PreferredRegion($Region = null) {
        if (!is_null($Region)) {
            $this->PreferredRegion = strtolower($Region);
        }
        return $this->PreferredRegion;
    }

    /**
     * Set the exection context
     *
     * @param string $Context
     * @return string
     */
    public function Context($Context = null) {
        if (!is_null($Context)) {
            $Opts = array(
                'internal' => 'internal',
                'private' => 'internal',
                'external' => 'public',
                'public' => 'public'
            );
            $this->Context = val($Context, $Opts, 'public');
        }
        return empty($this->Context) ? 'public' : $this->Context;
    }

    /**
     * Authenticate to the Authentication Service Gateway
     *   - Get a token
     *   - Get services catalog
     *
     * Response may be cached.
     *
     * @param boolean $LastAttempt
     * @return string Services Token
     */
    protected function Authenticate($LastAttempt = false) {
        $DataSource = 'unknown';
        $this->ServicesToken = null;
        $this->Services = null;
        $this->AccountID = null;
        $AuthCache = null;

        if ($this->CacheCredentials()) {
            $AuthCacheKey = $this->CacheKey($this->AuthURL, $this->AccountAPIUser, $this->AccountAPIKey);

            // Check memcached
            $AuthCache = Gdn::Cache()->Get($AuthCacheKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => true
            ));
        }

        // If memcached failed, ask Rackspace for a token
        if (!$this->CacheCredentials() || $AuthCache === Gdn_Cache::CACHEOP_FAILURE) {
            // Get Gateway URL
            $GatewayAuth = paths($this->AuthURL, "auth");

            $RequestArguments = array('credentials' => array(
                'username' => $this->AccountAPIUser,
                'key' => $this->AccountAPIKey
            ));
            $RequestBody = $this->MakeRequest($RequestArguments, 'json');

            // Get token from Rackspace
            $AuthResponse = $this->Rackspace->Request(array(
                'URL' => $GatewayAuth,
                'Method' => 'POST',
                'PreEncodePost' => false
            ), $RequestBody, null, array(
                'Content-Type' => 'application/json'
            ));

            if (!$this->Rackspace->ResponseClass('20x')) {
                $Status = $this->Rackspace->Status();
                throw new RackspaceAPIBadCredentialsException(
                    "Received error ({$Status}) from authentication service",
                    $AuthResponse,
                    $Status
                );
            }

            // Parse JSON data into array
            $DataSource = 'api';
            $AuthData = json_decode($AuthResponse, true);
            $AuthData = val('auth', $AuthData);
        } else {
            $DataSource = 'cache';
            $AuthData = $AuthCache;
        }

        if (!$AuthData || !is_array($AuthData)) {
            $this->ExpireToken($AuthCacheKey);
            throw new RackspaceAPIGatewayException("Could not parse service catalog response from '{$DataSource}'");
        }

        // Parse token
        $TokenData = valr('token', $AuthData);
        $TokenID = val('id', $TokenData);
        $TokenExpiry = val('expires', $TokenData);
        $TokenExpiryDateTime = strtotime($TokenExpiry);
        $TokenTTL = $TokenExpiryDateTime - time();

        // Token is expired, or nearly expired. Get a new one.
        if ($TokenTTL < 30) {
            $this->ExpireToken();

            // Try again
            if (!$LastAttempt) {
                return $this->Authenticate(true);
            }

            throw new RackspaceAPIExpiredTokenException("Could not re-issue authentication token, after retry");
        }

        // Store token
        $this->ServicesToken = $TokenID;

        // Enumerate services
        $AccountID = null;
        $ServiceCatalog = val('serviceCatalog', $AuthData);
        $this->Services = array();
        foreach ($ServiceCatalog as $Service => $ServiceData) {
            $ServiceName = strtolower(str_replace(' ', '', $Service));
            $this->Services[$ServiceName] = $ServiceData;

            // Parse AccountID
            if (is_null($this->AccountID)) {
                foreach ($ServiceData as $Endpoint) {
                    $ParseURL = null;
                    if (is_null($ParseURL)) {
                        $ParseURL = val('publicURL', $Endpoint, null);
                    }

                    if (is_null($ParseURL)) {
                        $ParseURL = val('internalURL', $Endpoint, null);
                    }

                    if (is_null($ParseURL)) {
                        continue;
                    }

                    $Matched = preg_match("`v[\d]+\.[\d]+\/([\d]+)$`", $ParseURL, $Matches);
                    if (!$Matched) {
                        continue;
                    }

                    $this->AccountID = $Matches[1];
                    break;
                }
            }
        }

        // Store token
        if ($DataSource == 'api' && $this->CacheCredentials()) {
            Gdn::Cache()->Store($AuthCacheKey, $AuthData, array(
                Gdn_Cache::FEATURE_NOPREFIX => true,
                Gdn_Cache::FEATURE_EXPIRY => floor($TokenTTL * 0.9)   // Keep tokens for 90% of their life
            ));
        }

        return true;
    }

    /**
     * Whether to use Garden memcaching for tokens
     *
     * @param boolean $CacheCredentials
     * @return boolean
     */
    public function CacheCredentials($CacheCredentials = null) {
        if (!is_null($CacheCredentials)) {
            $this->CacheCredentials = (bool)$CacheCredentials;
        }

        return (bool)$this->CacheCredentials;
    }

    /**
     * Create a cache key from account data
     *
     * @param string $AuthURL
     * @param string $ApiUser
     * @param string $ApiKey
     * @return string
     */
    protected function CacheKey($AuthURL, $ApiUser, $ApiKey) {
        $ApiHash = md5(implode('-', array($ApiUser, $ApiKey)));
        $AuthCacheKey = FormatString("rackspace.api.{authurl}-{apihash}.auth", array(
            'authurl' => md5($AuthURL),
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
            $AuthCacheKey = $this->CacheKey($this->AuthURL, $this->AccountAPIUser, $this->AccountAPIKey);
            Gdn::Cache()->Remove($AuthCacheKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => true
            ));
        }

        $this->ServicesToken = null;
        $this->Services = null;
    }

    /**
     * Get services token
     *
     * @return string|null
     */
    public function GetToken() {
        if (!$this->ServicesToken) {
            $this->Authenticate();
        }
        return $this->ServicesToken;
    }

    /**
     * Perform a request againt the active service endpoint
     *
     * @param string $Method
     * @param string $Url
     * @param array $Options Optional
     * @param array $Parameters Optional
     * @param array $Headers Optional
     * @param array $Files Optional
     */
    public function Request($Method, $Url, $Options = null, $Parameters = null, $Headers = null, $Files = null) {

        $DefaultOptions = array(
            'Retry' => 1,
            'Timeout' => 60,
            'AutoToken' => true,
            'SaveAs' => false,
            'Stream' => false,
            'Binary' => false,
            'Debug' => false
        );

        if (!is_array($Options)) {
            $Options = array();
        }
        $Options = array_merge($DefaultOptions, $Options);

        if (!StringBeginsWith($Url, 'http')) {
            $ServicerootURL = $this->GetUrl();
            $RequestUrl = paths($ServicerootURL, $Url);
        } else {
            $RequestUrl = $Url;
        }

        if (!is_array($Headers)) {
            $Headers = array();
        }

        if ($Options['AutoToken']) {
            $Headers['X-Auth-Token'] = $this->ServicesToken;
        }

        $RequestOptions = array();
        if ($Options['SaveAs'] !== false) {
            $RequestOptions['SaveAs'] = $Options['SaveAs'];
        }
        if ($Options['Binary']) {
            $RequestOptions['TransferMode'] = 'binary';
        }
        if ($Options['Debug']) {
            $RequestOptions['Debug'] = true;
        }
        if ($Options['Stream'] !== false) {
            $RequestOptions['Stream'] = $Options['Stream'];
        }

        // Set timeout
        $RequestOptions['Timeout'] = $Options['Timeout'];

        $RequestOptions = array_merge(
            $RequestOptions,
            array(
                'URL' => $RequestUrl,
                'Method' => $Method
            )
        );

        do {

            $Retry = false;
            $Response = $this->Rackspace->Request(
                $RequestOptions,
                $Parameters,
                $Files,
                $Headers
            );

            // Token probably expired
            if ($this->Rackspace->responseClass('401')) {
                $Retry = $Options['Retry'];
                if ($Retry) {
                    $this->ExpireToken();
                    $this->Authenticate();

                    $Options['Retry']--;
                    continue;
                }
            }

        } while ($Retry);

        // Check for final auth failure
        if ($this->Rackspace->responseClass('401')) {
            throw new RackspaceAPIProblem("Could not re-authenticate", array(
                'message' => 'Exhausted all retries but problem persists',
                'request' => print_r($RequestOptions, true),
                'response' => $this->Rackspace->Body()
            ), 500);
        }

        // Check for problems with the request
        $this->Fault();

        return $this->Rackspace->parsedBody ? $this->Rackspace->parsedBody : $Response;
    }

    /**
     * Perform a request againt the active service endpoint
     *
     * This method is an alias for RackspaceAPI::Request(), but it modifies the
     * headers to cause the requestion to be application/json.
     *
     * @param string $Method
     * @param string $Url
     * @param array $Options Optional
     * @param array $Parameters Optional
     * @param array $Headers Optional
     * @param array $Files Optional
     */
    public function JsonRequest($Method, $Url, $Options = null, $Parameters = null, $Headers = null, $Files = null) {
        if (!is_array($Headers)) {
            $Headers = array();
        }

        $Headers['Content-Type'] = 'application/json';
        $Headers['Accept'] = 'application/json';

        $this->Request($Method, $Url, $Options, $Parameters, $Headers, $Files);
        return $this->Rackspace->parsedBody;
    }

    /**
     * Perform a request againt the active service endpoint
     *
     * This method is an alias for RackspaceAPI::Request(), but it modifies the
     * headers to cause the requestion to be application/xml.
     *
     * @param string $Method
     * @param string $Url
     * @param array $Options Optional
     * @param array $Parameters Optional
     * @param array $Headers Optional
     * @param array $Files Optional
     */
    public function XmlRequest($Method, $Url, $Options = null, $Parameters = null, $Headers = null, $Files = null) {
        if (!is_array($Headers)) {
            $Headers = array();
        }

        $Headers['Content-Type'] = 'application/xml';
        $Headers['Accept'] = 'application/xml';

        $this->Request($Method, $Url, $Options, $Parameters, $Headers, $Files);
        return $this->Rackspace->parsedBody;
    }

    public function Paginate(&$Limit, &$Offset, &$Received) {
        if ($Limit && $Received == $Limit) {
            $Offset += $Received;
            return true;
        }

        return false;
    }

    /**
     * Check for errors and throw exceptions
     *
     * @throws RackspaceAPIProblem
     * @return type
     */
    public function Fault() {

        // These are fine, do nothing
        if ($this->Rackspace->ResponseClass('1xx')) {
            return;
        }
        if ($this->Rackspace->ResponseClass('2xx')) {
            return;
        }
        if ($this->Rackspace->ResponseClass('3xx')) {
            return;
        }

        $ErrorBody = $this->Rackspace->parsedBody;

        // If we get here, there was a problem
        if (is_array($ErrorBody)) {
            $ProblemStringCode = array_keys($ErrorBody);
            $ProblemStringCode = is_array($ProblemStringCode) ? array_shift($ProblemStringCode) : 'unknownError';
        } else {
            $ErrorBody = $this->Rackspace->Body();
            $ProblemStringCode = 'unknownError';
        }

        // Target error contents if we know what kind it is
        $ProblemMessage = $ProblemStringCode . ':';
        if ($ProblemStringCode !== 'unknownError') {
            $ErrorBody = val($ProblemStringCode, $ErrorBody);
        }

        // Message
        $ProblemMessage .= val("message", $ErrorBody, 'RackspaceAPI/unknown');

        // Details
        $ProblemDetails = val('details', $ErrorBody, $this->Rackspace->Body());
        $ProblemRequest = $this->Rackspace->Body();

        $Status = $this->Rackspace->Status();
        $Exception = new RackspaceAPIProblem($ProblemMessage, array(
            'message' => $ProblemDetails,
            'request' => $ProblemRequest,
            'response' => $this->Rackspace->Body()
        ), $Status);
        throw $Exception;
    }

    /**
     * Encode a request
     *
     * @param array $Request
     * @param type $RequestType
     */
    public function MakeRequest($Request, $RequestType) {
        switch ($RequestType) {
            case 'json':
                return json_encode($Request, JSON_NUMERIC_CHECK);
                break;

            case 'xml':
                $RootNode = array_shift(array_keys($Request));
                $Xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$RootNode}></{$RootNode}>");
                return self::ArrayXml($Request, $Xml);
                break;
        }

        return $Request;
    }

    /**
     * Check if the given array corresponds to a remote asynchronous job
     *
     * @param array $Job
     * @return boolean
     */
    public function IsAsynchronous($Job) {
        if (!is_array($Job)) {
            return false;
        }
        if (!array_key_exists('jobId', $Job) || !array_key_exists('callbackUrl', $Job)) {
            return false;
        }
        return true;
    }

    /**
     * Wait for this remote job to finish before proceeding
     *
     * @param array $Job
     * @param integer $Delay Optional. Number of seconds to sleep between requests.
     * @param integer $MaxRunTime Optional. Maximum amount of time to wait for the job to complete.
     */
    public function WaitForCompletion($Job, $Delay = 1, $MaxRunTime = 30) {
        if (!$this->IsAsynchronous($Job)) {
            return null;
        }

        $StartTime = time();
        $CallbackURL = val('callbackUrl', $Job);
        do {
            // This has gone on too long, abort
            if ((time() - $StartTime) > $MaxRunTime) {
                break;
            }

            $Response = $this->JsonRequest('GET', $CallbackURL);
            $Status = val('status', $Response, 'RUNNING');

            if ($Status != 'COMPLETED') {
                sleep($Delay);
            }
        } while ($Status != 'COMPLETED');
        return;
    }

    //public function

    /**
     * Get the best URL for the current service
     *
     * @return string
     * @throws RackspaceAPIUnknownServiceException
     * @throws RackspaceAPIException
     */
    protected function GetUrl() {
        $Service = val($this->Service, $this->Services, null);
        if (empty($Service)) {
            throw new RackspaceAPIUnknownServiceException("Service '{$this->Service}' is not defined");
        }

        $PreferredRegion = $this->PreferredRegion();
        $Endpoint = null;
        foreach ($Service as $ServiceEndpoint) {
            $EndpointRegion = strtolower(val('region', $ServiceEndpoint, null));
            if (!empty($PreferredRegion) && !empty($EndpointRegion)) {
                if ($EndpointRegion == $PreferredRegion) {
                    $Endpoint = $ServiceEndpoint;
                    break;
                }
            }
        }

        if (!$Endpoint) {
            $Endpoint = array_shift($Service);
        }

        // Determine Endpoint URL
        $PreferredContext = $this->Context();
        $ContextURL = array();

        $InternalURL = val('internalURL', $Endpoint, false);
        if ($InternalURL) {
            $ContextURL['internal'] = $InternalURL;
        }

        $PublicURL = val('publicURL', $Endpoint, false);
        if ($PublicURL) {
            $ContextURL['public'] = $PublicURL;
        }

        $PreferredURL = val($PreferredContext, $ContextURL, false);
        if (!empty($PreferredURL)) {
            return $PreferredURL;
        }
        if (!empty($PublicURL)) {
            return $PublicURL;
        }

        throw new RackspaceAPIException("No services URL available for '{$this->Service}'");
    }

    /**
     * Static Array -> XML converter
     *
     * @param array $Array
     * @param SimpleXML $Xml
     * @return string Encoded XML
     */
    public static function ArrayXml($Array, &$Xml) {
        foreach ($Array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $XmlChild = $Xml->addChild("{$key}");
                    self::ArrayXml($value, $XmlChild);
                } else {
                    self::ArrayXml($value, $Xml);
                }
            } else {
                $Xml->addChild("{$key}", "{$value}");
            }
        }
        return $Xml->asXML();
    }
}

class RackspaceAPIException extends \Exception {

    protected $TroubleMessage = null;
    protected $Request = null;
    protected $Response = null;

    public function __construct($Message, $Details = null, $Code = 500) {

        $ResponseBody = val('Response', $Details, $Details);
        if (is_array($ResponseBody)) {
            $ResponseBody = print_r($ResponseBody, true);
        }
        $CompositeMessage = "{$Message} ([{$Code}] {$ResponseBody})";

        parent::__construct($CompositeMessage, $Code);

        if (!is_array($Details)) {
            $JsonResponse = json_decode($Details, true);
            if ($JsonResponse) {
                $Details = array_pop($JsonResponse);
            }
        }

        // Look for 'message'
        $TroubleMessage = null;

        if (is_null($TroubleMessage)) {
            $TroubleMessage = valr('message', $Details, null);
        }

        if (is_null($TroubleMessage)) {
            $TroubleMessage = valr('messages', $Details, null);
            if (is_array($TroubleMessage)) {
                $TroubleMessage = implode(' ', $TroubleMessage);
            }
        }

        if (is_null($TroubleMessage)) {
            $TroubleMessage = print_r($Details, true);
        } else {
            $this->message = "{$Message} ([{$Code}] {$TroubleMessage})";
        }

        $this->TroubleMessage = $TroubleMessage;
        $this->Request = valr('request', $Details, null);
        $this->Response = valr('response', $Details, null);
    }

    public function getTroubleMessage() {
        return $this->TroubleMessage;
    }

    public function getRequest() {
        return $this->Request;
    }

    public function getResponse() {
        return $this->Response;
    }

    public function __toString() {
        return get_class($this).": [".$this->getCode()."] ".$this->getMessage()." - ".$this->getTroubleMessage();
    }
}

class RackspaceProxyRequest extends \CloudProxyRequest {

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

class RackspaceAPIProblem extends RackspaceAPIException {

}
