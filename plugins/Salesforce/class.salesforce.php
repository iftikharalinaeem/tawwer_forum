<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Communicate with the Salesforce REST API
 *
 * @link http://www.salesforce.com/us/developer/docs/api_rest/
 */
class Salesforce {

    /**
     * @var Salesforce
     */
    static $Instance;

    const PROVIDERKEY = 'Salesforce';

    /**
     * @var int time in seconds to cache GET requests; This will help limit you calls to the API for duplicate requests
     */
    protected $cacheTTL = 300;

    /**
     * @var string OAuth Access Token
     */
    protected $accessToken;

    /**
     * @var String Instance URL Used for API Calls
     */
    protected $instanceUrl;

    /**
     * @var string REST API Version
     */
    protected $APIVersion = '26.0';

    public $DashboardConnection = false;

    /**
     * Set up Salesforce access properties.
     *
     * @param bool $accessToken
     * @param bool $instanceUrl
     */
    public function __construct($accessToken = false, $instanceUrl = false) {
        if ($accessToken && $instanceUrl) {
            // We passed in a connection
            $this->accessToken = $accessToken;
            $this->instanceUrl = $instanceUrl;
        } elseif (Gdn::session()->isValid()) {
            // See if user has their own connection established.
            if ($userConnection = val('Salesforce', Gdn::session()->User->Attributes)) {
                $this->accessToken = val('AccessToken', $userConnection);
                $this->instanceUrl = val('InstanceUrl', $userConnection);
                $this->RefreshToken = val('RefreshToken', $userConnection);
            }
        }

        // Fallback to global dashboard connection.
        if (c('Plugins.Salesforce.DashboardConnection.Enabled') && !$this->accessToken) {
            $this->useDashboardConnection();
            $this->DashboardConnection = true;
        }
    }

    /**
     * Return the singleton instance of this class.
     *
     * @return Salesforce
     */
    public static function instance() {
        if (!isset(self::$Instance)) {
            self::$Instance = new Salesforce();
        }
        return self::$Instance;
    }

    /**
     * Get auto connect data from saved config.
     */
    public function useDashboardConnection() {
        trace('DashboardConnection');
        $this->accessToken = c('Plugins.Salesforce.DashboardConnection.Token');
        $this->instanceUrl = c('Plugins.Salesforce.DashboardConnection.InstanceUrl');
        $this->RefreshToken = c('Plugins.Salesforce.DashboardConnection.RefreshToken');
    }

    /**
     * @param string $object Case, Contact, Lead
     * @param string $objectID CaseId, ContactID, LeadID
     * @param bool|array $fullHttpResponse if true will return array with
     *    [ContentType]
     *    [Response]
     *    [HttpCode]
     *    [Headers]
     * @return array All the details returned from Salesforce
     */
    public function getObject($object, $objectID, $fullHttpResponse = false) {
        $result = $this->request('sobjects/'.$object.'/'.$objectID);
        if ($fullHttpResponse) {
            return $result;
        } else {
            return $result['Response'];
        }
    }

    /**
     * @param string $contactID
     * @return array All the details returned from Salesforce
     */
    public function getContact($contactID) {
        $result = $this->getObject('Contact', $contactID);
        return $result;
    }

    /**
     * @param string $leadID
     * @return array All the details returned from Salesforce
     */
    public function getLead($leadID) {
        return $this->getObject('Lead', $leadID, true);
    }

    /**
     * @param string $accountID
     * @return array All the details returned from Salesforce
     */
    public function getAccount($accountID) {
        return $this->getObject('Account', $accountID);
    }

    /**
     * @param string $userID
     * @return array All the details returned from Salesforce
     */
    public function getUser($userID) {
        return $this->getObject('User', $userID);
    }

    /**
     * @param string $email
     * @return array|bool false if not found or All the details returned from Salesforce
     */
    public function findLead($email) {
        $result = $this->select(['id'], 'Lead', ['Email' => $email], 1);
        if ($result['totalSize'] != 1) {
            return false;
        }
        return $this->getLead($result['records'][0]['Id']);
    }

