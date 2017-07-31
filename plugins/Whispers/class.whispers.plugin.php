<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class WhispersPlugin extends Gdn_Plugin {
   /// Properties ///
   public $Conversations = NULL;


   /// Methods ///

   public function GetWhispers($discussionID, $comments, $limit, $offset) {
      $firstDate = NULL;
      $lastDate = NULL;

      if (count($comments) > 0) {
         if ($offset > 0) {
            $firstComment = array_shift($comments);
            $firstDate = GetValue('DateInserted', $firstComment);
            array_unshift($comments, $firstComment);
         }

         if (count($comments) < $limit) {
            $lastComment = array_pop($comments);
            array_push($comments, $lastComment);

            $lastCommentID = GetValue('CommentID', $lastComment);

            // We need to grab the comment that is one after the last comment.
            $lastComment = Gdn::SQL()->Limit(1)->GetWhere('Comment', ['DiscussionID' => $discussionID, 'CommentID >' => $lastCommentID])->FirstRow();
            if ($lastComment)
               $lastDate = GetValue('DateInserted', $lastComment);
         }
      }

      // Grab the conversations that are associated with this discussion.
      $sql = Gdn::SQL()
         ->Select('c.ConversationID, c.DateUpdated')
         ->From('Conversation c')
         ->Where('c.DiscussionID', $discussionID);

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
         $sql->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->Where('uc.UserID', Gdn::Session()->UserID);
      }

      $conversations = $sql->Get()->ResultArray();
      $conversations = Gdn_DataSet::Index($conversations, 'ConversationID');

      // Join the participants into the conversations.
      $conversationModel = new ConversationModel();
      $conversationModel->JoinParticipants($conversations);
      $this->Conversations = $conversations;

      $conversationIDs = array_keys($conversations);

      // Grab all messages that are between the first and last dates.
      $sql = Gdn::SQL()
         ->Select('cm.*')
//         ->Select('iu.Name as InsertName, iu.Photo as InsertPhoto')
         ->From('ConversationMessage cm')
//         ->Join('User iu', 'cm.InsertUserID = iu.UserID')
         ->WhereIn('cm.ConversationID', $conversationIDs)
         ->OrderBy('cm.DateInserted');

      if ($firstDate)
         $sql->Where('cm.DateInserted >=', $firstDate);
      if ($lastDate)
         $sql->Where('cm.DateInserted <', $lastDate);

      $whispers = $sql->Get();

      Gdn::UserModel()->JoinUsers($whispers->Result(), ['InsertUserID']);

      // Add dummy comment fields to the whispers.
      $whispersResult =& $whispers->Result();
      foreach ($whispersResult as &$whisper) {
         SetValue('DiscussionID', $whisper, $discussionID);
         SetValue('CommentID', $whisper, 'w'.GetValue('MessageID', $whisper));
         SetValue('Type', $whisper, 'Whisper');
         SetValue('Url', $whisper, '');

         $participants = GetValueR(GetValue('ConversationID', $whisper).'.Participants', $conversations);
         SetValue('Participants', $whisper, $participants);
      }

      return $whispers;
   }

   public function MergeWhispers($comments, $whispers) {
      $result = array_merge($comments, $whispers);
      usort($result, ['WhispersPlugin', '_MergeWhispersSort']);
      return $result;
   }

   protected static function _MergeWhispersSort($a, $b) {
      $dateA = Gdn_Format::ToTimestamp(GetValue('DateInserted', $a));
      $dateB = Gdn_Format::ToTimestamp(GetValue('DateInserted', $b));

      if ($dateA > $dateB)
         return 1;
      elseif ($dateB < $dateB)
         return -1;
      else
         0;
   }

   public function Setup() {
      $this->Structure();
      SaveToConfig('Conversations.Moderation.Allow', TRUE);
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Conversation')
         ->Column('DiscussionID', 'int', NULL, 'index')
         ->Set();
   }

   public function UserRowCompare($a, $b) {
      return strcasecmp($a['Name'], $b['Name']);
   }

   /// Event Handlers ///

   public function CommentModel_AfterGet_Handler($sender, $args) {
      // Grab the whispers associated with this discussion.
      $discussionID = $args['DiscussionID'];
      $comments =& $args['Comments'];
      $commentsResult =& $comments->Result();
      $whispers = $this->GetWhispers($discussionID, $commentsResult, $args['Limit'], $args['Offset']);
      $whispers->DatasetType($comments->DatasetType());

      $commentsResult = $this->MergeWhispers($commentsResult, $whispers->Result());

      // Check to see if the whispers are more recent than the last comment in the discussion so that the discussion will update the watch.
      if (isset(Gdn::Controller()->Discussion)) {
         $discussion =& Gdn::Controller()->Discussion;
         $dateLastComment = Gdn_Format::ToTimestamp(GetValue('DateLastComment', $discussion));

         foreach ($this->Conversations as $conversation) {
            if (Gdn_Format::ToTimestamp($conversation['DateUpdated']) > $dateLastComment) {
               SetValue('DateLastComment', $discussion, $conversation['DateUpdated']);
               $dateLastComment = Gdn_Format::ToTimestamp($conversation['DateUpdated']);
            }
         }
      }
   }

   /**
    * @param Gdn_Controller $Sender
    * @param args $Args
    */
   public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
      $Sender->AddJsFile('whispers.js', 'plugins/Whispers');
      $Sender->AddJsFile('jquery.autogrow.js');
      $Sender->AddJsFile('jquery.autocomplete.js');

      $this->Form = $Sender->Form;
      include $Sender->FetchViewLocation('WhisperForm', '', 'plugins/Whispers');
   }

   public function DiscussionController_CommentInfo_Handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!GetValue('Type', $comment) == 'Whisper')
         return;

      $participants = GetValue('Participants', $comment);
      $conversationID = GetValue('ConversationID', $comment);
      $messageID = GetValue('MessageID', $comment);
      $messageUrl = "/messages/$conversationID#Message_$messageID";

      echo '<div class="Whisper-Info"><b>'.Anchor(T('Private Between'), $messageUrl).'</b>: ';
      $first = TRUE;
      foreach ($participants as $userID => $user) {
         if ($first)
            $first = FALSE;
         else
            echo ', ';

         echo UserAnchor($user);
      }
      echo '</div>';
   }

   public function DiscussionController_BeforeCommentDisplay_Handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!GetValue('Type', $comment) == 'Whisper')
         return;

      $args['CssClass'] = ConcatSep(' ', $args['CssClass'], 'Whisper');
   }

   public function DiscussionController_CommentOptions_Handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!GetValue('Type', $comment) == 'Whisper')
         return;

      $sender->Options = '';
   }

   public function DiscussionsController_AfterCountMeta_Handler($sender, $args) {
      $discussion = GetValue('Discussion', $args);
      if (!$discussion)
         return;

      if ($countWhispers = GetValue('CountWhispers', $discussion)) {
         $str = ' <span class="MItem WhisperCount">'.Plural($countWhispers, '%s whisper', '%s whispers').'</span> ';

         if (GetValue('NewWhispers', $discussion)) {
            $str .= ' <strong class="HasNew HasNew-Whispers">'.T('new').'</strong> ';
         }
         echo $str;
      }
   }

   /**
    * Give moderators the option to put a discussion into 'private' mode.
    *
    * @param DiscussionController $sender
    * @param int $discussionID
    */
   public function DiscussionController_MakeConversation_Create($sender, $discussionID) {
      $sender->Permission('Garden.Moderation.Manage');

      $discussion = $sender->DiscussionModel->GetID($discussionID);
      if (!$discussion)
         throw NotFoundException('Discussion');

      $discussion = (array)$discussion;
      if (!is_array($discussion['Attributes']))
         $discussion['Attributes'] = [];

      $insertUserID = $discussion['InsertUserID'];

      // Get the IDs of everyone that has participated in this conversation.
      $users = Gdn::SQL()
         ->Select('InsertUserID', '', 'UserID')
         ->Distinct(TRUE)
         ->From('Comment')
         ->Where('DiscussionID', $discussion['DiscussionID'])
         ->Get()->ResultArray();

      $users = Gdn_DataSet::Index($users, 'UserID');

      // Make sure the current and discussion users are added.
      if (!isset($users[$insertUserID]))
         $users[$insertUserID] = ['UserID' => $insertUserID];

      if (!isset($users[Gdn::Session()->UserID]))
         $users[Gdn::Session()->UserID] = ['UserID' => Gdn::Session()->UserID];

      Gdn::UserModel()->JoinUsers($users, ['UserID']);
      uasort($users, [$this, 'UserRowCompare']);

      $sender->SetData('Users', $users);
      $sender->SetData('Discussion', $discussion);

      if ($sender->Form->IsPostBack()) {
         $checkedIDs = $sender->Form->GetValue('UserID', []);

         if (empty($checkedIDs)) {
            $sender->Form->AddError('ValidateOneOrMoreArrayItemRequired', 'RecipientUserID');
         } else {
            // Tell the discussion to start a new conversation, but don't start it just yet...
            $discussion['Attributes']['WhisperConversationID'] = TRUE;
            $discussion['Attributes']['WhisperUserIDs'] = $checkedIDs;

            $sender->DiscussionModel->SetProperty($discussion['DiscussionID'], 'Attributes', dbencode($discussion['Attributes']));

            if ($sender->DeliveryType() == DELIVERY_TYPE_ALL) {
               redirectTo($discussion['Url'].'#Form_Comment');
            }
         }
      } else {
         $sender->Form->SetValue('UserID', array_keys($users));
      }

      $sender->SetData('Title', T('Continue in Private'));
      $sender->Render('MakeConversation', '', 'plugins/Whispers');
   }

   /**
    *
    * @param DiscussionController $sender
    * @param int $discussionID
    */
   public function DiscussionController_MakePublic_Create($sender, $tK, $discussionID) {
      if (!Gdn::Session()->ValidateTransientKey($tK))
         throw PermissionException();

      $discussion = $sender->DiscussionModel->GetID($discussionID);
      if (!$discussion)
         throw NotFoundException('Discussion');

      $discussion = (array)$discussion;

      unset(
         $discussion['Attributes']['WhisperConversationID'],
         $discussion['Attributes']['WhisperUserIDs']
         );

      $sender->DiscussionModel->SetProperty($discussionID, 'Attributes', dbencode($discussion['Attributes']));
      redirectTo($discussion['Url'].'#Form_Comment');
   }

   /**
    * @param DiscussionController $sender
    */
   public function DiscussionController_Render_Before($sender, $args) {
      $conversationID = $sender->Data('Discussion.Attributes.WhisperConversationID');
      if (!$conversationID)
         return;

      if ($conversationID === TRUE) {
         $userIDs = $sender->Data('Discussion.Attributes.WhisperUserIDs');
         // Grab the users that are in the conversaton.
         $whisperUsers = [];
         foreach ($userIDs as $userID) {
            $whisperUsers[] = ['UserID' => $userID];
         }
      } else {
         // There is already a conversation so grab its users.
         $whisperUsers = Gdn::SQL()
            ->Select('UserID')
            ->From('UserConversation')
            ->Where('ConversationID', $conversationID)
            ->Where('Deleted', 0)
            ->Get()->ResultArray();
         $userIDs = array_column($whisperUsers, 'UserID');
      }

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage') && !in_array(Gdn::Session()->UserID, $userIDs)) {
         $sender->Data['Discussion']->Closed = TRUE;
         return;
      }

      Gdn::UserModel()->JoinUsers($whisperUsers, ['UserID']);
      $sender->SetData('WhisperUsers', $whisperUsers);
   }

   /**
    * Join message counts into the discussion list.
    * @param DiscussionModel $sender
    * @param array $args
    */
   public function DiscussionModel_AfterAddColumns_Handler($sender, $args) {
      if (!Gdn::Session()->UserID)
         return;

      $data = $args['Data'];
      $result =& $data->Result();

      // Gather the discussion IDs.
      $discusisonIDs = [];

      foreach ($result as $row) {
         $discusisonIDs[] = GetValue('DiscussionID', $row);
      }

      // Grab all of the whispers associated to the discussions being looked at.
      $sql = Gdn::SQL()
         ->Select('c.DiscussionID')
         ->Select('c.CountMessages', 'sum', 'CountMessages')
         ->Select('c.DateUpdated', 'max', 'DateLastMessage')
         ->From('Conversation c')
         ->WhereIn('c.DiscussionID', $discusisonIDs)
         ->GroupBy('c.DiscussionID');

      if (!Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
         $sql->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->Where('uc.UserID', Gdn::Session()->UserID);
      }

      $conversations = $sql->Get()->ResultArray();
      $conversations = Gdn_DataSet::Index($conversations, 'DiscussionID');

      foreach ($result as &$row) {
         $discusisonID = GetValue('DiscussionID', $row);
         $cRow = GetValue($discusisonID, $conversations);

         if (!$cRow)
            continue;

         $dateLastViewed = GetValue('DateLastViewed', $row);
         $dateLastMessage = $cRow['DateLastMessage'];
         $newWhispers = Gdn_Format::ToTimestamp($dateLastViewed) < Gdn_Format::ToTimestamp($dateLastMessage);

         SetValue('CountWhispers', $row, $cRow['CountMessages']);
         SetValue('DateLastWhisper', $row, $dateLastMessage);
         SetValue('NewWhispers', $row, $newWhispers);
      }

   }

   public function MessagesController_BeforeConversation_Handler($Sender, $Args) {
      $DiscussionID = $Sender->Data('Conversation.DiscussionID');
      if (!$DiscussionID)
         return;

      include $Sender->FetchViewLocation('BeforeConversation', '', 'plugins/Whispers');
   }

   public function MessagesController_BeforeConversationMeta_Handler($sender, $args) {
      $discussionID = GetValueR('Conversation.DiscussionID', $args);

      if ($discussionID) {
         echo '<span class="MetaItem Tag Whispers-Tag">'.Anchor(T('Whisper'), "/discussion/$discussionID/x").'</span>';
      }
   }

   /**
    * @param PostController $Sender
    * @param array $Args
    * @return mixed
    */
   public function PostController_Comment_Create($Sender, $Args = []) {
      if ($Sender->Form->IsPostBack()) {
         $Sender->Form->SetModel($Sender->CommentModel);

         // Grab the discussion for use later.
         $DiscussionID = $Sender->Form->GetFormValue('DiscussionID');
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($DiscussionID);

         // Check to see if the discussion is supposed to be in private...
         $WhisperConversationID = GetValueR('Attributes.WhisperConversationID', $Discussion);
         if ($WhisperConversationID === TRUE) {
            // There isn't a conversation so we want to create one.
            $Sender->Form->SetFormValue('Whisper', TRUE);
            $WhisperUserIDs = GetValueR('Attributes.WhisperUserIDs', $Discussion);
            $Sender->Form->SetFormValue('RecipientUserID', $WhisperUserIDs);
         } elseif ($WhisperConversationID) {
            // There is already a conversation.
            $Sender->Form->SetFormValue('Whisper', TRUE);
            $Sender->Form->SetFormValue('ConversationID', $WhisperConversationID);
         }

         $Whisper = $Sender->Form->GetFormValue('Whisper') && GetIncomingValue('Type') != 'Draft';
         $WhisperTo = trim($Sender->Form->GetFormValue('To'));
         $ConversationID = $Sender->Form->GetFormValue('ConversationID');

         // If this isn't a whisper then post as normal.
         if (!$Whisper)
            return call_user_func_array([$Sender, 'Comment'], $Args);

         $ConversationModel = new ConversationModel();
         $ConversationMessageModel = new ConversationMessageModel();

         if ($ConversationID > 0) {
            $Sender->Form->SetModel($ConversationMessageModel);
         } else {
            // We have to remove the blank conversation ID or else the model won't validate.
            $FormValues = $Sender->Form->FormValues();
            unset($FormValues['ConversationID']);
            $FormValues['Subject'] = GetValue('Name', $Discussion);
            $Sender->Form->FormValues($FormValues);

            $Sender->Form->SetModel($ConversationModel);
            $ConversationModel->Validation->ApplyRule('DiscussionID', 'Required');
         }

         $ID = $Sender->Form->Save($ConversationMessageModel);

         if ($Sender->Form->ErrorCount() > 0) {
            $Sender->ErrorMessage($Sender->Form->Errors());
         } else {
            if ($WhisperConversationID === TRUE) {
               $Discussion->Attributes['WhisperConversationID'] = $ID;
               $DiscussionModel->SetProperty($DiscussionID, 'Attributes', dbencode($Discussion->Attributes));
            }

            $LastCommentID = GetValue('LastCommentID', $Discussion);
            $MessageID = GetValue('LastMessageID', $ConversationMessageModel, FALSE);

            // Randomize the querystring to force the browser to refresh.
            $Rand = mt_rand(10000, 99999);

            if ($LastCommentID) {
               // Link to the last comment.
               $HashID = $MessageID ? 'w'.$MessageID : $LastCommentID;

               $Sender->redirecTo(Url("discussion/comment/$LastCommentID?rand=$Rand#Comment_$HashID", TRUE), false);
            } else {
               // Link to the discussion.
               $Hash = $MessageID ? "Comment_w$MessageID" : 'Item_1';
               $Name = rawurlencode(GetValue('Name', $Discussion, 'x'));
               $Sender->setRedirectTo(Url("discussion/$DiscussionID/$Name?rand=$Rand#$Hash", TRUE));
            }
         }
         require_once $Sender->FetchViewLocation('helper_functions', 'Discussion');
         $Sender->Render();
      } else {
         return call_user_func_array([$Sender, 'Comment'], $Args);
      }
   }
}
