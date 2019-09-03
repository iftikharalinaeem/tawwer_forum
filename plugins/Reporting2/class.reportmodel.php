<?php

use Vanilla\ApiUtils;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Models\UserFragmentSchema;

/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */
class ReportModel extends Gdn_Model {

    /**
     * @var EmbedService $embedService
     */
    private $embedService;

    /** @var FormatService */
    private $formatService;

    /** @var UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param EmbedService $embedService
     * @param FormatService $formatService
     * @param UserModel $userModel
     */
    public function __construct(EmbedService $embedService, FormatService $formatService, UserModel $userModel) {
        parent::__construct('Comment');
        $this->embedService = $embedService;
        $this->formatService = $formatService;
        $this->userModel = $userModel;
    }


    /**
     * Get our special Reported Posts CategoryID.
     *
     * @return bool|mixed
     */
    public static function getReportCategory() {
        $category = Gdn::cache()->get('reporting.category');
        if ($category === Gdn_Cache::CACHEOP_FAILURE) {
            $categoryModel = new CategoryModel();
            $category = $categoryModel->getWhere(['Type' => 'Reporting'])->firstRow(DATASET_TYPE_ARRAY);
            Gdn::cache()->store('reporting.category', $category, [Gdn_Cache::FEATURE_EXPIRY => 300]);
        }

        return $category;
    }

    /**
     * How many unread Reported Posts user has.
     *
     * @deprecated since 2.4, reason: doesn't scale
     *
     * @return int
     */
    public static function getUnreadReportCount() {
        deprecated(__METHOD__);
        return 0;
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
    public function save($data, $settings = false) {
        // Validation and data-setting
        $this->Validation = new Gdn_Validation();
        $this->Validation->applyRule('RecordType', 'ValidateRequired');
        $this->Validation->applyRule('RecordID', 'ValidateRequired');
        $this->Validation->applyRule('Body', 'ValidateRequired');
        $this->Validation->applyRule('Format', 'ValidateRequired');
        
        if (!$this->Validation->validate($data, true)) {
            return false;
        }

        // Get reported content
        $reportedRecord = getRecord($data['RecordType'], $data['RecordID']);
        if (!$reportedRecord) {
            $this->Validation->addValidationResult('RecordID', 'ErrorRecordNotFound');
        }

        $foreignID = strtolower("{$data['RecordType']}-{$data['RecordID']}");

        // Temporarily verify user so they can always submit reports
        setValue('Verified', Gdn::session()->User, true);

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
                $this->Validation->addValidationResult('CategoryID', 'The category used for reporting has not been set up.');
            }

            // Grab the context discussion for the reported record
            if (strcasecmp($data['RecordType'], 'Comment') == 0) {
                $contextDiscussion = (array)$discussionModel->getID(val('DiscussionID', $reportedRecord));
            } else {
                $contextDiscussion = $reportedRecord;
            }

            // Set attributes
            $reportAttributes = [];
            $contextCategoryID = val('CategoryID', $contextDiscussion);
            if ($contextCategoryID) {
                $reportAttributes['CategoryID'] = $contextDiscussion['CategoryID'];
            }

            // All users should be able to report posts.
            Gdn::session()->setPermission(
                'Vanilla.Discussions.Add',
                [$category['CategoryID']]
            );

            // Build report name
            $reportName = sprintf(t('[Reported] %s', "%s"),
                $contextDiscussion['Name'],
                $reportedRecord['InsertName'], // Author Name
                $contextDiscussion['Category']
            );

            if (array_key_exists('Body', $reportedRecord) && array_key_exists('Format', $reportedRecord)) {
                $reportedRecord['Body'] = \Gdn::formatService()->filter($reportedRecord['Body'], $reportedRecord['Format']);
            }
            if (array_key_exists('Body', $data) && array_key_exists('Format', $data)) {
                $data['Body'] = \Gdn::formatService()->filter($data['Body'], $data['Format']);
            }

            $discussionBody = $this->encodeBody($reportedRecord) ?? '';

            // Build discussion record
            $discussion = [
                // Limit new name to 100 char (db column size)
                'Name' => sliceString($reportName, 100),
                'Body' => $discussionBody,
                'Type' => 'Report',
                'ForeignID' => $foreignID,
                'Format' => \Vanilla\Formatting\Formats\RichFormat::FORMAT_KEY,
                'CategoryID' => $category['CategoryID'],
                'Attributes' => ['Report' => $reportAttributes]
            ];

            $this->EventArguments['ReportedRecordType'] = strtolower($data['RecordType']);
            $this->EventArguments['ReportedRecord'] = $reportedRecord;
            $this->EventArguments['Discussion'] = &$discussion;
            $this->fireEvent('BeforeDiscussion');

            $discussionID = $discussionModel->save($discussion);
            if (!$discussionID) {
                trace('Discussion not saved.');
                $this->Validation->addValidationResult($discussionModel->validationResults());
                SpamModel::$Disabled = $spamCheckDisabled;
                return false;
            }
            $discussion['DiscussionID'] = $discussionID;
        } else {
            $discussionID = val('DiscussionID', $discussion);
        }

        if ($discussionID) {
            // Now that we have the discussion add the report.
            $newcommenT = [
                'DiscussionID' => $discussionID,
                'Body' => $data['Body'],
                'Format' => $data['Format'],
                'Attributes' => ['Type' => 'Report']
            ];
            $commentModel = new CommentModel();
            $commentID = $commentModel->save($newcommenT);
            $this->Validation->addValidationResult($commentModel->validationResults());
            SpamModel::$Disabled = $spamCheckDisabled;
            return $commentID;
        }

        // Failed to add report
        SpamModel::$Disabled = $spamCheckDisabled;
        return false;
    }

    /**
     * Encode the record to render and save.
     *
     * @param array $record The record that needs to be processed.
     *
     * @return string Json encoded data for the be rendered in the view and saved.
     */
    public function encodeBody(array $record): string {
        $recordType = "unknown";
        $recordID = -1;
        if (isset($record['DiscussionID'])) {
            $recordType = "discussion";
            $recordID = $record["DiscussionID"];
        } elseif (isset($record["CommentID"])) {
            $recordType = "comment";
            $recordID = $record["CommentID"];
        } elseif (isset($record["ActivityID"])) {
            $recordType = "activity";
            $recordID = $record["ActivityID"];
        }

        $bodyRaw = $record["Body"] ?? "";
        $bodyFormat = $record["Format"] ?? HtmlFormat::FORMAT_KEY;
        $userID = $record['UserID'] ?? $record['ActivityUserID'];
        $userRecord = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        $userRecord = UserFragmentSchema::normalizeUserFragment($userRecord);

        $embed = new QuoteEmbed([
            "embedType" => QuoteEmbed::TYPE,
            "recordType" => $recordType,
            "recordID" => $recordID,
            "body" => $this->formatService->renderQuote($bodyRaw, $bodyFormat),
            "format" => $bodyFormat,
            "bodyRaw" => $bodyRaw,
            "userID" => $userID,
            "insertUser" => $userRecord,
            "url" => $record['Url'],
            "dateInserted" =>  $record["DateInserted"],
        ]);

        $jsonOperations = [
            [
                "insert" => [
                    "embed-external" => [
                        "data" => $embed,
                        ],
                    ],
                ],
            ];

        return json_encode($jsonOperations);
    }
}
