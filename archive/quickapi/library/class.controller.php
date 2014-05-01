<?php if (!defined('APP')) return;

/**
 * Controller base class
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

class Controller {
   
   /**
    * @var array 
    */
   public $Data = NULL;

   /**
    * @var Request
    */
   public $Request;

   public function __construct() {
      header('Content-Type: application/json; charset=utf-8');
   }
   
   /** Get a value out of the controller's data array.
    *
    * @param string $path The path to the data.
    * @param mixed $default The default value if the data array doesn't contain the path.
    * @return mixed
    * @see ValR()
    */
   public function Data($path = null, $default = '' ) {
      if (is_null($path)) return $this->Data;
      return ValR($path, $this->Data, $default);
   }
   
   /**
    * Set data from a method call.
    *
    * @param string $key The key that identifies the data.
    * @param mixed $value The data.
    * @return mixed The $Value that was set.
    */
   public function SetData($key, $value = NULL) {
      
      // Make sure the config settings are in the right format
      if (!is_array($this->Data))
         $this->Data = array();

      if (!is_array($key)) {
         $key = array(
            $key => $value
         );
      }
      
      $data = $key;
      foreach ($data as $key => $value) {

         $keys = explode('.', $key);
         $keyCount = count($keys);
         $cursor = &$this->Data;
         
         for ($i = 0; $i < $keyCount; ++$i) {
            $key = $keys[$i];
            
            if (!is_array($cursor)) $cursor = array();
            $keyExists = array_key_exists($key, $cursor);
   
            if ($i == $keyCount - 1) {
               
               // If we are on the last iteration of the key, then set the value.
               $cursor[$key] = $value;
               
            } else {
               
               // Build the array as we loop over the key. Doucement.
               if ($keyExists === FALSE)
                  $cursor[$key] = array();
               
               // Advance the pointer
               $cursor = &$cursor[$key];
            }
         }
      }
      
      return $value;
   }
   
   public function Initialize();
   
   public static function Status($statusCode, $message = NULL) {
      if (is_null($message))
         $message = self::GetStatusMessage($statusCode);
      return $message;
   }
   
   public static function GetStatusMessage($statusCode) {
      switch ($statusCode) {
         case 100: $message = 'Continue'; break;
         case 101: $message = 'Switching Protocols'; break;

         case 200: $message = 'OK'; break;
         case 201: $message = 'Created'; break;
         case 202: $message = 'Accepted'; break;
         case 203: $message = 'Non-Authoritative Information'; break;
         case 204: $message = 'No Content'; break;
         case 205: $message = 'Reset Content'; break;

         case 300: $message = 'Multiple Choices'; break;
         case 301: $message = 'Moved Permanently'; break;
         case 302: $message = 'Found'; break;
         case 303: $message = 'See Other'; break;
         case 304: $message = 'Not Modified'; break;
         case 305: $message = 'Use Proxy'; break;
         case 307: $message = 'Temporary Redirect'; break;

         case 400: $message = 'Bad Request'; break;
         case 401: $message = 'Not Authorized'; break;
         case 402: $message = 'Payment Required'; break;
         case 403: $message = 'Forbidden'; break;
         case 404: $message = 'Not Found'; break;
         case 405: $message = 'Method Not Allowed'; break;
         case 406: $message = 'Not Acceptable'; break;
         case 407: $message = 'Proxy Authentication Required'; break;
         case 408: $message = 'Request Timeout'; break;
         case 409: $message = 'Conflict'; break;
         case 410: $message = 'Gone'; break;
         case 411: $message = 'Length Required'; break;
         case 412: $message = 'Precondition Failed'; break;
         case 413: $message = 'Request Entity Too Large'; break;
         case 414: $message = 'Request-URI Too Long'; break;
         case 415: $message = 'Unsupported Media Type'; break;
         case 416: $message = 'Requested Range Not Satisfiable'; break;
         case 417: $message = 'Expectation Failed'; break;

         case 500: $message = 'Internal Server Error'; break;
         case 501: $message = 'Not Implemented'; break;
         case 502: $message = 'Bad Gateway'; break;
         case 503: $message = 'Service Unavailable'; break;
         case 504: $message = 'Gateway Timeout'; break;
         case 505: $message = 'HTTP Version Not Supported'; break;

         default: $message = 'Unknown'; break;
      }
      return $message;
   }
   
}