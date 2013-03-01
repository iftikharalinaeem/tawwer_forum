<?php

/**
 * @param type $value
 * @param type $prefix
 */
function decho($value, $prefix = 'debug') {
   fwrite(STDERR, "$prefix: ".var_export($value, true)."\n");
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
 * Safely get a value out of an array by returning a default value of the key doesn't exist in the array.
 * 
 * @param string|int $key The array key.
 * @param array $array The array to get the value from.
 * @param mixed $default The default value to return if the key doesn't exist.
 * @return mixed
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
 * @return mixed Either $trueValue or $falseValue.
 */
function valif($key, $array, $trueValue, $falseValue = null, $default = false) {
   if (!array_key_exists($key, $array))
      return $default ? $trueValue : $falseValue;
   elseif ($array[$key])
      return $trueValue;
   else
      return $falseValue;
}