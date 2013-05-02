<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['VanillaPop'] = array(
   'Name' => 'Vanilla Pop',
   'Description' => "Users may start discussions, make comments, and even automatically register for your site via email.",
   'Version' => '1.0.6',
   'RequiredApplications' => array('Vanilla' => '2.0.18b3'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/settings/vanillapop',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'RegisterPermissions' => array(
      'Email.Discussions.Add' => 'Garden.Profiles.Edit',
      'Email.Comments.Add' => 'Garden.Profiles.Edit',
      'Email.Conversations.Add' => 'Garden.Profiles.Edit')
);

// 1.0.6 - Lincoln, Apr 2013
// -- Adds 'Force Notify' feature for roles

class VanillaPopPlugin extends Gdn_Plugin {
   /// Properties ///
   static $FormatDefaults = array(
          'DiscussionSubject' => '[{Title}] {Name}',
          'DiscussionBody' => "{Body}\n\n-- \n{Signature}",
          'CommentSubject' => 'Re: [{Title}] {Discussion.Name}',
          'CommentBody' => "{Body}\n\n-- \n{Signature}",
          'ConfirmationBody' => "Your request has been received (ticket #{ID}).\n\nThis is just a confirmation email, but you can reply directly to follow up.\n\nYou wrote:\n{Quote}\n\n-- \n{Signature}");
   
   /// Methods ///
   
   public static function AddIDToEmail($Email, $ID) {
      if (!C('Plugins.VanillaPop.AugmentFrom', TRUE))
         return;
      
      // Encode the message ID in the from.
      $FromParts = explode('@', $Email, 2);
      if (count($FromParts) == 2) {
         $Email = "{$FromParts[0]}+$ID@{$FromParts[1]}";   
      }
      return $Email;
   }
   
   public static function CheckUserPermission($UserID, $Permission) {
      $Permissions = Gdn::UserModel()->DefinePermissions($UserID, FALSE);
      $Result = in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
      return $Result;
   }
   
//   public static function FormatPlainText($Body, $Format) {
//      $Result = Gdn_Format::To($Body, $Format);
//      
//      if ($Format != 'Text')
//         $Result = Gdn_Format::Text($Result, FALSE);
//      $Result = trim(html_entity_decode($Result, ENT_QUOTES, 'UTF-8'));
//      return $Result;
//   }
   
   public static function FormatEmailBody($Body, $Route = '', $Quote = '', $Options = FALSE) {      
      // Construct the signature.
      if ($Route) {
         $Signature = FormatString(T('ReplyOrFollow'))."\n".ExternalUrl($Route);
      } elseif ($Route === FALSE) {
         $Signature = ExternalUrl('/');
      } else {
         $Signature = FormatString(T('ReplyOnly'));
      }
      
      if ($Quote) {
         if (is_array($Quote))
            $Quote = Gdn_Format::PlainText($Quote['Body'], GetValue('Format', $Quote, 'Text'));
         
         $Quote = "\n\n".T('You wrote:')."\n\n".self::FormatQuoteText($Quote);
      }
      
      $Result = FormatString(T('EmailTemplate'), array('Body' => $Body, 'Signature' => $Signature, 'Quote' => $Quote));
      return $Result;
   }
   
   public static function EmailSignature($Route = '', $CanView = TRUE, $CanReply = TRUE) {
      if (!$Route)
         $CanView = FALSE;
      
      if ($CanView && $CanReply) {
         $Signature = FormatString(T('ReplyOrFollow'))."\n".ExternalUrl($Route);
      } elseif ($CanView) {
         $Signature = FormatString(T('FollowOnly'))."\n".ExternalUrl($Route);
      } elseif ($CanReply) {
         $Signature = FormatString(T('ReplyOnly'));
      } else {
         $Signature = ExternalUrl('/');
      }
      return $Signature;
   }
   
   public static function FormatQuoteText($Text) {
      $Result = '> '.str_replace("\n", "\n> ", $Text);
      return $Result;
   }
   
