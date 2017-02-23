<?php if (!defined('APPLICATION')) {
    exit();
}
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Reporting2'] = [
    'Name' => 'Reporting',
    'Description' => 'Allows users to report posts to moderators for abuse, terms of service violations etc.',
    'Version' => '2.0.1',
    'RequiredApplications' => ['Vanilla' => '2.1'],
    'SettingsUrl' => '/settings/reporting',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com',
    'MobileFriendly' => true,
    'Icon' => 'reporting.png'
];

class Reporting2Plugin extends Gdn_Plugin {
    /// Methods ///
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
        $CategoryModel = new CategoryModel();
        $Category = $CategoryModel->getWhere(['Type' => 'Reporting'])->firstRow(DATASET_TYPE_ARRAY);

        if (empty($Category)) {
            // Try and get the category by slug.
            $Category = CategoryModel::categories('reported-posts');
            if (!empty($Category)) {
                // Set the reporting type on the category.
                $CategoryModel->setField($Category['CategoryID'], ['Type' => 'Reporting']);
            }
        }

        if (empty($Category)) {
            // Create the category if none exists
            $Row = [
                'Name' => 'Reported Posts',
                'UrlCode' => 'reported-posts',
                'HideAllDiscussions' => 1,
                'DisplayAs' => 'Discussions',
                'Type' => 'Reporting',
                'AllowDiscussions' => 1,
                'Sort' => 1000
            ];
            $CategoryID = $CategoryModel->save($Row);

            // Get RoleIDs for moderator-empowered roles
            $RoleModel = new RoleModel();
            $ModeratorRoles = $RoleModel->getByPermission('Garden.Moderation.Manage');
            $ModeratorRoleIDs = array_column($ModeratorRoles->result(DATASET_TYPE_ARRAY), 'RoleID');

            // Get RoleIDs for roles that can flag
            $AllowedRoles = $RoleModel->getByPermission('Garden.SignIn.Allow');
            $AllowedRoleIDs = array_column($AllowedRoles->result(DATASET_TYPE_ARRAY), 'RoleID');
            // Disallow applicants & unconfirmed by default
            if (($Key = array_search(c('Garden.Registration.ApplicantRoleID'), $AllowedRoleIDs)) !== false) {
                unset($AllowedRoleIDs[$Key]);
            }
            if (($Key = array_search(c('Garden.Registration.ConfirmEmailRole'), $AllowedRoleIDs)) !== false) {
                unset($AllowedRoleIDs[$Key]);
            }

            // Build permissions for the new Category
            $Permissions = [];
            $AllRoles = array_column(RoleModel::roles(), 'RoleID');
            foreach ($AllRoles as $RoleID) {
                $IsModerator = (in_array($RoleID, $ModeratorRoleIDs)) ? 1 : 0;
                $IsAllowed = (in_array($RoleID, $AllowedRoleIDs)) ? 1 : 0;
                $Permissions[] = [
                    'RoleID' => $RoleID,
                    'JunctionTable' => 'Category',
                    'JunctionColumn' => 'PermissionCategoryID',
                    'JunctionID' => $CategoryID,
                    'Vanilla.Discussions.View' => $IsModerator,
                    'Vanilla.Discussions.Add' => $IsAllowed,
                    'Vanilla.Comments.Add' => $IsAllowed
                ];
            }

            // Set category permission & mark it custom
            Gdn::permissionModel()->saveAll($Permissions, ['JunctionID' => $CategoryID, 'JunctionTable' => 'Category']);
            $CategoryModel->setField($CategoryID, 'PermissionCategoryID', $CategoryID);
        }

