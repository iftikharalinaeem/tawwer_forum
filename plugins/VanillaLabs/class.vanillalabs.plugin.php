<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['VanillaLabs'] = array(
   'Name' => 'Vanilla Labs',
   'Description' => "Get a preview of some of the features we are working on for the next release of Vanilla.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/settings/labs',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'MobileFriendly' => TRUE
);

class VanillaLabsPlugin extends Gdn_Plugin {
   public function Button($Label, $Operation, $Type, $ID, $Wrap = TRUE) {
      $Get = array('id' => $ID, 'tk' => Gdn::Session()->TransientKey());
      $Url = '/moderation/mod/'.$Operation."/$Type?".http_build_query($Get);
      
      $Button = Anchor($Label, $Url, 'Hijack');
      if ($Wrap) {
         $Button = '<span class="MItem Mod-Button Mod-'.ucfirst(StringBeginsWith($Operation, 'Undo', TRUE, TRUE)).'">'.$Button.'</span>';
      }
      return $Button;
   }
   
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
   public static function DefineSpamColumns($TableName, $Type = 'usmallint') {
      Gdn::Structure()
         ->Table($TableName)
         ->Column('Likes', $Type, 0)
         ->Column('Spam', $Type, 0)
//         ->Column('SpamScore', 'tinyint', TRUE)
         ->Column('Abuse', $Type, 0)
         ->Set();
   }
   
   public function GetRow($Type, $ID, $Operation) {
      switch ($Type) {
         case 'Comment':
            $Model = new CommentModel();
            $Row = $Model->GetID($ID, DATASET_TYPE_ARRAY);
            break;
         case 'Discussion':
            $Model = new DiscussionModel();
            $Row = $Model->GetID($ID);
            break;
         default:
            throw NotFoundException(ucfirst($Type));
      }
      
      $Log = NULL;
      if (!$Row) {
         // The row may have been logged so try and grab it.
         $LogModel = new LogModel();
         $Log = $LogModel->GetWhere(array('RecordType' => $Type, 'RecordID' => $ID, 'Operation' => $Operation));
         
         if (count($Log) == 0)
            throw NotFoundException($Type);
         $Log = $Log[0];
         $Row = $Log['Data'];
      }
      $Row = (array)$Row;
      
      // Make sure the attributes are in the row and unserialized.
      $Attributes = GetValue('Attributes', $Row, array());
      if (is_string($Attributes))
         $Attributes = unserialize($Attributes);
      if (!is_array($Attributes))
         $Attributes = array();
      $UserIDs = GetValue('ModUserIDs', $Attributes);
      if (!is_array($UserIDs)) {
         $Attributes['ModUserIDs'] = array();
      }
      $Row['Attributes'] = $Attributes;
      return array($Row, $Model, $Log);
   }
   
   public function IncrementUser($UserID, $Column, $Inc) {
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      $Curr = GetValue($Column, $User, 0);
      $Value = $Curr + $Inc;
      Gdn::UserModel()->SetField($UserID, $Column, $Value);
      
      $this->EventArguments['UserID'] = $UserID;
      $this->EventArguments['Column'] = $Column;
      $this->EventArguments['Inc'] = $Inc;
      $this->FireEvent('IncrementUser');
   }
   
   public function Request($Path, $Data) {
      $Domain = C('Plugins.VanillaSpam.Url', 'http://localhost/vspam');
      $Key = C('Plugins.VanillaSpam.APIKey', '214575b6976d705a1b82c55b766abb00');
      
      $Url = $Domain.'/'.ltrim($Path, '/');
      
      $RequestData = array('body' => $Data['Body']);
      $IP = GetValue('IPAddress', $Data, GetValue('InertIPAddress', $Data));
      $RequestData['ipaddress'] = $IP;
      
      // Curl the data to the spam server.
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($C, CURLOPT_URL, $Url);
      curl_setopt($C, CURLOPT_POST, TRUE);
      curl_setopt($C, CURLOPT_POSTFIELDS, $RequestData);
      $Contents = curl_exec($C);
      //decho($Contents);
      //die();
      //if (!$Contents)
         return FALSE;
      //return json_decode($Contents);
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
      
      $St = Gdn::Structure()->Table('Discussion');
      $Recalc = $St->ColumnExists('Spam');
      
      $this->DefineSpamColumns('Discussion');
      $this->DefineSpamColumns('Comment');
      
      $St = Gdn::Structure()->Table('User');
      $Recalc &= !$St->ColumnExists('Spam');
      $this->DefineSpamColumns('User', 'uint');
      if ($Recalc) {
         $Px = $St->DatabasePrefix();
         // Calculate the user sums.
         $Columns = array('Likes', 'Spam', 'Abuse');
         foreach ($Columns as $Column) {
            $Sql = "update {$Px}User u set $Column = 
               coalesce((select sum($Column) from {$Px}Discussion d where d.InsertUserID = u.UserID), 0)
             + coalesce((select sum($Column) from {$Px}Comment c where c.InsertUserID = u.UserID), 0)";
             
            Gdn::SQL()->Query($Sql, 'update');
         }
         // Clear the cache to make sure the user columns update.
         Gdn::Cache()->IncrementRevision();
      }
   }
   
