<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
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

function GetValueR($Key, $Collection, $Default = FALSE) {
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

function RandomString($Length, $CharacterOptions = 'A0') {
   $CharacterClasses = array(
       'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
       'a' => 'abcdefghijklmnopqrstuvwxyz',
       '0' => '0123456789',
       '!' => '~!@#$^&*_+-'
   );
   
   $Characters = '';
   for ($i=0;$i<strlen($CharacterOptions);$i++)
      $Characters .= GetValue($CharacterOptions{$i}, $CharacterClasses);
      
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
