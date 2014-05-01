<?php if (!defined('APP')) return;

/**
 * General Global Functions
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

/**
 * Basic autoloader function.
 * 
 * @param string $className
 */
function __autoload($className) {
   if (!preg_match('`^[a-z][a-z0-9]*$`i', $className)) {
      return;
   }
   $paths = array();
   $paths[] = PATH_ROOT.'/library/class.'.strtolower($className).'.php';
   $paths[] = PATH_ROOT.'/controllers/class.'.strtolower($className).'.php';

   foreach ($paths as $path) {
      if (file_exists($path)) {
         include_once $path;
         return;
      }
   }
}

/**
 * Reflect args onto controller method
 * 
 * @param Request $request
 * @param string $className
 * @param string $methodName
 * @return array
 */
function ReflectArgs($request, $className, $methodName) {
   // Reflect the method args onto the get.
   $reflect = array();
   $method = new ReflectionMethod($className, $methodName);
   $params = $method->getParameters();
   $pathArgs = $request->PathArgs();
   
   foreach ($params as $i => $param) {
      $name = strtolower($param->getName());
      if (isset($reflect[$name])) {
         $reflect[$name] = $reflect[$name];
         unset($reflect[$name]);
      } elseif ($request->PathArgs($i) != null)
         $reflect[$name] = $request->PathArgs($i);
      elseif ($param->isDefaultValueAvailable())
         $reflect[$name] = $param->getDefaultValue();
      else
         $reflect[$name] = null;
   }
   
   return $reflect;
}

/**
 * Route finder
 * 
 * Parses the Request and determines the method and controller.
 * 
 * @param Request $request
 * @return Request
 */
function Route($request) {
   // Figure out the controller and method.
   $pathParts = $request->Path();
   $get = array_change_key_case($request->Get());
   $requestMethod = $request->Method();
   
   // Look for a controller/method in the form: controller/method, controller[/index], [/index]method, [/index].
   $dispatchParts = array();
   $indexedArgs = array();
   if (empty($pathParts)) {
      $pathParts[0] = 'index';
      $pathParts[1] = 'index';
   }
   
   $routed = FALSE;
   
   $className = ucfirst($pathParts[0]).'Controller';
   if (class_exists($className)) {
      $dispatchParts[0] = $pathParts[0];
      
      // Method with HTTP verb prefix?
      if (isset($pathParts[1]) && CheckRoute($className, "{$requestMethod}_{$pathParts[1]}", $routed)) {
         $dispatchParts[1] = "{$requestMethod}_{$pathParts[1]}";
         $indexedArgs = array_slice($pathParts, 2);
         
      // Method without HTTP verb prefix?
      } elseif (isset($pathParts[1]) && CheckRoute($className, $pathParts[1], $routed)) {
         $dispatchParts[1] = $pathParts[1];
         $indexedArgs = array_slice($pathParts, 2);
      
      // Controller with index method, HTTP verb prefix?
      } elseif (CheckRoute($className, "{$requestMethod}_Index", $routed)) {
         $dispatchParts[1] = "{$requestMethod}_index";
         $indexedArgs = array_slice($pathParts, 1);
         
      // Controller with index method, no HTTP verb prefix?
      } elseif (CheckRoute($className, 'Index', $routed)) {
         $dispatchParts[1] = 'index';
         $indexedArgs = array_slice($pathParts, 1);
      }
   }
   
   // Index Controller, method with HTTP verb prefix?
   if (CheckRoute('IndexController', "{$requestMethod}_{$pathParts[0]}", $routed)) {
      $dispatchParts[0] = 'index';
      $dispatchParts[1] = "{$requestMethod}_{$pathParts[0]}";
      $indexedArgs = array_slice($pathParts, 1);
   }
   
   // Index Controller, method without HTTP verb prefix?
   if (CheckRoute('IndexController', $pathParts[0], $routed)) {
      $dispatchParts[0] = 'index';
      $dispatchParts[1] = $pathParts[0];
      $indexedArgs = array_slice($pathParts, 1);
   }
   
   // Home NotFound
   if (!$routed) {
      $dispatchParts[0] = 'home';
      $dispatchParts[1] = 'notfound';
      $get = array('url' => $request->Url());
   }
   
   $result = new Request(
      implode('/', $dispatchParts),
      $get,
      $request->Post());
   $result->PathArgs($indexedArgs);
   
   return $result;
}

