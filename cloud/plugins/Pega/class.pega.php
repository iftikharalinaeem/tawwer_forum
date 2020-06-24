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

   public function __construct($accessToken = FALSE, $instanceUrl = FALSE) {

   }

   /**
    * Return the singleton instance of this class.
    * @return Pega
    */
   public static function instance() {
      if (!isset(self::$Instance)) {
         self::$Instance = new Pega();
      }
      return self::$Instance;
   }

   /**
    * @param string $object Case, Contact, Lead
    * @param string $objectID CaseId, ContactID, LeadID
    * @param bool|array $fullHttpResponse if true will return array with
    *    [ContentType]
    *    [Response]
    *    [HttpCode]
    *    [Headers]
    * @return array All the details returned from Pega
    */
   public function getObject($object, $objectID, $fullHttpResponse = FALSE) {
      $result = $this->request($object . $objectID);

      if ($fullHttpResponse) {
         return $result;
      }
      return $result['Response'];
   }


   /**
    * @param string $caseID
    * @return array All the details returned from Pega
    */
    public function getCase($caseID) {
        $result = $this->getObject('forum/interaction/get/', $caseID, TRUE);
        return $result;
    }

   /**
    * Create a new Case Object in Pega
    *
    * @link http://www.Pega.com/us/developer/docs/api/Content/sforce_api_objects_case.htm
    * @see Pega::ValidateCase
    * @param array $case
    * @return string CaseID
    * @throws Gdn_UserException
    */
    public function createCase(array $case) {
        if ($this->validateCase($case) === TRUE) {
            return $this->createObject('forum/interaction', $case);
        }
        throw new Gdn_UserException('Create Case: Required Fields Missing: ' . print_r($this->validateContact($case)));
    }

    /**
    * @param array $case
    * @return array|bool True or array of missing required fields
    */
    public function validateCase(array $case) {
        return true;
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
    public function createObject($object, array $fields) {
        $response = $this->request($object . '/', json_encode($fields));
        if ($response['Response']['pzStatus'] == 'valid') {
            return $response['Response']['pyID'];
        }
        throw new Gdn_UserException("Response: " . $response['Response']);
    }

   /**
    *
    * Sends Request to the Pegas REST API
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
    */
   public function request($path, $post = FALSE, $cache = TRUE) {
      $url = c("Plugins.Pega.BaseUrl") . '/' . ltrim($path, '/');

//      $CacheKey = 'Pega.Request' . md5($Url);
//       if ($Cache && !$Post) {
//         $HttpResponse = Gdn::cache()->get($CacheKey, array(Gdn_Cache::FEATURE_COMPRESS => TRUE));
//         if ($HttpResponse) {
//            trace('Cached Response');
//            return $HttpResponse;
//         }
//      }
//       $this->AccessToken = 'ETcRgfVog0djraMI_EURmC1z8H-n7Cl2s-NIwxGY6oxxcJHB7USF6pWPaNcKaUek*/!STANDARD?';//temporary
//      if (!$this->AccessToken) {
//         throw new gdn_UserException("You don't have a valid Pega connection.");
//      }

      $httpResponse = $this->httpRequest($url, $post, 'application/json');
      $contentType = $httpResponse['ContentType'];

      Gdn::controller()->setJson('Type', $contentType);
      if (strpos($contentType, 'application/json') !== FALSE) {
         $httpResponse['Response'] = json_decode($httpResponse['Response'], TRUE);
         if (isset($result['error'])) {
            Gdn::dispatcher()->passData('PegaResponse', $result);
            throw new Gdn_UserException($result['error']['message']);
         }
      }
//      if ($Cache && $HttpResponse['HttpCode'] == 200 && !$Post) {
//         $CacheTTL = $this->CacheTTL + rand(0, 30);
//         Gdn::cache()->store($CacheKey, $HttpResponse, array(
//            Gdn_Cache::FEATURE_EXPIRY  => $CacheTTL,
//            Gdn_Cache::FEATURE_COMPRESS => TRUE
//         ));
//      }

      return $httpResponse;
   }

   /**
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
    public function httpRequest($url, $post = FALSE, $requestContentType = NULL) {
        $proxy = new ProxyRequest();
        $options['URL'] =  $url;
        $options['Method'] = 'POST';
        $options['ConnectTimeout'] = 60;
        $options['Timeout'] = 60;
        $options['Debug'] = false;
        $queryParams = NULL;
        if (!empty($requestContentType)) {
            $headers['Content-Type'] = $requestContentType;
        }

        if ($post)  {
            $options['Method'] = 'POST';
            $queryParams = $post;
        }

        $headers['Authorization'] = 'Basic ' . base64_encode(c('Plugins.Pega.Username') . ":" . c('Plugins.Pega.Password'));
        $headers['email'] = 'realsaraconnor@gmail.com';

//        error_log(json_encode($Headers) . "\n", 3, "/Users/patrick/my-errors.log");
//        error_log(json_encode($QueryParams) . "\n\n", 3, "/Users/patrick/my-errors.log");
//        error_log(json_encode($Options) . "\n\n\n", 3, "/Users/patrick/my-errors.log");

        $response = $proxy->request(
            $options,
            $queryParams,
            NULL,
            $headers
        );

        $failureCodes = [
            500 => TRUE,
        ];

        if (isset($failureCodes[$proxy->ResponseStatus])) {
            throw new Gdn_UserException('HTTP Error communicating with Pega.  Code: ' . $proxy->ResponseStatus);
        }

        /*
         * Presently, pega returns in xml
         */


        $result = simplexml_load_string ($response, 'SimpleXmlElement', LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);

        if(false != $result) {
            $xmlElement = new SimpleXMLElement($response);
            $responseJSON = json_encode($xmlElement);
            $responseArray = json_decode($responseJSON, true);
        }


        return [
            'HttpCode' => $proxy->ResponseStatus,
            'Header' => $proxy->RequestHeaders,
            'Response' => $responseArray,
            'ContentType' => $proxy->ContentType
        ];
   }

    function isConfigured() {
        return true;
    }

}