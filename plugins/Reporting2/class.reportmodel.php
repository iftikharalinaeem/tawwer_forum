<?php

if (!defined('APPLICATION'))
    exit();

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
    public static function getReportCategory() {
        $categoryModel = new CategoryModel();
        $category = $categoryModel->GetWhereCache(array('Type' => 'Reporting'));
        if (empty($category)) {
            return false;
        }
        $category = array_pop($category);
        return $category;
    }

    /**
     * How many unread Reported Posts user has.
     *
     * @return int
     */
    public static function getUnreadReportCount() {
        // This methods needs to be optimized.
        return null;

        static $count = null;

        if ($count === null) {
            $category = self::GetReportCategory();
            $discussionModel = new DiscussionModel();
            // Add DiscussionID to shamelessly bypass the faulty cache code
            $count = $discussionModel->getUnreadCount(array('d.CategoryID' => $category['CategoryID'], 'd.DiscussionID >' => 0));
        }

        return $count;
    }

    /**
     * Saves a new content report.
     *
     * @param array $data The data to save. This takes the following fields.
     *  - RecordType: The type of record being reported on.
     *  - RecordID: The id of the record.
     *  - Body: The reason for the report.
     *  - Format: The format of the reason. TextEx is good.
     * @param array|false $settings Not used.
     */
    public function Save($data, $settings = false) {
        // Validation and data-setting
        $this->Validation = new Gdn_Validation();
        $this->Validation->ApplyRule('RecordType', 'ValidateRequired');
        $this->Validation->ApplyRule('RecordID', 'ValidateRequired');
        $this->Validation->ApplyRule('Body', 'ValidateRequired');
        $this->Validation->ApplyRule('Format', 'ValidateRequired');

        touchValue('Format', $data, C('Garden.InputFormatter'));

        if (!$this->Validation->Validate($data, true)) {
            return false;
        }

        // Get reported content
        $reportedRecord = getRecord($data['RecordType'], $data['RecordID']);
        if (!$reportedRecord) {
            $this->Validation->AddValidationResult('RecordID', 'ErrorRecordNotFound');
        }

        $foreignID = strtolower("{$data['RecordType']}-{$data['RecordID']}");

        // Temporarily verify user so they can always submit reports
        setValue('Verified', Gdn::Session()->User, true);

        // Create report discussion
        // Try to find existing report discussion
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getForeignID($foreignID, 'Report');

        $spamCheckDisabled = SpamModel::$Disabled;
        SpamModel::$Disabled = true;

        // Can't find one, must create
        if (!$discussion) {

            // Get category for report discussions
            $category = self::getReportCategory();
            if (!$category) {
                $this->Validation->AddValidationResult('CategoryID', 'The category used for reporting has not been set up.');
            }

            // Grab the context discussion for the reported record
            if (strcasecmp($data['RecordType'], 'Comment') == 0) {
                $contextDiscussion = (array)$discussionModel->getID(val('DiscussionID', $reportedRecord));
            } else {
                $contextDiscussion = $reportedRecord;
            }

            // Set attributes
            $reportAttributes = array();
            $contextCategoryID = val('CategoryID', $contextDiscussion);
            if ($contextCategoryID) {
                $reportAttributes['CategoryID'] = $contextDiscussion['CategoryID'];
            }

            // All users should be able to report posts.
            Gdn::session()->setPermission(
                'Vanilla.Discussions.Add',
                array($category['CategoryID'])
            );

            // Build report name
            $reportName = sprintf(T('[Reported] %s', "%s"),
               $contextDiscussion['Name'],
               $reportedRecord['InsertName'], // Author Name
               $contextDiscussion['Category']
            );

            // Build discussion record
            $discussion = array(
                // Limit new name to 100 char (db column size)
                'Name' => SliceString($reportName , 100),
                'Body' => sprintf(T('Report Body Format', "%s\n\n%s"),
                    formatQuote($reportedRecord),
                    reportContext($reportedRecord)
                ),
                'Type' => 'Report',
                'ForeignID' => $foreignID,
                'Format' => 'Quote',
                'CategoryID' => $category['CategoryID'],
                'Attributes' => array('Report' => $reportAttributes)
            );

            $this->EventArguments['ReportedRecordType'] = strtolower($data['RecordType']);
            $this->EventArguments['ReportedRecord'] = $reportedRecord;
            $this->EventArguments['Discussion'] = &$discussion;
            $this->fireEvent('BeforeDiscussion');

            $discussionID = $discussionModel->save($discussion);
            if (!$discussionID) {
                trace('Discussion not saved.');
                $this->Validation->AddValidationResult($discussionModel->ValidationResults());
                SpamModel::$Disabled = $spamCheckDisabled;
                return false;
            }
            $discussion['DiscussionID'] = $discussionID;
        } else {
            $discussionID = val('DiscussionID', $discussion);
        }

        if ($discussionID) {
            // Now that we have the discussion add the report.
            $newComment = array(
                'DiscussionID' => $discussionID,
                'Body' => $data['Body'],
                'Format' => $data['Format'],
                'Attributes' => array('Type' => 'Report')
            );
            $commentModel = new CommentModel();
            $commentID = $commentModel->save($newComment);
            $this->Validation->AddValidationResult($commentModel->ValidationResults());
            SpamModel::$Disabled = $spamCheckDisabled;
            return $commentID;
        }

        // Failed to add report
        SpamModel::$Disabled = $spamCheckDisabled;
        return false;
    }

}
