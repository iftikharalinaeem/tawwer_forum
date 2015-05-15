<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
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

   public function __construct($AccessToken = FALSE, $InstanceUrl = FALSE) {
      if ($AccessToken != FALSE && $InstanceUrl != FALSE) {
         $this->AccessToken = $AccessToken;
         $this->InstanceUrl = $InstanceUrl;
      } elseif (Gdn::Session()->IsValid()) {
         $this->AccessToken = GetValue('AccessToken', Gdn::Session()->User->Attributes['Salesforce']);
         $this->InstanceUrl = GetValue('InstanceUrl', Gdn::Session()->User->Attributes['Salesforce']);
         $this->RefreshToken = GetValue('RefreshToken', Gdn::Session()->User->Attributes['Salesforce']);
      }
      if (C('Plugins.Salesforce.DashboardConnection.Enabled', FALSE)
         && $this->AccessToken == FALSE && $this->InstanceUrl == FALSE)
      {
         $this->UseDashboardConnection();
         $this->DashboardConnection = TRUE;
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
    * @param string $Object Case, Contact, Lead
    * @param string $ObjectID CaseId, ContactID, LeadID
    * @param bool|array $FullHttpResponse if true will return array with
    *    [ContentType]
    *    [Response]
    *    [HttpCode]
    *    [Headers]
    * @return array All the details returned from Salesforce
    */
   public function GetObject($Object, $ObjectID, $FullHttpResponse = FALSE) {
      $Result = $this->Request('sobjects/' . $Object . '/' . $ObjectID);

      if ($FullHttpResponse) {
         return $Result;
      }
      return $Result['Response'];
   }

   /**
    * @param string $ContactID
    * @return array All the details returned from Salesforce
    */
   public function GetContact($ContactID) {
      $Result = $this->GetObject('Contact', $ContactID);
      return $Result;
   }

   /**
    * @param string $LeadID
    * @return array All the details returned from Salesforce
    */
   public function GetLead($LeadID) {
      return $this->GetObject('Lead', $LeadID, TRUE);
   }

   /**
    * @param string $AccountID
    * @return array All the details returned from Salesforce
    */
   public function GetAccount($AccountID) {
      return $this->GetObject('Account', $AccountID);
   }

   /**
    * @param string $UserID
    * @return array All the details returned from Salesforce
    */
   public function GetUser($UserID) {
      return $this->GetObject('User', $UserID);
   }

   /**
    * @param string $Email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindLead($Email) {
      $Result = $this->Select(array('id'), 'Lead', array('Email' => $Email), 1);
      if ($Result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetLead($Result['records'][0]['Id']);
   }

   /**
    * @param string $Email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindUser($Email) {
      $Result = $this->Select(array('id'), 'User', array('Email' => $Email), 1);
      if ($Result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetUser($Result['records'][0]['Id']);
   }

   /**
    * @param string $Email
    * @return array|bool FALSE if not found or All the details returned from Salesforce
    */
   public function FindContact($Email) {
      $Result = $this->Select(array('id'), 'Contact', array('Email' => $Email), 1);
      if ($Result['totalSize'] != 1) {
         return FALSE;
      }
      return $this->GetContact($Result['records'][0]['Id']);
   }

   /**
    * @param string $CaseID
    * @return array All the details returned from Salesforce
    */
   public function GetCase($CaseID) {
      $Result = $this->GetObject('Case', $CaseID, TRUE);
      return $Result;
   }

   /**
    * Create a new Lead Object in Salesforce
    *
    * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_lead.htm
    * @see Salesforce::ValidateLead
    * @param array $Lead
    * @return string LeadID
    * @throws Gdn_UserException
    */
   public function CreateLead(array $Lead) {
      if ($this->ValidateLead($Lead) === TRUE) {
         return $this->CreateObject('Lead', $Lead);
      }
      throw new Gdn_UserException('Create Lead: Required Fields Missing: ' . print_r($this->ValidateLead($Lead)));
   }

   /**
    * @param array $Lead
    * @return array|bool True or array of missing required fields
    */
   public function ValidateLead(array $Lead) {
      $RequiredFields = array(
         'LastName' => TRUE,
         'FirstName' => TRUE,
         'Email' => TRUE,
         'LeadSource' => TRUE,
         'Company' => TRUE,
      );
      $MissingFields = array_diff_key($RequiredFields, $Lead);
      if (!empty($MissingFields)) {
         return $MissingFields;
      }
      return TRUE;
   }

   /**
    * Create a new Contact Object in Salesforce
    *
    * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_contact.htm
    * @see Salesforce::ValidateContact
    * @param array $Contact
    * @return string ContactID
    * @throws Gdn_UserException
    */
   public function CreateContact(array $Contact) {
      if ($this->ValidateContact($Contact) === TRUE) {
         return $this->CreateObject('Contact', $Contact);
      }
      throw new Gdn_UserException('Create Contact: Required Fields Missing: '
         . print_r($this->ValidateContact($Contact)));
   }

   /**
    * @param array $Contact
    * @return array|bool True or array of missing required fields
    */
   public function ValidateContact(array $Contact) {
      $RequiredFields = array(
         'LastName' => TRUE,
         'FirstName' => TRUE,
         'Email' => TRUE,
      );
      $MissingFields = array_diff_key($RequiredFields, $Contact);
      if (!empty($MissingFields)) {
         return $MissingFields;
      }
      return TRUE;
   }


   /**
    * Create a new Case Object in Salesforce
    *
    * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_case.htm
    * @see Salesforce::ValidateCase
    * @param array $Case
    * @return string CaseID
    * @throws Gdn_UserException
    */
   public function CreateCase(array $Case) {
      if ($this->ValidateCase($Case) === TRUE) {
         return $this->CreateObject('Case', $Case);
      }
      throw new Gdn_UserException('Create Case: Required Fields Missing: '
         . print_r($this->ValidateContact($Case)));
   }

   /**
    * @param array $Case
    * @return array|bool True or array of missing required fields
    */
   public function ValidateCase(array $Case) {
      $RequiredFields = array(
         'ContactId' => TRUE,
         'Status' => TRUE,
         'Origin' => TRUE,
         'Subject' => TRUE,
         'Priority' => TRUE,
         'Description' => TRUE
      );
      $MissingFields = array_diff_key($RequiredFields, $Case);
      if (!empty($MissingFields)) {
         return $MissingFields;
      }
      return TRUE;
   }

   /**
    * @param $Object
    * @param array $Fields
    * @return mixed
    * @throws Gdn_UserException
    */
   public function CreateObject($Object, array $Fields) {
      $Response = $this->Request('sobjects/' . $Object . '/', json_encode($Fields));
      if (isset($Response['Response']['success'])) {
         return $Response['Response']['id'];
      }
      throw new Gdn_UserException($Response['Response'][0]['message']);
   }

   /**
    * Preform a SELECT query using SOQL
    *
    * @param array $Fields
    * @param $From
    * @param array $Where
    * @param int $Limit
    * @return array Response from SFDC; Hopefully a Valid Object
    *    [done] - bool
    *    [totalSize] - int
    *    [records] - array
    *       with the fields from $Fields
    *
    * @link http://www.salesforce.com/us/developer/docs/soql_sosl/index.htm
    */
   public function Select(array $Fields, $From, array $Where, $Limit = 0) {
      $Select = implode(', ', $Fields);
      $WhereClause = 'WHERE ';
      $WhereCount = count($Where);
      $I = 0;
      foreach ($Where as $Field => $Value) {
         $WhereClause .= "$Field = '$Value'";
         $I++;
         if ($I < $WhereCount && $I != $WhereCount) {
            $WhereClause .= ' AND ';
         }
      }
      $Query = 'SELECT ' . $Select . ' FROM ' . $From . ' ' . $WhereClause;
      if ($Limit > 0) {
         $Query .= ' LIMIT ' . $Limit;
      }
      $Response = $this->Request('query?q=' . urlencode($Query));
      return $Response['Response'];
   }

   /**
    * Get User Profile fields.
    *
    * @param string $LoginID - id from the Access Tokens after successful OAuth
    * @return array $Profile
    * @throws Exception
    */
   public function GetLoginProfile($LoginID) {
      $HttpResponse = $this->HttpRequest($LoginID);
      if ($HttpResponse['HttpCode'] != 200) {
         return FALSE;
      }
      $FullProfile = json_decode($HttpResponse['Response']);
      $Profile = array(
         'id' => $FullProfile->user_id,
         'email' => $FullProfile->email,
         'fullname' => $FullProfile->display_name,
         'photo' => $FullProfile->photos->thumbnail,
      );
      return $Profile;
   }

   /**
    * @return string the <option> string for Form
    * @throws Gdn_UserException
    */
   public function GetLeadStatusOptions() {
      $Options = '';
      $Response = $this->Request('sobjects/Lead/describe');
      if ($Response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Lead Status Options');
      }
      foreach ($Response['Response']['fields'] as $FieldNum => $Field) {
         if ($Field['name'] == 'Status') {
            foreach ($Field['picklistValues'] as $pickListValue) {
               $Options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $Options .= 'selected';
               }

               $Options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
            }
         }
      }
      return $Options;
   }

   /**
    * @return string the <option> string for Form
    * @throws Gdn_UserException
    */
   public function GetCaseStatusOptions() {
      $Options = '';
      $Response = $this->Request('sobjects/Case/describe');
      if ($Response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Case status Options');
      }
      foreach ($Response['Response']['fields'] as $FieldNum => $Field) {
         if ($Field['name'] == 'Status') {
            foreach ($Field['picklistValues'] as $pickListValue) {
               $Options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $Options .= 'selected';
               }
               $Options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
            }
         }
      }
      return $Options;
   }

   /**
    * @return string the <option> string for Form
    * @throws Gdn_UserException
    */
   public function GetCasePriorityOptions() {
      $Options = '';
      $Response = $this->Request('sobjects/Case/describe');
      if ($Response['HttpCode'] != 200) {
         throw new Gdn_UserException('Error getting Case status Options');
      }
      foreach ($Response['Response']['fields'] as $FieldNum => $Field) {
         if ($Field['name'] == 'Priority') {
            foreach ($Field['picklistValues'] as $pickListValue) {
               $Options .= '<option ';
               if ($pickListValue['defaultValue'] == TRUE) {
                  $Options .= 'selected';
               }
               $Options .= ' value="' . $pickListValue['value']
                  . '">' . $pickListValue['label'] . '</option>' . "\n";
            }
         }
      }
      return $Options;
   }

   /**
    * Sends Request to the Salesforces REST API.
    *
    * @param $Path
    * @param bool|array $Post false or array of values to be sent as json POST
    * @param bool $Cache
    * @return array $HttpResponse with the following keys
    *    [HttpCode] - HTTP Status Code
    *    [Response] - JSON Decoded Values if Content Type == Json
    *    [Header] - HTTP Header
    *    [ContentType] - HTTP Content Type
    * @throws Gdn_UserException
    *
    * @see http://www.salesforce.com/us/developer/docs/api_rest/
    */
   public function Request($Path, $Post = FALSE, $Cache = TRUE) {
      $Url = $this->InstanceUrl . '/services/data/v' . $this->APIVersion . '/' . ltrim($Path, '/');
      $CacheKey = 'Salesforce.Request' . md5($Url);

      if ($Cache && !$Post) {
         $HttpResponse = Gdn::Cache()->Get($CacheKey, array(Gdn_Cache::FEATURE_COMPRESS => TRUE));
         if ($HttpResponse) {
            Trace('Cached Response');
            return $HttpResponse;
         }
      }
      if (!$this->AccessToken) {
         throw new Gdn_UserException("You don't have a valid Salesforce connection.");
      }
      $HttpResponse = $this->HttpRequest($Url, $Post, 'application/json');
      $ContentType = $HttpResponse['ContentType'];
      Gdn::Controller()->SetJson('Type', $ContentType);
      if (strpos($ContentType, 'application/json') !== FALSE) {
         $HttpResponse['Response'] = json_decode($HttpResponse['Response'], TRUE);
         if (isset($Result['error'])) {
            Gdn::Dispatcher()->PassData('SalesforceResponse', $Result);
            throw new Gdn_UserException($Result['error']['message']);
         }
      }
      if ($Cache && $HttpResponse['HttpCode'] == 200 && !$Post) {
         $CacheTTL = $this->CacheTTL + rand(0, 30);
         Gdn::Cache()->Store($CacheKey, $HttpResponse, array(
            Gdn_Cache::FEATURE_EXPIRY  => $CacheTTL,
            Gdn_Cache::FEATURE_COMPRESS => TRUE
         ));
      }
      return $HttpResponse;
   }

   /**
    * Send an HTTP request to Salesforce with Authorize header.
    *
    * @param string $Url -
    * @param bool|array $Post
    * @param string|bull AccessToken
    * @return array $HttpResponse with the following keys
    *    [HttpCode] - HTTP Status Code
    *    [Response] - HTTP Body
    *    [Header] - HTTP Header
    *    [ContentType] - HTTP Content Type
    * @throws Exception
    */
   public function HttpRequest($Url, $Post = FALSE, $RequestContentType = NULL) {
      $Proxy = new ProxyRequest();
      $Options['URL'] =  $Url;
      $Options['Method'] = 'GET';
      $Options['ConnectTimeout'] = 10;
      $Options['Timeout'] = 10;
      $QueryParams = NULL;
      if (!empty($RequestContentType)) {
         $Headers['Content-Type'] = $RequestContentType;
      }
      if ($Post)  {
         $Options['Method'] = 'POST';
         $QueryParams = $Post;
      }
      $Headers['Authorization'] = 'OAuth ' . $this->AccessToken;
      Trace('Salesforce Request - ' . $Options['Method']. ' : ' . $Url);
      $Response = $Proxy->Request(
         $Options,
         $QueryParams,
         NULL,
         $Headers
      );
      $FailureCodes = array(
         500 => TRUE,
      );
      if (isset($FailureCodes[$Proxy->ResponseStatus])) {
         throw new Gdn_UserException('HTTP Error communicating with Salesforce.  Code: ' . $Proxy->ResponseStatus);
      }

      return array(
         'HttpCode' => $Proxy->ResponseStatus,
         'Header' => $Proxy->RequestHeaders,
         'Response' => $Response,
         'ContentType' => $Proxy->ContentType
      );

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
         $Response = $this->Refresh($this->RefreshToken);
         $InstanceUrl = $Response['instance_url'];
         $AccessToken = $Response['access_token'];
         SaveToConfig(array(
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $InstanceUrl,
            'Plugins.Salesforce.DashboardConnection.Token' => $AccessToken,
         ));
         $this->SetAccessToken($AccessToken);
         $this->SetInstanceUrl($InstanceUrl);
      } else {
         $Response = $this->Refresh($this->RefreshToken);
         if ($Response != FALSE) {
            $Profile = GetValueR('Attributes.' . self::ProviderKey . '.Profile', Gdn::Session()->User);
            $Attributes = array(
               'RefreshToken' => $this->RefreshToken,
               'AccessToken' => $Response['access_token'],
               'InstanceUrl' => $Response['instance_url'],
               'Profile' => $Profile,
            );

            Gdn::UserModel()->SaveAttribute(Gdn::Session()->UserID, self::ProviderKey, $Attributes);
            $this->SetAccessToken($Response['access_token']);
            $this->SetInstanceUrl($Response['instance_url']);
         }
      }
   }

   /**
    * Revoke an access token.
    *
    * @param $Token
    * @return bool
    * @throws Gdn_UserException
    */
   public function Revoke($Token) {
      $Response = $this->HttpRequest(C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/revoke?token=' . $Token);
      if ($Response['HttpCode'] == 200) {
         return TRUE;
      }
      return FALSE;
   }

   /**
    * Sends refresh_token to Salesforce API and simply returns the response.
    *
    * @param $Token
    * @return bool|mixed On success, returns entire API response.
    * @throws Gdn_UserException
    * @see Reconnect() is probably what you want.
    */
   public function Refresh($Token) {
      $Response = $this->HttpRequest(
         C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/token',
         array(
            'grant_type' => 'refresh_token',
            'client_id' => C('Plugins.Salesforce.ApplicationID'),
            'client_secret' => C('Plugins.Salesforce.Secret'),
            'refresh_token' => $Token
         )
      );
      Trace($Response);
      if ($Response['HttpCode'] == 400) {
         throw new Gdn_UserException('Someone has Revoked your Connection.  Please reconnect manually,');
         return FALSE;
      }
      if (strpos($Response['ContentType'], 'application/json') !== FALSE) {
         $RefreshResponse = json_decode($Response['Response'], TRUE);

         return $RefreshResponse;
      }
      return FALSE;
   }

   /**
    * Used in the OAuth process.
    *
    * @param bool|string $RedirectUri
    * @param bool|string $State
    * @return string Authorize URL
    */
   public static function AuthorizeUri($RedirectUri = FALSE, $State = FALSE) {
      $AppID = C('Plugins.Salesforce.ApplicationID');
      if (!$RedirectUri) {
         $RedirectUri = self::RedirectUri();
      }
      $Query = array(
         'redirect_uri' => $RedirectUri,
         'client_id' => $AppID,
         'response_type' => 'code',
         'scope' => 'full refresh_token'
      );
      if ($State) {
         $Query['state'] = $State;
      }
      $Return = C('Plugins.Salesforce.AuthenticationUrl') . "/services/oauth2/authorize?"
         . http_build_query($Query, null , "&");
      return $Return;
   }

   /**
    * Used in the OAuth process.
    *
    * @param null $NewValue a different redirect url
    * @return null|string
    */
   public static function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL) {
         $RedirectUri = $NewValue;
      } else {
         $RedirectUri = Url('/profile/salesforceconnect', TRUE, TRUE, TRUE);
         if (strpos($RedirectUri, '=') !== FALSE) {
            $p = strrchr($RedirectUri, '=');
            $Uri = substr($RedirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $RedirectUri = $Uri . '=' . $p;
         }
      }
      return $RedirectUri;
   }

   /**
    * Used in the Oath process.
    *
    * @param $Code - OAuth Code
    * @param $RedirectUri - Redirect Uri
    * @return string Response
    * @throws Gdn_UserException
    */
   public static function GetTokens($Code, $RedirectUri) {
      $Post = array(
         'grant_type' => 'authorization_code',
         'client_id' => C('Plugins.Salesforce.ApplicationID'),
         'client_secret' => C('Plugins.Salesforce.Secret'),
         'code' => $Code,
         'redirect_uri' => $RedirectUri,
      );
      $Url = C('Plugins.Salesforce.AuthenticationUrl') . '/services/oauth2/token';
      $Proxy = new ProxyRequest();
      $Response = $Proxy->Request(
         array(
            'URL' => $Url,
            'Method' => 'POST',
         ),
         $Post
      );

      if (strpos($Proxy->ContentType, 'application/json') !== FALSE) {
         $Response = json_decode($Response);
      }
      if (isset($Response->error)) {
         throw new Gdn_UserException('Error Communicating with Salesforce API: ' . $Response->error_description);
      }

      return $Response;

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
      $AppID = C('Plugins.Salesforce.ApplicationID');
      $Secret = C('Plugins.Salesforce.Secret');
      if (!$AppID || !$Secret) {
         return FALSE;
      }
      return TRUE;
   }

   /**
    * Setter for AccessToken.
    *
    * @param $AccessToken
    */
   public function SetAccessToken($AccessToken) {
      $this->AccessToken = $AccessToken;
   }

   /**
    * Setter for InstanceUrl.
    *
    * @param $InstanceUrl
    */
   public function SetInstanceUrl($InstanceUrl) {
      $this->InstanceUrl = $InstanceUrl;
   }

}
