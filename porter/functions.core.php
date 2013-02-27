<?php

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
         case '':
            return 0;
      }
      return 1;
   }
   return intval($value);
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