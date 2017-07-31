<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Communicate with the Salesforce REST API
 * @link http://www.salesforce.com/us/developer/docs/api_rest/
 */
class Salesforce {

   /**
    * @var Salesforce
    */
   static $Instance;

   const ProviderKey = 'Salesforce';

   /**
    * @var int time in seconds to cache GET requests; This will help limit you calls to the API for duplicate requests
    */
   protected $CacheTTL = 300;

   /**
    * @var string OAuth Access Token
    */
   protected $AccessToken;

   /**
    * @var String Instance URL Used for API Calls
    */
   protected $InstanceUrl;

   /**
    * @var string REST API Version
    */
   protected $APIVersion = '26.0';

   public $DashboardConnection = FALSE;

   /**
    * Set up Salesforce access properties.
    *
    * @param bool $accessToken
    * @param bool $instanceUrl
    */
   public function __construct($accessToken = FALSE, $instanceUrl = FALSE) {
      if ($accessToken && $instanceUrl) {
         // We passed in a connection
         $this->AccessToken = $accessToken;
         $this->InstanceUrl = $instanceUrl;
      } elseif (Gdn::Session()->IsValid()) {
         // See if user has their own connection established.
         if ($userConnection = val('Salesforce', Gdn::Session()->User->Attributes)) {
            $this->AccessToken = val('AccessToken', $userConnection);
            $this->InstanceUrl = val('InstanceUrl', $userConnection);
            $this->RefreshToken = val('RefreshToken', $userConnection);
         }
      }

      // Fallback to global dashboard connection.
      if (C('Plugins.Salesforce.DashboardConnection.Enabled') && !$this->AccessToken) {
         $this->UseDashboardConnection();
         $this->DashboardConnection = true;
      }
   }

   /**
    * Return the singleton instance of this class.
    * @return Salesforce
    */
   public static function Instance() {
      if (!isset(self::$Instance)) {
         self::$Instance = new Salesforce();
      }
      return self::$Instance;
   }