function CheckRoute($className, $methodName, &$routed) {
   if ($routed) return FALSE;
   if (class_exists($className) && method_exists($className, $methodName))
      return $routed = TRUE;
   return $routed = FALSE;
}

/**
 * Simple HTTP redirect 
 * 
 * @param string $url
 * @param integer $code Optional.
 */
function Redirect($url, $code = 301) {
   header("Location: $url", TRUE, $code);
   exit();
}

/**
 * URL builder
 * 
 * @param string $path
 * @return string
 */
function Url($path) {
   return Request::$Current->Scheme().'://'.Request::$Current->Host().Request::$Current->Root().'/'.ltrim($path, '/');
}

/**
 * Return the value from an associative array or an object.
 *
 * @param string $Key The key or property name of the value.
 * @param mixed $Collection The array or object to search.
 * @param mixed $Default The value to return if the key does not exist.
 * @param bool $Remove Whether or not to remove the item from the collection.
 * @return mixed The value from the array or object.
 */
function Val($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
   $Result = $Default;
   if(is_array($Collection) && array_key_exists($Key, $Collection)) {
      $Result = $Collection[$Key];
      if($Remove)
         unset($Collection[$Key]);
   } elseif(is_object($Collection) && property_exists($Collection, $Key)) {
      $Result = $Collection->$Key;
      if($Remove)
         unset($Collection->$Key);
   }

   return $Result;
}

function ValR($Key, $Collection, $Default = FALSE) {
   $Path = explode('.', $Key);

   $Value = $Collection;
   for($i = 0; $i < count($Path); ++$i) {
      $SubKey = $Path[$i];

      if(is_array($Value) && isset($Value[$SubKey])) {
         $Value = $Value[$SubKey];
      } elseif(is_object($Value) && isset($Value->$SubKey)) {
         $Value = $Value->$SubKey;
      } else {
         return $Default;
      }
   }
   return $Value;
}

/**
 * Get a value from the global config
 * 
 * @param string $key
 * @param mixed $default Optional.
 */
function C($key, $default = null) {
   return Api::$Config->Get($key, $default);
}

/**
 * Generate a random string
 * 
 * @param integer $Length Desired length of result string
 * @param string $Characters Character set
 * @return string
 */
function RandomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
   $CharLen = strlen($Characters) - 1;
   $String = '' ;
   for ($i = 0; $i < $Length; ++$i) {
     $Offset = rand() % $CharLen;
     $String .= substr($Characters, $Offset, 1);
   }
   return $String;
}

/**
 * Formats a string by inserting data from its arguments, similar to sprintf, but with a richer syntax.
 *
 * @param string $String The string to format with fields from its args enclosed in curly braces. The format of fields is in the form {Field,Format,Arg1,Arg2}. The following formats are the following:
 *  - date: Formats the value as a date. Valid arguments are short, medium, long.
 *  - number: Formats the value as a number. Valid arguments are currency, integer, percent.
 *  - time: Formats the valud as a time. This format has no additional arguments.
 *  - url: Calls Url() function around the value to show a valid url with the site. You can pass a domain to include the domain.
 *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
 *  - html: Calls htmlspecialchars.
 * @param array $Args The array of arguments. If you want to nest arrays then the keys to the nested values can be seperated by dots.
 * @return string The formatted string.
 * <code>
 * echo FormatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
 * // This would output the following string:
 * // Hello Frank, It's 12:59PM.
 * </code>
 */
function FormatString($String, $Args = array()) {
   _FormatStringCallback($Args, TRUE);
   $Result = preg_replace_callback('/{([^}]+?)}/', '_FormatStringCallback', $String);

   return $Result;
}

