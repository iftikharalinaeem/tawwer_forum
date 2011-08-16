<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['VanillaPop'] = array(
   'Name' => 'Vanilla Pop',
   'Description' => "Integrates your forum with Vanilla's email service.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.18a3'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class VanillaPopPlugin extends Gdn_Plugin {
   /// Properties ///
   
   /// Methods ///
   
   public static function FormatPlainText($Body, $Format) {
      $Result = Gdn_Format::To($Body, $Format);
      
      if ($Format != 'Text')
         $Result = Gdn_Format::Text($Result, FALSE);
      $Result = html_entity_decode($Result, ENT_QUOTES, 'UTF-8');
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
      }
      
      return array($Type, $ID);
   }
   
   public static function ParseType($Email) {
      if (preg_match('`\+([a-z]+-?[0-9]+)@`', $Email, $Matches)) {
         list($Type, $ID) = self::ParseUID($Matches[1]);
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
      
      if (preg_match('`([a-z]+)-?([0-9]+)`i', $UID, $Matches)) {
         $Type = GetValue($Matches[1], self::$Types, NULL);
         if ($Type)
            $ID = $Matches[2];
         else
            $ID = NULL;
         return array($Type, $ID);
         
      } else {
         return array(NULL, NULL);
      }
   }
   
   protected function Save($Data, $Sender) {
      // Save the email so we know what's going on.
      $Path = PATH_LOCAL_UPLOADS.'/email/'.time().'.txt';
      if (!file_exists(dirname($Path)))
         mkdir(dirname($Path), 0777, TRUE);
      
      $Sender->Data['_Status'][] = "Saving backup to $Path.";
      file_put_contents($Path, print_r($Data, TRUE));
      
      // Save the full post for debugging.
      $Data['Attributes'] = serialize(array('POST' => $_POST));
      
      $Data['Body'] = self::StripEmail($Data['Body']);
      if (!$Data['Body'])
         $Data['Body'] = T('(empty message)');
      
      list($Name, $Email) = self::ParseEmailAddress($Data['From']);
      
      // Check for a category.
      $CategoryID = C('Plugins.VanillaPop.DefaultCategoryID', -1);
      TouchValue('CategoryID', $Data, $CategoryID);
      
      // See if there is a user at the given email.
      $UserModel = new UserModel();
      $User = $UserModel->GetByEmail($Email);
      if (!$User) {
         $Sender->Data['_Status'][] = 'Creating user.';
         $User = array(
             'Name' => $Name,
             'Email' => $Email,
             'Password' => RandomString(10),
             'HashMethod' => 'Email'
             );
         
         $UserID = $UserModel->InsertForBasic($User, FALSE, array('NoConfirmEmail' => 'NoConfirmEmail'));
         
         if (!$UserID) {
            throw new Exception(T('Error creating user.').' '.$UserModel->Validation->ResultsText(), 400);
         }
         
         $User['UserID'] = $UserID;
      } else {
         $Sender->Data['_Status'][] = 'User exists';
         $User = (array)$User;
      }
      Gdn::Session()->Start($User['UserID'], FALSE);
      $Data['InsertUserID'] = $User['UserID'];
      
      $ReplyType = NULL;
      $ReplyID = NULL;
      
      if (GetValue('ReplyTo', $Data)) {
         // See if we are replying to something specifically.
         list($ReplyType, $ReplyID) = self::ParseUID($Data['ReplyTo']);
      }
      
      if (!$ReplyType) {
         // Grab the reply from the to.
         list($ToName, $ToEmail) = self::ParseEmailAddress(GetValue('To', $Data));
//         $Data['Attributes']['POST']['ToEmail'] = $ToEmail;
//         $Data['Attributes']['POST']['ToName'] = $ToName;
         $Data['Body'] .= "\n\nTo: $ToName, $ToEmail";
         list($ReplyType, $ReplyID) = self::ParseType($ToEmail);
//         $Data['Attributes']['POST']['RepyType'] = $ReplyType;
//         $Data['Attributes']['POST']['ReplyID'] = $ReplyID;
         $Data['Body'] .= "\n\nReplyType: $ReplyType, $ReplyID";
      }
      
      if (!$ReplyType && GetValue('ReplyTo', $Data)) {
         // This may be replying to the SourceID rather than the UID.
         $SaveType = $this->SaveTypeFromRepyTo($Data);
      }
      
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
      
      switch ($SaveType) {
         case 'Comment':
            $Sender->Data['_Status'][] = 'Saving comment.';
            $CommentModel = new CommentModel();
            $CommentID = $CommentModel->Save($Data);
            $Sender->Data['_Status'][] = "CommentID: $CommentID";
            $CommentModel->Save2($CommentID, TRUE);
            return $CommentID;
         case 'Message':
            $Sender->Data['_Status'][] = 'Saving message.';
            $MessageModel = new ConversationMessageModel();
            $MessageID = $MessageModel->Save($Data);
            $Sender->Data['_Status'][] = "MessageID: $MessageID";
            return $MessageID;
         default:
            $Sender->Data['_Status'][] = 'Saving discussion.';
            $Data['Name'] = $Data['Subject'];
            $Data['UpdateUserID'] = $Data['InsertUserID'];
            $DiscussionModel = new DiscussionModel();
            $DiscussionID = $DiscussionModel->Save($Data);            
            if (!$DiscussionID) {
               throw new Exception($DiscussionModel->Validation->ResultsText(), 400);
               $Sender->Data['_Status'][] = $DiscussionModel->Validation->Results();
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
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      SaveToConfig(array('Garden.Registration.NameUnique' => FALSE));
      
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
            if (preg_match('`^\s*On.*wrote:\s*$`i', $Line)) {
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
      list($Type, $ID) = self::ParseRoute(GetValue('Route', $Args));
      
      if (in_array($Type, array('Discussion', 'Comment', 'Conversation', 'Message'))) {
         $Email = $Args['Email']; //new Gdn_Email(); //
         $Story = GetValue('Story', $Args);
         
         // Encode the message ID in the from.
         $FromParts = explode('@', $Email->PhpMailer->From, 2);
         if (count($FromParts) == 2) {
            $UID = self::UID($Type, $ID);
            $FromEmail = "{$FromParts[0]}+$UID@{$FromParts[1]}";
            $Email->PhpMailer->From = $FromEmail;
            $Email->PhpMailer->Sender = $FromEmail;
         }
         
         if (GetValueR('Activity.ActivityType', $Args) == 'NewDiscussion') {
            // Format the new discussion notification a bit nicer.
            $Discussion = Gdn::SQL()->GetWhere('Discussion', array('DiscussionID' => $ID))->FirstRow(DATASET_TYPE_ARRAY);
            if ($Discussion) {
               $Args['Headline'] = self::FormatPlainText($Discussion['Name'], 'Text');
               $Story = self::FormatPlainText($Discussion['Body'], $Discussion['Format']);
            }
            
            $Email->Subject(sprintf(T('[%1$s] %2$s'), Gdn::Config('Garden.Title'), $Args['Headline']));
         } else {
            // Add the In-Reply-To field.
            switch ($Type) {
               case 'Comment':
                  $CommentModel = new CommentModel();
                  $Comment = $CommentModel->GetID($ID, DATASET_TYPE_ARRAY);
                  if ($Comment) {
                     $Story = self::FormatPlainText($Comment['Body'], $Comment['Format']);
                     $this->SetFrom($Email, $Comment['InsertUserID']);
                     
                     $DiscussionModel = new DiscussionModel();
                     $Discussion = $DiscussionModel->GetID($Comment['DiscussionID']);
                     
                     if ($Discussion) {
                        $Email->Subject(sprintf(T('Re: [%1$s] %2$s'), Gdn::Config('Garden.Title'), self::FormatPlainText(GetValue('Name', $Discussion), 'Text')));
                        
                        $Source = GetValue('Source', $Discussion);
                        if ($Source == 'Email')
                           $ReplyTo = GetValue('SourceID', $Discussion); // replying to an email...
                        else
                           $ReplyTo = self::UID('Discussion', GetValue('DiscussionID', $Discussion), 'email');
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
                  }
                  
                  break;
            }
            if (isset($ReplyTo)) {
               $Email->PhpMailer->AddCustomHeader("In-Reply-To:$ReplyTo");
               $Email->PhpMailer->AddCustomHeader("References:$ReplyTo");
            }
         }
         
         // Switch the message with the new header/footer.
         $Message = sprintf(
            T($Story == '' ? 'EmailNotificationPop' : 'EmailStoryNotificationPop'),
            GetValue('Headline', $Args),
            ExternalUrl(GetValue('Route', $Args)),
            $Story
         );
         
         $Email->Message($Message);
         $Email->PhpMailer->MessageID = self::UID($Type, $ID, 'email');
      }
   }
   
   
   public function CommentModel_BeforeNotification_Handler($Sender, $Args) {
      // Make sure the discussion's user is notified if they started the discussion by email.
      if (GetValueR('Discussion.Source', $Args) != 'Email') {
         return;
      }
      
      $NotifiedUsers = (array)GetValue('NotifiedUsers', $Args);
      $InsertUserID = GetValueR('Discussion.InsertUserID', $Args);
      if (in_array($InsertUserID, $NotifiedUsers))
         return;
      $CommentUserID = GetValueR('Comment.InsertUserID', $Args);
      if ($CommentUserID == $InsertUserID)
         return;
      
      $ActivityModel = $Args['ActivityModel'];
      $ActivityID = $Sender->RecordActivity($ActivityModel, $Args['Discussion'], $CommentUserID, GetValueR('Comment.CommentID', $Args), 'Force');
      $ActivityModel->QueueNotification($ActivityID, '');
   }
   
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      $Attributes = GetValueR('Object.Attributes', $Args);
      if (is_string($Attributes)) {
         $Attributes = @unserialize($Attributes);
      }
      
      $Body = GetValueR('Object.Body', $Args);
      $Format = GetValueR('Object.Format', $Args);
      $Text = self::FormatPlainText($Body, $Format);
      echo '<pre>'.nl2br(htmlspecialchars($Text)).'</pre>';
      
      
      $Post = GetValue('POST', $Attributes, FALSE);
      if (is_array($Post))
         echo '<pre>'.htmlspecialchars(print_r($Post, TRUE)).'</pre>';
   }
   
   public function PostController_Email_Create($Sender, $Args) {
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
   
   /**
    *
    * @param PostController $Sender
    * @param array $Args 
    */
   public function PostController_Sendgrid_Create($Sender, $Args) {
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
   
   public static function Log($Message) {
//      $Line = Gdn_Format::ToDateTime().' '.$Message."\n";
//      file_put_contents(PATH_UPLOADS.'/email/log.txt', $Line, FILE_APPEND);
   }
}