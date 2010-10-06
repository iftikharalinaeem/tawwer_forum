<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VpiController extends Gdn_Controller {
   /**
    * @var string The base url of the api.
    */
   public $Url = 'http://vanilla.local';


   public function __construct() {
      parent::__construct();
      $this->DeliveryType(DELIVERY_TYPE_DATA);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
   }

   protected $_ApplicationModel = NULL;
   /** Gets the application model.
    * @return ApplicationModel
    */
   public function ApplicationModel() {
      if ($this->_ApplicationModel === NULL)
         $this->_ApplicationModel = new ApplicationModel();
      return $this->_ApplicationModel;
   }

   /**
    * Check to make sure an array contains the required keys.
    *
    * @param array $Keys An array of key names to check.
    * @param array|null $Array The array to check against. If missing this will check against $_GET.
    * @param bool $Throw Whether or not to throw an exception if the keys don't exist. Default FALSE.
    * @return VPIException|TRUE If none of the keys are missing then TRUE is returned. Otherwise the exception is returned if $Throw is FALSE.
    */
   protected function _CheckRequiredKeys($Keys, $Array = NULL, $Throw = FALSE) {
      if ($Array == NULL)
         $Array = $_GET;

      $Missing = array();

      foreach ($Keys as $Key) {
         if (!array_key_exists($Key, $Array))
            $Missing[] = $Key;
      }

      if (count($Missing) > 0) {
         // The array is missing some keys, return the exception.
         $Message = sprintf(T('The request is missing the following keys: %s.'), implode(', ', $Missing));
         $Ex = new VPIException($Message, 'invalid_request');
         $Ex->State = GetValue('state', $Array, NULL);
         if ($Throw)
            throw $Ex;
         return $Ex;
      }
      return TRUE;
   }

   /**
    * Get the given user's profile information.
    *
    * @param int|NULL $UserID The id of the user to grab.
    */
   public function Profile($UserID = NULL) {
      try {
         $this->StartSession(TRUE);

         // todo: check permissions.

         $UserModel = new UserModel();
         $User = $UserModel->GetID(Gdn::Session()->UserID, DATASET_TYPE_ARRAY);

         if ($User == FALSE)
            throw new VPIException(sprintf(T('User %s not found.'), Gdn::Session()->UserID), 'not_found');

         $Profile = array_intersect_key($User, array('Name' => 1, 'Email' => 1, 'Gender' => 1));
         $Profile['UniqueID'] = $User['UserID'];

         $this->SetData($Profile);
         $this->Render();

      } catch (VPIException $Ex) {
         $Ex->SetHeader();
         return $this->RenderData($Ex->ToOAuth());
      }
   }
   
   public function Status() {
      $Session = Gdn::Session();

      if ($Session->UserID > 0) {
         $this->SetData('Status', 'signedin');
         $Status = 'signedin';

         $Session = array(
            'ID' => $Session->UserID,
            'Username' => $Session->User->Name);
         $this->SetData('Session', $Session);
      } else {
         $this->SetData('Status', 'signedout');
      }

      // Sign the data.
      $Secret = $this->Application('Secret');
      $this->SetData('Sig', VPIUtil::SignHMACSha1($this->Data, $Secret, 'Sig'));

      $this->DeliveryType(DELIVERY_TYPE_DATA);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      
      return $this->RenderData($this->Data);
   }

   public function StartSession($ThrowException = FALSE) {
      if (!isset($_GET['oauth_token']) && is_object(Gdn::Session()->User)) {
         // There is already a user signed in so no extra auth is necessary.
         return TRUE;
      }

      $Ex = $this->_CheckRequiredKeys(array('oauth_token'));
      if (is_a($Ex, 'VPIException')) {
         if ($ThrowException)
            throw $Ex;
         return FALSE;
      }

      $AccessToken = $_GET['oauth_token'];
      // Check the user ID in the db.
      $TokenModel = new OAuth2TokenModel();
      $TokenRow = $TokenModel->GetID($AccessToken);

      // Validate the token.
      if ($TokenRow == FALSE || $TokenRow['TokenType'] != 'access_token') {
         if ($ThrowException)
            throw new VPIException('Invalid oauth_token.', 'invalid_token');
         return FALSE;
      }
      if ($TokenModel->Expired($TokenRow)) {
         if ($ThrowException)
            throw new VPIException('Invalid oauth_token.', 'expired_token');
         return FALSE;
      }

      // The token is valid, so start the session.
      Gdn::Session()->Start($TokenRow['UserID']);
      return TRUE;
   }
}