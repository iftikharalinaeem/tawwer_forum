<?php if (!defined('APP')) return;

/**
 * HTTP Request representation
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

class Request {
   
   /**
    * @var Request The current request.
    */
   public static $Current = null;
   
   public function __construct($url = null, $get = null, $post = null) {
      if ($url === null && $get === null && $post === null) {
         $url = Val('p', $_GET);
         $get = $_GET;
         $post = $_POST;
      }

      if (is_string($url)) {
         $this->Url($url);
      }

      if ($get !== null) {
         $this->_Get = array_merge($this->_Get, $get);
      }

      unset($this->_Get['p']); // p stores path.

      if ($post !== null) {
         $this->Post($post);
      }
      
      // Host & Method
      
      $this->Host(     isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? Val('HTTP_X_FORWARDED_HOST',$_SERVER) : (isset($_SERVER['HTTP_HOST']) ? Val('HTTP_HOST',$_SERVER) : Val('SERVER_NAME',$_SERVER)));
      $this->Method(   isset($_SERVER['REQUEST_METHOD']) ? Val('REQUEST_METHOD',$_SERVER) : 'CONSOLE');

      // IP Address
      
      // Loadbalancers
      $IP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? Val('HTTP_X_FORWARDED_FOR',$_SERVER) : $_SERVER['REMOTE_ADDR'];
      if (strpos($IP, ',') !== FALSE) $IP = substr($IP, 0, strpos($IP, ','));
      // Varnish
      $OriginalIP = Val('HTTP_X_ORIGINALLY_FORWARDED_FOR', $_SERVER, NULL);
      if (!is_null($OriginalIP)) $IP = $OriginalIP;
      
      $this->IP($IP);
      
      // Scheme
      
      $Scheme = 'http';
      // Webserver-originated SSL
      if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') $Scheme = 'https';
      // Loadbalancer-originated (and terminated) SSL
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') $Scheme = 'https';
      // Varnish
      $OriginalProto = Val('HTTP_X_ORIGINALLY_FORWARDED_PROTO', $_SERVER, NULL);
      if (!is_null($OriginalProto)) $Scheme = $OriginalProto;
      
      $this->Scheme($Scheme);
      
      // Figure out the root.
      $scriptName = $_SERVER['SCRIPT_NAME'];
      if ($scriptName && substr($scriptName, -strlen('index.php')) == 0) {
         $root = substr($scriptName, 0, -strlen('index.php'));
         $this->Root($root);
      }
   }

   public function __toString() {
      return $this->Url();
   }

   protected $_Get = array();
   public function Get($key = null, $default = null) {
      if ($key === null)
         return $this->_Get;
      if (is_string($key))
         return isset($this->_Get[$key]) ? $this->_Get[$key] : $default;
      if (is_array($key))
         $this->_Get = $key;
   }
   
   protected $_IP = array();
   public function IP($ip = null) {
      if ($ip === null)
         return $this->_IP;
      $this->_IP = $ip;
   }

   protected $_Host = array();
   public function Host($host = null) {
      if ($host === null)
         return $this->_Host;
      $this->_Host = $host;
   }
   
   protected $_Method = array();
   public function Method($method = null) {
      if ($method === null)
         return $this->_Method;
      $this->_Method = $method;
   }

   protected $_Path = array();
   public function Path($path = null) {
      if (is_string($path)) {
         $path = trim(trim($path, '/'));
         if (empty($path))
            $this->_Path = array();
         else
            $this->_Path = explode('/', $path);
      } elseif (is_array($path)) {
         $this->_Path = $path;
      } elseif (is_numeric($path)) {
         if (array_key_exists($path, $this->_Path))
            return $this->_Path[$path];
         else
            return '';
      } else {
         return $this->_Path;
      }
   }
   
   protected $_PathArgs = NULL;
   public function PathArgs($path = null) {
      $pathArgs = is_null($this->_PathArgs) ? $this->Path() : $this->_PathArgs;
      if (is_string($path)) {
         $path = trim(trim($path, '/'));
         if (empty($path))
            $this->_PathArgs = array();
         else
            $this->_PathArgs = explode('/', $path);
      } elseif (is_array($path)) {
         $this->_PathArgs = $path;
      } elseif (is_numeric($path)) {
         if (array_key_exists($path, $pathArgs))
            return $pathArgs[$path];
         else
            return NULL;
      } else {
         return $pathArgs;
      }
   }

   protected $_Post = array();
   public function Post($key = null, $default = null) {
      if ($key === null)
         return $this->_Post;
      if (is_string($key))
         return isset($this->_Post[$key]) ? $this->_Post[$key] : $default;
      if (is_array($key))
         $this->_Post = $key;
   }
   
   protected $_Root = '';
   public function Root($value = null) {
      if ($value !== null) {
         $value = trim($value, '/');
         if (!empty($value))
            $value = '/'.$value;
         $this->_Root = $value;
      }
      return $this->_Root;
   }
   
   protected $_Scheme = 'http';
   public function Scheme($value = null) {
      if ($value === null)
         return $this->_Scheme;
      $this->_Scheme = $value;
   }

   public function Url($url = null) {
      if ($url !== null) {
         $urlParts = parse_url($url);
         $this->Path(trim($urlParts['path'], '/'));
         if (isset($urlParts['query']))
            $this->_Get = parse_str($urlParts['query']);
      }

      $pathEncoded = implode('/', array_map('rawurlencode', $this->_Path));
      return $this->Scheme().'://'.$this->Host().$this->Root().'/'.$pathEncoded.(!empty($this->_Get) ? '?'.http_build_query($this->_Get) : '');
   }
}

