<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class Pega {

   /**
    * @var Pega
    */
   static $Instance;

   const ProviderKey = 'Pega';

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

   }

   /**
    * Return the singleton instance of this class.
    * @return Pega
    */
   public static function Instance() {
      if (!isset(self::$Instance)) {
         self::$Instance = new Pega();
      }
      return self::$Instance;
   }

   /**
    * @param string $Object Case, Contact, Lead
    * @param string $ObjectID CaseId, ContactID, LeadID
    * @param bool|array $FullHttpResponse if true will return array with
    *    [ContentType]
    *    [Response]
    *    [HttpCode]
    *    [Headers]
    * @return array All the details returned from Pega
    */
   public function GetObject($Object, $ObjectID, $FullHttpResponse = FALSE) {
      $Result = $this->Request($Object . $ObjectID);

      if ($FullHttpResponse) {
         return $Result;
      }
      return $Result['Response'];
   }


   /**
    * @param string $CaseID
    * @return array All the details returned from Pega
    */
    public function GetCase($CaseID) {
        $Result = $this->GetObject('forum/interaction/get/', $CaseID, TRUE);
        return $Result;
    }

   /**
    * Create a new Case Object in Pega
    *
    * @link http://www.Pega.com/us/developer/docs/api/Content/sforce_api_objects_case.htm
    * @see Pega::ValidateCase
    * @param array $Case
    * @return string CaseID
    * @throws Gdn_UserException
    */
    public function CreateCase(array $Case) {
        if ($this->ValidateCase($Case) === TRUE) {
            return $this->CreateObject('forum/interaction', $Case);
        }
        throw new Gdn_UserException('Create Case: Required Fields Missing: ' . print_r($this->ValidateContact($Case)));
    }

    /**
    * @param array $Case
    * @return array|bool True or array of missing required fields
    */
    public function ValidateCase(array $Case) {
        return true;
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
        $Response = $this->Request($Object . '/', json_encode($Fields));
        if ($Response['Response']['pzStatus'] == 'valid') {
            return $Response['Response']['pyID'];
        }
        throw new Gdn_UserException("Response: " . $Response['Response']);
    }

   /**
    *
    * Sends Request to the Pegas REST API
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
    */
   public function Request($Path, $Post = FALSE, $Cache = TRUE) {
      $Url = C("Plugins.Pega.BaseUrl") . '/' . ltrim($Path, '/');

//      $CacheKey = 'Pega.Request' . md5($Url);
//       if ($Cache && !$Post) {
//         $HttpResponse = Gdn::Cache()->Get($CacheKey, array(Gdn_Cache::FEATURE_COMPRESS => TRUE));
//         if ($HttpResponse) {
//            Trace('Cached Response');
//            return $HttpResponse;
//         }
//      }
//       $this->AccessToken = 'ETcRgfVog0djraMI_EURmC1z8H-n7Cl2s-NIwxGY6oxxcJHB7USF6pWPaNcKaUek*/!STANDARD?';//temporary
//      if (!$this->AccessToken) {
//         throw new Gdn_UserException("You don't have a valid Pega connection.");
//      }

      $HttpResponse = $this->HttpRequest($Url, $Post, 'application/json');
      $ContentType = $HttpResponse['ContentType'];

      Gdn::Controller()->SetJson('Type', $ContentType);
      if (strpos($ContentType, 'application/json') !== FALSE) {
         $HttpResponse['Response'] = json_decode($HttpResponse['Response'], TRUE);
         if (isset($Result['error'])) {
            Gdn::Dispatcher()->PassData('PegaResponse', $Result);
            throw new Gdn_UserException($Result['error']['message']);
         }
      }
//      if ($Cache && $HttpResponse['HttpCode'] == 200 && !$Post) {
//         $CacheTTL = $this->CacheTTL + rand(0, 30);
//         Gdn::Cache()->Store($CacheKey, $HttpResponse, array(
//            Gdn_Cache::FEATURE_EXPIRY  => $CacheTTL,
//            Gdn_Cache::FEATURE_COMPRESS => TRUE
//         ));
//      }

      return $HttpResponse;
   }

   /**
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
        $Options['Method'] = 'POST';
        $Options['ConnectTimeout'] = 60;
        $Options['Timeout'] = 60;
        $Options['Debug'] = false;
        $QueryParams = NULL;
        if (!empty($RequestContentType)) {
            $Headers['Content-Type'] = $RequestContentType;
        }

        if ($Post)  {
            $Options['Method'] = 'POST';
            $QueryParams = $Post;
        }

        $Headers['Authorization'] = 'Basic ' . base64_encode(C('Plugins.Pega.Username') . ":" . C('Plugins.Pega.Password'));
        $Headers['email'] = 'realsaraconnor@gmail.com';

//        error_log(json_encode($Headers) . "\n", 3, "/Users/patrick/my-errors.log");
//        error_log(json_encode($QueryParams) . "\n\n", 3, "/Users/patrick/my-errors.log");
//        error_log(json_encode($Options) . "\n\n\n", 3, "/Users/patrick/my-errors.log");

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
            throw new Gdn_UserException('HTTP Error communicating with Pega.  Code: ' . $Proxy->ResponseStatus);
        }

        /*
         * Presently, pega returns in xml
         */


        $result = simplexml_load_string ($Response, 'SimpleXmlElement', LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);

        if(false != $result) {
            $xmlElement = new SimpleXMLElement($Response);
            $ResponseJSON = json_encode($xmlElement);
            $ResponseArray = json_decode($ResponseJSON, true);
        }


        return array(
            'HttpCode' => $Proxy->ResponseStatus,
            'Header' => $Proxy->RequestHeaders,
            'Response' => $ResponseArray,
            'ContentType' => $Proxy->ContentType
        );
   }

    function isConfigured() {
        return true;
    }

}