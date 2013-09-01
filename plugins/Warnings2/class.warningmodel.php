<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class WarningModel extends UserNoteModel {
   /// Properties ///
   protected static $_Special;
   
   /// Methods ///
   
   protected function _Notify($Warning) {
      if (!is_array($Warning))
         $Warning = $this->GetID($Warning);
      
      if (!class_exists('ConversationModel')) {
         return FALSE;
      }
      
      // Send a message from the moderator to the person being warned.
      $Model = new ConversationModel();
      $MessageModel = new ConversationMessageModel();
      
      $Row = array(
         'Subject' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
         'Type' => 'warning',
         'Body' => $Warning['Body'],
         'Format' => $Warning['Format'],
         'RecipientUserID' => (array)$Warning['UserID']
         );
      
      $ConversationID = $Model->Save($Row, $MessageModel);
      
      if (!$ConversationID) {
         throw new Gdn_UserException($Model->Validation->ResultsText());
      }
      return $ConversationID;
   }
   
   public function ProcessAllWarnings() {
      $Alerts = $this->SQL->GetWhere('UserAlert', array('TimeExpires <', time()))->ResultArray();
      
      $Result = array();
      foreach ($Alerts as $Alert) {
         $UserID = $Alert['UserID'];
         $Processed = $this->ProcessWarnings($Alert);
         $Result[$UserID] = $Processed;
      }
      return $Result;
   }
   
   public function ProcessWarnings($UserID) {
      $AlertModel = new UserAlertModel();
      
      if (is_array($UserID)) {
         if (array_key_exists('WarningLevel', $UserID)) {
            $Alert = $UserID;
         }
         $UserID = $Alert['UserID'];
      }
      
      // Grab the user's current alert level.
      if (!isset($Alert))
         $Alert = $AlertModel->GetID($UserID);
      
      if (!$Alert)
         return;
      
      $Now = time();
      
      // See if the warnings have expired.
      if ($Alert['TimeWarningExpires'] < $Now) {
         $Alert['WarningLevel'] = 0;
         $Alert['TimeWarningExpires'] = NULL;
         
         $AlertModel->SetTimeExpires($Alert);
         $AlertModel->Save($Alert);
      }
      
      $WarningLevel = $Alert['WarningLevel'];
      
      // See if there's something special to do.
      $Punished = 0;
      if ($WarningLevel >= 3) {
         // The user is punished (jailed).
         $Punished = 1;
      }
      $Banned = 0;
      if ($WarningLevel >= 5) {
         // The user is banned.
         $Banned = 1;
      }
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
     
      $Set = array();
      if ($User['Banned'] != $Banned)
         $Set['Banned'] = $Banned;
      if ($User['Punished'] != $Punished)
         $Set['Punished'] = $Punished;
      
      if (!empty($Set)) {
         Gdn::UserModel()->SetField($UserID, $Set);
      }
      
      return array('WarnLevel' => $WarningLevel, 'Set' => $Set);
   }
   
   /**
    * Reverse a warning.
    * 
    * @param array|int $Warning The warning to reverse.
    * @return boolean Whether the warning was reversed.
    */
   public function Reverse($Warning) {
      if (!is_array($Warning)) {
         $Warning = $this->GetID($Warning);
      }
      
      if (!$Warning)
         throw NotFoundException('Warning');
      
      if (GetValue('Reversed', $Warning)) {
         $this->Validation->AddValidationResult('Reversed', 'The warning was already reversed.');
         return FALSE;
      }
      
      // First, reverse the warning.
      $this->SetField($Warning['UserNoteID'], 'Reversed', TRUE);
      
      // Reverse the amount of time on the warning and its points.
      $ExpiresTimespan = GetValue('ExpiresTimespan', $Warning, '0');
      $Points = GetValue('Points', $Warning, 0);
      
      $AlertModel = new UserAlertModel();
      $Alert = $AlertModel->GetID($Warning['UserID']);
      if ($Alert) {
         $NewWarningLevel = $Alert['WarningLevel'] - $Points;
         if ($NewWarningLevel < 0)
            $NewWarningLevel = 0;
         $Alert['WarningLevel'] = $NewWarningLevel;
         
         
         $NewTimeWarningExpires = $Alert['TimeWarningExpires'] - $ExpiresTimespan;
         if ($NewTimeWarningExpires <= time())
            $NewTimeWarningExpires = NULL;
         $Alert['TimeWarningExpires'] = $NewTimeWarningExpires;
         $AlertModel->SetTimeExpires($Alert);
         if (!$AlertModel->Save($Alert)) {
            $this->Validation->AddValidationResult($AlertModel->ValidationResults());
         } else {
            $this->ProcessWarnings($Alert);
         }
      }
      return TRUE;
   }
   
   /**
    * @param array $Data The warning data to save.
    *  - UserID: The user being warned.
    *  - Body: A private message to the user being warned.
    *  - Format: The format of the body.
    *  
    *  **The following**
    *  - Points: The number of warning points.
    *  - ExpiresString: A string used for the expiry. (ex. 1 week, 3 days, etc)
    *  - ExpiresTimespan: The number of seconds until expiry.
    * 
    *  **Or**
    *  - WarningTypeID: The type of warning given.
    * 
    * @return type
    */
   public function Save($Data) {
      $UserID = GetValue('UserID', $Data);
      
      // Coerce the data.
      $Data['Type'] = 'warning';
      if (isset($Data['WarningTypeID'])) {
         $WarningType = $this->SQL->GetWhere('WarningType', array('WarningTypeID' => $Data['WarningTypeID']))->FirstRow(DATASET_TYPE_ARRAY);
         if (!$WarningType) {
            $this->Validation->AddValidationResult('WarningTypeID', 'Invalid warning type');
         } else {
            TouchValue('Points', $Data, $WarningType['Points']);
            if ($WarningType['ExpireNumber'] > 0) {
               TouchValue('ExpiresString', $Data, Plural($WarningType['ExpireNumber'], '%s '.rtrim($WarningType['ExpireType'], 's'), '%s '.$WarningType['ExpireType']));
               $Seconds = strtotime($WarningType['ExpireNumber'].' '.$WarningType['ExpireType'], 0);
               TouchValue('ExpiresTimespan', $Data, $Seconds);
            }
         }
      }
      
      if (!isset($Data['Points']))
         $this->Validation->AddValidationResult('Points', 'ValidateRequired');
      elseif ($Data['Points']) {
         if (!ValidateRequired(GetValue('ExpiresString', $Data)) && !ValidateRequired(GetValue('ExpiresTimespan', $Data))) {
            $this->Validation->AddValidationResult('ExpiresString/ExpiresNumber', 'ValidateRequired');
         } elseif (!ValidateRequired(GetValue('ExpiresTimespan', $Data))) {
            // Calculate the seconds from the string.
            $Seconds = strtotime(GetValue('ExpiresString', $Data), 0);
            TouchValue('ExpiresTimespan', $Seconds);
         } elseif (!ValidateRequired(GetValue('ExpiresString', $Data))) {
            $Days = round($Data['ExpiresTimespan'] / strtotime('1 day', 0));
            TouchValue('ExpiresString', $Data, Plural($Days, '%s day', '%s days'));
         }
      }
      
      // First we save the warning.
      $ID = parent::Save($Data);
      if (!$ID)
         return FALSE;
      
      // Send the private message.
      $ConversationID = $this->_Notify($Data);
      if ($ConversationID) {
         // Save the conversation link back to the warning.
         $this->SetField($ID, array('RecordType' => 'Conversation', 'RecordID' => $ConversationID));
      }
      
      // Increment the user's alert level.
      $AlertModel = new UserAlertModel();
      $Alert = $AlertModel->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$Alert)
         $Alert = array('UserID' => $UserID);
      
      if ($Data['Points']) {
         $Alert['WarningLevel'] = GetValue('WarningLevel', $Alert, 0) + $Data['Points'];
         
         $Now = time();
         
         $Expires = GetValue('TimeWarningExpires', $Alert, 0);
         if ($Expires < $Now)
            $Expires = $Now;
         
         $Expires += $Data['ExpiresTimespan'];
         $Alert['TimeWarningExpires'] = $Expires;
         
         $AlertModel->SetTimeExpires($Alert);
      }
      
      if ($Alert)
         $AlertModel->Save($Alert);
      else {
         $Set['UserID'] = $Data['UserID'];
         $AlertModel->Insert($Set);
      }
      
      // Process this user's warnings.
      $this->ProcessWarnings($UserID);
      
      return $ID;
   }
   
   public static function Special() {
      if (self::$_Special === NULL) {
         self::$_Special = array(
            3 => array('Label' => T('Jail'), 'Title' => T('Jailed users have reduced abilities.')),
            5 => array('Label' => T('Ban'), 'Title' => T("Banned users can no longer access the site."))
         );
      }
      return self::$_Special;
   }
   
}