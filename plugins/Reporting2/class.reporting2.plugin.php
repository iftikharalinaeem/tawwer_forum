<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class Reporting2Plugin extends Gdn_Plugin {
    /// Methods ///

    /** @var ReportModel */
    private $reportModel;

    /**
     * Reporting2Plugin constructor.
     *
     * @param ReportModel $reportModel
     */
    public function __construct(ReportModel $reportModel) {
        parent::__construct();

        $this->reportModel = $reportModel;
    }

    public function setup() {
        $this->structure();
    }

    /**
     * Add a category 'Type' and a special category for reports.
     */
    public function structure() {
        Gdn::structure()->table('Category')
            ->column('Type', 'varchar(20)', true)
            ->set();

        // Try and find the category by type.
        $categoryModel = new CategoryModel();
        $category = $categoryModel->getWhere(['Type' => 'Reporting'])->firstRow(DATASET_TYPE_ARRAY);

        if (empty($category)) {
            // Try and get the category by slug.
            $category = CategoryModel::categories('reported-posts');
            if (!empty($category)) {
                // Set the reporting type on the category.
                $categoryModel->setField($category['CategoryID'], ['Type' => 'Reporting']);
            }
        }

        if (empty($category)) {
            // Create the category if none exists
            $row = [
                'Name' => 'Reported Posts',
                'UrlCode' => 'reported-posts',
                'HideAllDiscussions' => 1,
                'DisplayAs' => 'Discussions',
                'Type' => 'Reporting',
                'CanDelete' => 0,
                'AllowDiscussions' => 1,
                'Sort' => 1000
            ];
            $categoryID = $categoryModel->save($row);

            // Get RoleIDs for moderator-empowered roles
            $roleModel = new RoleModel();
            $moderatorRoles = $roleModel->getByPermission('Garden.Moderation.Manage');
            $moderatorRoleIDs = array_column($moderatorRoles->result(DATASET_TYPE_ARRAY), 'RoleID');

            // Get RoleIDs for roles that can flag
            $allowedRoles = $roleModel->getByPermission('Garden.SignIn.Allow');
            $allowedRoleIDs = array_column($allowedRoles->result(DATASET_TYPE_ARRAY), 'RoleID');
            // Disallow applicants & unconfirmed by default
            if (($key = array_search(c('Garden.Registration.ApplicantRoleID'), $allowedRoleIDs)) !== false) {
                unset($allowedRoleIDs[$key]);
            }
            if (($key = array_search(c('Garden.Registration.ConfirmEmailRole'), $allowedRoleIDs)) !== false) {
                unset($allowedRoleIDs[$key]);
            }

            // Build permissions for the new category
            $permissions = [];
            $allRoles = array_column(RoleModel::roles(), 'RoleID');
            foreach ($allRoles as $roleID) {
                $isModerator = (in_array($roleID, $moderatorRoleIDs)) ? 1 : 0;
                $isAllowed = (in_array($roleID, $allowedRoleIDs)) ? 1 : 0;
                $permissions[] = [
                    'RoleID' => $roleID,
                    'JunctionTable' => 'Category',
                    'JunctionColumn' => 'PermissionCategoryID',
                    'JunctionID' => $categoryID,
                    'Vanilla.Discussions.View' => $isModerator,
                    'Vanilla.Discussions.Add' => $isAllowed,
                    'Vanilla.Comments.Add' => $isAllowed
                ];
            }

            // Set category permission & mark it custom
            Gdn::permissionModel()->saveAll($permissions, ['JunctionID' => $categoryID, 'JunctionTable' => 'Category']);
            $categoryModel->setField($categoryID, 'PermissionCategoryID', $categoryID);
        }

        $category = CategoryModel::categories('reported-posts');
        if ($category && $category['CanDelete'] === 1) {
            $categoryModel->setField($category['CategoryID'], ['CanDelete' => 0]);
        }

        // Turn off Flagging & Reporting plugins (upgrade)
        removeFromConfig('EnabledPlugins.Flagging');
        removeFromConfig('EnabledPlugins.Reporting');
    }

    /**
     * Generates the 'Report' button in the Reactions Flag menu.
     *
     * @param $row
     * @param $recordType
     * @param $recordID
     * @return string
     */
    public function reportButton($row, $recordType, $recordID): string {
        $row = (array)$row;
        $result = anchor(
            '<span class="ReactSprite ReactFlag"></span> '.t('Report'),
            '/report/'.$recordType.'/'.$recordID,
            'ReactButton ReactButton-Report Popup',
            ['title' => t('Report'), 'rel' => "nofollow"]
        );
        return $result;
    }

    /// Controller ///

    /**
     * Set up optional default reasons.
     */
    public function settingsController_reporting_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $conf = new ConfigurationModule($sender);
        $confItems = [
            'Plugins.Reporting2.Reasons' => [
                'Description' => 'Optionally add pre-defined reasons a user must select from to report content. One reason per line.',
                'Options' => ['MultiLine' => true]
            ]
        ];
        $conf->initialize($confItems);

        $sender->addSideMenu();
        $sender->setData('Title', 'Reporting Settings');
        $sender->ConfigurationModule = $conf;
        $conf->renderAll();
    }

    /**
     * Handles report actions.
     *
     * @param $sender
     * @param $recordType
     * @param $iD
     * @throws Gdn_UserException
     */
    public function rootController_report_create($sender, $recordType, $iD) {
        $sender->permission('Reactions.Flag.Add');

        $sender->Form = new Gdn_Form();
        $reportModel = $this->reportModel;
        $sender->Form->setModel($reportModel);

        $sender->Form->setFormValue('RecordID', $iD);
        $sender->Form->setFormValue('RecordType', $recordType);
        $sender->Form->setFormValue('Format', 'TextEx');

        $sender->setData('Title', sprintf(t('Report %s'), t($recordType), 'Report'));

        // Set up data for Reason dropdown
        $sender->setData('Reasons', false);
        if ($reasons = c('Plugins.Reporting2.Reasons', false)) {
            $reasons = explode("\n", $reasons);
            $sender->setData('Reasons', array_combine($reasons, $reasons));
        }

        // Handle form submission / setup
        if ($sender->Form->authenticatedPostBack()) {
            // Temporarily disable length limit on comments
            saveToConfig('Vanilla.Comment.MaxLength', 0, false);

            // If optional Reason field is set, prepend it to the Body with labels
            if ($reason = $sender->Form->getFormValue('Reason')) {
                $body = 'Reason: '.$reason."\n".'Notes: '.$sender->Form->getFormValue('Body');
                $sender->Form->setFormValue('Body', $body);
            }

            if ($sender->Form->save()) {
                $sender->informMessage(t('FlagSent', "Your complaint has been registered."));
            }
        } else {
            // Create excerpt to show in form popup
            $row = getRecord($recordType, $iD);

            $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
            $row = $discussionModel->fixRow($row);


            $quoteHtml = $this->renderQuote($row, $recordType, $iD);
            $sender->setData('quote', $quoteHtml);
        }

        $sender->render('report', '', 'plugins/Reporting2');
    }

    /// Event Handlers ///

    /**
     * Make sure Reactions' flags are triggered.
     */
    public function base_beforeFlag_handler($sender, $args) {
        if (Gdn::session()->checkPermission('Reactions.Flag.Add')) {
            $args['Flags']['Report'] = [$this, 'ReportButton'];
        }
    }

    /**
     * Adds "Reported Posts" to MeModule menu.
     *
     * @param MeModule $sender
     * @param array $args
     */
    public function meModule_flyoutMenu_handler($sender, $args) {
        if (!val('Dropdown', $args, false) || !checkPermission('Garden.Moderation.Manage')) {
            return;
        }
        /** @var DropdownModule $dropdown */
        $category = ReportModel::getReportCategory();
        $reportModifiers['listItemCssClasses'] = ['ReportCategory', 'link-report-category'];
        $dropdown = $args['Dropdown'];
        $dropdown->addLink(htmlspecialchars(t($category['Name'])), categoryUrl($category), 'moderation.report-category', '', [], $reportModifiers);
    }

    /**
     * Adds counter for Reported Posts to MeModule's Dashboard menu.
     */
    public function meModule_beforeFlyoutMenu_handler($sender, $args) {
        if (checkPermission('Garden.Moderation.Manage')) {
            $args['DashboardCount'] = $args['DashboardCount'];
        }
    }

    /**
     * Force report discussion types to be Rich.
     *
     * @param $args
     * @return string|null
     */
    public function discussionModel_inputFormatter_handler($args) {
        if ($args['Type'] === 'Report') {
            return \Vanilla\Formatting\Formats\RichFormat::FORMAT_KEY;
        }
        return null;
    }

    /**
     * Render the Quote html for the view.
     *
     * @param array $record The Record to create a quote from.
     * @param string $recordType The type of the record.
     * @param int $recordID The ID of the record.
     *
     * @return string $quoteHtml The html markup to generate a quote.
     */
    private function renderQuote(array $record, string $recordType, int $recordID): string {
        $reportModel = $this->reportModel;
        $encodeData =  $reportModel->encodeBody($record, $recordType, $recordID);
        $quoteHtml =\Gdn::formatService()->renderHTML($encodeData, \Vanilla\Formatting\Formats\RichFormat::FORMAT_KEY);
        return $quoteHtml;
    }
}

