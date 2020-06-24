<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class WhispersPlugin extends Gdn_Plugin {
   /// Properties ///
   public $Conversations = NULL;


   /// Methods ///

   public function getWhispers($discussionID, $comments, $limit, $offset) {
      $firstDate = NULL;
      $lastDate = NULL;

      if (count($comments) > 0) {
         if ($offset > 0) {
            $firstComment = array_shift($comments);
            $firstDate = getValue('DateInserted', $firstComment);
            array_unshift($comments, $firstComment);
         }

         if (count($comments) < $limit) {
            $lastComment = array_pop($comments);
            array_push($comments, $lastComment);

            $lastCommentID = getValue('CommentID', $lastComment);

            // We need to grab the comment that is one after the last comment.
            $lastComment = Gdn::sql()->limit(1)->getWhere('Comment', ['DiscussionID' => $discussionID, 'CommentID >' => $lastCommentID])->firstRow();
            if ($lastComment)
               $lastDate = getValue('DateInserted', $lastComment);
         }
      }

      // Grab the conversations that are associated with this discussion.
      $sql = Gdn::sql()
         ->select('c.ConversationID, c.DateUpdated')
         ->from('Conversation c')
         ->where('c.DiscussionID', $discussionID);

      if (!Gdn::session()->checkPermission('Conversations.Moderation.Manage')) {
         $sql->join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->where('uc.UserID', Gdn::session()->UserID);
      }

      $conversations = $sql->get()->resultArray();
      $conversations = Gdn_DataSet::index($conversations, 'ConversationID');

      // Join the participants into the conversations.
      $conversationModel = new ConversationModel();
      $conversationModel->joinParticipants($conversations);
      $this->Conversations = $conversations;

      $conversationIDs = array_keys($conversations);

      // Grab all messages that are between the first and last dates.
      $sql = Gdn::sql()
         ->select('cm.*')
//         ->select('iu.Name as InsertName, iu.Photo as InsertPhoto')
         ->from('ConversationMessage cm')
//         ->join('User iu', 'cm.InsertUserID = iu.UserID')
         ->whereIn('cm.ConversationID', $conversationIDs)
         ->orderBy('cm.DateInserted');

      if ($firstDate)
         $sql->where('cm.DateInserted >=', $firstDate);
      if ($lastDate)
         $sql->where('cm.DateInserted <', $lastDate);

      $whispers = $sql->get();

      Gdn::userModel()->joinUsers($whispers->result(), ['InsertUserID']);

      // Add dummy comment fields to the whispers.
      $whispersResult =& $whispers->result();
      foreach ($whispersResult as &$whisper) {
         setValue('DiscussionID', $whisper, $discussionID);
         setValue('CommentID', $whisper, 'w'.getValue('MessageID', $whisper));
         setValue('Type', $whisper, 'Whisper');
         setValue('Url', $whisper, '');

         $participants = getValueR(getValue('ConversationID', $whisper).'.Participants', $conversations);
         setValue('Participants', $whisper, $participants);
      }

      return $whispers;
   }

   public function mergeWhispers($comments, $whispers) {
      $result = array_merge($comments, $whispers);
      usort($result, ['WhispersPlugin', '_MergeWhispersSort']);
      return $result;
   }

   protected static function _MergeWhispersSort($a, $b) {
      $dateA = Gdn_Format::toTimestamp(getValue('DateInserted', $a));
      $dateB = Gdn_Format::toTimestamp(getValue('DateInserted', $b));

      if ($dateA > $dateB)
         return 1;
      elseif ($dateB < $dateB)
         return -1;
      else
         0;
   }

   public function setup() {
      $this->structure();
      saveToConfig('Conversations.Moderation.Allow', TRUE);
   }

   public function structure() {
      Gdn::structure()
         ->table('Conversation')
         ->column('DiscussionID', 'int', NULL, 'index')
         ->set();
   }

   public function userRowCompare($a, $b) {
      return strcasecmp($a['Name'], $b['Name']);
   }

   /// Event Handlers ///

   public function commentModel_afterGet_handler($sender, $args) {
      // Grab the whispers associated with this discussion.
      $discussionID = $args['DiscussionID'];
      $comments =& $args['Comments'];
      $commentsResult =& $comments->result();
      $whispers = $this->getWhispers($discussionID, $commentsResult, $args['Limit'], $args['Offset']);
      $whispers->datasetType($comments->datasetType());

      $commentsResult = $this->mergeWhispers($commentsResult, $whispers->result());

      // Check to see if the whispers are more recent than the last comment in the discussion so that the discussion will update the watch.
      if (isset(Gdn::controller()->Discussion)) {
         $discussion =& Gdn::controller()->Discussion;
         $dateLastComment = Gdn_Format::toTimestamp(getValue('DateLastComment', $discussion));

         foreach ($this->Conversations as $conversation) {
            if (Gdn_Format::toTimestamp($conversation['DateUpdated']) > $dateLastComment) {
               setValue('DateLastComment', $discussion, $conversation['DateUpdated']);
               $dateLastComment = Gdn_Format::toTimestamp($conversation['DateUpdated']);
            }
         }
      }
   }

   /**
    * @param Gdn_Controller $Sender
    * @param args $Args
    */
   public function discussionController_afterBodyField_handler($Sender, $Args) {
      $Sender->addJsFile('whispers.js', 'plugins/Whispers');
      $Sender->addJsFile('jquery.autogrow.js');
      $Sender->addJsFile('jquery.autocomplete.js');

      $this->Form = $Sender->Form;
      include $Sender->fetchViewLocation('WhisperForm', '', 'plugins/Whispers');
   }

   public function discussionController_commentInfo_handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!getValue('Type', $comment) == 'Whisper')
         return;

      $participants = getValue('Participants', $comment);
      $conversationID = getValue('ConversationID', $comment);
      $messageID = getValue('MessageID', $comment);
      $messageUrl = "/messages/$conversationID#Message_$messageID";

      echo '<div class="Whisper-Info"><b>'.anchor(t('Private Between'), $messageUrl).'</b>: ';
      $first = TRUE;
      foreach ($participants as $userID => $user) {
         if ($first)
            $first = FALSE;
         else
            echo ', ';

         echo userAnchor($user);
      }
      echo '</div>';
   }

   public function discussionController_beforeCommentDisplay_handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!getValue('Type', $comment) == 'Whisper')
         return;

      $args['CssClass'] = concatSep(' ', $args['CssClass'], 'Whisper');
   }

   public function discussionController_commentOptions_handler($sender, $args) {
      if (!isset($args['Comment']))
         return;
      $comment = $args['Comment'];
      if (!getValue('Type', $comment) == 'Whisper')
         return;

      $sender->Options = '';
   }

   public function discussionsController_afterCountMeta_handler($sender, $args) {
      $discussion = getValue('Discussion', $args);
      if (!$discussion)
         return;

      if ($countWhispers = getValue('CountWhispers', $discussion)) {
         $str = ' <span class="MItem WhisperCount">'.plural($countWhispers, '%s whisper', '%s whispers').'</span> ';

         if (getValue('NewWhispers', $discussion)) {
            $str .= ' <strong class="HasNew HasNew-Whispers">'.t('new').'</strong> ';
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
   public function discussionController_makeConversation_create($sender, $discussionID) {
      $sender->permission('Garden.Moderation.Manage');

      $discussion = $sender->DiscussionModel->getID($discussionID);
      if (!$discussion)
         throw notFoundException('Discussion');

      $discussion = (array)$discussion;
      if (!is_array($discussion['Attributes']))
         $discussion['Attributes'] = [];

      $insertUserID = $discussion['InsertUserID'];

      // Get the IDs of everyone that has participated in this conversation.
      $users = Gdn::sql()
         ->select('InsertUserID', '', 'UserID')
         ->distinct(TRUE)
         ->from('Comment')
         ->where('DiscussionID', $discussion['DiscussionID'])
         ->get()->resultArray();

      $users = Gdn_DataSet::index($users, 'UserID');

      // Make sure the current and discussion users are added.
      if (!isset($users[$insertUserID]))
         $users[$insertUserID] = ['UserID' => $insertUserID];

      if (!isset($users[Gdn::session()->UserID]))
         $users[Gdn::session()->UserID] = ['UserID' => Gdn::session()->UserID];

      Gdn::userModel()->joinUsers($users, ['UserID']);
      uasort($users, [$this, 'UserRowCompare']);

      $sender->setData('Users', $users);
      $sender->setData('Discussion', $discussion);

      if ($sender->Form->isPostBack()) {
         $checkedIDs = $sender->Form->getValue('UserID', []);

         if (empty($checkedIDs)) {
            $sender->Form->addError('ValidateOneOrMoreArrayItemRequired', 'RecipientUserID');
         } else {
            // Tell the discussion to start a new conversation, but don't start it just yet...
            $discussion['Attributes']['WhisperConversationID'] = TRUE;
            $discussion['Attributes']['WhisperUserIDs'] = $checkedIDs;

            $sender->DiscussionModel->setProperty($discussion['DiscussionID'], 'Attributes', dbencode($discussion['Attributes']));

            if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
               redirectTo($discussion['Url'].'#Form_Comment');
            }
         }
      } else {
         $sender->Form->setValue('UserID', array_keys($users));
      }

      $sender->setData('Title', t('Continue in Private'));
      $sender->render('MakeConversation', '', 'plugins/Whispers');
   }

   /**
    *
    * @param DiscussionController $sender
    * @param int $discussionID
    */
   public function discussionController_makePublic_create($sender, $tK, $discussionID) {
      if (!Gdn::session()->validateTransientKey($tK))
         throw permissionException();

      $discussion = $sender->DiscussionModel->getID($discussionID);
      if (!$discussion)
         throw notFoundException('Discussion');

      $discussion = (array)$discussion;

      unset(
         $discussion['Attributes']['WhisperConversationID'],
         $discussion['Attributes']['WhisperUserIDs']
         );

      $sender->DiscussionModel->setProperty($discussionID, 'Attributes', dbencode($discussion['Attributes']));
      redirectTo($discussion['Url'].'#Form_Comment');
   }

   /**
    * @param DiscussionController $sender
    */
   public function discussionController_render_before($sender, $args) {
      $conversationID = $sender->data('Discussion.Attributes.WhisperConversationID');
      if (!$conversationID)
         return;

      if ($conversationID === TRUE) {
         $userIDs = $sender->data('Discussion.Attributes.WhisperUserIDs');
         // Grab the users that are in the conversaton.
         $whisperUsers = [];
         foreach ($userIDs as $userID) {
            $whisperUsers[] = ['UserID' => $userID];
         }
      } else {
         // There is already a conversation so grab its users.
         $whisperUsers = Gdn::sql()
            ->select('UserID')
            ->from('UserConversation')
            ->where('ConversationID', $conversationID)
            ->where('Deleted', 0)
            ->get()->resultArray();
         $userIDs = array_column($whisperUsers, 'UserID');
      }

      if (!Gdn::session()->checkPermission('Conversations.Moderation.Manage') && !in_array(Gdn::session()->UserID, $userIDs)) {
         $sender->Data['Discussion']->Closed = TRUE;
         return;
      }

      Gdn::userModel()->joinUsers($whisperUsers, ['UserID']);
      $sender->setData('WhisperUsers', $whisperUsers);
   }

   /**
    * Join message counts into the discussion list.
    * @param DiscussionModel $sender
    * @param array $args
    */
   public function discussionModel_afterAddColumns_handler($sender, $args) {
      if (!Gdn::session()->UserID)
         return;

      $data = $args['Data'];
      $result =& $data->result();

      // Gather the discussion IDs.
      $discusisonIDs = [];

      foreach ($result as $row) {
         $discusisonIDs[] = getValue('DiscussionID', $row);
      }

      // Grab all of the whispers associated to the discussions being looked at.
      $sql = Gdn::sql()
         ->select('c.DiscussionID')
         ->select('c.CountMessages', 'sum', 'CountMessages')
         ->select('c.DateUpdated', 'max', 'DateLastMessage')
         ->from('Conversation c')
         ->whereIn('c.DiscussionID', $discusisonIDs)
         ->groupBy('c.DiscussionID');

      if (!Gdn::session()->checkPermission('Conversations.Moderation.Manage')) {
         $sql->join('UserConversation uc', 'c.ConversationID = uc.ConversationID')
            ->where('uc.UserID', Gdn::session()->UserID);
      }

      $conversations = $sql->get()->resultArray();
      $conversations = Gdn_DataSet::index($conversations, 'DiscussionID');

      foreach ($result as &$row) {
         $discusisonID = getValue('DiscussionID', $row);
         $cRow = getValue($discusisonID, $conversations);

         if (!$cRow)
            continue;

         $dateLastViewed = getValue('DateLastViewed', $row);
         $dateLastMessage = $cRow['DateLastMessage'];
         $newWhispers = Gdn_Format::toTimestamp($dateLastViewed) < Gdn_Format::toTimestamp($dateLastMessage);

         setValue('CountWhispers', $row, $cRow['CountMessages']);
         setValue('DateLastWhisper', $row, $dateLastMessage);
         setValue('NewWhispers', $row, $newWhispers);
      }

   }

   public function messagesController_beforeConversation_handler($Sender, $Args) {
      $DiscussionID = $Sender->data('Conversation.DiscussionID');
      if (!$DiscussionID)
         return;

      include $Sender->fetchViewLocation('BeforeConversation', '', 'plugins/Whispers');
   }

   public function messagesController_beforeConversationMeta_handler($sender, $args) {
      $discussionID = getValueR('Conversation.DiscussionID', $args);

      if ($discussionID) {
         echo '<span class="MetaItem Tag Whispers-Tag">'.anchor(t('Whisper'), "/discussion/$discussionID/x").'</span>';
      }
   }

   /**
    * @param PostController $Sender
    * @param array $Args
    * @return mixed
    */
   public function postController_comment_create($Sender, $Args = []) {
      if ($Sender->Form->isPostBack()) {
         $Sender->Form->setModel($Sender->CommentModel);

         // Grab the discussion for use later.
         $DiscussionID = $Sender->Form->getFormValue('DiscussionID');
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->getID($DiscussionID);

         // Check to see if the discussion is supposed to be in private...
         $WhisperConversationID = getValueR('Attributes.WhisperConversationID', $Discussion);
         if ($WhisperConversationID === TRUE) {
            // There isn't a conversation so we want to create one.
            $Sender->Form->setFormValue('Whisper', TRUE);
            $WhisperUserIDs = getValueR('Attributes.WhisperUserIDs', $Discussion);
            $Sender->Form->setFormValue('RecipientUserID', $WhisperUserIDs);
         } elseif ($WhisperConversationID) {
            // There is already a conversation.
            $Sender->Form->setFormValue('Whisper', TRUE);
            $Sender->Form->setFormValue('ConversationID', $WhisperConversationID);
         }

         $Whisper = $Sender->Form->getFormValue('Whisper') && getIncomingValue('Type') != 'Draft';
         $WhisperTo = trim($Sender->Form->getFormValue('To'));
         $ConversationID = $Sender->Form->getFormValue('ConversationID');

         // If this isn't a whisper then post as normal.
         if (!$Whisper)
            return call_user_func_array([$Sender, 'Comment'], $Args);

         $ConversationModel = new ConversationModel();
         $ConversationMessageModel = new ConversationMessageModel();

         if ($ConversationID > 0) {
            $Sender->Form->setModel($ConversationMessageModel);
         } else {
            // We have to remove the blank conversation ID or else the model won't validate.
            $FormValues = $Sender->Form->formValues();
            unset($FormValues['ConversationID']);
            $FormValues['Subject'] = getValue('Name', $Discussion);
            $Sender->Form->formValues($FormValues);

            $Sender->Form->setModel($ConversationModel);
            $ConversationModel->Validation->applyRule('DiscussionID', 'Required');
         }

         $ID = $Sender->Form->save($ConversationMessageModel);

         if ($Sender->Form->errorCount() > 0) {
            $Sender->errorMessage($Sender->Form->errors());
         } else {
            if ($WhisperConversationID === TRUE) {
               $Discussion->Attributes['WhisperConversationID'] = $ID;
               $DiscussionModel->setProperty($DiscussionID, 'Attributes', dbencode($Discussion->Attributes));
            }

            $LastCommentID = getValue('LastCommentID', $Discussion);
            $MessageID = getValue('LastMessageID', $ConversationMessageModel, FALSE);

            // Randomize the querystring to force the browser to refresh.
            $Rand = mt_rand(10000, 99999);

            if ($LastCommentID) {
               // Link to the last comment.
               $HashID = $MessageID ? 'w'.$MessageID : $LastCommentID;

               $Sender->setRedirectTo(url("discussion/comment/$LastCommentID?rand=$Rand#Comment_$HashID", TRUE), false);
            } else {
               // Link to the discussion.
               $Hash = $MessageID ? "Comment_w$MessageID" : 'Item_1';
               $Name = rawurlencode(getValue('Name', $Discussion, 'x'));
               $Sender->setRedirectTo(url("discussion/$DiscussionID/$Name?rand=$Rand#$Hash", TRUE));
            }
         }
         require_once $Sender->fetchViewLocation('helper_functions', 'Discussion');
         $Sender->render();
      } else {
         return call_user_func_array([$Sender, 'Comment'], $Args);
      }
   }
}