   public function UseDashboardConnection() {
      Trace('DashboardConnection');
      $this->AccessToken = C('Plugins.Salesforce.DashboardConnection.Token');
      $this->InstanceUrl = C('Plugins.Salesforce.DashboardConnection.InstanceUrl');
      $this->RefreshToken = C('Plugins.Salesforce.DashboardConnection.RefreshToken');
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
   public function GetObject($object, $objectID, $fullHttpResponse = FALSE) {
      $result = $this->Request('sobjects/' . $object . '/' . $objectID);

      if ($fullHttpResponse) {
         return $result;
      }
      return $result['Response'];
   }

   /**
    * @param string $contactID
    * @return array All the details returned from Salesforce
    */
   public function GetContact($contactID) {
      $result = $this->GetObject('Contact', $contactID);
      return $result;
   }

   /**
    * @param string $leadID
    * @return array All the details returned from Salesforce
    */
   public function GetLead($leadID) {
      return $this->GetObject('Lead', $leadID, TRUE);
   }

   /**
    * @param string $accountID
    * @return array All the details returned from Salesforce
    */
   public function GetAccount($accountID) {
      return $this->GetObject('Account', $accountID);
   }

   /**
    * @param string $userID
    * @return array All the details returned from Salesforce
    */
   public function GetUser($userID) {
      return $this->GetObject('User', $userID);
   }

   /**
    * @param string $email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindLead($email) {
      $result = $this->Select(['id'], 'Lead', ['Email' => $email], 1);
      if ($result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetLead($result['records'][0]['Id']);
   }

   /**
    * @param string $email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindUser($email) {
      $result = $this->Select(['id'], 'User', ['Email' => $email], 1);
      if ($result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetUser($result['records'][0]['Id']);
   }

   /**
    * @param string $email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindContact($email) {
      $result = $this->Select(['id'], 'Contact', ['Email' => $email], 1);
      if ($result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetContact($result['records'][0]['Id']);
   }

   /**
    * @param string $caseID
    * @return array All the details returned from Salesforce
    */
   public function GetCase($caseID) {
      $result = $this->GetObject('Case', $caseID, TRUE);
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
   public function CreateLead(array $lead) {
      if ($this->ValidateLead($lead) === TRUE) {
         return $this->CreateObject('Lead', $lead);
      }
      throw new Gdn_UserException('Create Lead: Required Fields Missing: ' . print_r($this->ValidateLead($lead)));
   }

   /**
    * @param array $lead
    * @return array|bool True or array of missing required fields
    */
   public function ValidateLead(array $lead) {
      $requiredFields = [
         'LastName' => TRUE,
         'FirstName' => TRUE,
         'Email' => TRUE,
         'LeadSource' => TRUE,
         'Company' => TRUE,
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
      return TRUE;
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
   public function CreateContact(array $contact) {
      if ($this->ValidateContact($contact) === TRUE) {
         return $this->CreateObject('Contact', $contact);
      }
      throw new Gdn_UserException('Create Contact: Required Fields Missing: '
         . print_r($this->ValidateContact($contact)));
   }

   /**
    * @param array $contact
    * @return array|bool True or array of missing required fields
    */
   public function ValidateContact(array $contact) {
      $requiredFields = [
         'LastName' => TRUE,
         'FirstName' => TRUE,
         'Email' => TRUE,
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
      return TRUE;
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
   public function CreateCase(array $case) {
      if ($this->ValidateCase($case) === TRUE) {
         return $this->CreateObject('Case', $case);
      }
      throw new Gdn_UserException('Create Case: Required Fields Missing: '
         . print_r($this->ValidateContact($case)));
   }

   /**
    * @param array $case
    * @return array|bool True or array of missing required fields
    */
   public function ValidateCase(array $case) {
      $requiredFields = [
         'ContactId' => TRUE,
         'Status' => TRUE,
         'Origin' => TRUE,
         'Subject' => TRUE,
         'Priority' => TRUE,
         'Description' => TRUE
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
      return TRUE;
   }

   /**
    * @param $object
    * @param array $fields
    * @return mixed
    * @throws Gdn_UserException
    */
   public function CreateObject($object, array $fields) {
      $response = $this->Request('sobjects/' . $object . '/', json_encode($fields));
      if (isset($response['Response']['success'])) {
         return $response['Response']['id'];
      }
      throw new Gdn_UserException($response['Response'][0]['message']);
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
   public function Select(array $fields, $from, array $where, $limit = 0) {
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
      $query = 'SELECT ' . $select . ' FROM ' . $from . ' ' . $whereClause;
      if ($limit > 0) {
         $query .= ' LIMIT ' . $limit;
      }
      $response = $this->Request('query?q=' . urlencode($query));
      return $response['Response'];
   }

   /**
    * Get User Profile fields.
    *
    * @param string $loginID - id from the Access Tokens after successful OAuth
    * @return array $profile
    * @throws Exception
    */
   public function GetLoginProfile($loginID) {
      $httpResponse = $this->HttpRequest($loginID);
      if ($httpResponse['HttpCode'] != 200) {
         return FALSE;
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
   public function GetLeadStatusOptions() {
      $options = '';
      $response = $this->Request('sobjects/Lead/describe');
      if ($response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Lead Status Options');
      }
      foreach ($response['Response']['fields'] as $fieldNum => $field) {
         if ($field['name'] == 'Status') {
            foreach ($field['picklistValues'] as $pickListValue) {
               $options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $options .= 'selected';
               }

               $options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
            }
         }
      }
      return $options;
   }

   /**
    * @return string the <option> string for Form
    * @throws Gdn_UserException
    */
   public function GetCaseStatusOptions() {
      $options = '';
      $response = $this->Request('sobjects/Case/describe');
      if ($response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Case status Options');
      }
      foreach ($response['Response']['fields'] as $fieldNum => $field) {
         if ($field['name'] == 'Status') {
            foreach ($field['picklistValues'] as $pickListValue) {
               $options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $options .= 'selected';
               }
               $options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
            }
         }
      }
      return $options;
   }

   /**
    * @return string the <option> string for Form
    * @throws Gdn_UserException
    */
   public function GetCasePriorityOptions() {
      $options = '';
      $response = $this->Request('sobjects/Case/describe');
      if ($response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Case status Options');
      }
      foreach ($response['Response']['fields'] as $fieldNum => $field) {
         if ($field['name'] == 'Priority') {
            foreach ($field['picklistValues'] as $pickListValue) {
               $options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $options .= 'selected';
               }
               $options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
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
    * @return array $httpResponse with the following keys
    *    [HttpCode] - HTTP Status Code
    *    [Response] - JSON Decoded Values if Content Type == Json
    *    [Header] - HTTP Header
    *    [ContentType] - HTTP Content Type
    * @throws Gdn_UserException
    *
    * @see http://www.salesforce.com/us/developer/docs/api_rest/
    */
   public function Request($path, $post = FALSE, $cache = TRUE) {
      $url = $this->InstanceUrl . '/services/data/v' . $this->APIVersion . '/' . ltrim($path, '/');
      $cacheKey = 'Salesforce.Request' . md5($url);

      if ($cache && !$post) {
         $httpResponse = Gdn::Cache()->Get($cacheKey, [Gdn_Cache::FEATURE_COMPRESS => TRUE]);
         if ($httpResponse) {
            Trace('Cached Response');
            return $httpResponse;
         }
      }
      if (!$this->AccessToken) {
         throw new Gdn_UserException("You don't have a valid Salesforce connection.");
      }
      $httpResponse = $this->HttpRequest($url, $post, 'application/json');
      $contentType = $httpResponse['ContentType'];
      Gdn::Controller()->SetJson('Type', $contentType);
      if (strpos($contentType, 'application/json') !== FALSE) {
         $httpResponse['Response'] = json_decode($httpResponse['Response'], TRUE);
         if (isset($result['error'])) {
            Gdn::Dispatcher()->PassData('SalesforceResponse', $result);
            throw new Gdn_UserException($result['error']['message']);
         }
      }
      if ($cache && $httpResponse['HttpCode'] == 200 && !$post) {
         $cacheTTL = $this->CacheTTL + rand(0, 30);
         Gdn::Cache()->Store($cacheKey, $httpResponse, [
            Gdn_Cache::FEATURE_EXPIRY  => $cacheTTL,
            Gdn_Cache::FEATURE_COMPRESS => TRUE
         ]);
      }
      return $httpResponse;
   }

   /**
    * Send an HTTP request to Salesforce with Authorize header.
    *
    * @param string $url -
    * @param bool|array $post
    * @param string|bull AccessToken
    * @return array $HttpResponse with the following keys
    *    [HttpCode] - HTTP Status Code
    *    [Response] - HTTP Body
    *    [Header] - HTTP Header
    *    [ContentType] - HTTP Content Type
    * @throws Exception
    */
   public function HttpRequest($url, $post = FALSE, $requestContentType = NULL) {
      $proxy = new ProxyRequest();
      $options['URL'] =  $url;
      $options['Method'] = 'GET';
      $options['ConnectTimeout'] = 10;
      $options['Timeout'] = 10;
      $queryParams = NULL;
      if (!empty($requestContentType)) {
         $headers['Content-Type'] = $requestContentType;
      }
      if ($post)  {
         $options['Method'] = 'POST';
         $queryParams = $post;
      }
      $headers['Authorization'] = 'OAuth ' . $this->AccessToken;
      Trace('Salesforce Request - ' . $options['Method']. ' : ' . $url);

      // log the query params being sent to salesforce
      Logger::event(
          'salesforce_data_sent',
          Logger::INFO,
          'Post data being sent to salesforce',
          (array)$post
      );

      $response = $proxy->Request(
         $options,
         $queryParams,
         NULL,
         $headers
      );
      $failureCodes = [
         500 => TRUE,
      ];
      if (isset($failureCodes[$proxy->ResponseStatus])) {
         throw new Gdn_UserException('HTTP Error communicating with Salesforce.  Code: ' . $proxy->ResponseStatus);
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
    public function IsConnected() {
      if (!$this->AccessToken || !$this->InstanceUrl) {
         return FALSE;
      }
      return TRUE;
   }

   /**
    * Reestablishes a valid token session with Salesforce using refresh_token.
    *
    * @throws Gdn_UserException
    * @see Refresh()
    */
   public function Reconnect() {
      if ($this->DashboardConnection) {
         $response = $this->Refresh($this->RefreshToken);
         if (!$response) {
            return false;
         }

         // Update global connection.
         $instanceUrl = $response['instance_url'];
         $accessToken = $response['access_token'];
         SaveToConfig([
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $instanceUrl,
            'Plugins.Salesforce.DashboardConnection.Token' => $accessToken,
         ]);
         $this->SetAccessToken($accessToken);
         $this->SetInstanceUrl($instanceUrl);
      } else {
         $response = $this->Refresh($this->RefreshToken);
         if (!$response) {
            return false;
         }

         // Update user connection.
         $profile = valr('Attributes.' . self::ProviderKey . '.Profile', Gdn::Session()->User);
         $attributes = [
            'RefreshToken' => $this->RefreshToken,
            'AccessToken' => $response['access_token'],
            'InstanceUrl' => $response['instance_url'],
            'Profile' => $profile,
         ];

         Gdn::UserModel()->SaveAttribute(Gdn::Session()->UserID, self::ProviderKey, $attributes);
         $this->SetAccessToken($response['access_token']);
         $this->SetInstanceUrl($response['instance_url']);
      }
   }

   /**
    * Revoke an access token.
    *
    * @param $token
    * @return bool
    * @throws Gdn_UserException
    */
   public function Revoke($token) {
      $response = $this->HttpRequest(C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/revoke?token=' . $token);
      if ($response['HttpCode'] == 200) {
         return TRUE;
      }
      return FALSE;
   }

   /**
    * Sends refresh_token to Salesforce API and simply returns the response.
    *
    * @param $token
    * @return bool|mixed On success, returns entire API response.
    * @throws Gdn_UserException
    * @see Reconnect() is probably what you want.
    */
   public function Refresh($token) {
      $response = $this->HttpRequest(
         C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/token',
         [
            'grant_type' => 'refresh_token',
            'client_id' => C('Plugins.Salesforce.ApplicationID'),
            'client_secret' => C('Plugins.Salesforce.Secret'),
            'refresh_token' => $token
         ]
      );
      Trace($response);
      if ($response['HttpCode'] == 400) {
         throw new Gdn_UserException('Someone has Revoked your Connection.  Please reconnect manually,');
         return FALSE;
      }
      if (strpos($response['ContentType'], 'application/json') !== FALSE) {
         $refreshResponse = json_decode($response['Response'], TRUE);

         return $refreshResponse;
      }
      return FALSE;
   }

   /**
    * Used in the OAuth process.
    *
    * @param bool|string $redirectUri
    * @param bool|string $state
    * @return string Authorize URL
    */
   public static function AuthorizeUri($redirectUri = FALSE, $state = FALSE) {
      $appID = C('Plugins.Salesforce.ApplicationID');
      if (!$redirectUri) {
         $redirectUri = self::RedirectUri();
      }
      $query = [
         'redirect_uri' => $redirectUri,
         'client_id' => $appID,
         'response_type' => 'code',
         'scope' => 'full refresh_token'
      ];
      if ($state) {
         $query['state'] = $state;
      }
      $return = C('Plugins.Salesforce.AuthenticationUrl') . "/services/oauth2/authorize?"
         . http_build_query($query, null , "&");
      return $return;
   }

   /**
    * Used in the OAuth process.
    *
    * @param null $newValue a different redirect url
    * @return null|string
    */
   public static function RedirectUri($newValue = NULL) {
      if ($newValue !== NULL) {
         $redirectUri = $newValue;
      } else {
         $redirectUri = Url('/profile/salesforceconnect', TRUE, TRUE, TRUE);
         if (strpos($redirectUri, '=') !== FALSE) {
            $p = strrchr($redirectUri, '=');
            $uri = substr($redirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $redirectUri = $uri . '=' . $p;
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
   public static function GetTokens($code, $redirectUri) {
      $post = [
         'grant_type' => 'authorization_code',
         'client_id' => C('Plugins.Salesforce.ApplicationID'),
         'client_secret' => C('Plugins.Salesforce.Secret'),
         'code' => $code,
         'redirect_uri' => $redirectUri,
      ];
      $url = C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/token';
      $proxy = new ProxyRequest();
      $response = $proxy->Request(
         [
            'URL' => $url,
            'Method' => 'POST',
         ],
         $post
      );

      if (strpos($proxy->ContentType, 'application/json') !== FALSE) {
         $response = json_decode($response);
      }
      if (isset($response->error)) {
         throw new Gdn_UserException('Error Communicating with Salesforce API: ' . $response->error_description);
      }

      return $response;

   }

   /**
    * Used in the OAuth process.
    *
    * @return string $Url
    */
   public static function ProfileConnecUrl() {
      return Gdn::Request()->Url('/profile/salesforceconnect', TRUE, TRUE, TRUE);
   }

   /**
    * Used in the OAuth process.
    *
    * @return bool
    */
   public static function IsConfigured() {
      $appID = C('Plugins.Salesforce.ApplicationID');
      $secret = C('Plugins.Salesforce.Secret');
      if (!$appID || !$secret) {
         return FALSE;
      }
      return TRUE;
   }

   /**
    * Setter for AccessToken.
    *
    * @param $accessToken
    */
   public function SetAccessToken($accessToken) {
      $this->AccessToken = $accessToken;
   }

   /**
    * Setter for InstanceUrl.
    *
    * @param $instanceUrl
    */
   public function SetInstanceUrl($instanceUrl) {
      $this->InstanceUrl = $instanceUrl;
   }

}
