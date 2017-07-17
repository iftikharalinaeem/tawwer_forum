<?php if (!defined('APPLICATION')) exit();

class ModNotifyPlugin extends Gdn_Plugin {   
   /**
    * Let users with permission choose to receive notifications.
    */
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $Sender->Preferences['Notifications']['Email.ModQueue'] = T('Notify me when an item is queued for moderation.');
         $Sender->Preferences['Notifications']['Popup.ModQueue'] = T('Notify me when an item is queued for moderation.');
         
         // Set preferences in UserMeta
         if ($Sender->Form->AuthenticatedPostBack()) {
            $Set = [];
            $Set['Email.ModQueue'] = ($Sender->Form->GetFormValue('Email.ModQueue', NULL)) ? 1 : NULL;
            $Set['Popup.ModQueue'] = ($Sender->Form->GetFormValue('Popup.ModQueue', NULL)) ? 1 : NULL;
            UserModel::SetMeta($Sender->User->UserID, $Set, 'Preferences.');
         }
      }
   }
   
   /**
    * Detect additions to moderation queue & notify.
    */
   public function LogModel_AfterInsert_Handler($Sender, $Args) {
      $Log = $Args['Log'];
      $LogID = $Args['LogID'];
      
      // Only deal with discussion & comment additions to moderation queue
      if ($Log['Operation'] != 'Moderate' && $Log['Operation'] != 'Pending')
         return;
      if ($Log['RecordType'] != 'Discussion' && $Log['RecordType'] != 'Comment')
         return;
      
      // Grab all of the users that need to be notified.
      $Data = Gdn::Database()->SQL()
         ->WhereIn('Name', ['Preferences.Email.ModQueue', 'Preferences.Popup.ModQueue'])
         ->Get('UserMeta')->ResultArray();
      
      // Prep notification list
      $NotifyUsers = [];
      foreach ($Data as $Row) {
         if (!$Row['Value'])
            continue;
         
         $UserID = $Row['UserID'];
         $Name = $Row['Name'];
         if (strpos($Name, '.Email.') !== FALSE) {
            $NotifyUsers[$UserID]['Emailed'] = ActivityModel::SENT_PENDING;
         } elseif (strpos($Name, '.Popup.') !== FALSE) {
            $NotifyUsers[$UserID]['Notified'] = ActivityModel::SENT_PENDING;
         }
      }
            
      // Prep the activity
      $ActivityModel = new ActivityModel();
      switch ($Log['Operation']) {
         case 'Pending':
            $HeadlineFormat = T('HeadlineFormat.ModQueuePending', 
               'A new {Data.RecordType,text} by {Data.RecordUserID, user} is awaiting approval in the <a href="{Url,html}">Moderation Queue</a>');
            break;
         case 'Moderate':
            $HeadlineFormat = T('HeadlineFormat.ModQueueModerate', 
               'A {Data.RecordType,text} by {Data.RecordUserID, user} has been moved to the <a href="{Url,html}">Moderation Queue</a>');
            break;
      }
      $Activity = [
         'ActivityType' => 'ModQueue',
         //'ActivityUserID' => $Fields['InsertUserID'],
         'HeadlineFormat' => $HeadlineFormat,
         'RecordType' => 'Log',
         'RecordID' => $LogID,
         'Route' => Url('/log/moderation'),
         'Data' => [
            'Operation' => $Log['Operation'],
            'RecordType' => strtolower($Log['RecordType']),
            'RecordUserID' => $Log['RecordUserID']
         ]
      ];
      
       // Queue the notifications
      foreach ($NotifyUsers as $UserID => $Prefs) {         
         $Activity['NotifyUserID'] = $UserID;
         $Activity['Emailed'] = GetValue('Emailed', $Prefs, FALSE);
         $Activity['Notified'] = GetValue('Notified', $Prefs, FALSE);
         $ActivityModel->Queue($Activity);
      }
      
      // Send all notifications.
      $ActivityModel->SaveQueue();
   }

   public function Setup() {
      // Create activity type
      $SQL = Gdn::Database()->SQL();
      if ($SQL->GetWhere('ActivityType', ['Name' => 'ModQueue'])->NumRows() == 0)
         $SQL->Insert('ActivityType', ['AllowComments' => 0, 'Name' => 'ModQueue', 'Public' => 0]);
   }
   
}