    /**
     * @param string $email
     * @return array|bool false if not found or All the details returned from Salesforce
     */
    public function findUser($email) {
        $result = $this->select(['id'], 'User', ['Email' => $email], 1);
        if ($result['totalSize'] != 1) {
            return false;
        }
        return $this->getUser($result['records'][0]['Id']);
    }

    /**
     * @param string $email
     * @return array|bool false if not found or All the details returned from Salesforce
     */
    public function findContact($email) {
        $result = $this->select(['id'], 'Contact', ['Email' => $email], 1);
        if ($result['totalSize'] != 1) {
            return false;
        }
        return $this->getContact($result['records'][0]['Id']);
    }

    /**
     * @param string $caseID
     * @return array All the details returned from Salesforce
     */
    public function getCase($caseID) {
        $result = $this->getObject('Case', $caseID, true);
        return $result;
    }

    /**
     * Create a new Lead Object in Salesforce
     *
     * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_lead.htm
     * @see Salesforce::ValidateLead
     * @param array $lead
     * @return string LeadID
     * @throws Gdn_UserException
     */
    public function createLead(array $lead) {
        if ($this->validateLead($lead) === true) {
            return $this->createObject('Lead', $lead);
        }
        throw new Gdn_UserException('Create Lead: Required Fields Missing: '.print_r($this->validateLead($lead)));
    }

    /**
     * @param array $lead
     * @return array|bool True or array of missing required fields
     */
    public function validateLead(array $lead) {
        $requiredFields = [
            'LastName' => true,
            'FirstName' => true,
            'Email' => true,
            'LeadSource' => true,
            'Company' => true,
        ];
        $missingFields = array_diff_key($requiredFields, $lead);
        if (!empty($missingFields)) {
            Logger::event(
                'salesforce_failure',
                Logger::ERROR,
                'Failed to validate lead ',
                $missingFields
            );
            return $missingFields;
        }
        return true;
    }

    /**
     * Create a new Contact Object in Salesforce
     *
     * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_contact.htm
     * @see Salesforce::ValidateContact
     * @param array $contact
     * @return string ContactID
     * @throws Gdn_UserException
     */
    public function createContact(array $contact) {
        if ($this->validateContact($contact) === true) {
            return $this->createObject('Contact', $contact);
        }
        throw new Gdn_UserException('Create Contact: Required Fields Missing: '.print_r($this->validateContact($contact)));
    }

    /**
     * Update a new Contact Object in Salesforce
     *
     * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_contact.htm
     * @see Salesforce::ValidateContact
     * @param array $contact
     * @param string $id
     * @return string $contactID
     */
    public function updateContact(array $contact, string $id): string {
        $validate = $this->validateContact($contact);
        if ($validate === true) {
            return $this->updateObject('Contact', $contact, $id);
        }
        // error
        Logger::event(
            'salesforce_failure',
            Logger::ERROR,
            'Update Contact: Required Fields Missing: '.$validate,
            [(array)$contact, $id]
        );

        return '';
    }

    /**
     * @param array $contact
     * @return array|bool True or array of missing required fields
     */
    public function validateContact(array $contact) {
        $requiredFields = [
            'LastName' => true,
            'FirstName' => true,
            'Email' => true,
        ];
        $missingFields = array_diff_key($requiredFields, $contact);
        if (!empty($missingFields)) {
            Logger::event(
                'salesforce_failure',
                Logger::ERROR,
                'Failed to validate contact ',
                $missingFields
            );
            return $missingFields;
        }
        return true;
    }


    /**
     * Create a new Case Object in Salesforce
     *
     * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_case.htm
     * @see Salesforce::ValidateCase
     * @param array $case
     * @return string CaseID
     * @throws Gdn_UserException
     */
    public function createCase(array $case) {
        if ($this->validateCase($case) === true) {
            return $this->createObject('Case', $case);
        }
        throw new Gdn_UserException('Create Case: Required Fields Missing: '.print_r($this->validateContact($case)));
    }

    /**
     * @param array $case
     * @return array|bool True or array of missing required fields
     */
    public function validateCase(array $case) {
        $requiredFields = [
            'ContactId' => true,
            'Status' => true,
            'Origin' => true,
            'Subject' => true,
            'Priority' => true,
            'Description' => true
        ];
        $missingFields = array_diff_key($requiredFields, $case);
        if (!empty($missingFields)) {
            Logger::event(
                'salesforce_failure',
                Logger::ERROR,
                'Failed to validate case ',
                $missingFields
            );
            return $missingFields;
        }
        return true;
    }