   public function UserID() {
      return C('Plugins.VanillaSpam.UserID', NULL);
   }
   
   public function Likes($Row, $Wrap = TRUE) {
      $Likes = GetValue('Likes', $Row);
      $Result = '';
      if ($Likes > 0) {
         $Result = '<span class="Tag Tag-Likes">'.Plural($Likes, '%s like', '%s likes').'</span>';
      }
      if ($Wrap) {
         $Result = '<span class="Mod-Likes">'.$Result.'</span>';
      }
      return $Result;
   }
   
//   public function Tag($Column, $Row, $Wrap = TRUE) {
//      $Sing = strtolower(StringEndsWith($Column, 
//   }
   
   /// EVENT HANDLERS ///

//   public function Base_CheckSpam_Handler($Sender, $Args) {
//      if ($Args['IsSpam'])
//         return; // don't double check
//
//      $RecordType = $Args['RecordType'];
//      $Data =& $Args['Data'];
//
//
//      switch ($RecordType) {
//         case 'User':
////            $Data['Name'] = '';
////            $Data['Body'] = GetValue('DiscoveryText', $Data);
////            $Result = $this->CheckAkismet($RecordType, $Data);
//            break;
//         case 'Comment':
//         case 'Discussion':
//         case 'Activity':
//            $Result = $this->CheckSpam($RecordType, $Data);
//            if ($Result)
//               $Data['Log_InsertUserID'] = $this->UserID();
//            break;
//      }
//      $Sender->EventArguments['IsSpam'] = $Result;
//   }
   
