<?php if (!defined('APPLICATION')) exit();

class ReplyModel extends Gdn_Model {
   function __construct($name = '') {
      parent::__construct('Reply');
   }
   
   function GetRecord($reply, $getDiscussion = FALSE) {
      if (is_numeric($reply))
         $reply = $this->GetID($reply, DATASET_TYPE_ARRAY);
      
      if (!$reply)
         throw NotFoundException('Reply');
      
      $commentID = $reply['CommentID'];
      
      if ($commentID > 0) {
         $commentModel = new CommentModel();
         $comment = $commentModel->GetID($commentID, DATASET_TYPE_ARRAY);
         
         if (!$comment)
            throw NotFoundException('Comment');
         
         if ($getDiscussion) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->GetID($comment['DiscussionID']);
            
            if (!$discussion)
               throw NotFoundException('Discussion');
            return (array)$discussion;
         }
         return $comment;
      } else {
         $discussionModel = new DiscussionModel();
         $discussion = $discussionModel->GetID(-$commentID);

         if (!$discussion)
            throw NotFoundException('Discussion');
         return (array)$discussion;
      }
   }
   
   function JoinReplies(&$discussion, &$comments) {
      $commentIDs = array_column($comments, 'CommentID');
      
      if ($discussion) {
         $discussionID = GetValue('DiscussionID', $discussion);
         $commentIDs[] = -$discussionID;
      }
      
      $replies = $this->GetWhere(['CommentID' => $commentIDs], 'DateInserted')->ResultArray();
      $replies = Gdn_DataSet::Index($replies, ['CommentID'], ['Unique' => false]);
      
      if ($discussion) {
         if (isset($replies[-$discussionID]))
            SetValue('Replies', $discussion, $replies[-$discussionID]);
         else
            SetValue('Replies', $discussion, []);
      }
      
      // Join to the comments.
      foreach ($comments as &$row) {
         $commentID = GetValue('CommentID', $row);
         if (isset($replies[$commentID])) {
            SetValue('Replies', $row, $replies[$commentID]);
         } else {
            SetValue('Replies', $row, []);
         }
      }
   }
   
   function MoveFromComment($comment, $replyToCommentID) {
      $commentModel = new CommentModel();
      
      if (is_numeric($comment)) {
         $comment = $commentModel->GetID($comment);
      }
      
      $newReply = (array)$comment;
      $newReply['OldCommentID'] = $newReply['CommentID'];
      $newReply['CommentID'] = $replyToCommentID;
      $newReply['Body'] = Gdn_Format::PlainText($newReply['Body'], $newReply['Format']);
      
      // See if the comment had already been made into a reply.
      $replyID = GetValueR('Attributes.OldReplyID', $comment);
      if ($replyID) {
         $reply = $this->GetID($replyID, DATASET_TYPE_ARRAY);
         
         if (!$reply) {
            $newReply['ReplyID'] = $replyID;
         }
      }
      
      $replyID = $this->Insert($newReply);
      if ($replyID) {
         // Move any replies that belonged to this comment.
         $this->SQL->Put('Reply', 
            ['CommentID' => $replyToCommentID],
            ['CommentID' => GetValue('CommentID', $comment)]);
         
         $commentModel->DeleteID(GetValue('CommentID', $comment), ['Log' => FALSE]);
      }
      return $replyID;
   }
   
   function MoveToComment($reply, $discussion = NULL) {
      if (is_numeric($reply))
         $reply = $this->GetID($reply, DATASET_TYPE_ARRAY);
      
      if (!$discussion) {
         $discussion = $this->GetRecord($reply, TRUE);
      }
      
      $commentModel = new CommentModel();
      
      $newComment = $reply;
      unset($newComment['CommentID']);
      $newComment['Format'] = 'Text';
      $newComment['Attributes'] = ['OldReplyID' => $reply['ReplyID']];
      $newComment['DiscussionID'] = GetValue('DiscussionID', $discussion);
      
      // See if this reply had already been made into a comment.
      $commentID = $reply['OldCommentID'];
      if ($commentID) {
         $comment = $commentModel->GetID($commentID, DATASET_TYPE_ARRAY);
         if (!$comment) {
            // We only use the comment if it doesn't exist.
            $newComment['CommentID'] = $commentID;
         }
      }
      
      $commentID = $commentModel->Insert($newComment);
      if ($commentID) {
         $this->Delete(['ReplyID' => $reply['ReplyID']]);
         $commentModel->Save2($commentID, TRUE);
      } else {
         $this->Validation->AddValidationResult($commentModel->ValidationResults());
      }
      return $commentID;
   }
}