    /**
     * @param string $object
     * @param array $fields
     * @return mixed
     * @throws Gdn_UserException
     */
    public function createObject(string $object, array $fields) {
        $response = $this->request('sobjects/'.$object.'/', json_encode($fields), true, 'POST');
        if (isset($response['Response']['success'])) {
            return $response['Response']['id'];
        }
        throw new Gdn_UserException($response['Response'][0]['message']);
    }

    /**
     * Update Object
     *
     * @param string $object
     * @param array $fields
     * @param string $id
     * @return string
     */
    public function updateObject(string $object, array $fields, string $id): string {
        $response = $this->request('sobjects/'.$object.'/'.$id, json_encode($fields), false, 'PATCH');
        // PATCH requests to salesforce return 204 on success and no message.
        if (isset($response['HttpCode']) && $response['HttpCode'] === 204) {
            return $response['Response']['id'];
        }
        return $response['Response'][0]['message'] ?? '';
    }

    /**
     * Preform a SELECT query using SOQL
     *
     * @param array $fields
     * @param $from
     * @param array $where
     * @param int $limit
     * @return array Response from SFDC; Hopefully a Valid Object
     *    [done] - bool
     *    [totalSize] - int
     *    [records] - array
     *       with the fields from $fields
     *
     * @link http://www.salesforce.com/us/developer/docs/soql_sosl/index.htm
     */
    public function select(array $fields, $from, array $where, $limit = 0) {
        $select = implode(', ', $fields);
        $whereClause = 'WHERE ';
        $whereCount = count($where);
        $i = 0;
        foreach ($where as $field => $value) {
            $whereClause .= "$field = '$value'";
            $i++;
            if ($i < $whereCount && $i != $whereCount) {
                $whereClause .= ' AND ';
            }
        }
        $query = 'SELECT '.$select.' FROM '.$from.' '.$whereClause;
        if ($limit > 0) {
            $query .= ' LIMIT '.$limit;
        }
        $response = $this->request('query?q='.urlencode($query));
        return $response['Response'];
    }

    /**
     * Get User Profile fields.
     *
     * @param string $loginID - id from the Access Tokens after successful OAuth
     * @return array|bool $profile
     * @throws Exception
     */
    public function getLoginProfile($loginID) {
        $httpResponse = $this->httpRequest($loginID);
        if ($httpResponse['HttpCode'] != 200) {
            return false;
        }
        $fullProfile = json_decode($httpResponse['Response']);
        $profile = [
            'id' => $fullProfile->user_id,
            'email' => $fullProfile->email,
            'fullname' => $fullProfile->display_name,
            'photo' => $fullProfile->photos->thumbnail,
        ];
        return $profile;
    }

    /**
     * @return string the <option> string for Form
     * @throws Gdn_UserException
     */
    public function getLeadStatusOptions() {
        $options = '';
        $response = $this->request('sobjects/Lead/describe');
        if ($response['HttpCode'] != 200) {
            throw new Gdn_UserException('Error getting Lead Status Options');
        }
        foreach ($response['Response']['fields'] as $fieldNum => $field) {
            if ($field['name'] == 'Status') {
                foreach ($field['picklistValues'] as $pickListValue) {
                    $options .= '<option ';
                    if ($pickListValue['defaultValue'] == true) {
                        $options .= 'selected';
                    }

                    $options .= ' value="'.$pickListValue['value'].'">'.$pickListValue['label'].'</option>'."\n";
                }
            }
        }
        return $options;
    }

    /**
     * @return string the <option> string for Form
     * @throws Gdn_UserException
     */
    public function getCaseStatusOptions() {
        $options = '';
        $response = $this->request('sobjects/Case/describe');
        if ($response['HttpCode'] != 200) {
            throw new Gdn_UserException('Error getting Case status Options');
        }
        foreach ($response['Response']['fields'] as $fieldNum => $field) {
            if ($field['name'] == 'Status') {
                foreach ($field['picklistValues'] as $pickListValue) {
                    $options .= '<option ';
                    if ($pickListValue['defaultValue'] == true) {
                        $options .= 'selected';
                    }
                    $options .= ' value="'.$pickListValue['value'].'">'.$pickListValue['label'].'</option>'."\n";
                }
            }
        }
        return $options;
    }

