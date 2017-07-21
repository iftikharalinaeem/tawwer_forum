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
   
   public function GetWhere($where = FALSE, $orderFields = '', $orderDirection = 'asc', $limit = 30, $offset = FALSE) {
      $this->SQL
         ->Select('Warning.*')
         ->Select('Warning.DateExpires is null', '', 'NeverExpires');
      
      if (!$orderFields) {
         $this->SQL
            ->OrderBy('Warning.Expired')
            ->OrderBy('Warning.DateExpires', 'desc')
            ->OrderBy('Warning.DateInserted', 'desc');
      }
      
      $result = parent::GetWhere($where, $orderFields, $orderDirection, $limit, $offset);
      $result->Unserialize();
      Gdn::UserModel()->JoinUsers($result->ResultArray(), ['InsertUserID', 'WarnUserID']);
      
      return $result;
   }
   
   protected function _Notify($warning) {
      if (!is_array($warning))
         $warning = $this->GetID($warning);
      
      if (!class_exists('ConversationModel')) {
         $this->_NotifyActivity($warning);
      }
      
      // Send a message from the moderator to the person being warned.
      $model = new ConversationModel();
      $messageModel = new ConversationMessageModel();
      
      $row = [
         'Subject' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
         'Body' => $warning['Body'],
         'Format' => $warning['Format'],
         'RecipientUserID' => (array)$warning['WarnUserID']
         ];
      if (!$model->Save($row, $messageModel)) {
         throw new Gdn_UserException($model->Validation->ResultsText());
      }
   }
   
   protected function _NotifyActivity($warning) {
      $activityModel = new ActivityModel();
      
      // Add a notification to the user.
      $activity = [
          'ActivityType' => 'Warning',
          'Photo' => 'https://images.v-cdn.net/warn_50.png',
          'ActivityUserID' => Gdn::Session()->UserID,
          'NotifyUserID' => $warning['WarnUserID'],
          'RegardingUserID' => $warning['WarnUserID'],
          'HeadlineFormat' => T('HeadlineFormat.Warning.ToUser', "You've been warned."),
          'Story' => $warning['Body'],
          'Format' => $warning['Format'],
          'RecordType' => 'Warning',
          'RecordID' => $warning['WarningID'],
          'Notified' => ActivityModel::SENT_PENDING,
          'Emailed' => ActivityModel::SENT_PENDING,
          'Route' => '/profile/warnings?warningid='.$warning['WarningID'],
          'Data' => ['Bump' => TRUE, 'Points' => $warning['Points']]
      ];
      $newActivity = $activityModel->Save($activity, FALSE, []);
      
      // Add an activity for the moderators.
      unset($activity['Notified'], $activity['Emailed']);
      
      $activity['NotifyUserID'] = ActivityModel::NOTIFY_MODS;
      $activity['HeadlineFormat'] = T('HeadlineFormat.Warning', '{ActivityUserID,You} warned {RegardingUserID,you}.');
      
      if ($note = GetValue('ModeratorNote', $warning)) {
         $note = trim($note);
         $title = T('Private Note for Moderators');
         switch ($warning['Format']) {
            case 'Html':
            case 'Markdown':
               $activity['Story'] = "{$warning['Body']}<div class=\"Hero ModeratorNote\"><div><b>$title</b></div>$note</div>";
               break;
            case 'BBCode':
               $activity['Story'] = "{$warning['Body']}\n\n[b]{$title}[/b]$note";
               break;
            default:
               $activity['Story'] = "$note\n\n$title:\n{$warning['Body']}";
         }
      }
      $activity['Data']['CommentActivityID'] = $newActivity['ActivityID'];
      
      $modActivity = $activityModel->Save($activity);
   }
   
   public function ProcessAllWarnings() {
      $users = $this->SQL
         ->Distinct()
         ->Select('WarnUserID')
         ->From('Warning')
         ->Where('Expired', 0)
         ->Where('DateExpires <=', Gdn_Format::ToDateTime())
         ->Get()->ResultArray();
      
      $result = [];
      foreach ($users as $row) {
         $userID = $row['WarnUserID'];
         $processed = $this->ProcessWarnings($userID);
         $result[$userID] = $processed;
      }
      return $result;
   }
   
   public function ProcessWarnings($userID) {
      // Get all of the un-expired warnings.
      $warnings = $this->SQL->GetWhere('Warning', ['WarnUserID' => $userID, 'Expired' => 0])->ResultArray();
      $warnLevel = 0;
      $banned = FALSE;
      $punished = FALSE;
      $now = time();
      
      foreach ($warnings as $row) {
         if ($row['Expired'])
            continue;
         
         $set = [];
         
         if ($dateExpires = $row['DateExpires']) {
            // Check to see if the warning has expired.
            if (Gdn_Format::ToTimestamp($dateExpires) <= $now) {
               $set['Expired'] = TRUE;
            } else {
               $warnLevel += $row['Points'];
               
               switch ($row['Type']) {
                  case 'Punish':
                     $punished = 1;
                     break;
                  case 'Ban':
                     $banned = 1;
                     break;
               }
            }
         } else {
            $warnLevel += $row['Points'];
         }
         
         if (!empty($set)) {
            $this->SetField($row['WarningID'], $set);
         }
      }
      
      // Save the user's current warning level.
      Gdn::UserMetaModel()->SetUserMeta($userID, 'Warnings.Level', $warnLevel);
      
      // See if there's something special to do.
      if ($warnLevel >= 3) {
         // The user is punished (jailed).
         $punished = 1;
      }
      if ($warnLevel >= 5) {
         // The user is banned.
         $banned = 1;
      }
      
      $user = Gdn::UserModel()->GetID($userID, DATASET_TYPE_ARRAY);
     
      $set = [];
      if ($user['Banned'] != $banned)
         $set['Banned'] = $banned;
      if ($user['Punished'] != $punished)
         $set['Punished'] = $punished;
      
      if (!empty($set)) {
         Gdn::UserModel()->SetField($userID, $set);
         Gdn::UserModel()->ClearCache($userID);
      }
      
      return ['WarnLevel' => $warnLevel, 'Set' => $set, 'ActiveWarnings' => $warnings];
   }
   
   public function Save($data) {
      $userID = GetValue('WarnUserID', $data);
      
      $meta = Gdn::UserMetaModel()->GetUserMeta($userID, 'Warnings.%');
      $currentLevel = GetValue('Warnings.Level', $meta, 0);
      
      TouchValue('Format', $data, C('Garden.InputFormatter'));
      TouchValue('Type', $data, 'Warning');
      TouchValue('Expired', $data, 0);
      TouchValue('Level', $data, $currentLevel);
      $attributes = TouchValue('Attributes', $data, []);
      
      // Calculate some fields.
      if (!isset($data['Points'])) {
         $data['Points'] = $data['Level'] - $currentLevel;
         $newLevel = $data['Level'];
      } else {
         $newLevel = $currentLevel + GetValue('Points', $data);
      }
//      decho($NewLevel, 'New Level');
      
      if (($expireNumber = GetValue('ExpireNumber', $data)) && ($expireUnit = GetValue('ExpireUnit', $data))) {
         // Calculate the date the warning expires.
         $attributes['ExpireNumber'] = $expireNumber;
         $attributes['ExpireUnit'] = $expireUnit;
         
         if ($expireNumber == 'never') {
            unset($attributes['ExpireUnit']);
         } else {
            $timestampExpires = strtotime("+$expireNumber $expireUnit");
            $data['DateExpires'] = Gdn_Format::ToDateTime($timestampExpires);
         }
      }
      
//      $Data['Attributes'] = dbencode($Attributes);
      $insert = GetValue('WarningID', $data) == FALSE;
      $iD = parent::Save($data);
      
      if ($iD) {
         Trace('Warning Saved');
         
         $warning = $data;
         TouchValue('WarningID', $warning, $iD);
         
         if ($insert) {
            // Notifiy the user.
            $this->_Notify($warning);
            
            if ($warning['Points'] > 0 && class_exists('UserBadgeModel')) {
               // Take a few points away from the user.
               $userBadgeModel = new UserBadgeModel();
               $userBadgeModel->GivePoints($userID, -$warning['Points'], 'Warnings');
            }
         }
         
         // Process this user's warnings.
         $this->ProcessWarnings($userID);
      }
      
      return $iD;
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