function _FormatStringCallback($Match, $SetArgs = FALSE) {
   static $Args = array();
   if ($SetArgs) {
      $Args = $Match;
      return;
   }

   $Match = $Match[1];
   if ($Match == '{')
      return $Match;

   // Parse out the field and format.
   $Parts = explode(',', $Match);
   $Field = trim($Parts[0]);
   $Format = strtolower(trim(GetValue(1, $Parts, '')));
   $SubFormat = strtolower(trim(GetValue(2, $Parts, '')));
   $FomatArgs = GetValue(3, $Parts, '');

   if (in_array($Format, array('currency', 'integer', 'percent'))) {
      $FormatArgs = $SubFormat;
      $SubFormat = $Format;
      $Format = 'number';
   } elseif(is_numeric($SubFormat)) {
      $FormatArgs = $SubFormat;
      $SubFormat = '';
   }

   $Value = GetValueR($Field, $Args, '');
   if ($Value == '' && $Format != 'url') {
      $Result = '';
   } else {
      switch(strtolower($Format)) {
         case 'date':
            $TimeValue = strtotime($Value);
            switch($SubFormat) {
               case 'short':
                  $Result = date($TimeValue, 'd/m/Y');
                  break;
               case 'medium':
                  $Result = date($TimeValue, 'j M Y');
                  break;
               case 'long':
                  $Result = date($TimeValue, 'j F Y');
                  break;
               default:
                  $Result = date($TimeValue);
                  break;
            }
            break;
         case 'html':
         case 'htmlspecialchars':
            $Result = htmlspecialchars($Value);
            break;
         case 'number':
            if(!is_numeric($Value)) {
               $Result = $Value;
            } else {
               switch($SubFormat) {
                  case 'currency':
                     $Result = '$'.number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 2);
                  case 'integer':
                     $Result = (string)round($Value);
                     if(is_numeric($FormatArgs) && strlen($Result) < $FormatArgs) {
                           $Result = str_repeat('0', $FormatArgs - strlen($Result)).$Result;
                     }
                     break;
                  case 'percent':
                     $Result = round($Value * 100, is_numeric($FormatArgs) ? $FormatArgs : 0);
                     break;
                  default:
                     $Result = number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 0);
                     break;
               }
            }
            break;
         case 'rawurlencode':
            $Result = rawurlencode($Value);
            break;
         case 'time':
            $TimeValue = strtotime($Value);
            $Result = date($TimeValue, 'H:ia');
            break;
         case 'url':
            if (strpos($Field, '/') !== FALSE)
               $Value = $Field;
            $Result = Url($Value, $SubFormat == 'domain');
            break;
         case 'urlencode':
            $Result = urlencode($Value);
            break;
         default:
            $Result = $Value;
            break;
      }
   }
   return $Result;
}

/** Checks whether or not string A begins with string B.
 *
 * @param string $Haystack The main string to check.
 * @param string $Needle The substring to check against.
 * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool Whether or not to trim $B off of $A if it is found.
 * @return bool|string Returns true/false unless $Trim is true.
 */
function StringBeginsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
   if (strlen($Haystack) < strlen($Needle))
      return $Trim ? $Haystack : FALSE;
   elseif (strlen($Needle) == 0) {
      if ($Trim)
         return $Haystack;
      return TRUE;
   } else {
      $Result = substr_compare($Haystack, $Needle, 0, strlen($Needle), $CaseInsensitive) == 0;
      if ($Trim)
         $Result = $Result ? substr($Haystack, strlen($Needle)) : $Haystack;
      return $Result;
   }
}

/** Checks whether or not string A ends with string B.
 *
 * @param string $Haystack The main string to check.
 * @param string $Needle The substring to check against.
 * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool Whether or not to trim $B off of $A if it is found.
 * @return bool|string Returns true/false unless $Trim is true.
 */
function StringEndsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
   if (strlen($Haystack) < strlen($Needle)) {
      return $Trim ? $Haystack : FALSE;
   } elseif (strlen($Needle) == 0) {
      if ($Trim)
         return $Haystack;
      return TRUE;
   } else {
      $Result = substr_compare($Haystack, $Needle, -strlen($Needle), strlen($Needle), $CaseInsensitive) == 0;
      if ($Trim)
         $Result = $Result ? substr($Haystack, 0, -strlen($Needle)) : $Haystack;
      return $Result;
   }
}

/**
 * Takes a list of path parts and concatenates them with a forward slash (/) 
 * betwee each part. Delimiters will not be duplicated. Example: all of the
 * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
 * array('/path/to/vanilla', 'applications/dashboard')
 * array('/path/to/vanilla/', '/applications/dashboard')
 * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
 * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
 * 
 * @param $part A variable list of path parts
 * @returns The concatentated path.
 */
function P() {
   $paths = func_get_args();
   $delimiter = '/';
   if (is_array($paths)) {
      $mungedPath = implode($delimiter, $paths);
      $mungedPath = str_replace(array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter), array($delimiter, $delimiter), $mungedPath);
      return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $mungedPath);
   } else {
      return $paths;
   }
}