    /**
     * @return string the <option> string for Form
     * @throws Gdn_UserException
     */
    public function getCasePriorityOptions() {
        $options = '';
        $response = $this->request('sobjects/Case/describe');
        if ($response['HttpCode'] != 200) {
            throw new Gdn_UserException('Error getting Case status Options');
        }
        foreach ($response['Response']['fields'] as $fieldNum => $field) {
            if ($field['name'] == 'Priority') {
                foreach ($field['picklistValues'] as $pickListValue) {
                    $options .= '<option ';
                    if ($pickListValue['defaultValue'] == true) {
                        $options .= 'selected';
                    }
                    $options .= ' value="'.$pickListValue['value'].'">'.$pickListValue['label'].'</option>'."\n";
                }
            }
        }
        return $options;
    }

    /**
     * Sends Request to the Salesforces REST API.
     *
     * @param $path
     * @param bool|array $post false or array of values to be sent as json POST
     * @param bool $cache
     * @param string $method
     * @return array $httpResponse with the following keys
     *    [HttpCode] - HTTP Status Code
     *    [Response] - JSON Decoded Values if Content Type == Json
     *    [Header] - HTTP Header
     *    [ContentType] - HTTP Content Type
     * @throws Gdn_UserException
     *
     * @see http://www.salesforce.com/us/developer/docs/api_rest/
     */
    public function request($path, $post = false, $cache = true, string $method = 'GET') {
        $url = $this->instanceUrl.'/services/data/v'.$this->APIVersion.'/'.ltrim($path, '/');
        $cacheKey = 'Salesforce.Request'.md5($url);

        if ($cache && !$post) {
            $httpResponse = Gdn::cache()->get($cacheKey, [Gdn_Cache::FEATURE_COMPRESS => true]);
            if ($httpResponse) {
                trace('Cached Response');
                return $httpResponse;
            }
        }
        if (!$this->accessToken) {
            throw new Gdn_UserException("You don't have a valid Salesforce connection.");
        }

        $httpResponse = $this->httpRequest($url, $post, 'application/json', $method);

        $contentType = $httpResponse['ContentType'];
        Gdn::controller()->setJson('Type', $contentType);
        if (strpos($contentType, 'application/json') !== false) {
            $httpResponse['Response'] = json_decode($httpResponse['Response'], true);
            if (isset($httpResponse['error'])) {
                Gdn::dispatcher()->passData('SalesforceResponse', $httpResponse);
                throw new Gdn_UserException($httpResponse['Response'][0]['message']);
            }
        }
        if ($cache && $httpResponse['HttpCode'] == 200 && !$post) {
            $cacheTTL = $this->cacheTTL + rand(0, 30);
            Gdn::cache()->store($cacheKey, $httpResponse, [
                Gdn_Cache::FEATURE_EXPIRY => $cacheTTL,
                Gdn_Cache::FEATURE_COMPRESS => true
            ]);
        }
        return $httpResponse;
    }

    /**
     * Send an HTTP request to Salesforce with Authorize header.
     *
     * @param string $url -
     * @param bool|array $post
     * @param string|bool AccessToken
     * @return array $HttpResponse with the following keys
     *    [HttpCode] - HTTP Status Code
     *    [Response] - HTTP Body
     *    [Header] - HTTP Header
     *    [ContentType] - HTTP Content Type
     * @throws Exception
     */
    public function httpRequest($url, $post = false, $requestContentType = null, $method = 'GET') {
        $proxy = new ProxyRequest();
        $options['URL'] = $url;
        $options['Method'] = $method;
        $options['ConnectTimeout'] = 10;
        $options['Timeout'] = 10;
        $queryParams = null;
        if (!empty($requestContentType)) {
            $headers['Content-Type'] = $requestContentType;
        }

        if ($post || $method === 'PATCH') {
            $queryParams = $post;
        }

        $headers['Authorization'] = 'OAuth '.$this->accessToken;
        trace('Salesforce Request - '.$options['Method'].' : '.$url);

        // log the query params being sent to salesforce
        Logger::event(
            'salesforce_data_sent',
            Logger::INFO,
            'Post data being sent to salesforce',
            (array)$post
        );

        $response = $proxy->request(
            $options,
            $queryParams,
            null,
            $headers
        );

        $failureCodes = [500 => true];
        if (isset($failureCodes[$proxy->ResponseStatus])) {
            throw new Gdn_UserException('HTTP Error communicating with Salesforce.  Code: '.$proxy->ResponseStatus);
        }

        return [
            'HttpCode' => $proxy->ResponseStatus,
            'Header' => $proxy->RequestHeaders,
            'Response' => $response,
            'ContentType' => $proxy->ContentType
        ];

    }