        // Turn off Flagging & Reporting plugins (upgrade)
        removeFromConfig('EnabledPlugins.Flagging');
        removeFromConfig('EnabledPlugins.Reporting');
    }

    /**
     * Generates the 'Report' button in the Reactions Flag menu.
     *
     * @param $Row
     * @param $RecordType
     * @param $RecordID
     * @return string
     */
    public function reportButton($Row, $RecordType, $RecordID) {
        $Result = anchor(
            '<span class="ReactSprite ReactFlag"></span> '.t('Report'),
            '/report/'.$RecordType.'/'.$RecordID,
            'ReactButton ReactButton-Report Popup',
            ['title' => t('Report'), 'rel' => "nofollow"]
        );
        return $Result;
    }

    /// Controller ///

    /**
     * Set up optional default reasons.
     */
    public function settingsController_Reporting_Create($Sender) {
        $Sender->permission('Garden.Settings.Manage');

        $Conf = new ConfigurationModule($Sender);
        $ConfItems = [
            'Plugins.Reporting2.Reasons' => [
                'Description' => 'Optionally add pre-defined reasons a user must select from to report content. One reason per line.',
                'Options' => ['MultiLine' => true]
            ]
        ];
        $Conf->initialize($ConfItems);

        $Sender->addSideMenu();
        $Sender->setData('Title', 'Reporting Settings');
        $Sender->ConfigurationModule = $Conf;
        $Conf->renderAll();
    }

    /**
     * Handles report actions.
     *
     * @param $Sender
     * @param $RecordType
     * @param $ID
     * @throws Gdn_UserException
     */
    public function rootController_Report_Create($Sender, $RecordType, $ID) {
        if (!Gdn::session()->isValid()) {
            throw new Gdn_UserException(t('You need to sign in before you can do this.'), 403);
        }

        $Sender->Form = new Gdn_Form();
        $ReportModel = new ReportModel();
        $Sender->Form->setModel($ReportModel);

        $Sender->Form->setFormValue('RecordID', $ID);
        $Sender->Form->setFormValue('RecordType', $RecordType);
        $Sender->Form->setFormValue('Format', 'TextEx');

        $Sender->setData('Title', sprintf(t('Report %s'), t($RecordType), 'Report'));

        // Set up data for Reason dropdown
        $Sender->setData('Reasons', false);
        if ($Reasons = c('Plugins.Reporting2.Reasons', false)) {
            $Reasons = explode("\n", $Reasons);
            $Sender->setData('Reasons', array_combine($Reasons, $Reasons));
        }

        // Handle form submission / setup
        if ($Sender->Form->authenticatedPostBack()) {
            // Temporarily disable length limit on comments
            saveToConfig('Vanilla.Comment.MaxLength', 0, false);

            // If optional Reason field is set, prepend it to the Body with labels
            if ($Reason = $Sender->Form->getFormValue('Reason')) {
                $Body = 'Reason: '.$Reason."\n".'Notes: '.$Sender->Form->getFormValue('Body');
                $Sender->Form->setFormValue('Body', $Body);
            }

            if ($Sender->Form->save()) {
                $Sender->informMessage(t('FlagSent', "Your complaint has been registered."));
            }
        } else {
            // Create excerpt to show in form popup
            $Row = getRecord($RecordType, $ID);
            $Row['Body'] = sliceString(Gdn_Format::plainText($Row['Body'], $Row['Format']), 150);
            $Sender->setData('Row', $Row);
        }

        $Sender->render('report', '', 'plugins/Reporting2');
    }

    /// Event Handlers ///

    /**
     * Make sure Reactions' flags are triggered.
     */
    public function base_BeforeFlag_Handler($Sender, $Args) {
        if (Gdn::session()->checkPermission('Garden.SignIn.Allow')) {
            $Args['Flags']['Report'] = [$this, 'ReportButton'];
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
        $reportCount = ReportModel::getUnreadReportCount();
        $reportModifiers = $reportCount > 0 ? ['badge' => $reportCount] : [];
        $reportModifiers['listItemCssClasses'] = ['ReportCategory', 'link-report-category'];
        $dropdown = $args['Dropdown'];
        $dropdown->addLink(htmlspecialchars(t($category['Name'])), categoryUrl($category), 'moderation.report-category', '', [], $reportModifiers);
    }

    /**
     * Adds counter for Reported Posts to MeModule's Dashboard menu.
     */
    public function meModule_BeforeFlyoutMenu_Handler($Sender, $Args) {
        if (checkPermission('Garden.Moderation.Manage')) {
            $Args['DashboardCount'] = $Args['DashboardCount'] + ReportModel::getUnreadReportCount();
        }
    }

}

