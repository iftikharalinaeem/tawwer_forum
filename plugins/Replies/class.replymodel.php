<?php if (!defined('APPLICATION')) exit();

class ReplyModel extends Gdn_Model {
   function __construct($Name = '') {
      parent::__construct('Reply');
   }
   
   function JoinReplies(&$Data) {
      $CommentIDs = ConsolidateArrayValuesByKey($Data, 'CommentID');
      $Replies = $this->GetWhere(array('CommentID' => $CommentIDs))->ResultArray();
      $Replies = Gdn_DataSet::Index($Replies, array('CommentID'), array('Unique' => false));
      
      foreach ($Data as &$Row) {
         $CommentID = GetValue('CommentID', $Row);
         if (isset($Replies[$CommentID])) {
            SetValue('Replies', $Row, $Replies[$CommentID]);
         } else {
            SetValue('Replies', $Row, array());
         }
      }
   }
}