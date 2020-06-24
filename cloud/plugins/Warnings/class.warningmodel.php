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
   
   public function getWhere($where = FALSE, $orderFields = '', $orderDirection = 'asc', $limit = 30, $offset = FALSE) {
      $this->SQL
         ->select('Warning.*')
         ->select('Warning.DateExpires is null', '', 'NeverExpires');
      
      if (!$orderFields) {
         $this->SQL
            ->orderBy('Warning.Expired')
            ->orderBy('Warning.DateExpires', 'desc')
            ->orderBy('Warning.DateInserted', 'desc');
      }
      
      $result = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
      $result->unserialize();
      Gdn::userModel()->joinUsers($result->resultArray(), ['InsertUserID', 'WarnUserID']);
      
      return $result;
   }
   
   protected function _Notify($warning) {
      if (!is_array($warning))
         $warning = $this->getID($warning);
      
      if (!class_exists('ConversationModel')) {
         $this->_NotifyActivity($warning);
      }
      
      // Send a message from the moderator to the person being warned.
      $model = new ConversationModel();
      $messageModel = new ConversationMessageModel();
      
      $row = [
         'Subject' => t('HeadlineFormat.Warning.ToUser', "You've been warned."),
         'Body' => $warning['Body'],
         'Format' => $warning['Format'],
         'RecipientUserID' => (array)$warning['WarnUserID']
         ];
      if (!$model->save($row, $messageModel)) {
         throw new Gdn_UserException($model->Validation->resultsText());
      }
   }
   
   protected function _NotifyActivity($warning) {
      $activityModel = new ActivityModel();
      
      // Add a notification to the user.
      $activity = [
          'ActivityType' => 'Warning',
          'Photo' => 'https://images.v-cdn.net/warn_50.png',
          'ActivityUserID' => Gdn::session()->UserID,
          'NotifyUserID' => $warning['WarnUserID'],
          'RegardingUserID' => $warning['WarnUserID'],
          'HeadlineFormat' => t('HeadlineFormat.Warning.ToUser', "You've been warned."),
          'Story' => $warning['Body'],
          'Format' => $warning['Format'],
          'RecordType' => 'Warning',
          'RecordID' => $warning['WarningID'],
          'Notified' => ActivityModel::SENT_PENDING,
          'Emailed' => ActivityModel::SENT_PENDING,
          'Route' => '/profile/warnings?warningid='.$warning['WarningID'],
          'Data' => ['Bump' => TRUE, 'Points' => $warning['Points']]
      ];
      $newActivity = $activityModel->save($activity, FALSE, []);
      
      // Add an activity for the moderators.
      unset($activity['Notified'], $activity['Emailed']);
      
      $activity['NotifyUserID'] = ActivityModel::NOTIFY_MODS;
      $activity['HeadlineFormat'] = t('HeadlineFormat.Warning', '{ActivityUserID,You} warned {RegardingUserID,you}.');
      
      if ($note = getValue('ModeratorNote', $warning)) {
         $note = trim($note);
         $title = t('Private Note for Moderators');
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
      
      $modActivity = $activityModel->save($activity);
   }
   
   public function processAllWarnings() {
      $users = $this->SQL
         ->distinct()
         ->select('WarnUserID')
         ->from('Warning')
         ->where('Expired', 0)
         ->where('DateExpires <=', Gdn_Format::toDateTime())
         ->get()->resultArray();
      
      $result = [];
      foreach ($users as $row) {
         $userID = $row['WarnUserID'];
         $processed = $this->processWarnings($userID);
         $result[$userID] = $processed;
      }
      return $result;
   }
   
   public function processWarnings($userID) {
      // Get all of the un-expired warnings.
      $warnings = $this->SQL->getWhere('Warning', ['WarnUserID' => $userID, 'Expired' => 0])->resultArray();
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
            if (Gdn_Format::toTimestamp($dateExpires) <= $now) {
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
            $this->setField($row['WarningID'], $set);
         }
      }
      
      // Save the user's current warning level.
      Gdn::userMetaModel()->setUserMeta($userID, 'Warnings.Level', $warnLevel);
      
      // See if there's something special to do.
      if ($warnLevel >= 3) {
         // The user is punished (jailed).
         $punished = 1;
      }
      if ($warnLevel >= 5) {
         // The user is banned.
         $banned = 1;
      }
      
      $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
     
      $set = [];
      if ($user['Banned'] != $banned)
         $set['Banned'] = $banned;
      if ($user['Punished'] != $punished)
         $set['Punished'] = $punished;
      
      if (!empty($set)) {
         Gdn::userModel()->setField($userID, $set);
         Gdn::userModel()->clearCache($userID);
      }
      
      return ['WarnLevel' => $warnLevel, 'Set' => $set, 'ActiveWarnings' => $warnings];
   }
   
   public function save($data) {
      $userID = getValue('WarnUserID', $data);
      
      $meta = Gdn::userMetaModel()->getUserMeta($userID, 'Warnings.%');
      $currentLevel = getValue('Warnings.Level', $meta, 0);
      
      touchValue('Format', $data, c('Garden.InputFormatter'));
      touchValue('Type', $data, 'Warning');
      touchValue('Expired', $data, 0);
      touchValue('Level', $data, $currentLevel);
      $attributes = touchValue('Attributes', $data, []);
      
      // Calculate some fields.
      if (!isset($data['Points'])) {
         $data['Points'] = $data['Level'] - $currentLevel;
         $newLevel = $data['Level'];
      } else {
         $newLevel = $currentLevel + getValue('Points', $data);
      }
//      decho($NewLevel, 'New Level');
      
      if (($expireNumber = getValue('ExpireNumber', $data)) && ($expireUnit = getValue('ExpireUnit', $data))) {
         // Calculate the date the warning expires.
         $attributes['ExpireNumber'] = $expireNumber;
         $attributes['ExpireUnit'] = $expireUnit;
         
         if ($expireNumber == 'never') {
            unset($attributes['ExpireUnit']);
         } else {
            $timestampExpires = strtotime("+$expireNumber $expireUnit");
            $data['DateExpires'] = Gdn_Format::toDateTime($timestampExpires);
         }
      }
      
//      $Data['Attributes'] = dbencode($Attributes);
      $insert = getValue('WarningID', $data) == FALSE;
      $iD = parent::save($data);
      
      if ($iD) {
         trace('Warning Saved');
         
         $warning = $data;
         touchValue('WarningID', $warning, $iD);
         
         if ($insert) {
            // Notifiy the user.
            $this->_Notify($warning);
            
            if ($warning['Points'] > 0 && class_exists('UserBadgeModel')) {
               // Take a few points away from the user.
               $userBadgeModel = new UserBadgeModel();
               $userBadgeModel->givePoints($userID, -$warning['Points'], 'Warnings');
            }
         }
         
         // Process this user's warnings.
         $this->processWarnings($userID);
      }
      
      return $iD;
   }
   
   public static function special() {
      if (self::$_Special === NULL) {
         self::$_Special = [
            3 => ['Label' => t('Jail'), 'Title' => t('Jailed users have reduced abilities.')],
            5 => ['Label' => t('Ban'), 'Title' => t("Banned users can no longer access the site.")]
         ];
      }
      return self::$_Special;
   }
   
}