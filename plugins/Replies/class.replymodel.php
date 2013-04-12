<?php if (!defined('APPLICATION')) exit();

class ReplyModel extends Gdn_Model {
   function __construct($Name = '') {
      parent::__construct('Reply');
   }
   
   function GetRecord($Reply, $GetDiscussion = FALSE) {
      if (is_numeric($Reply))
         $Reply = $this->GetID($Reply, DATASET_TYPE_ARRAY);
      
      if (!$Reply)
         throw NotFoundException('Reply');
      
      $CommentID = $Reply['CommentID'];
      
      if ($CommentID > 0) {
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);
         
         if (!$Comment)
            throw NotFoundException('Comment');
         
         if ($GetDiscussion) {
            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->GetID($Comment['DiscussionID']);
            
            if (!$Discussion)
               throw NotFoundException('Discussion');
            return (array)$Discussion;
         }
      } else {
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID(-$CommentID);

         if (!$Discussion)
            throw NotFoundException('Discussion');
         return (array)$Discussion;
      }
   }
   
   function JoinReplies(&$Discussion, &$Comments) {
      $CommentIDs = ConsolidateArrayValuesByKey($Comments, 'CommentID');
      
      if ($Discussion) {
         $DiscussionID = GetValue('DiscussionID', $Discussion);
         $CommentIDs[] = -$DiscussionID;
      }
      
      $Replies = $this->GetWhere(array('CommentID' => $CommentIDs))->ResultArray();
      $Replies = Gdn_DataSet::Index($Replies, array('CommentID'), array('Unique' => false));
      
      if ($Discussion) {
         if (isset($Replies[-$DiscussionID]))
            SetValue('Replies', $Discussion, $Replies[-$DiscussionID]);
         else
            SetValue('Replies', $Discussion, array());
      }
      
      // Join to the comments.
      foreach ($Comments as &$Row) {
         $CommentID = GetValue('CommentID', $Row);
         if (isset($Replies[$CommentID])) {
            SetValue('Replies', $Row, $Replies[$CommentID]);
         } else {
            SetValue('Replies', $Row, array());
         }
      }
   }
   
   function MoveFromComment($Comment, $ReplyToCommentID) {
      $CommentModel = new CommentModel();
      
      if (is_numeric($Comment)) {
         $Comment = $CommentModel->GetID($Comment);
      }
      
      $NewReply = (array)$Comment;
      $NewReply['OldCommentID'] = $NewReply['CommentID'];
      $NewReply['CommentID'] = $ReplyToCommentID;
      $NewReply['Body'] = Gdn_Format::PlainText($NewReply['Body'], $NewReply['Format']);
      
      // See if the comment had already been made into a reply.
      $ReplyID = GetValueR('Attributes.OldReplyID', $Comment);
      if ($ReplyID) {
         $Reply = $this->GetID($ReplyID, DATASET_TYPE_ARRAY);
         
         if (!$Reply) {
            $NewReply['ReplyID'] = $ReplyID;
         }
      }
      
      $ReplyID = $this->Insert($NewReply);
      if ($ReplyID) {
         // Move any replies that belonged to this comment.
         $this->SQL->Put('Reply', 
            array('CommentID' => $ReplyToCommentID),
            array('CommentID' => GetValue('CommentID', $Comment)));
         
         $CommentModel->Delete(array('CommentID' => GetValue('CommentID', $Comment)));
      }
      return $ReplyID;
   }
   
   function MoveToComment($Reply, $Discussion = NULL) {
      if (is_numeric($Reply))
         $Reply = $this->GetID($Reply, DATASET_TYPE_ARRAY);
      
      if (!$Discussion) {
         $Discussion = $this->GetRecord($Reply, TRUE);
      }
      
      $CommentModel = new CommentModel();
      
      $NewComment = $Reply;
      unset($NewComment['CommentID']);
      $NewComment['Format'] = 'Text';
      $NewComment['Attributes'] = array('OldReplyID' => $Reply['ReplyID']);
      $NewComment['DiscussionID'] = GetValue('DiscussionID', $Discussion);
      
      // See if this reply had already been made into a comment.
      $CommentID = $Reply['OldCommentID'];
      if ($CommentID) {
         $Comment = $CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);
         if (!$Comment) {
            // We only use the comment if it doesn't exist.
            $NewComment['CommentID'] = $CommentID;
         }
      }
      
      $CommentID = $CommentModel->Insert($NewComment);
      if ($CommentID) {
         $this->Delete(array('ReplyID' => $Reply['ReplyID']));
         $CommentModel->Save2($CommentID, TRUE);
      } else {
         $this->Validation->AddValidationResult($CommentModel->ValidationResults());
      }
      return $CommentID;
   }
}