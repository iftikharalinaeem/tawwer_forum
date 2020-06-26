<?php if (!defined('APPLICATION')) exit();

class ReplyModel extends Gdn_Model {
   function __construct($name = '') {
      parent::__construct('Reply');
   }
   
   function getRecord($reply, $getDiscussion = FALSE) {
      if (is_numeric($reply))
         $reply = $this->getID($reply, DATASET_TYPE_ARRAY);
      
      if (!$reply)
         throw notFoundException('Reply');
      
      $commentID = $reply['CommentID'];
      
      if ($commentID > 0) {
         $commentModel = new CommentModel();
         $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
         
         if (!$comment)
            throw notFoundException('Comment');
         
         if ($getDiscussion) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($comment['DiscussionID']);
            
            if (!$discussion)
               throw notFoundException('Discussion');
            return (array)$discussion;
         }
         return $comment;
      } else {
         $discussionModel = new DiscussionModel();
         $discussion = $discussionModel->getID(-$commentID);

         if (!$discussion)
            throw notFoundException('Discussion');
         return (array)$discussion;
      }
   }
   
   function joinReplies(&$discussion, &$comments) {
      $commentIDs = array_column($comments, 'CommentID');
      
      if ($discussion) {
         $discussionID = getValue('DiscussionID', $discussion);
         $commentIDs[] = -$discussionID;
      }
      
      $replies = $this->getWhere(['CommentID' => $commentIDs], 'DateInserted')->resultArray();
      $replies = Gdn_DataSet::index($replies, ['CommentID'], ['Unique' => false]);
      
      if ($discussion) {
         if (isset($replies[-$discussionID]))
            setValue('Replies', $discussion, $replies[-$discussionID]);
         else
            setValue('Replies', $discussion, []);
      }
      
      // Join to the comments.
      foreach ($comments as &$row) {
         $commentID = getValue('CommentID', $row);
         if (isset($replies[$commentID])) {
            setValue('Replies', $row, $replies[$commentID]);
         } else {
            setValue('Replies', $row, []);
         }
      }
   }
   
   function moveFromComment($comment, $replyToCommentID) {
      $commentModel = new CommentModel();
      
      if (is_numeric($comment)) {
         $comment = $commentModel->getID($comment);
      }
      
      $newReply = (array)$comment;
      $newReply['OldCommentID'] = $newReply['CommentID'];
      $newReply['CommentID'] = $replyToCommentID;
      $newReply['Body'] = Gdn_Format::plainText($newReply['Body'], $newReply['Format']);
      
      // See if the comment had already been made into a reply.
      $replyID = getValueR('Attributes.OldReplyID', $comment);
      if ($replyID) {
         $reply = $this->getID($replyID, DATASET_TYPE_ARRAY);
         
         if (!$reply) {
            $newReply['ReplyID'] = $replyID;
         }
      }
      
      $replyID = $this->insert($newReply);
      if ($replyID) {
         // Move any replies that belonged to this comment.
         $this->SQL->put('Reply', 
            ['CommentID' => $replyToCommentID],
            ['CommentID' => getValue('CommentID', $comment)]);
         
         $commentModel->deleteID(getValue('CommentID', $comment), ['Log' => FALSE]);
      }
      return $replyID;
   }
   
   function moveToComment($reply, $discussion = NULL) {
      if (is_numeric($reply))
         $reply = $this->getID($reply, DATASET_TYPE_ARRAY);
      
      if (!$discussion) {
         $discussion = $this->getRecord($reply, TRUE);
      }
      
      $commentModel = new CommentModel();
      
      $newComment = $reply;
      unset($newComment['CommentID']);
      $newComment['Format'] = 'Text';
      $newComment['Attributes'] = ['OldReplyID' => $reply['ReplyID']];
      $newComment['DiscussionID'] = getValue('DiscussionID', $discussion);
      
      // See if this reply had already been made into a comment.
      $commentID = $reply['OldCommentID'];
      if ($commentID) {
         $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
         if (!$comment) {
            // We only use the comment if it doesn't exist.
            $newComment['CommentID'] = $commentID;
         }
      }
      
      $commentID = $commentModel->insert($newComment);
      if ($commentID) {
         $this->delete(['ReplyID' => $reply['ReplyID']]);
         $commentModel->save2($commentID, TRUE);
      } else {
         $this->Validation->addValidationResult($commentModel->validationResults());
      }
      return $commentID;
   }
}