   public static function LabelCode($SchemaRow) {
      if (isset($SchemaRow['LabelCode']))
         return $SchemaRow['LabelCode'];

      $LabelCode = $SchemaRow['Name'];
      if (strpos($LabelCode, '.') !== FALSE)
         $LabelCode = trim(strrchr($LabelCode, '.'), '.');

      // Split camel case labels into seperate words.
      $LabelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $LabelCode);
      $LabelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $LabelCode);
      $LabelCode = trim($LabelCode);

      return $LabelCode;
   }
   
   public static function Log($Message) {
//      $Line = Gdn_Format::ToDateTime().' '.$Message."\n";
//      file_put_contents(PATH_UPLOADS.'/email/log.txt', $Line, FILE_APPEND);
   }
   
   public static function ParseEmailAddress($Email) {
      $Name = '';
      if (preg_match('`([^<]*)<([^>]+)>`', $Email, $Matches)) {
         $Name = trim(trim($Matches[1]), '"');
         $Email = trim($Matches[2]);
      }
         
      if (!$Name) {
         $Name = trim(substr($Email, 0, strpos($Email, '@')), '@');
         
         $NameParts = explode('.', $Name);
         $NameParts = array_map('ucfirst', $NameParts);
         $Name = implode(' ', $NameParts);
      }
      
      $Result = array($Name, $Email);
      return $Result;
   }
   
   public static function ParseEmailHeader($Header) {
      $Result = array();
      $Parts = explode("\n", $Header);

      $i = NULL;
      foreach ($Parts as $Part) {
         if (!$Part)
            continue;
         if (preg_match('`^\s`', $Part)) {
            if (isset($Result[$i])) {
               $Result[$i] .= "\n".$Part;
            }
         } else {
            self::Log("Headerline: $Part");
            list($Name, $Value) = explode(':', $Part, 2);
            $i = trim($Name);
            $Result[$i] = ltrim($Value);
         }
      }

      return $Result;
   }
   
   public static function ParseRoute($Route) {
      if (preg_match('`/?(?:vanilla/)?discussion/(\d+)`i', $Route, $Matches)) {
         $Type = 'Discussion';
         $ID = $Matches[1];
      } elseif (preg_match('`/?(?:vanilla/)?discussion/comment/(\d+)`i', $Route, $Matches)) {
         $Type = 'Comment';
         $ID = $Matches[1];
      } elseif (preg_match('`/?(?:conversations/)?messages/\d+#(\d+)`i', $Route, $Matches)) {
         $Type = 'Message';
         $ID = $Matches[1];
      } else {
         return array(NULL, NULL);
      }
      
      return array($Type, $ID);
   }
   
   public static function ParseType($Email) {
      if (preg_match('`\+([a-z]+-?[0-9]+)@`', $Email, $Matches)) {
         list($Type, $ID) = self::ParseUID($Matches[1]);
      } elseif (preg_match('`\+noreply@`i', $Email, $Matches)) {
         $Type = 'noreply';
         $ID = NULL;
      } else {
         $Type = NULL;
         $ID = NULL;
      }
      return array($Type, $ID);
   }
   
   public static $Types = array('d' => 'Discussion', 'c' => 'Comment', 'u' => 'User', 'cv' => 'Conversation', 'm' => 'Message');
   
   public static function ParseUID($UID) {
      // Strip off email stuff.
      if (preg_match('`<([^@]+)@`', $UID, $Matches)) {
         $UID = trim(trim($Matches[1]), '"');
      }
      
      if (strcasecmp($UID, 'noreply') == 0) {
         return array('noreply', NULL);
      }
      
      if (preg_match('`([a-z]+)-?([0-9]+)`i', $UID, $Matches)) {
         $Type = GetValue($Matches[1], self::$Types, NULL);
         if ($Type) {
            $ID = $Matches[2];
            return array($Type, $ID);
         }
      } else {
         // This might be a category.
         $Category = CategoryModel::Categories($UID);
         if ($Category)
            return array('Category', $Category['CategoryID']);
         else
            return array(NULL, NULL);
      }
   }
   
   protected function Save($Data, $Sender) {
      $ReplyType = NULL;
      $ReplyID = NULL;
      
      if (GetValue('ReplyTo', $Data)) {
         // See if we are replying to something specifically.
         list($ReplyType, $ReplyID) = self::ParseUID($Data['ReplyTo']);
      }
      
      if (!$ReplyType) {
         // Grab the reply from the to.
         list($ToName, $ToEmail) = self::ParseEmailAddress(GetValue('To', $Data));
         list($ReplyType, $ReplyID) = self::ParseType($ToEmail);
      }
      
      if (!$ReplyType && GetValue('ReplyTo', $Data)) {
         // This may be replying to the SourceID rather than the UID.
         $SaveType = $this->SaveTypeFromRepyTo($Data);
      }
      
      if (strcasecmp($ReplyType, 'noreply') == 0) {
         return TRUE;
      }
      
      // Save the full post for debugging.
      $Data['Attributes'] = serialize(ArrayTranslate($Data, array('Headers', 'Source')));
      
      $Data['Body'] = self::StripEmail($Data['Body']);
      if (!$Data['Body'])
         $Data['Body'] = T('(empty message)');
      
      list($FromName, $FromEmail) = self::ParseEmailAddress($Data['From']);
      
      // Check for a category.
      if ($ReplyType == 'Category') {
         $CategoryID = $ReplyID;
      } else {
         $CategoryID = C('Plugins.VanillaPop.DefaultCategoryID', -1);
      }
      if (!$CategoryID)
         $CategoryID = -1;
      TouchValue('CategoryID', $Data, $CategoryID);
      
      // See if there is a user at the given email.
      $UserModel = new UserModel();
      $User = $UserModel->GetByEmail($FromEmail);
      if (!$User) {
         if (C('Plugins.VanillaPop.AllowUserRegistration')) {
            SaveToConfig('Garden.Registration.NameUnique', FALSE, FALSE);
            $Sender->Data['_Status'][] = 'Creating user.';
            $User = array(
                'Name' => $FromName,
                'Email' => $FromEmail,
                'Password' => RandomString(10),
                'HashMethod' => 'Random',
                'Source' => 'Email',
                'SourceID' => $FromEmail
                );

            $UserID = $UserModel->InsertForBasic($User, FALSE, array('NoConfirmEmail' => 'NoConfirmEmail'));

            if (!$UserID) {
               throw new Exception(T('Error creating user.').' '.$UserModel->Validation->ResultsText(), 400);
            }

            $User['UserID'] = $UserID;
         } else {
            $this->SendEmail($FromEmail, '', 
               T("Whoops! You'll need to register before you can email our site."), $Data);
            return TRUE;
         }
      } else {
         $Sender->Data['_Status'][] = 'User exists';
         $User = (array)$User;
      }
      Gdn::Session()->Start($User['UserID'], FALSE);
      $Data['InsertUserID'] = $User['UserID'];
      
      // Get the parent record and make sure the post is going in the right place.
      if (!isset($SaveType)) {
         switch ($ReplyType) {
            case 'Discussion':
               // Grab the discussion.
               $DiscussionModel = new DiscussionModel();
               $Discussion = $DiscussionModel->GetID($ReplyID);
               if (!$Discussion) {
                  $InvalidReply = TRUE;
                  $SaveType = 'Discussion';
               } else {
                  $SaveType = 'Comment';
                  $Data['DiscussionID'] = $ReplyID;
               }

               break;
            case 'Comment':
               $CommentModel = new CommentModel();
               $Comment = $CommentModel->GetID($ReplyID, DATASET_TYPE_ARRAY);
               if (!$Comment) {
                  $InvalidReply = TRUE;
                  $SaveType = 'Discussion';
               } else {
                  // Grab the discussion so we can see its category.
                  $DiscussionModel = new DiscussionModel();
                  $Discussion = $DiscussionModel->GetID($Comment['DiscussionID'], DATASET_TYPE_ARRAY);
                  $Data['CategoryID'] = GetValue('CategoryID', $Discussion);
                  
                  $SaveType = 'Comment';
                  $Data['DiscussionID'] = $Comment['DiscussionID'];
               }
               break;
            case 'Conversation':
               $ConversationModel = new ConversationModel();
               $Conversation = $ConversationModel->GetID($ReplyID);
               if (!$Conversation) {
                  $InvalidReply = TRUE;
                  $SaveType = 'Discussion';
               } else {
                  // TODO: Check permission.
                  
                  $SaveType = 'Message';
                  $Data['ConversationID'] = $Conversation['ConversationID'];
               }

               break;
            case 'Message':
               $MessageModel = new ConversationMessageModel();
               $Message = $MessageModel->GetID($ReplyID, DATASET_TYPE_ARRAY);
               if (!$Message) {
                  $InvalidReply = TRUE;
                  $SaveType = 'Discussion';
               } else {
                  // TODO: Check permission.
                  $SaveType = 'Message';
                  $Data['ConversationID'] = $Message['ConversationID'];
               }
               break;
             default:
                $SaveType = 'Discussion';
                break;
         }
      }
      
      if (isset($InvalidReply)) {
         $Data['Body'] .= "\n\n".sprintf(T('Note: The email was trying to reply to an invalid %s.'), "$ReplyType ($ReplyID)");
      }
      
      // Set the source of the post.
      $Data['Source'] = 'Email';
      $Data['SourceID'] = GetValue('MessageID', $Data, NULL);
      
      $Category = CategoryModel::Categories(GetValue('CategoryID', $Data));
      if ($Category) {
         $PermissionCategoryID = $Category['PermissionCategoryID'];
      } else {
         $PermissionCategoryID = -1;
      }
      
      switch ($SaveType) {
         case 'Comment':
            if (!Gdn::Session()->CheckPermission('Email.Comments.Add')) {
               $this->SendEmail($FromEmail, '',
                  T("Sorry! You don't have permission to comment through email."), $Data);
               return TRUE;
            } elseif (!Gdn::Session()->CheckPermission('Vanilla.Comments.Add', TRUE, 'CategoryID', $PermissionCategoryID)) {
               $this->SendEmail($FromEmail, '',
                  T("Sorry! You don't have permission to post right now."), $Data);
               return TRUE;
            }
            
            $CommentModel = new CommentModel();
            $CommentID = $CommentModel->Save($Data);
            if (!$CommentID) {
               throw new Exception($CommentModel->Validation->ResultsText().print_r($Data, TRUE), 400);
            } else {
               $CommentModel->Save2($CommentID, TRUE);
            }
            return $CommentID;
         case 'Message':
            if (!Gdn::Session()->CheckPermission('Email.Conversations.Add')) {
               $this->SendEmail($FromEmail, '',
                  T("Sorry! You don't have permission to send messages through email."), $Data);
               return TRUE;
            }
            
            $MessageModel = new ConversationMessageModel();
            $MessageID = $MessageModel->Save($Data);
            if (!$MessageID) {
               throw new Exception($MessageModel->Validation->ResultsText().print_r($Data, TRUE), 400);
            }
            return $MessageID;
         case 'Discussion':
         default:
            // Check the permission on the discussion.
            if (!Gdn::Session()->CheckPermission('Email.Discussions.Add')) {
               $this->SendEmail($FromEmail, '',
                  T("Sorry! You don't have permission to post discussions/questions through email."), $Data);
               return TRUE;
            } elseif (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'CategoryID', $PermissionCategoryID)) {
               $this->SendEmail($FromEmail, '',
                  T("Sorry! You don't have permission to post right now."), $Data);
               return TRUE;
            }
            
            $Data['Name'] = $Data['Subject'];
            $Data['UpdateUserID'] = $Data['InsertUserID'];
            $DiscussionModel = new DiscussionModel();
            $DiscussionID = $DiscussionModel->Save($Data);
            if (!$DiscussionID) {
               throw new Exception($DiscussionModel->Validation->ResultsText().print_r($Data, TRUE), 400);
            }
            
            // Send a confirmation email.
            if (C('Plugins.VanillaPop.SendConfirmationEmail')) {
               $Data['DiscussionID'] = $DiscussionID;
               $this->SendConfirmationEmail($Data, $User);
            }
            
            return $DiscussionID;
      }
   }
   
   public function SaveTypeFromRepyTo(&$Data) {
      $Tables = array(
          'Discussion' => array('Comment', 'DiscussionID'),
          'Comment' => array('Comment', 'DiscussionID'),
          'ConversationMessage' => array('Message', 'ConversationID'));
      
      $ReplyTo = trim(GetValue('ReplyTo', $Data));
      if (!$ReplyTo)
         return NULL;
      
      foreach ($Tables as $Name => $Info) {
         $Row = Gdn::SQL()->GetWhere($Name, array('Source' => 'Email', 'SourceID' => $ReplyTo))->FirstRow(DATASET_TYPE_ARRAY);
         if($Row) {
            $Result = $Info[0];
            $Data[$Info[1]] = $Row[$Info[1]];
            $Data['ParentID'] = $Row[$Info[1]];
            return $Result;
         }
      }
      return NULL;
   }
   
   public function SendEmail($To, $Subject, $Body, $Quote = FALSE) {
      $Email = new Gdn_Email();
      $Email->To($To);
      $Email->Subject(sprintf('[%s] %s', C('Garden.Title'), $Subject));
      $From = $Email->PhpMailer->From;
      $Email->PhpMailer->From = self::AddIDToEmail($From, 'noreply');
      
      if (is_array($Quote)) {
         $MessageID = GetValue('MessageID', $Quote);
         if ($MessageID) {
            $Email->PhpMailer->AddCustomHeader("In-Reply-To:$MessageID");
            $Email->PhpMailer->AddCustomHeader("References:$MessageID");
         }
         
         $Subject = GetValue('Subject', $Quote);
         if ($Subject) {
            $Email->Subject(sprintf('Re: [%s] %s', C('Garden.Title'), ltrim(StringBeginsWith($Subject, 'Re:', TRUE, TRUE))));
         }
      }

      $Message = self::FormatEmailBody($Body, FALSE, $Quote);
      
      $Email->Message($Message);
      @$Email->Send();
   }
   
   /**
    * Set the from address to the name of the user that sent the notification.
    * @param Gdn_Email $PhpMailer
    * @param int|array
    */
   public function SetFrom($Email, $User) {
      if (!C('Plugins.VanillaPop.OverrideFrom', TRUE))
         return;
      
      if (is_numeric($User))
         $User = Gdn::UserModel()->GetID($User);
      
      $Email->PhpMailer->FromName = GetValue('Name', $User);
   }
   
   /**
    * Send the initial confirmation email when a discussion is first started through email.
    * @param type $Discussion
    * @param type $User 
    */
   public function SendConfirmationEmail($Discussion, $User) {
      $FormatData = $Discussion;
      $FormatData['Title'] = C('Garden.Title');
      $FormatData['ID'] = $Discussion['DiscussionID'];
      $FormatData['Category'] = CategoryModel::Categories($Discussion['CategoryID']);
      $FormatData['Url'] = ExternalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::Url($Discussion['Name']));
      
      $FormatData['Quote'] = self::FormatQuoteText($FormatData['Body']);
      
      $CanView = Gdn::UserModel()->GetCategoryViewPermission($User['UserID'], GetValue('CategoryID', $Discussion));
      $CanReply = self::CheckUserPermission($User['UserID'], 'Email.Comments.Add');
      $Route = '/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::Url($Discussion['Name']);
      $FormatData['Signature'] = self::EmailSignature($Route, $CanView, $CanReply);
      
      $Email = new Gdn_Email();
      
      $Message = FormatString(C('EmailFormat.ConfirmationBody', self::$FormatDefaults['ConfirmationBody']), $FormatData);
      $Email->Message($Message);

      // We are using the standard confirmation subject because some email clients won't group emails unless their subject are the exact same.
      $Subject = FormatString(C('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $FormatData);
      $Email->Subject($Subject);
      
      $Email->PhpMailer->MessageID = self::UID('Discussion', $Discussion['DiscussionID'], 'email');
      $Email->PhpMailer->From = self::AddIDToEmail($Email->PhpMailer->From, self::UID('Discussion', $Discussion['DiscussionID']));
      $Email->To($User['Email'], $User['Name']);
      
      $ReplyTo = GetValue('SourceID', $Discussion);
      if (isset($ReplyTo)) {
            $Email->PhpMailer->AddCustomHeader("In-Reply-To:$ReplyTo");
            $Email->PhpMailer->AddCustomHeader("References:$ReplyTo");
         }
      
      
      try {
         $Email->Send();
      } catch (Exception $Ex) {
         // Do nothing for now...
         if (Debug())
            throw $Ex;
      }
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::PermissionModel()->Define(array(
         'Email.Discussions.Add' => 'Garden.Profiles.Edit',
         'Email.Comments.Add' => 'Garden.Profiles.Edit',
         'Email.Conversations.Add' => 'Garden.Profiles.Edit'));
      
      Gdn::Structure()
         ->Table('User')
         ->Column('Source', 'varchar(20)', NULL)
         ->Column('SourceID', 'varchar(255)', NULL, 'index')
         ->Set();
      
      Gdn::Structure()
         ->Table('Discussion')
         ->Column('Source', 'varchar(20)', NULL)
         ->Column('SourceID', 'varchar(255)', NULL, 'index')
         ->Set();
      
      Gdn::Structure()
         ->Table('Comment')
         ->Column('Source', 'varchar(20)', NULL)
         ->Column('SourceID', 'varchar(255)', NULL, 'index')
         ->Set();
      
      Gdn::Structure()
         ->Table('ConversationMessage')
         ->Column('Source', 'varchar(20)', NULL)
         ->Column('SourceID', 'varchar(255)', NULL, 'index')
         ->Set();
         
      Gdn::Structure()
         ->Table('Role')
         ->Column('ForceNotify', 'tinyint(1)', '0')
         ->Set();
   }
   
   public static function SimpleForm($Form, $Schema) {
      echo '<ul>';
      foreach ($Schema as $Index => $Row) {
         if (is_string($Row))
            $Row = array('Name' => $Index, 'Control' => $Row);
         
         if (!isset($Row['Name']))
            $Row['Name'] = $Index;
         if (!isset($Row['Options']))
            $Row['Options'] = array();
         
         echo "<li>\n  ";

         $LabelCode = self::LabelCode($Row);
         
         $Description = GetValue('Description', $Row, '');
         if ($Description)
            $Description = '<div class="Info">'.$Description.'</div>';
         
         TouchValue('Control', $Row, 'TextBox');

         switch (strtolower($Row['Control'])) {
            case 'checkbox':
               echo $Description;
               echo $Form->CheckBox($Row['Name'], T($LabelCode));
               break;
            case 'dropdown':
               echo $Form->Label($LabelCode, $Row['Name']);
               echo $Description;
               echo $Form->DropDown($Row['Name'], $Row['Items'], $Row['Options']);
               break;
            case 'radiolist':
               echo $Description;
               echo $Form->RadioList($Row['Name'], $Row['Items'], $Row['Options']);
               break;
            case 'checkboxlist':
               echo $Form->Label($LabelCode, $Row['Name']);
               echo $Description;
               echo $Form->CheckBoxList($Row['Name'], $Row['Items'], NULL, $Row['Options']);
               break;
            case 'textbox':
               echo $Form->Label($LabelCode, $Row['Name']);
               echo $Description;
               echo $Form->TextBox($Row['Name'], $Row['Options']);
               break;
            default:
               echo "Error a control type of {$Row['Control']} is not supported.";
               break;
         }
         echo "\n</li>\n";
      }
      echo '</ul>';
   }
   
   public static function StripSignature($Body) {
      $i = strrpos($Body, "\n--");
      if ($i === FALSE)
         return $Body;
      $j = strpos($Body, "\n", $i + 1);
      if ($j === FALSE)
         return $Body;

      $Delim = trim(substr($Body, $i, $j - $i + 1));
      if (preg_match('`^-+$`', $Delim)) {
         $Body = trim(substr($Body, 0, $i));
      }

      return $Body;
   }
   
   public static function StripEmail($Body) {
      $SigFound = FALSE; 
      $InQuotes = 0;

      $Lines = explode("\n", trim($Body));
      $LastLine = count($Lines);

      for ($i = $LastLine - 1; $i >= 0; $i--) {
         $Line = $Lines[$i];

         if ($InQuotes === 0 && preg_match('`^\s*[>|]`', $Line)) {
            // This is a quote line.
            $LastLine = $i;
         } elseif (!$SigFound && preg_match('`^\s*--`', $Line)) {
            // -- Signature delimiter.
            $LastLine = $i;
            $SigFound = TRUE;
         } elseif (preg_match('`^\s*---.+---\s*$`', $Line)) {
            // This will catch an ------Original Message------ heade
            $LastLine = $i;
            $InQuotes = FALSE;
         } elseif ($InQuotes === 0) {
            if (preg_match('`wrote:\s*$`i', $Line)) {
               // This is the quote line...
               $LastLine = $i;
               $InQuotes = FALSE;
            } elseif (preg_match('`^\s*$`', $Line)) {
               $LastLine = $i;
            } else {
               $InQuotes = FALSE;
            }
         }
      }

      if ($LastLine >= 1) {
         $Lines = array_slice($Lines, 0, $LastLine);
      }
      $Result = trim(implode("\n", $Lines));
      return $Result;
   }
   
   public static function UID($Type, $ID, $Format = FALSE) {
      $TypeKey = GetValue($Type, array_flip(self::$Types), NULL);
      if (!$TypeKey)
         return NULL;
      $UID = $TypeKey.$ID;
      switch (strtolower($Format)) {
         case 'email':
            $UID = '<'.$UID.'@'.Gdn::Request()->Host().'>';
      }
      return $UID;
   }
   
   /// Event Handlers ///
   
   /**
    * @param ActivityModel $Sender
    * @param type $Args
    */
   public function ActivityModel_BeforeSendNotification_Handler($Sender, $Args) {
      if (isset($Args['RecordType']) && isset($Args['RecordID'])) {
         $Type = $Args['RecordType'];
         $ID = $Args['RecordID'];
      } else {
         list($Type, $ID) = self::ParseRoute(GetValue('Route', $Args));
      }
      
      $FormatData = array('Title' => C('Garden.Title'), 'Signature' => self::EmailSignature(GetValue('Route', $Args)));
      $NotifyUserID = GetValueR('Activity.NotifyUserID', $Args);
      
      if (in_array($Type, array('Discussion', 'Comment', 'Conversation', 'Message'))) {
         $Email = $Args['Email']; //new Gdn_Email(); //
         $Story = GetValue('Story', $Args);
         
         switch ($Type) {
            case 'Discussion':
               $DiscussionModel = new DiscussionModel();
               $Discussion = $DiscussionModel->GetID($ID);
               if ($Discussion) {
                  // See if the user has permission to view this discussion on the site.
                  $CanView = Gdn::UserModel()->GetCategoryViewPermission($NotifyUserID, GetValue('CategoryID', $Discussion));
                  $CanReply = self::CheckUserPermission($NotifyUserID, 'Email.Comments.Add');
                  $FormatData['Signature'] = self::EmailSignature(GetValue('Route', $Args), $CanView, $CanReply);
                  
                  $Discussion = (array)$Discussion;
                  $Discussion['Name'] = Gdn_Format::PlainText($Discussion['Name'], 'Text');
                  $Discussion['Body'] = Gdn_Format::PlainText($Discussion['Body'], $Discussion['Format']);
                  $Discussion['Category'] = CategoryModel::Categories($Discussion['CategoryID']);
                  $Discussion['Url'] = ExternalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::Url($Discussion['Name']));
                  $FormatData = array_merge($FormatData, $Discussion);

                  $Message = FormatString(C('EmailFormat.DiscussionBody', self::$FormatDefaults['DiscussionBody']), $FormatData);
                  $Email->Message($Message);

                  $Subject = FormatString(C('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $FormatData);
                  $Email->Subject($Subject);

                  $this->SetFrom($Email, $Discussion['InsertUserID']);
                  $Email->PhpMailer->From = self::AddIDToEmail($Email->PhpMailer->From, self::UID('Discussion', GetValue('DiscussionID', $Discussion)));
               }
               break;
            case 'Comment':
               $CommentModel = new CommentModel();
               $Comment = $CommentModel->GetID($ID, DATASET_TYPE_ARRAY);

               if ($Comment) {
                  $Comment['Body'] = Gdn_Format::PlainText($Comment['Body'], $Comment['Format']);
                  $Comment['Url'] = ExternalUrl(GetValue('Route', $Args));
                  $Comment = array($Comment);
                  Gdn::UserModel()->JoinUsers($Comment, array('InsertUserID', 'UpdateUserID'));
                  $Comment = $Comment[0];
                  
                  if (in_array(GetValueR('Activity.ActivityType', $Args), array('AnswerAccepted'))) {
                     $Comment['Body'] = Gdn_Format::PlainText($Args['Headline'], 'Html')."\n\n".$Comment['Body'];
                  }
                  
                  $FormatData = array_merge($FormatData, $Comment);

                  $this->SetFrom($Email, $Comment['InsertUserID']);

                  $DiscussionModel = new DiscussionModel();
                  $Discussion = (array)$DiscussionModel->GetID($Comment['DiscussionID']);

                  if ($Discussion) {
                     // See if the user has permission to view this discussion on the site.
                     $CanView = Gdn::UserModel()->GetCategoryViewPermission($NotifyUserID, GetValue('CategoryID', $Discussion));
                     $CanReply = self::CheckUserPermission($NotifyUserID, 'Email.Comments.Add');
                     $FormatData['Signature'] = self::EmailSignature(GetValue('Route', $Args), $CanView, $CanReply); //.print_r(array('CanView' => $CanView, 'CanReply' => $CanReply), TRUE);
                     
                     $Discussion['Name'] = Gdn_Format::PlainText($Discussion['Name'], 'Text');
                     $Discussion['Body'] = Gdn_Format::PlainText($Discussion['Body'], $Discussion['Format']);
                     $Discussion['Url'] = ExternalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::Url($Discussion['Name']));
                     $FormatData['Discussion'] = $Discussion;
                     $FormatData['Category'] = CategoryModel::Categories($Discussion['CategoryID']);

                     $Message = FormatString(C('EmailFormat.CommentBody', self::$FormatDefaults['CommentBody']), $FormatData);
                     $Email->Message($Message);

                     $Subject = FormatString(C('EmailFormat.CommentSubject', self::$FormatDefaults['CommentSubject']), $FormatData);
                     $Email->Subject($Subject);

                     $Source = GetValue('Source', $Discussion);
                     if ($Source == 'Email')
                        $ReplyTo = GetValue('SourceID', $Discussion); // replying to an email...
                     else
                        $ReplyTo = self::UID('Discussion', GetValue('DiscussionID', $Discussion), 'email');

                     $Email->PhpMailer->From = self::AddIDToEmail($Email->PhpMailer->From, self::UID('Discussion', GetValue('DiscussionID', $Discussion)));
                  }
               }

               break;
            case 'Message':
               // Get this message.
               $Message = Gdn::SQL()->GetWhere('ConversationMessage', array('MessageID' => $ID))->FirstRow(DATASET_TYPE_ARRAY);
               if ($Message) {
                  $ConversationID = $Message['ConversationID'];
                  $this->SetFrom($Email, $Message['InsertUserID']);

                  // Get the message before this one.
                  $Message2 = Gdn::SQL()
                     ->Select('*')
                     ->From('ConversationMessage')
                     ->Where('ConversationID', $ConversationID)
                     ->Where('MessageID <', $ID)
                     ->OrderBy('MessageID', 'desc')
                     ->Limit(1)
                     ->Get()->FirstRow(DATASET_TYPE_ARRAY);

                  if ($Message2) {
                     if ($Message2['Source'] == 'Email')
                        $ReplyTo = $Message2['SourceID'];
                     else
                        $ReplyTo = self::UID('Message', $Message2['MessageID'], 'email');
                  }

                  $Email->PhpMailer->From = self::AddIDToEmail($Email->PhpMailer->From, self::UID('Message', GetValue('MessageID', $Message)));
               }
               
               // See if the user has permission to view this discussion on the site.
               $CanView = TRUE;
               $CanReply = self::CheckUserPermission($NotifyUserID, 'Email.Conversations.Add');
               $FormatData['Signature'] = self::EmailSignature(GetValue('Route', $Args), $CanView, $CanReply);
               $Message = Gdn_Format::PlainText($Message['Body'], $Message['Format'])."\n\n-- \n".$FormatData['Signature'];
               $Email->Message($Message);

               break;
         }
         if (isset($ReplyTo)) {
            $Email->PhpMailer->AddCustomHeader("In-Reply-To:$ReplyTo");
            $Email->PhpMailer->AddCustomHeader("References:$ReplyTo");
         }
         $Email->PhpMailer->MessageID = self::UID($Type, $ID, 'email');
      }
   }
   
   /**
	 * Adds items to dashboard menu.
	 *
	 * @param object $Sender DashboardController.
	 */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
//      $Menu->AddItem('Pages', T('Pages Settings'), FALSE, array('class' => 'Pages', 'After' => 'Forum'));
      $Menu->AddLink('Site Settings', T('Incoming Email'), '/settings/vanillapop', 'Garden.Settings.Manage', array('After' => 'dashboard/settings/email'));
   }
   
   /**
    * Add notifications.
    */
   public function CommentModel_BeforeNotification_Handler($Sender, $Args) {
      // Make sure the discussion's user is notified if they started the discussion by email.
      if (GetValueR('Discussion.Source', $Args) == 'Email') {
         $NotifiedUsers = (array)GetValue('NotifiedUsers', $Args);
         $InsertUserID = GetValueR('Discussion.InsertUserID', $Args);
         
         // Construct an activity and send it.
         $ActivityModel = $Args['ActivityModel'];
         
         $Comment = $Args['Comment'];
         $CommentID = $Comment['CommentID'];
         $HeadlineFormat = T('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');
         
         $Activity = array(
            'ActivityType' => 'Comment',
            'ActivityUserID' => $Comment['InsertUserID'],
            'NotifyUserID' => $InsertUserID,
            'HeadlineFormat' => $HeadlineFormat,
            'RecordType' => 'Comment',
            'RecordID' => $CommentID,
            'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
            'Data' => array('Name' => GetValue('Name', $Args['Discussion'])),
            'Notified' => ActivityModel::SENT_OK,
            'Emailed' => ActivityModel::SENT_PENDING
         );
         
         $ActivityModel->Queue($Activity, FALSE, array('Force' => TRUE));
      }

      // Notify anyone in a ForceNotify role
      $this->ForceNotify($Sender, $Args);
   }

   /**
    * Add notifications.
    */
   public function DiscussionModel_BeforeNotification_Handler($Sender, $Args) {
      // Notify anyone in a ForceNotify role
      $this->ForceNotify($Sender, $Args);
   }
   