if (!function_exists('FormatQuote')):

    /**
     * Build our flagged content quote for the new Discussion.
     *
     * @param $Body
     * @return string
     */
    function formatQuote($Body) {
        if (is_object($Body)) {
            $Body = (array)$Body;
        } elseif (is_string($Body)) {
            return $Body;
        }

        $User = Gdn::userModel()->getID(val('InsertUserID', $Body));
        if ($User) {
            $Result = '<blockquote class="Quote UserQuote Media">'.
                '<div class="Img QuoteAuthor">'.userPhoto($User).'</div>'.
                '<div class="Media-Body QuoteText">'.
                '<div>'.userAnchor($User).' - '.Gdn_Format::dateFull($Body['DateInserted'], 'html').'</div>'.
                Gdn_Format::to($Body['Body'], $Body['Format']).
                '</div>'.
                '</blockquote>';
        } else {
            $Result = '<blockquote class="Quote">'.
                Gdn_Format::to($Body['Body'], $Body['Format']).
                '</blockquote>';
        }

        return $Result;
    }

endif;

if (!function_exists('Quote')):

    function quote($Body) {
        return formatQuote($Body);
    }

endif;

if (!function_exists('ReportContext')):
    /**
     * Create a linked sentence about the context of the report.
     *
     * @param $Context array or object being reported.
     * @return string Html message to direct moderators to the content.
     */
    function reportContext($Context) {
        if (is_object($Context)) {
            $Context = (array)$Context;
        }

        if ($ActivityID = val('ActivityID', $Context)) {
            // Point to an activity
            $Type = val('ActivityType', $Context);
            if ($Type == 'Status') {
                // Link to author's wall
                $ContextHtml = sprintf(t('Report Status Context', '%1$s by <a href="%2$s">%3$s</a>'),
                    t('Activity Status', 'Status'),
                    userUrl($Context, 'Activity').'#Activity_'.$ActivityID,
                    Gdn_Format::text($Context['ActivityName'])
                );
            } elseif ($Type == 'WallPost') {
                // Link to recipient's wall
                $ContextHtml = sprintf(t('Report WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'),
                    userUrl($Context, 'Regarding').'#Activity_'.$ActivityID, // Post on recipient's wall
                    t('Activity WallPost', 'Wall Post'),
                    userUrl($Context, 'Activity'), // Author's profile
                    Gdn_Format::text($Context['ActivityName']),
                    userUrl($Context, 'Regarding'), // Recipient's profile
                    Gdn_Format::text($Context['RegardingName'])
                );
            }
        } elseif (val('CommentID', $Context)) {
            // Point to comment & its discussion
            $DiscussionModel = new DiscussionModel();
            $Discussion = (array)$DiscussionModel->getID(val('DiscussionID', $Context));
            $ContextHtml = sprintf(t('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                commentUrl($Context),
                t('Comment'),
                strtolower(t('Discussion')),
                discussionUrl($Discussion),
                Gdn_Format::text($Discussion['Name'])
            );
        } elseif (val('DiscussionID', $Context)) {
            // Point to discussion & its category
            $Category = CategoryModel::categories($Context['CategoryID']);
            $ContextHtml = sprintf(t('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                discussionUrl($Context),
                t('Discussion'),
                strtolower(t('Category')),
                categoryUrl($Category),
                Gdn_Format::text($Category['Name']),
                Gdn_Format::text($Context['Name']) // In case folks want the full discussion name
            );
        } else {
            throw new Exception(t("You cannot report this content."));
        }

        return $ContextHtml;
    }

endif;
