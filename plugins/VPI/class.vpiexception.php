<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VPIException extends Exception {
   protected $_Code = NULL;
   public function Code($Value = NULL) {
      if ($Value !== NULL)
         $this->_Code = $Value;
      return $this->_Code;
   }

   protected $_State = NULL;
   public function State($Value = NULL) {
      if ($Value !== NULL)
         $this->_State = $Value;
      return $this->_State;
   }

   protected $_Uri = NULL;
   public function Uri($Value = NULL) {
      if ($Value !== NULL)
         $this->_Uri = $Value;
      return $this->_Uri;
   }

   public function __construct($Message, $Code, $Previous = NULL) {
      $Number = self::ErrorNumber($Code);
      parent::__construct($Message, $Number, $Previous);

      $this->Code($Code);
   }

   public static function ErrorNumber($Code) {
      switch ($Code) {
         case 'invalid_request':
         case 'unsupported_grant_type':
         case 'unsupported_response_type':
            return 400;
         case 'access_denied':
         case 'expired_token':
         case 'invalid_client':
         case 'invalid_grant':
         case 'invalid_scope':
         case 'invalid_token':
         case 'redirect_uri_mismatch':
         case 'unautherized_client':
            return 401;
         case 'not_found':
            return 404;
         case 'error':
         default:
            return 500;
      }
   }

   public static function FromOAuth($OAuthError, $Previous = NULL) {
      $Ex = new VPIException(
         GetValue('error_description', $OAuthError),
         GetValue('error', $OAuthError),
         $Previous);

      $Ex->State(GetValue('state', $OAuthError, NULL));
      $Ex->State(GetValue('error_uri', $OAuthError, NULL));
   }

   /**
    * Set the data from this object to a given controller.
    * @param Gdn_Controller $Controller
    */
   public function SetData($Controller) {
      $Data = $this->ToOAuth();
      foreach ($Data as $Key => $Value) {
         $Controller->SetData($Key, $Value);
      }
   }

   public function SetHeader() {
      $Number = self::ErrorNumber($this->Code());

      switch ($Number) {
         case 400:
            header('HTTP/1.1 400 Bad Request', TRUE, 400);
            break;
         case 401:
            header('HTTP/1.1 401 Unauthorized', TRUE, 401);
            break;
         case 404:
            header('HTTP/1.1 404 Not Found', TRUE, 404);
            break;
         default:
            header('HTTP/1.1 500 Internal Server Error', TRUE, 500);
            break;
      }
   }

   public function ToOAuth() {
      $Result = array(
         'error' => $this->getCode(),
         'error_description' => $this->getMessage());
         
      if ($this->Uri())
         $Result['error_uri'] = $this->Uri();

      return $Result;
   }
}