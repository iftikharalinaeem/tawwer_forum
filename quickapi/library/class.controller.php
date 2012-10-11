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
   
}