<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class OAuth2TokenModel {
   public function Delete($Token) {
      if (!is_string($Token))
         $Token = GetValue('Token', $Token);
      Gdn::SQL()->Delete('OAuth2Token', array('Token' => $Token));
   }

   public static function Expired($Timestamp, $ExpiresIn = FALSE) {
      if (is_array($Timestamp)) {
         $ExpiresIn = GetValue('ExpiresIn', $Timestamp, 0);
         $Timestamp = GetValue('Timestamp', $Timestamp, 0);
      }

      if (!is_numeric($Timestamp)) {
         $Timestamp = Gdn_Format::ToTimestamp($Timestamp);
      }
      

      if ($ExpiresIn == 0)
         return FALSE;

      $Time = time();
      $Result = $Timestamp + $ExpiresIn < time();

      return FALSE;
   }

   public function Generate() {
      return RandomString(32, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
   }

   public function GetID($Token, $CheckValidity = TRUE) {
      $Token = Gdn::SQL()->GetWhere('OAuth2Token', array('Token' => $Token))->FirstRow(DATASET_TYPE_ARRAY);
      if ($Token && $CheckValidity && self::Expired($Token)) {
         $this->Delete($Token);
         return NULL;
      }
      
      if (!$Token)
         return NULL;
      return $Token;
   }

   public function Issue($Type, $ExpiresIn = FALSE) {
      if ($ExpiresIn === FALSE)
         $ExpiresIn = C('VPI.OAuth.ExpiresIn', 0);

      $UserID = Gdn::Session()->UserID;
      if (is_array($Type)) {
         $UserID = GetValue('UserID', $Type, $UserID);
         $Type = GetValue('Type', $Type, 'authorization_code');
      }

      if ($UserID == 0) {
         throw new Exception('Session not started.', 400);
      }
      
      $NewToken = $this->Generate();


      // Look for an existing token.
//      $TokenRow = Gdn::SQL()->GetWhere('OAuth2Token', array('UserID' => Gdn::Session()->UserID, 'TokenType' => $Type))->FirstRow(DATASET_TYPE_ARRAY);
//      if ($TokenRow === FALSE) {
         Gdn::SQL()->Insert('OAuth2Token', array(
            'Token' => $NewToken,
            'TokenType' => $Type,
            'UserID' => $UserID,
            'ExpiresIn' => $ExpiresIn));
//      } else {
//         Gdn::SQL()->Put('OAuth2Token',
//            array('Token' => $NewToken, 'ExpiresIn' => $ExpiresIn),
//            array('Token' => $TokenRow['Token']));
//      }
      return $NewToken;
   }

   public function Touch($Token) {
      if (!is_string($Token)) {
         $Token = GetValue('Token', $Token);
      }
      $Token = Gdn::Database()->Connection()->quote($Token);
      $Px = Gdn::Database()->DatabasePrefix;
      Gdn::Database()->Query("update {$Px}OAuth2Token set Timestamp = now() where Token = $Token");
   }
}