if (!function_exists('formatQuote')):

    /**
     *
     *@param $body the quote body.
     *@deprecated 25 Jan 2018
     */
    function formatQuote($body) {
        deprecated('formatQuote', 'gdn_formatter_quote');
        return gdn_formatter_quote($body);
    }

endif;

if (!function_exists('gdn_formatter_quote')):

    /**
     * Build our flagged content quote for the new discussion.
     *
     * @param $body
     * @return string
     */
    function gdn_formatter_quote($body) {
        if (is_object($body)) {
            $body = (array)$body;
        } elseif (is_string($body)) {
            return $body;
        }

        $user = Gdn::userModel()->getID(val('InsertUserID', $body));

        // Already formatted to plaintext in rootController_report_create.
        $content = Gdn_Format::to($body['Body'], $body['Format']);
        if ($user) {
            $result = '<blockquote class="Quote UserQuote Media">'.
                '<div class="Img QuoteAuthor">'.userPhoto($user).'</div>'.
                '<div class="Media-Body QuoteText userContent">'.
                '<div>'.userAnchor($user).' - '.Gdn_Format::dateFull($body['DateInserted'], 'html').'</div>'.
                $content.
                '</div>'.
                '</blockquote>';
        } else {
            $result = '<blockquote class="Quote">'.
                $content.
                '</blockquote>';
        }

        return $result;
    }

