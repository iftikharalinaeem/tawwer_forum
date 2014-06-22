<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class ReportModel extends Gdn_Model {

   public function __construct($Name = '') {
      parent::__construct('Comment');
   }

   /**
    * Get our special Reported Posts CategoryID.
    *
    * @return bool|mixed
    */
   public static function GetReportCategory() {
      $CategoryModel = new CategoryModel();
      $Category = $CategoryModel->GetWhereCache(array('Type' => 'Reporting'));
      if (empty($Category)) {
         return FALSE;
      }
      $Category = array_pop($Category);
      return $Category;
   }

   /**
    * How many unread Reported Posts user has.
    *
    * @return int
    */
   public static function GetUnreadReportCount() {
      // This methods needs to be optimized.
      return null;

      static $Count = null;

      if ($Count === null) {
         $Category = self::GetReportCategory();
         $DiscussionModel = new DiscussionModel();
         // Add DiscussionID to shamelessly bypass the faulty cache code
         $Count = $DiscussionModel->GetUnreadCount(array('d.CategoryID' => $Category['CategoryID'], 'd.DiscussionID >' => 0));
      }

      return $Count;
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
      // Validation and data-setting
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

      // Temporarily verify user so they can always submit reports
      SetValue('Verified', Gdn::Session()->User, TRUE);

      // Create report discussion

      // Check to see if there was already a report.
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetForeignID($ForeignID, 'report');

      // Need to create
      if (!$Discussion) {
         $Category = self::GetReportCategory();
         if (!$Category) {
            $this->Validation->AddValidationResult('CategoryID', 'The category used for reporting has not been set up.');
         }

         // Grab the discussion that is being reported.
         if (strcasecmp($Data['RecordType'], 'Comment') == 0) {
            $ReportedDiscussion = (array)$DiscussionModel->GetID(GetValue('DiscussionID', $Record));
         } else {
            $ReportedDiscussion = $Record;
         }

         // Set attributes
         $ReportAttributes = array();
         if ($CategoryID = GetValue('CategoryID', $ReportedDiscussion)) {
            $ReportAttributes['CategoryID'] = $ReportedDiscussion['CategoryID'];
         }

         $Discussion = array(
            'Name' => sprintf(T('[Reported] %s', "%s"),
               $ReportedDiscussion['Name'],
               $ReportedDiscussion['InsertName'],
               $ReportedDiscussion['Category']),
            'Body' => sprintf(T('Report Body Format', "%s\n\n%s"),
               FormatQuote($Record),
               ReportContext($Record)),
            'Type' => 'Report',
            'ForeignID' => $ForeignID,
            'Format' => 'Quote',
            'CategoryID' => $Category['CategoryID'],
            'Attributes' => array('Report' => $ReportAttributes)
         );

         $this->EventArguments['ReportedRecordType'] = strtolower($Data['RecordType']);
         $this->EventArguments['ReportedRecord'] = $Record;
         $this->EventArguments['Discussion'] = &$Discussion;
         $this->fireEvent('BeforeDiscussion');

         $DiscussionID = $DiscussionModel->Save($Discussion);
         if (!$DiscussionID) {
            Trace('Discussion not saved.');
            $this->Validation->AddValidationResult($DiscussionModel->ValidationResults());
            return FALSE;
         }
         $Discussion['DiscussionID'] = $DiscussionID;
      }

      $DiscussionID = GetValue('DiscussionID', $Discussion);

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