    /**
     * @return bool
     */
    public function isConnected() {
        if (!$this->accessToken || !$this->instanceUrl) {
            return false;
        }
        return true;
    }

    /**
     * Reestablishes a valid token session with Salesforce using refresh_token.
     *
     * @throws Gdn_UserException
     * @see refresh()
     */
    public function reconnect() {
        if ($this->DashboardConnection) {
            $response = $this->refresh($this->RefreshToken);
            if (!$response) {
                return false;
            }

            // Update global connection.
            $instanceUrl = $response['instance_url'];
            $accessToken = $response['access_token'];
            saveToConfig([
                'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $instanceUrl,
                'Plugins.Salesforce.DashboardConnection.Token' => $accessToken,
            ]);
            $this->setAccessToken($accessToken);
            $this->setInstanceUrl($instanceUrl);
        } else {
            $response = $this->refresh($this->RefreshToken);
            if (!$response) {
                return false;
            }

            // Update user connection.
            $profile = valr('Attributes.'.self::PROVIDERKEY.'.Profile', Gdn::session()->User);
            $attributes = [
                'RefreshToken' => $this->RefreshToken,
                'AccessToken' => $response['access_token'],
                'instanceUrl' => $response['instance_url'],
                'Profile' => $profile,
            ];

            Gdn::userModel()->saveAttribute(Gdn::session()->UserID, self::PROVIDERKEY, $attributes);
            $this->setAccessToken($response['access_token']);
            $this->setInstanceUrl($response['instance_url']);
        }
    }

    /**
     * Revoke an access token.
     *
     * @param $token
     * @return bool
     * @throws Gdn_UserException
     */
    public function revoke($token) {
        $response = $this->httpRequest(c('Plugins.Salesforce.AuthenticationUrl').'/services/oauth2/revoke?token='.$token);
        if ($response['HttpCode'] == 200) {
            return true;
        }
        return false;
    }

    /**
     * Sends refresh_token to Salesforce API and simply returns the response.
     *
     * @param $token
     * @return bool|mixed On success, returns entire API response.
     * @throws Gdn_UserException
     * @see reconnect() is probably what you want.
     */
    public function refresh($token) {
        $response = $this->httpRequest(
            c('Plugins.Salesforce.AuthenticationUrl').'/services/oauth2/token',
            [
                'grant_type' => 'refresh_token',
                'client_id' => c('Plugins.Salesforce.ApplicationID'),
                'client_secret' => c('Plugins.Salesforce.Secret'),
                'refresh_token' => $token
            ], "", "POST"
        );
        trace($response);
        if ($response['HttpCode'] == 400) {
            throw new Gdn_UserException('Someone has Revoked your Connection.  Please reconnect manually,');
        }
        if (strpos($response['ContentType'], 'application/json') !== false) {
            $refreshResponse = json_decode($response['Response'], true);
            return $refreshResponse;
        }
        return false;
    }

    /**
     * Checks if custom field is an salesforce object.
     *
     * @param string $searchField fields that we need to search for.
     * @param string $type salesforce object type.
     * @throws Gdn_UserException
     * @return bool
     */
    public function salesforceFieldExists(string $searchField = '', string $type = '') {
        $salesforceObjectTypes = ['Case', 'Lead'];

        if (!in_array($type, $salesforceObjectTypes) || !$searchField) {
            return false;
        }

        $response = $this->request('sobjects/'.$type.'/describe');
        if ($response['HttpCode'] != 200) {
            throw new Gdn_UserException('Error getting '.$type.' status Options');
        }
        
        foreach ($response['Response']['fields'] as $fieldNum => $field) {
            if ($field['name'] == $searchField) {
                return true;
            }
        }
        return false;
    }

