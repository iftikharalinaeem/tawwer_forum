<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VPIUtil {
   /** Sign data using Facebook's algorithm
    *
    * @param array $Data The data to sign.
    * @param string $Secret The secret used to hash with the data.
    * @param string $Exclude The name of a key to omit, usually the signature key.
    * @return string The signature of the data.
    */
   public static function SignFacebook($Data, $Secret, $Exclude = 'sig') {
      ksort($Data);

      $Payload = '';
      foreach ($Data as $Key => $Value) {
         if ($Key == $Exclude)
            continue;

         $Payload .= $Key.'='.$Value;
      }

      $Result = md5($Payload, $Secret);
      return $Result;
   }

   /** Sign data using oauth's HMACSha1 algorithm.
    *
    * @param array $Data The data to sign.
    * @param string $Secret The secret used to hash with the data.
    * @param string $Exclude The name of a key to omit, usually the signature key.
    * @return string The signature of the data.
    */
   public static function SignHMACSha1($Data, $Secret, $Exclude = 'oauth_signature') {
      if (isset($Data[$Exclude]))
         unset($Data[$Exclude]);

      // Urlencode both keys and values.
      $Data = self::UrlEncodeRfc3986($Data);

      // Parameters are sorted by name, using lexicographical byte value ordering.
      // Ref: Spec: 9.1.1 (1)
      uksort($Data, 'strcmp');

      $Pairs = array();
      foreach ($Data as $Key => $Value) {
         if (is_array($Value)) {
            // If two or more parameters share the same name, they are sorted by their value
            // Ref: Spec: 9.1.1 (1)
            natsort($Value);
            foreach ($Value as $DupValue) {
               $Pairs[] = $Key.'='.$DupValue;
            }
         } else {
            $Pairs[] = $Key.$Value;
         }
      }
      $String = implode('&', $Pairs);

      $Result = hash_hmac('sha1', $String, $Secret, true);
      $Result = base64_encode($Result);
      return $Result;
   }

   /** Url encode the keys and values of an array.
    *
    * @param array $Data The data to encode.
    * @return array The encoded data.
    */
   public static function UrlEncodeRfc3986($Data) {
      if (is_array($Data)) {
         $Result = array();
         foreach ($Data as $Key => $Value) {
            $Result[self::UrlEncodeRfc3986($Key)] = self::UrlEncodeRfc3986($Value);
         }
         return $Result;
      } else if (is_scalar($Data)) {
         return str_replace(
            '+',
            ' ',
            str_replace('%7E', '~', rawurlencode($Data))
          );
      } else {
         return '';
      }
   }
}