<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['VanillaSpam'] = array(
   'Name' => 'Vanilla Spam',
   'Description' => "Anti-spam services from Vanilla",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
//   'SettingsUrl' => '/settings/vanillaspam',
//   'SettingsPermission' => 'Garden.Settings.Manage'
);

class VanillaSpamPlugin extends Gdn_Plugin {
   
   
   public function CheckSpam($RecordType, $Data) {
      $Result = $this->Request('check', $Data);
      if (!$Result === FALSE)
         return FALSE;
      return GetValue('spam', $Result);
   }
   
   /**
    *
    * @param Gdn_DatabaseStructure $Structure 
    */
   public static function DefineSpamColumns($TableName) {
      Gdn::Structure()
         ->Table($TableName)
         ->Column('Spam', 'tinyint', 0)
         ->Set();
   }
   
   public function MarkSpam($RecordType, $Data) {
   }
   
   public function MarkHam($RecordType, $Data) {
   }
   
   public function Request($Path, $Data) {
      $Domain = C('Plugins.VanillaSpam.Url', 'http://localhost/vspam');
      $Key = C('Plugins.VanillaSpam.APIKey', '214575b6976d705a1b82c55b766abb00');
      
      $Url = $Domain.'/'.ltrim($Path, '/');
      
      // Curl the data to the spam server.
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($C, CURLOPT_URL, $Url);
      curl_setopt($C, CURLOPT_POST, TRUE);
      curl_setopt($C, CURLOPT_POSTFIELDS, $Data);
      $Contents = curl_exec($C);
      decho($Contents);
      die();
      if (!$Contents)
         return FALSE;
      return json_decode($Contents);
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      // Get a user for operations.
      $UserID = Gdn::SQL()->GetWhere('User', array('Name' => 'VanillaSpam', 'Admin' => 2))->Value('UserID');

      if (!$UserID) {
         $UserID = Gdn::SQL()->Insert('User', array(
            'Name' => 'VanillaSpam',
            'Password' => RandomString('20'),
            'HashMethod' => 'Random',
            'Email' => 'vanillaspam@domain.com',
            'DateInserted' => Gdn_Format::ToDateTime(),
            'Admin' => '2'
         ));
      }
      SaveToConfig('Plugins.VanillaSpam.UserID', $UserID, array('CheckExisting' => TRUE));
      
      $this->DefineSpamColumns('Discussion');
      $this->DefineSpamColumns('Comment');
   }
   
   public function UserID() {
      return C('Plugins.VanillaSpam.UserID', NULL);
   }
   
   /// EVENT HANDLERS ///

   public function Base_CheckSpam_Handler($Sender, $Args) {
      if ($Args['IsSpam'])
         return; // don't double check

      $RecordType = $Args['RecordType'];
      $Data =& $Args['Data'];


      switch ($RecordType) {
         case 'User':
//            $Data['Name'] = '';
//            $Data['Body'] = GetValue('DiscoveryText', $Data);
//            $Result = $this->CheckAkismet($RecordType, $Data);
            break;
         case 'Comment':
         case 'Discussion':
         case 'Activity':
            $Result = $this->CheckSpam($RecordType, $Data);
            if ($Result)
               $Data['Log_InsertUserID'] = $this->UserID();
            break;
      }
      $Sender->EventArguments['IsSpam'] = $Result;
   }
   
   public function Base_CommentOptions_Handler($Sender) {
      // You can't report or 'awesome' your own posts
      // if (GetValue('InsertUserID', $Sender->EventArguments['Object']) == GDN::Session()->UserID) return;
      
      $Type = strtolower($Sender->EventArguments['Type']);

      switch ($Type) {
         case 'comment':
            $ID = $Sender->EventArguments['Comment']->CommentID;
            $Attributes = $Sender->EventArguments['Comment']->Attributes;
            $Target = "/discussion/comment/$ID#Comment_$ID";
            break;
         case 'discussion':
            $ID = $Sender->EventArguments['Discussion']->DiscussionID;
            $Attributes = $Sender->EventArguments['Discussion']->Attributes;
            $Target = '/discussion/$ID/x';
            break;
         default:
            return;
      }
      if (is_string($Attributes))
         $Attributes = unserialize($Attributes);
      $Mod = GetValueR('ModUserIDs.'.Gdn::Session()->UserID, $Attributes);
      if ($Mod)
         return;
      
      $Get = array('TK' => Gdn::Session()->TransientKey());
      $Url = "/discussion/spam/$Type/$ID?".http_build_query($Get);
      
      $Button = '<span class="Mod-Spam">'.Anchor(T('Report Spam', 'Spam'), $Url, 'Hijack').'</span>';
      echo $Button;
   }
   