//   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
//      $Attributes = GetValueR('Object.Attributes', $Args);
//      if (is_string($Attributes)) {
//         $Attributes = @unserialize($Attributes);
//      }
//      
//      $Body = GetValueR('Object.Body', $Args);
//      $Format = GetValueR('Object.Format', $Args);
//      $Text = self::FormatPlainText($Body, $Format);
//      
//      $Source = GetValue('Source', $Attributes, FALSE);
//      if (is_array($Source))
//         echo '<pre>'.htmlspecialchars(GetValue("Headers", $Attributes), $Source).'</pre>';
//   }
   
   public function Gdn_Dispatcher_BeforeBlockDetect_Handler($Sender, $Args) {
      $Args['BlockExceptions']['`post/sendgrid(\/.*)?$`'] = Gdn_Dispatcher::BLOCK_NEVER;
   }
   
   public function PostController_Email_Create($Sender, $Args = array()) {
      $this->UtilityController_Email_Create($Sender, $Args);
   }
   
   public function UtilityController_Email_Create($Sender, $Args = array()) {
      if (Gdn::Session()->UserID == 0) {
         Gdn::Session()->Start(Gdn::UserModel()->GetSystemUserID(), FALSE);
         Gdn::Session()->User->Admin = FALSE;
      }
      
      $Sender->Form->InputPrefix = '';
      
      if ($Sender->Form->IsPostBack()) {
         $Data = $Sender->Form->FormValues();
         $Sender->Data['_Status'][] = 'Saving data.';
         if ($this->Save($Data, $Sender)) {
            $Sender->StatusMessage = T('Saved');
         }
      }
      
      $Sender->SetData('Title', T('Post an Email'));
      $Sender->Render('Email', '', 'plugins/VanillaPop');
   }
   
   public function PostController_Sendgrid_Create($Sender, $Args = array()) {
      $this->UtilityController_Sendgrid_Create($Sender, $Args);
   }
   
   /**
    *
    * @param PostController $Sender
    * @param array $Args 
    */
   public function UtilityController_Sendgrid_Create($Sender, $Args = array()) {
      try {
         Gdn::Session()->Start(Gdn::UserModel()->GetSystemUserID(), FALSE);
         Gdn::Session()->User->Admin = FALSE;

         $Sender->Form->InputPrefix = '';

         if ($Sender->Form->IsPostBack()) {
            self::Log("Postback");

            self::Log("Getting post...");
            $Post = $Sender->Form->FormValues();
            self::Log("Post got...");
            $Data = ArrayTranslate($Post, array(
                'from' => 'From',
                'to' => 'To',
                'subject' => 'Subject'
            ));

   //         self::Log('Parsing headers.'.GetValue('headers', $Post, ''));
            $Headers = self::ParseEmailHeader(GetValue('headers', $Post, ''));
   //         self::Log('Headers: '.print_r($Headers, TRUE));
            $Headers = array_change_key_case($Headers);
            $HeaderData = ArrayTranslate($Headers, array('message-id' => 'MessageID', 'references' => 'References', 'in-reply-to' => 'ReplyTo'));
            $Data = array_merge($Data, $HeaderData);

            if (FALSE && GetValue('html', $Post)) {
               $Data['Body'] = $Post['html'];
               $Data['Format'] = 'Html';
            } else {
               $Data['Body'] = $Post['text'];
               $Data['Format'] = 'Html';
            }

            self::Log("Saving data...");
            $Sender->Data['_Status'][] = 'Saving data.';


            if ($this->Save($Data, $Sender)) {
               $Sender->StatusMessage = T('Saved');
            } else {
               throw new Exception('Could not save...', 400);
            }
         }

         $Sender->SetData('Title', T('Sendgrid Proxy'));
         $Sender->Render('Sendgrid', '', 'plugins/VanillaPop');
      } catch (Exception $Ex) {
         $Contents = $Ex->getMessage()."\n"
            .$Ex->getTraceAsString()."\n"
            .print_r($_POST, TRUE);
         file_put_contents(PATH_UPLOADS.'/email/error_'.time().'.txt', $Contents);
         
         throw $Ex;
      }
   }
   
   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_VanillaPop_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      
      if (defined('CLIENT_NAME')) {
         if (StringEndsWith(CLIENT_NAME, '.vanillaforums.com'))
            $IncomingTo = StringEndsWith(CLIENT_NAME, '.vanillaforums.com', TRUE, TRUE);
         else
            $IncomingTo = CLIENT_NAME;
         $Sender->SetData('IncomingAddress', $IncomingTo.'@email.vanillaforums.com');
         if (strpos($IncomingTo, '.') === FALSE)
            $Sender->SetData('CategoryAddress', "categorycode.$IncomingTo@email.vanillaforums.com");
         else
            $Sender->SetData('CategoryAddress', "$IncomingTo+categorycode@email.vanillaforums.com");
      }
      
      $ConfSettings = array(
          'Plugins.VanillaPop.DefaultCategoryID' => array('Control' => 'CategoryDropDown', 'Description' => 'Place discussions started through email in the following category.'),
          'Plugins.VanillaPop.AllowUserRegistration' => array('Control' => 'CheckBox', 'LabelCode' => 'Allow new users to be registered through email.'),
          'Plugins.VanillaPop.AugmentFrom' => array('Control' => 'CheckBox', 'LabelCode' => 'Add information into the from field in email addresses to help with replies (recommended).', 'Default' => TRUE),
          'Garden.Email.SupportAddress' => array('Control' => 'TextBox', 'LabelCode' => 'Outgoing Email Address', 'Description' => 'This is the address that will show up in the from field of emails sent from the application.'),
          'EmailFormat.DiscussionSubject' => array(),
          'EmailFormat.DiscussionBody' => array(),
          'EmailFormat.CommentSubject' => array(),
          'EmailFormat.CommentBody' => array(),
          'Plugins.VanillaPop.SendConfirmationEmail' => array('Control' => 'CheckBox', 'LabelCode' => 'Send a confirmation email when people ask a question or start a discussion over email.'),
          'EmailFormat.ConfirmationBody' => array(),
          'Plugins.VanillaPop.AllowForceNotify' => array('Control' => 'CheckBox', 'LabelCode' => 'Allow roles to be configured to force email notifications to users.'),
          
      );
      
      foreach (self::$FormatDefaults as $Name => $Default) {
         $Options = array();
         if (StringEndsWith($Name, 'Body'))
            $Options['Multiline'] = TRUE;
         $ConfSettings['EmailFormat.'.$Name] = array('Control' => 'TextBox', 'Default' => $Default, 'Options' => $Options);   
      }

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize($ConfSettings);

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Incoming Email'));
      $Sender->ConfigurationModule = $Conf;
