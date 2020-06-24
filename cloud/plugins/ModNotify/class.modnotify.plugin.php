<?php if (!defined('APPLICATION')) exit();

class ModNotifyPlugin extends Gdn_Plugin {   
   /**
    * Let users with permission choose to receive notifications.
    */
   public function profileController_afterPreferencesDefined_handler($sender) {
      if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
         $sender->Preferences['Notifications']['Email.ModQueue'] = t('Notify me when an item is queued for moderation.');
         $sender->Preferences['Notifications']['Popup.ModQueue'] = t('Notify me when an item is queued for moderation.');
         
         // Set preferences in UserMeta
         if ($sender->Form->authenticatedPostBack()) {
            $set = [];
            $set['Email.ModQueue'] = ($sender->Form->getFormValue('Email.ModQueue', NULL)) ? 1 : NULL;
            $set['Popup.ModQueue'] = ($sender->Form->getFormValue('Popup.ModQueue', NULL)) ? 1 : NULL;
            UserModel::setMeta($sender->User->UserID, $set, 'Preferences.');
         }
      }
   }
   
   /**
    * Detect additions to moderation queue & notify.
    */
   public function logModel_afterInsert_handler($sender, $args) {
      $log = $args['Log'];
      $logID = $args['LogID'];
      
      // Only deal with discussion & comment additions to moderation queue
      if ($log['Operation'] != 'Moderate' && $log['Operation'] != 'Pending')
         return;
      if ($log['RecordType'] != 'Discussion' && $log['RecordType'] != 'Comment')
         return;
      
      // Grab all of the users that need to be notified.
      $data = Gdn::database()->sql()
         ->whereIn('Name', ['Preferences.Email.ModQueue', 'Preferences.Popup.ModQueue'])
         ->get('UserMeta')->resultArray();
      
      // Prep notification list
      $notifyUsers = [];
      foreach ($data as $row) {
         if (!$row['Value'])
            continue;
         
         $userID = $row['UserID'];
         $name = $row['Name'];
         if (strpos($name, '.Email.') !== FALSE) {
            $notifyUsers[$userID]['Emailed'] = ActivityModel::SENT_PENDING;
         } elseif (strpos($name, '.Popup.') !== FALSE) {
            $notifyUsers[$userID]['Notified'] = ActivityModel::SENT_PENDING;
         }
      }
            
      // Prep the activity
      $activityModel = new ActivityModel();
      switch ($log['Operation']) {
         case 'Pending':
            $headlineFormat = t('HeadlineFormat.ModQueuePending', 
               'A new {Data.RecordType,text} by {Data.RecordUserID, user} is awaiting approval in the <a href="{Url,html}">Moderation Queue</a>');
            break;
         case 'Moderate':
            $headlineFormat = t('HeadlineFormat.ModQueueModerate', 
               'A {Data.RecordType,text} by {Data.RecordUserID, user} has been moved to the <a href="{Url,html}">Moderation Queue</a>');
            break;
      }
      $activity = [
         'ActivityType' => 'ModQueue',
         //'ActivityUserID' => $Fields['InsertUserID'],
         'HeadlineFormat' => $headlineFormat,
         'RecordType' => 'Log',
         'RecordID' => $logID,
         'Route' => url('/log/moderation'),
         'Data' => [
            'Operation' => $log['Operation'],
            'RecordType' => strtolower($log['RecordType']),
            'RecordUserID' => $log['RecordUserID']
         ]
      ];
      
       // Queue the notifications
      foreach ($notifyUsers as $userID => $prefs) {         
         $activity['NotifyUserID'] = $userID;
         $activity['Emailed'] = getValue('Emailed', $prefs, FALSE);
         $activity['Notified'] = getValue('Notified', $prefs, FALSE);
         $activityModel->queue($activity);
      }
      
      // Send all notifications.
      $activityModel->saveQueue();
   }

   public function setup() {
      // Create activity type
      $sQL = Gdn::database()->sql();
      if ($sQL->getWhere('ActivityType', ['Name' => 'ModQueue'])->numRows() == 0)
         $sQL->insert('ActivityType', ['AllowComments' => 0, 'Name' => 'ModQueue', 'Public' => 0]);
   }
   
}