    public static function authorizeUri($redirectUri = false, $extraStateParameters = []) {
        $ssoUtils = Gdn::getContainer()->get('SsoUtils');

        $appID = c('Plugins.Salesforce.ApplicationID');
        if (!$redirectUri) {
            $redirectUri = self::redirectUri();
        }

        // Backward compatibility
        if (!is_array($extraStateParameters)) {
            $extraStateParameters = ['type' => $extraStateParameters];
        }

        // Get a state token.
        $stateToken = $ssoUtils->getStateToken();

        $query = [
            'redirect_uri' => $redirectUri,
            'client_id' => $appID,
            'response_type' => 'code',
            'scope' => 'full refresh_token',
            'state' => json_encode(
                ['token' => $stateToken] + $extraStateParameters
            ),
        ];
        $return = c('Plugins.Salesforce.AuthenticationUrl')."/services/oauth2/authorize?".http_build_query($query, null , "&");
        return $return;
    }

    /**
     * Used in the OAuth process.
     *
     * @param null $newValue a different redirect url
     * @return null|string
     */
    public static function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $redirectUri = $newValue;
        } else {
            $redirectUri = url('/profile/salesforceconnect', true, true, true);
            if (strpos($redirectUri, '=') !== false) {
                $p = strrchr($redirectUri, '=');
                $uri = substr($redirectUri, 0, -strlen($p));
                $p = urlencode(ltrim($p, '='));
                $redirectUri = $uri.'='.$p;
            }
        }
        return $redirectUri;
    }

    /**
     * Used in the Oath process.
     *
     * @param $code - OAuth Code
     * @param $redirectUri - Redirect Uri
     * @return string Response
     * @throws Gdn_UserException
     */
    public static function getTokens($code, $redirectUri) {
        $post = [
            'grant_type' => 'authorization_code',
            'client_id' => c('Plugins.Salesforce.ApplicationID'),
            'client_secret' => c('Plugins.Salesforce.Secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];
        $url = c('Plugins.Salesforce.AuthenticationUrl').'/services/oauth2/token';
        $proxy = new ProxyRequest();
        $response = $proxy->request(
            ['URL' => $url, 'Method' => 'POST'],
            $post
        );

        if (strpos($proxy->ContentType, 'application/json') !== false) {
            $response = json_decode($response);
        }
        if (isset($response->error)) {
            throw new Gdn_UserException('Error Communicating with Salesforce API: '.$response->error_description);
        }

        return $response;

    }

    /**
     * Used in the OAuth process.
     *
     * @return string $Url
     */
    public static function profileConnecUrl() {
        return Gdn::request()->url('/profile/salesforceconnect', true, true, true);
    }

    /**
     * Used in the OAuth process.
     *
     * @return bool
     */
    public static function isConfigured() {
        $appID = c('Plugins.Salesforce.ApplicationID');
        $secret = c('Plugins.Salesforce.Secret');
        if (!$appID || !$secret) {
            return false;
        }
        return true;
    }

    /**
     * Setter for AccessToken.
     *
     * @param $accessToken
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
    }

    /**
     * Setter for instanceUrl.
     *
     * @param $instanceUrl
     */
    public function setInstanceUrl($instanceUrl) {
        $this->instanceUrl = $instanceUrl;
    }

    /**
     * Get Salesforce Object fields
     *
     * @param string $object
     * @return array
     */
    public function getFields(string $object): array {
        $salesforceFields = [];
        $path = 'sobjects/'.$object.'/describe';
        $response = $this->request($path);
        //error
        if ($response['HttpCode'] != 200) {
            trace('Salesforce Request - GET : '.$path);
        }
        $fields = valr('Response.fields', $response, []);
        foreach ($fields as $field) {
            $name = $field['name'];

            $salesforceFields[$name] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'length' => $field['length'],
                'defaultValue' => $field['defaultValue'],
                'picklistValue' => $field['picklistValues'],
                'inlinehelptext' => $field['inlineHelpText'],
            ];
        }
        return $salesforceFields;
    }
}