//      $Conf->RenderAll();
      $Sender->Render('Settings', '', 'plugins/VanillaPop');
   }

   /**
    * Allow roles to be configured to force email notifications.
    */
   public function Base_BeforeRolePermissions_Handler($Sender) {
      if (!C('Plugins.VanillaPop.AllowForceNotify'))
         return;
      
      $NotifyOptions = array(
         0 => 'Notify these users normally using their preferences (recommended)', 
         1 => 'Notify these users for every new comment and discussion', 
         2 => 'Notify these users for new announcements'
      );
      
      $Sender->Data['_ExtendedFields']['ForceNotify'] = array(
         'LabelCode' => 'Notifications Override',
         'Control' => 'DropDown', 
         'Items' => $NotifyOptions
      );
      
   }
   
   /**
    * Send forced email notifications.
    */
   public function ForceNotify($Sender, $Args) {
      if (!C('Plugins.VanillaPop.AllowForceNotify'))
         return;
      
      $Activity = $Args['Activity'];
      $ActivityModel = $Args['ActivityModel'];
      $ActivityType = (isset($Args['Comment'])) ? 'Comment' : 'Discussion';
      $Fields = $Args[$ActivityType]; 
      
      // Email them.
      $Activity['Emailed'] = ActivityModel::SENT_PENDING;
      
      // Get effected roles.
      $RoleModel = new RoleModel();
      $RoleIDs = array();
      if ($ActivityType == 'Discussion' && GetValue('Announce', $Args['Discussion'])) {
         // Add everyone with force notify all OR announcement-only option.
         $Wheres = array('ForceNotify >' => 0);
      }
      else {
         // Only get users with force notify all. 
         $Wheres = array('ForceNotify' => 1);
      }

      $Roles = $RoleModel->GetWhere($Wheres)->ResultArray();
      foreach ($Roles as $Role) {
         $RoleIDs[] = GetValue('RoleID', $Role);
      }
      
      // Get users in those roles.
      $UserRoles = $Sender->SQL
         ->Select('UserID')
         ->Distinct()
         ->From('UserRole')
         ->WhereIn('RoleID', $RoleIDs)
         ->Get()->ResultArray();      
      
      // Add an activity for each person and pray we don't melt the wibbles.
      foreach ($UserRoles as $UserRole) {
         $Activity['ActivityUserID'] = $UserRole['UserID'];
         $Activity['NotifyUserID'] = $UserRole['UserID'];
         $ActivityModel->Queue($Activity, FALSE, array('Force' => TRUE));
      }
   }
   
}