   /**
    *
    * @param DiscussionController $Sender
    * @param string $Type
    * @param int $ID 
    */
   public function DiscussionController_Spam_Create($Sender, $Type, $ID, $TK) {
      $Sender->Permission('Garden.SignIn.Allow');
      
      $Threshold = C('Plugins.VanillaSpam.Threshold', 5);
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $Inc = C('Plugins.VanillaSpam.ModeratorIncrement', 5);
      } else {
         $Inc = 1;
      }
      
      switch ($Type) {
         case 'comment':
            $Model = new CommentModel();
            $Row = $Model->GetID($ID, DATASET_TYPE_ARRAY);
            if (!$Row)
               throw NotFoundException();
            break;
         case 'discussion':
            $Model = new DiscussionModel();
            $Row = $Model->GetID($ID);
            if (!$Row)
               throw NotFoundException();
            $Row = (array)$Row;
            break;
         default:
            throw NotFoundException(ucfirst($Type));
      }
      
      // Make sure the user hasn't already marked the comment as spam.
      $Attibutes = GetValue('Attributes', $Row, array());
      if (is_string($Attibutes))
         $Attibutes = unserialize($Attibutes);
      if (!is_array($Attibutes))
         $Attibutes = array();
      $UserIDs = (array)GetValue('ModUserIDs', $Attibutes, array());
      if (array_key_exists(Gdn::Session()->UserID, $UserIDs)) {
         throw new Gdn_UserException(T('You already marked this.'));
      }
      $UserIDs[Gdn::Session()->UserID] = 'S'; // s is for spam
      $Spam = $Row['Spam'] + $Inc;
      $Attibutes['ModUserIDs'] = $UserIDs;
      
      $Row['Spam'] = $Spam;
      $Row['Attributes'] = serialize($Attibutes);
      
      if ($Spam >= $Threshold) {
         $LogOptions = array('GroupBy' => array('RecordID'));
         // Get the User IDs that marked as spam.
         $OtherUserIDs = array();
         foreach ($UserIDs as $UserID => $Value) {
            if ($Value == 'S' && $UserID != Gdn::Session()->UserID)
               $OtherUserIDs[] = $UserID;
         }
         $LogOptions['OtherUserIDs'] = $OtherUserIDs;

         // Add the row to moderation.
         LogModel::Insert('Spam', ucfirst($Type), $Row, $LogOptions);
         
         if ($Type == 'comment') {
            // Remove the row.
            $Model->Delete($ID, array('Log' => FALSE));
            $Sender->InformMessage(sprintf(T('The %s has been removed for moderation.'), T($Type)));
         
            // Send back a command to remove the row in the browser.
            $Target = array('Target' => "#Comment_$ID", 'Type' => 'SlideUp');
            $Sender->SetJson('Targets', array($Target));
         } else {
            // Don't remove the discussion.
            $Model->SetProperty($ID,
               array('Spam' => $Row['Spam'], 'Attributes' => $Row['Attributes']),
               ''
            );
            
            $Target = array('Target' => "#Discussion_$ID .Mod-Spam", 'Type' => 'Remove');
            $Sender->SetJson('Targets', array($Target));
            $Sender->InformMessage(sprintf(T('The %s has been flagged. Thanks!'), T($Type)));
         }
      } else {
         // This isn't spam yet, but save the flag.
         $Model->SetProperty($ID,
            array('Spam' => $Row['Spam'], 'Attributes' => $Row['Attributes']),
            ''
         );
         
         // Send back a command to remove the button in the browser.
         if ($Type == 'comment') {
            $Target = array('Target' => "#Comment_$ID .Mod-Spam", 'Type' => 'Remove');
         } else {
            $Target = array('Target' => "#Discussion_$ID .Mod-Spam", 'Type' => 'Remove');
         }
         $Sender->SetJson('Targets', array($Target));
         $Sender->InformMessage(sprintf(T('The %s has been flagged. Thanks!'), T($Type)));
      }
      $Sender->Render('Blank', 'Utility', 'dashboard');
   }
   
   public function LogModel_BeforeInsert_Handler($Sender, $Args) {
      $Log = $Args['Log'];
      if ($Log['Operation'] != 'Spam')
         return;
      
      
   }
   
   public function LogModel_BeforeRestore_Handler($Sender, $Args) {
      $Log = $Args['Log'];
      if ($Log['Operation'] != 'Spam')
         return;
   }

//   public function SettingsController_Akismet_Create($Sender, $Args) {
//      $Sender->SetData('Title', T('Vanilla Spam Settings'));
//
//      $Cf = new ConfigurationModule($Sender);
//      $Cf->Initialize(array('Plugins.Akismet.Key' => array('Description' => 'Enter the key you obtained from <a href="http://akismet.com">akismet.com</a>')));
//
//      $Sender->AddSideMenu('dashboard/settings/plugins');
//      $Cf->RenderAll();
//   }
}