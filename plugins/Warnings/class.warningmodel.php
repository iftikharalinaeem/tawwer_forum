<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class WarningModel extends Gdn_Model {
   /// Properties ///
   protected static $_Special;
   
   /// Methods ///
   
   public function __construct() {
      parent::__construct('Warning');
   }
   
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = 30, $Offset = FALSE) {
      $this->SQL
         ->Select('Warning.*')
         ->Select('Warning.DateExpires is null', '', 'NeverExpires');
      
      if (!$OrderFields) {
         $this->SQL
            ->OrderBy('Warning.Expired')
            ->OrderBy('Warning.DateExpires', 'desc')
            ->OrderBy('Warning.DateInserted', 'desc');
      }
      
      $Result = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      $Result->Unserialize();
      Gdn::UserModel()->JoinUsers($Result->ResultArray(), ['InsertUserID', 'WarnUserID']);
      
      return $Result;
   }
   
   protected function _Notify($Warning) {
      if (!is_array($Warning))
         $Warning = $this->GetID($Warning);
      
      if (!class_exists('ConversationModel')) {
         $this->_NotifyActivity($Warning);
      }
      
      // Send a message from the moderator to the person being warned.
      $Model = new ConversationModel();
      $MessageModel = new ConversationMessageModel();
      
      $Row = [
         'Subject' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
         'Body' => $Warning['Body'],
         'Format' => $Warning['Format'],
         'RecipientUserID' => (array)$Warning['WarnUserID']
         ];
      if (!$Model->Save($Row, $MessageModel)) {
         throw new Gdn_UserException($Model->Validation->ResultsText());
      }
   }
   
   protected function _NotifyActivity($Warning) {
      $ActivityModel = new ActivityModel();
      
      // Add a notification to the user.
      $Activity = [
          'ActivityType' => 'Warning',
          'Photo' => 'https://images.v-cdn.net/warn_50.png',
          'ActivityUserID' => Gdn::Session()->UserID,
          'NotifyUserID' => $Warning['WarnUserID'],
          'RegardingUserID' => $Warning['WarnUserID'],
          'HeadlineFormat' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
          'Story' => $Warning['Body'],
          'Format' => $Warning['Format'],
          'RecordType' => 'Warning',
          'RecordID' => $Warning['WarningID'],
          'Notified' => ActivityModel::SENT_PENDING,
          'Emailed' => ActivityModel::SENT_PENDING,
          'Route' => '/profile/warnings?warningid='.$Warning['WarningID'],
          'Data' => ['Bump' => TRUE, 'Points' => $Warning['Points']]
      ];
      $NewActivity = $ActivityModel->Save($Activity, FALSE, []);
      
      // Add an activity for the moderators.
      unset($Activity['Notified'], $Activity['Emailed']);
      
      $Activity['NotifyUserID'] = ActivityModel::NOTIFY_MODS;
      $Activity['HeadlineFormat'] = T('HeadlineFormat.Warning', '{ActivityUserID,You} warned {RegardingUserID,you}.');
      
      if ($Note = GetValue('ModeratorNote', $Warning)) {
         $Note = trim($Note);
         $Title = T('Private Note for Moderators');
         switch ($Warning['Format']) {
            case 'Html':
            case 'Markdown':
               $Activity['Story'] = "{$Warning['Body']}<div class=\"Hero ModeratorNote\"><div><b>$Title</b></div>$Note</div>";
               break;
            case 'BBCode':
               $Activity['Story'] = "{$Warning['Body']}\n\n[b]{$Title}[/b]$Note";
               break;
            default:
               $Activity['Story'] = "$Note\n\n$Title:\n{$Warning['Body']}";
         }
      }
      $Activity['Data']['CommentActivityID'] = $NewActivity['ActivityID'];
      
      $ModActivity = $ActivityModel->Save($Activity);
   }
   
   public function ProcessAllWarnings() {
      $Users = $this->SQL
         ->Distinct()
         ->Select('WarnUserID')
         ->From('Warning')
         ->Where('Expired', 0)
         ->Where('DateExpires <=', Gdn_Format::ToDateTime())
         ->Get()->ResultArray();
      
      $Result = [];
      foreach ($Users as $Row) {
         $UserID = $Row['WarnUserID'];
         $Processed = $this->ProcessWarnings($UserID);
         $Result[$UserID] = $Processed;
      }
      return $Result;
   }
   
   public function ProcessWarnings($UserID) {
      // Get all of the un-expired warnings.
      $Warnings = $this->SQL->GetWhere('Warning', ['WarnUserID' => $UserID, 'Expired' => 0])->ResultArray();
      $WarnLevel = 0;
      $Banned = FALSE;
      $Punished = FALSE;
      $Now = time();
      
      foreach ($Warnings as $Row) {
         if ($Row['Expired'])
            continue;
         
         $Set = [];
         
         if ($DateExpires = $Row['DateExpires']) {
            // Check to see if the warning has expired.
            if (Gdn_Format::ToTimestamp($DateExpires) <= $Now) {
               $Set['Expired'] = TRUE;
            } else {
               $WarnLevel += $Row['Points'];
               
               switch ($Row['Type']) {
                  case 'Punish':
                     $Punished = 1;
                     break;
                  case 'Ban':
                     $Banned = 1;
                     break;
               }
            }
         } else {
            $WarnLevel += $Row['Points'];
         }
         
         if (!empty($Set)) {
            $this->SetField($Row['WarningID'], $Set);
         }
      }
      
      // Save the user's current warning level.
      Gdn::UserMetaModel()->SetUserMeta($UserID, 'Warnings.Level', $WarnLevel);
      
      // See if there's something special to do.
      if ($WarnLevel >= 3) {
         // The user is punished (jailed).
         $Punished = 1;
      }
      if ($WarnLevel >= 5) {
         // The user is banned.
         $Banned = 1;
      }
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
     
      $Set = [];
      if ($User['Banned'] != $Banned)
         $Set['Banned'] = $Banned;
      if ($User['Punished'] != $Punished)
         $Set['Punished'] = $Punished;
      
      if (!empty($Set)) {
         Gdn::UserModel()->SetField($UserID, $Set);
         Gdn::UserModel()->ClearCache($UserID);
      }
      
      return ['WarnLevel' => $WarnLevel, 'Set' => $Set, 'ActiveWarnings' => $Warnings];
   }
   
   public function Save($Data) {
      $UserID = GetValue('WarnUserID', $Data);
      
      $Meta = Gdn::UserMetaModel()->GetUserMeta($UserID, 'Warnings.%');
      $CurrentLevel = GetValue('Warnings.Level', $Meta, 0);
      
      TouchValue('Format', $Data, C('Garden.InputFormatter'));
      TouchValue('Type', $Data, 'Warning');
      TouchValue('Expired', $Data, 0);
      TouchValue('Level', $Data, $CurrentLevel);
      $Attributes = TouchValue('Attributes', $Data, []);
      
      // Calculate some fields.
      if (!isset($Data['Points'])) {
         $Data['Points'] = $Data['Level'] - $CurrentLevel;
         $NewLevel = $Data['Level'];
      } else {
         $NewLevel = $CurrentLevel + GetValue('Points', $Data);
      }
//      decho($NewLevel, 'New Level');
      
      if (($ExpireNumber = GetValue('ExpireNumber', $Data)) && ($ExpireUnit = GetValue('ExpireUnit', $Data))) {
         // Calculate the date the warning expires.
         $Attributes['ExpireNumber'] = $ExpireNumber;
         $Attributes['ExpireUnit'] = $ExpireUnit;
         
         if ($ExpireNumber == 'never') {
            unset($Attributes['ExpireUnit']);
         } else {
            $TimestampExpires = strtotime("+$ExpireNumber $ExpireUnit");
            $Data['DateExpires'] = Gdn_Format::ToDateTime($TimestampExpires);
         }
      }
      
//      $Data['Attributes'] = dbencode($Attributes);
      $Insert = GetValue('WarningID', $Data) == FALSE;
      $ID = parent::Save($Data);
      
      if ($ID) {
         Trace('Warning Saved');
         
         $Warning = $Data;
         TouchValue('WarningID', $Warning, $ID);
         
         if ($Insert) {
            // Notifiy the user.
            $this->_Notify($Warning);
            
            if ($Warning['Points'] > 0 && class_exists('UserBadgeModel')) {
               // Take a few points away from the user.
               $UserBadgeModel = new UserBadgeModel();
               $UserBadgeModel->GivePoints($UserID, -$Warning['Points'], 'Warnings');
            }
         }
         
         // Process this user's warnings.
         $this->ProcessWarnings($UserID);
      }
      
      return $ID;
   }
   
   public static function Special() {
      if (self::$_Special === NULL) {
         self::$_Special = [
            3 => ['Label' => T('Jail'), 'Title' => T('Jailed users have reduced abilities.')],
            5 => ['Label' => T('Ban'), 'Title' => T("Banned users can no longer access the site.")]
         ];
      }
      return self::$_Special;
   }
   
}