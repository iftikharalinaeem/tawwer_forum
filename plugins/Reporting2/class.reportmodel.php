<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class ReportModel extends Gdn_Model {
   
   public function __construct($Name = '') {
      parent::__construct('Comment');
   }
   
   /**
    * Saves a new content report.
    * 
    * @param array $Data The data to save. This takes the following fields.
    *  - RecordType: The type of record being reported on.
    *  - RecordID: The id of the record.
    *  - Body: The reason for the report.
    *  - Format: The format of the reason. TextEx is good.
    */
   public function Save($Data) {
      $this->Validation = new Gdn_Validation();
      $this->Validation->ApplyRule('RecordType', 'ValidateRequired');
      $this->Validation->ApplyRule('RecordID', 'ValidateRequired');
      $this->Validation->ApplyRule('Body', 'ValidateRequired');
      $this->Validation->ApplyRule('Format', 'ValidateRequired');
      
      TouchValue('Format', $Data, C('Garden.InputFormatter'));
      
      if (!$this->Validation->Validate($Data, TRUE))
         return FALSE;
      
      $Record = GetRecord($Data['RecordType'], $Data['RecordID']);
      if (!$Record) {
         $this->Validation->AddValidationResult('RecordID', 'ErrorRecordNotFound');
      }
      
      $ForeignID = strtolower("{$Data['RecordType']}-{$Data['RecordID']}");
      
      // Check to see if there was already a report.
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetForeignID($ForeignID, 'report');
      decho($Discussion, 'report discussion');
      if (!$Discussion) {
         $CategoryModel = new CategoryModel();
         $Category = $CategoryModel->GetWhereCache(array('Type' => 'Reporting'));
         if (empty($Category)) {
            $this->Validation->AddValidationResult('CategoryID', 'The categeory used for reporting has not been set up.');
            return FALSE;
         }
         $Category = array_pop($Category);
         
         // Grab the discussion that is being reported.
         if (strcasecmp($Data['RecordType'], 'Discussion') != 0) {
            $ReportDiscussion = (array)$DiscussionModel->GetID(GetValue('DiscussionID', $Record));
         } else {
            $ReportDiscussion = $Record;
         }
         
         $NewDiscussion = array(
            'Name' => $ReportDiscussion['Name'],
            'Body' => FormatQuote($Record),
            'Type' => 'Report',
            'ForeignID' => $ForeignID,
            'Format' => 'Quote',
            'CategoryID' => $Category['CategoryID'],
            'Attributes' => array('Report' => array(
               'CategoryID' => $ReportDiscussion['CategoryID'],
               ))
            );
         
         $DiscussionID = $DiscussionModel->Save($NewDiscussion);
         if (!$DiscussionID) {
            Trace('Discussion not saved.');
            $this->Validation->AddValidationResult($DiscussionModel->ValidationResults());
            return FALSE;
         }
      } else {
         $DiscussionID = GetValue('DiscussionID', $Discussion);
      }
      
      // Now that we have the discussion add the report.
      $NewComment = array(
         'DiscussionID' => $DiscussionID,
         'Body' => $Data['Body'],
         'Format' => $Data['Format'],
         'Attributes' => array('Type' => 'Report')
         );
      $CommentModel = new CommentModel();
      $CommentID = $CommentModel->Save($NewComment);
      $this->Validation->AddValidationResult($CommentModel->ValidationResults());
      return $CommentID;
   }
}