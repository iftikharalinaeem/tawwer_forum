<?php if (!defined('APPLICATION')) exit();

class ModNotifyPlugin extends Gdn_Plugin {   
   /**
    * Let users with permission choose to receive notifications.
    */
   public function ProfileController_AfterPreferencesDefined_Handler($sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $sender->Preferences['Notifications']['Email.ModQueue'] = T('Notify me when an item is queued for moderation.');
         $sender->Preferences['Notifications']['Popup.ModQueue'] = T('Notify me when an item is queued for moderation.');
         
         // Set preferences in UserMeta
         if ($sender->Form->AuthenticatedPostBack()) {
            $set = [];
            $set['Email.ModQueue'] = ($sender->Form->GetFormValue('Email.ModQueue', NULL)) ? 1 : NULL;
            $set['Popup.ModQueue'] = ($sender->Form->GetFormValue('Popup.ModQueue', NULL)) ? 1 : NULL;
            UserModel::SetMeta($sender->User->UserID, $set, 'Preferences.');
         }
      }
   }
   
   /**
    * Detect additions to moderation queue & notify.
    */
   public function LogModel_AfterInsert_Handler($sender, $args) {
      $log = $args['Log'];
      $logID = $args['LogID'];
      
      // Only deal with discussion & comment additions to moderation queue
      if ($log['Operation'] != 'Moderate' && $log['Operation'] != 'Pending')
         return;
      if ($log['RecordType'] != 'Discussion' && $log['RecordType'] != 'Comment')
         return;
      
      // Grab all of the users that need to be notified.
      $data = Gdn::Database()->SQL()
         ->WhereIn('Name', ['Preferences.Email.ModQueue', 'Preferences.Popup.ModQueue'])
         ->Get('UserMeta')->ResultArray();
      
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
            $headlineFormat = T('HeadlineFormat.ModQueuePending', 
               'A new {Data.RecordType,text} by {Data.RecordUserID, user} is awaiting approval in the <a href="{Url,html}">Moderation Queue</a>');
            break;
         case 'Moderate':
            $headlineFormat = T('HeadlineFormat.ModQueueModerate', 
               'A {Data.RecordType,text} by {Data.RecordUserID, user} has been moved to the <a href="{Url,html}">Moderation Queue</a>');
            break;
      }
      $activity = [
         'ActivityType' => 'ModQueue',
         //'ActivityUserID' => $Fields['InsertUserID'],
         'HeadlineFormat' => $headlineFormat,
         'RecordType' => 'Log',
         'RecordID' => $logID,
         'Route' => Url('/log/moderation'),
         'Data' => [
            'Operation' => $log['Operation'],
            'RecordType' => strtolower($log['RecordType']),
            'RecordUserID' => $log['RecordUserID']
         ]
      ];
      
       // Queue the notifications
      foreach ($notifyUsers as $userID => $prefs) {         
         $activity['NotifyUserID'] = $userID;
         $activity['Emailed'] = GetValue('Emailed', $prefs, FALSE);
         $activity['Notified'] = GetValue('Notified', $prefs, FALSE);
         $activityModel->Queue($activity);
      }
      
      // Send all notifications.
      $activityModel->SaveQueue();
   }

   public function Setup() {
      // Create activity type
      $sQL = Gdn::Database()->SQL();
      if ($sQL->GetWhere('ActivityType', ['Name' => 'ModQueue'])->NumRows() == 0)
         $sQL->Insert('ActivityType', ['AllowComments' => 0, 'Name' => 'ModQueue', 'Public' => 0]);
   }
   
}