   public function Base_CommentOptions_Handler($Sender) {
      if (GetValue('InsertUserID', $Sender->EventArguments['Object']) == GDN::Session()->UserID && !Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         return;
      if (!Gdn::Session()->CheckPermission('Garden.SignIn.Allow'))
         return;
      
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
            $Target = '/discussion/$ID/'.Gdn_Format::Url($Sender->EventArguments['Discussion']->Name);
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
      $Url = "/moderation/spam/$Type/$ID?".http_build_query($Get);
      
      
      $LikeButton = $this->Button(T('Like'), 'like', $Type, $ID);
      $SpamButton = $this->Button(T('Report Spam', 'Spam'), 'spam', $Type, $ID);
      $AbuseButton = $this->Button(T('Report Abuse', 'Abuse'), 'abuse', $Type, $ID);
      echo $LikeButton, $SpamButton, $AbuseButton;
   }
   
   public function Base_CommentInfo_Handler($Sender, $Args) {
      $Likes = $this->Likes($Sender->EventArguments['Object']);
      echo ' '.$Likes.' ';
   }
   
   public function ModerationController_Mod_Create($Sender, $Op, $Type, $ID, $TK) {
      if (!Gdn::Session()->ValidateTransientKey($TK))
         throw PermissionException();
      
      $Sender->Permission('Garden.SignIn.Allow');
      
      $Undo = '';
      $Op = strtolower($Op);
      $Op2 = $Op;
      if (StringBeginsWith($Op, 'undo-')) {
         $Undo = 'undo';
         $Op2 = StringBeginsWith($Op, 'undo-', TRUE, TRUE);
      }
      
      if (!in_array($Op2, array('like', 'spam', 'abuse')))
         throw NotFoundException();
      
      $this->SaveOperation($Sender, $Type, $ID, $Op);
   }
   
   /**
    *
    * @param DiscussionController $Sender
    * @param string $Type
    * @param int $ID 
    */
//   public function DiscussionController_Ham_Create($Sender, $Type, $ID, $TK) {
//      $Sender->Permission('Garden.Moderation.Manage');
//      
//      if (!Gdn::Session()->ValidateTransientKey($TK))
//         throw PermissionException();
//      
//      list($Row, $Model) = $this->GetRow($Type, $ID);
//      
//      $UserIDs = $Row['Attributes']['ModUserIDs'];
//      if (array_key_exists(Gdn::Session()->UserID, $UserIDs)) {
//         throw new Gdn_UserException(sprintf(T('You already flagged this %s.'), T($Type)));
//      }
//      $UserIDs[Gdn::Session()->UserID] = 'H'; // h is for ham
//      $Spam = 0;
//      $Row['Attributes']['ModUserIDs'] = $UserIDs;
//      
//      $Row['Spam'] = $Spam;
//      // Save the hamness to the db.
//      $Model->SetProperty($ID,
//            array('Spam' => $Row['Spam'], 'Attributes' => serialize($Row['Attributes'])),
//            ''
//         );
//      // Tell the filter.
//      $this->Request('ham', $Row);
//   }
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param type $Type
    * @param type $ID
    * @param type $Operation 
    */
   public function SaveOperation($Sender, $Type, $ID, $Operation) {
      $Undo = FALSE;
      if (StringBeginsWith($Operation, 'Undo-', TRUE)) {
         $Undo = TRUE;
         $Operation = StringBeginsWith($Operation, 'Undo-', TRUE, TRUE);
      }
      $Type = ucfirst($Type);
      $Operation = strtolower($Operation);
      $Column = ucfirst($Operation);
      if ($Operation == 'like')
         $Column = 'Likes';
      $Abbrev = strtoupper(substr($Operation, 0, 1));
      $LogOperation = $Operation == 'spam' ? 'Spam' : 'Moderate';
      
      list($Row, $Model, $Log) = $this->GetRow($Type, $ID, $LogOperation);
      
      // Make sure the user has/hasn't already flagged the row.
      $UserIDs = $Row['Attributes']['ModUserIDs'];
      if (array_key_exists(Gdn::Session()->UserID, $UserIDs)) {
         if (!$Undo)
            throw new Gdn_UserException(sprintf(T('You already flagged this %s.'), T($Type)));
      } else {
         if ($Undo)
            throw new Gdn_UserException(sprintf(T('You haven\'t flagged this %s.'), T($Type)));
      }
      
      // Figure out the increment.
      if (in_array($Operation, array('spam', 'abuse')) && Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $Inc = C('Vanilla.ModeratorIncrement.ModeratorIncrement', 5);
      } else {
         $Inc = 1;
      }
      
      $Value = $Row[$Column];
      
      // Update the row values with the operation.
      if ($Undo) {
         $Value -= $Inc;
         unset($UserIDs[Gdn::Session()->UserID]);
      } else {
         $Value += $Inc;
         $UserIDs[Gdn::Session()->UserID] = $Abbrev;
      }
      if ($Value < 0)
         $Value = 0;
      $Row[$Column] = $Value;
      $Row['Attributes']['ModUserIDs'] = $UserIDs;
      
      
      // Now deciede whether we need to log or delete the record.
      $LogThresholds = array(
          'spam' => C('Vanilla.Moderation.SpamThreshold1', 5),
          'abuse' => C('Vanilla.Moderation.AbuseThreshold1', 5)
      );
      $LogThreshold = GetValue($Operation, $LogThresholds);
      
      $DeleteThresholds = array(
          'spam' => C('Vanilla.Moderation.SpamThreshold2', 5),
          'abuse' => C('Vanilla.Moderation.AbuseThreshold2', 10)
      );
      $DeleteThreshold = GetValue($Operation, $DeleteThresholds);
      
      if ($Undo)
         $UndoButton = $this->Button(T('Report '.ucfirst($Operation), ucfirst($Operation)), $Operation, $Type, $ID, FALSE);
      else
         $UndoButton = $this->Button(T('Undo '.ucfirst($Operation), 'Undo'), 'undo-'.$Operation, $Type, $ID, FALSE);
      
      
      $Targets = array();
      if ($Operation == 'like') 
         $MessageBody = sprintf('You liked the %s. Thanks!', strtolower($Type));
      else
         $MessageBody = sprintf('The %s has been flagged. Thanks!', strtolower($Type));
      
      $MessageBody = T($MessageBody);
      $Message = array('<span class="InformSprite Flag"></span> '.$MessageBody, array('CssClass' => 'Dismissable AutoDismiss HasSprite', 'id' => 'mod'));
      
      if ($Undo) {
         if ($Log) {
            // The row was logged and now must be restored.
            $LogModel = new LogModel();
            $Log['Data'] = $Row;
            $LogModel->Restore($Log);

            if ($Type == 'Comment') {
               $Targets[] = array('Target' => "#{$Type}_$ID", 'Type' => 'SlideDown');
            } else {
               // Send back a refresh command. It's a bit too complicated to reveal everything.
               $Sender->RedirectUrl = Url("/discussion/{$Row['DiscussionID']}/".Gdn_Format::Url($Row['Name']));
            }
         } else {
            // The row just needs to be updated.
            $Model->SetProperty($ID,
               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
               ''
            );
         }
         $Message[0] = '';
      } else {
         $LogOptions = array('GroupBy' => array('RecordID'));
         // Get the User IDs that marked as spam.
         $OtherUserIDs = array();
         
         foreach ($UserIDs as $UserID => $Val) {
            if ($Val == $Abbrev && $UserID != Gdn::Session()->UserID)
               $OtherUserIDs[] = $UserID;
         }
         $LogOptions['OtherUserIDs'] = $OtherUserIDs;
         
         if ($DeleteThreshold && $Value >= $DeleteThreshold) {
            // We still need to update the row before deleting to get the right values in there.
            $Model->SetProperty($ID,
               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
               ''
            );
            
            // The row needs to be deleted.
            $Model->Delete($ID, array('Log' => $LogOperation, 'LogOptions' => $LogOptions));
            $Message = array(
            sprintf(T('The %s has been removed for moderation.'), T($Type))
               .' '.$UndoButton,
               array('CssClass' => 'Dismissable', 'id' => 'mod')
            );
            // Send back a command to remove the row in the browser.
            if ($Type == 'Discussion') {
               $Targets[] = array('Target' => 'ul.Discussion', 'Type' => 'SlideUp');
               $Targets[] = array('Target' => '.CommentForm', 'Type' => 'SlideUp');
            } else
               $Targets[] = array('Target' => "#{$Type}_$ID", 'Type' => 'SlideUp');
         } elseif ($LogThreshold && $Value >= $LogThreshold) {
            // The row needs to be logged and updated.
            $Model->SetProperty($ID,
               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
               ''
            );
            
            LogModel::Insert($LogOperation, $Type, $Row, $LogOptions);
         } else {
            // The row needs to just be updated.
            $Model->SetProperty($ID,
               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
               ''
            );
         }
      }
      // Increment the user.
      if ($Undo)
         $Inc = -$Inc;
      $this->IncrementUser($Row['InsertUserID'], $Column, $Inc);
      
      // Send back a button to undo/redo the operation.
      $Targets[] = array('Target' => "#{$Type}_$ID .Mod-".ucfirst($Operation), 'Type' => 'Html', 'Data' => $UndoButton);
         
      // Send back the likes.
      $Targets[] = array('Target' => "#{$Type}_$ID .Mod-Likes", 'Type' => 'Html', 'Data' => $this->Likes($Row, FALSE));
      $Sender->InformMessage($Message[0], $Message[1]);
      
      $Sender->SetJson('Targets', $Targets);
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

   public function SettingsController_Reactions_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->SetData('Title', T('Reaction Settings'));

      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
			'Vanilla.Moderation.SpamThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'Posts will be logged as spam after this many reports.'),
			'Vanilla.Moderation.SpamThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'Posts will be removed after this many reports of spam.'),
         'Vanilla.Moderation.AbuseThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'Posts will be logged for moderation after this many reports.'),
			'Vanilla.Moderation.AbuseThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 10, 'Description' => 'Posts will be removed after this many reports of abuse.'),
         'Vanilla.ModeratorIncrement.ModeratorIncrement' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => 5, 'Description' => 'Moderators that report posts have a higher weight than normal users.')
		));

      $Sender->AddSideMenu('dashboard/settings/plugins');
      $Cf->RenderAll();
   }
}