endif;

if (!function_exists('Quote')):

    function quote($body) {
        return formatQuote($body);
    }

endif;

if (!function_exists('ReportContext')):
    /**
     * Create a linked sentence about the context of the report.
     *
     * @param $context array or object being reported.
     * @return string Html message to direct moderators to the content.
     */
    function reportContext($context) {
        if (is_object($context)) {
            $context = (array)$context;
        }

        if ($activityID = val('ActivityID', $context)) {
            // Point to an activity
            $type = val('ActivityType', $context);
            if ($type == 'Status') {
                // Link to author's wall
                $contextHtml = sprintf(t('Report Status Context', '%1$s by <a href="%2$s">%3$s</a>'),
                    t('Activity Status', 'Status'),
                    userUrl($context, 'Activity').'#Activity_'.$activityID,
                    Gdn_Format::text($context['ActivityName'])
                );
            } elseif ($type == 'WallPost') {
                // Link to recipient's wall
                $contextHtml = sprintf(t('Report WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'),
                    userUrl($context, 'Regarding').'#Activity_'.$activityID, // Post on recipient's wall
                    t('Activity WallPost', 'Wall Post'),
                    userUrl($context, 'Activity'), // Author's profile
                    Gdn_Format::text($context['ActivityName']),
                    userUrl($context, 'Regarding'), // Recipient's profile
                    Gdn_Format::text($context['RegardingName'])
                );
            }
        } elseif (val('CommentID', $context)) {
            // Point to comment & its discussion
            $discussionModel = new DiscussionModel();
            $discussion = (array)$discussionModel->getID(val('DiscussionID', $context));
            $contextHtml = sprintf(t('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                commentUrl($context),
                t('Comment'),
                strtolower(t('Discussion')),
                discussionUrl($discussion),
                Gdn_Format::text($discussion['Name'])
            );
        } elseif (val('DiscussionID', $context)) {
            // Point to discussion & its category
            $category = CategoryModel::categories($context['CategoryID']);
            $contextHtml = sprintf(t('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                discussionUrl($context),
                t('Discussion'),
                strtolower(t('Category')),
                categoryUrl($category),
                Gdn_Format::text($category['Name']),
                Gdn_Format::text($context['Name']) // In case folks want the full discussion name
            );
        } else {
            throw new Exception(t("You cannot report this content."));
        }

        return $contextHtml;
    }

endif;
