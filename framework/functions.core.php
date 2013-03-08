<?php

/**
 * Simple autoloader that looks at a single directory.
 * 
 * This autoloader can load the following classes.
 * 
 * - Any [PSR-0](//github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) named class.
 * - Any other class should be in the directory named as `class.classname.php`. Make sure to use all lowercase in the filename.
 * 
 * @param string $class The name of the class to autoload.
 * @param string $dir The directory to look in.
 */
function autoLoadDir($class, $dir = null) {
   if ($dir === null)
      $dir = __DIR__;
   
   // Support namespaces and underscore classes.
   $class = str_replace(array('\\', '_'), '/', $class);
   
   $pos = strrpos($class, '/');
   if ($pos !== false) {
      // Load as a P0 compliant class.
      $subdir = '/'.substr($class, 0, $pos + 1);
      $filename = substr($class, $pos + 1).'.php';
   } else {
      $subdir = '/';
      $filename = strtolower("class.$class.php");
   }
   
   $path = $dir.$subdir.$filename;
   if (file_exists($path)) {
      require_once $path;
      return true;
   }
}

/**
 * @param type $value
 * @param type $prefix
 */
function decho($value, $prefix = 'debug') {
   fwrite(STDERR, "$prefix: ".var_export($value, true)."\n");
}

/**
 * Make sure that a directory exists.
 * 
 * @param string $dir The name of the directory.
 * @param int $mode The file permissions on the folder if it's created.
 */
function ensureDir($dir, $mode = 0777) {
   if (!file_exists($dir)) {
      mkdir($dir, $mode, true);
   }
}

/**
 * Force a value into a boolean.
 * 
 * @param mixed $value The value to force.
 * @return boolean
 */
function forceBool($value) {
   if (is_string($value)) {
      switch (strtolower($value)) {
         case 'disabled':
         case 'false':
         case 'no':
         case 'off':
         case '':
            return false;
      }
      return true;
   }
   return boolval($value);
}

/**
 * Force a value to be an integer.
 * 
 * @param mixed $value The value to force.
 * @return int
 */
function forceInt($value) {
   if (is_string($value)) {
      switch (strtolower($value)) {
         case 'false':
         case 'no':
         case 'off':
         case '':
            return 0;
         case 'true':
         case 'yes':
         case 'on':
            return 1;
      }
   }
   return intval($value);
}

/**
 * Get the file extension from a mime-type.
 * @param string $mime
 * @param string $ext If this argument is specified then this extension will be added to the list of known types.
 * @return string The file extension without the dot.
 */
function mimeToExt($mime, $ext = null) {
   static $known = array('text/plain' => 'txt', 'image/jpeg' => 'jpg');
   $mime = strtolower($mime);
   
   if ($ext !== null) {
      $known[$mime] = ltrim($ext, '.');
   }
   
   if (array_key_exists($mime, $known))
      return $known[$mime];
   
   // We don't know the mime type so we need to just return the second part as the extension.
   $result = trim(strrchr($mime, '/'), '/');
   
   if (substr($result, 0, 2) === 'x-')
      $result = substr($result, 2);
   
   return $result;
}

/**
 * Make sure that a key exists in an array.
 * 
 * @param string|int $key The array key to ensure.
 * @param array $array The array to modify.
 * @param mixed $default The default value to set if key does not exist.
 */
function touchValue($key, &$array, $default) {
   if (!array_key_exists($key, $array))
      $array[$key] = $default;
}

/**
 * Safely get a value out of an array.
 * 
 * This function will always return a value even if the array key doesn't exist.
 * The val() function is one of the biggest workhorses of Vanilla and shows up a lot throughout other code.
 * It's much preferable to use this function if your not sure whether or not an array key exists rather than
 * using @ error suppression.
 * 
 * @param string|int $key The array key.
 * @param array $array The array to get the value from.
 * @param mixed $default The default value to return if the key doesn't exist.
 * @return mixed The item from the array or `$default` if the array key doesn't exist.
 */
function val($key, $array, $default = null) {
   if (array_key_exists($key, $array))
      return $array[$key];
   return $default;
}

/**
 * Look up an item in an array and return a different value depending on whether or not that value is true/false.
 * 
 * @param string|int $key The key of the array.
 * @param array $array The array to look at.
 * @param mixed $trueValue The value to return if we have true.
 * @param mixed $falseValue The value to return if we have true.
 * @param bool $default The default value of the key isn't in the array.
 * @return mixed Either `$trueValue` or `$falseValue`.
 */
function valif($key, $array, $trueValue, $falseValue = null, $default = false) {
   if (!array_key_exists($key, $array))
      return $default ? $trueValue : $falseValue;
   elseif ($array[$key])
      return $trueValue;
   else
      return $falseValue;
}