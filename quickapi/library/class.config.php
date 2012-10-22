<?php if (!defined('APP')) exit;

/**
 * Config parser
 * 
 * Abstracts config file parsing logic away from application logic.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

class Config {
   
   /**
    * @var array
    */
   protected $Config;
   
   /**
    * @var string
    */
   protected $File;

   public function __construct($file) {
      if (!file_exists($file)) return FALSE;
      $this->File = $file;
      $this->Config = parse_ini_file($this->File, TRUE, INI_SCANNER_RAW);
   }
   
   /**
    * Get a config setting
    * 
    * @param string $setting
    * @param mixed $default
    * @return mixed
    */
   public function Get($setting = null, $default = NULL) {
      $setting = trim($setting);
      if (empty($setting)) return $this->Config;
      
      $settingParts = explode(' ', $setting);
      $keyLength = sizeof($settingParts);
      $settingTopic = array_shift($settingParts);
      $settingKey = implode(' ', $settingParts);
      
      if (!array_key_exists($settingTopic, $this->Config)) 
         return $default;
      
      if ($keyLength == 1)
         return self::ParseConfig($this->Config[$settingTopic]);
      
      if (!array_key_exists($settingKey, $this->Config[$settingTopic])) 
         return $default;
      return self::ParseConfig($this->Config[$settingTopic][$settingKey]);
   }
   
   /**
    * Post-parse a returned value from the config
    * 
    * Allows special meanings for things like 'on', 'off' and 'true' or 'false'.
    * 
    * @param string $param
    * @return mixed
    */
   protected static function ParseConfig($param) {
      if (!is_array($param) && !is_object($param)) {
         $compare = trim(strtolower($param));
         if (in_array($compare, array('yes','true','on','1')))
            return TRUE;
         if (in_array($compare, array('no', 'false', 'off','0')))
            return FALSE;
      }
      return $param;
   }
   
}