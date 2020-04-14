<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedDisplayOptions;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\FormatService;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Web\TwigFormWrapper;

/**
 * Plugin that allows moderators to warn users and help police the community.
 *
 * Permissions
 *
 * - Garden.Moderation.Manage
 * - Moderation.UserNotes.View
 * - Moderation.UserNotes.Add
 * - Moderation.Warnings.Add
 */
class Warnings2Plugin extends Gdn_Plugin {

    /** @var FormatService */
    private $formatService;

    /** @var \Vanilla\EmbeddedContent\EmbedService */
    private $embedService;

    /// Properties ///

    public $pageSize = 20;

    /** @var RuleModel $ruleModel */
    private $ruleModel;

    /** @var UserNoteModel $userNoteModel */
    private $userNoteModel;

    /** @var Gdn_Session $session */
    private $session;

    /**
     * @var bool Whether or not to restrict the viewing of warnings on posts.
     */
    public $PublicPostWarnings = false;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link Warnings2Plugin}.
     *
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param UserModel $userModel
     * @param FormatService $formatService
     * @param EmbedService $embedService
     * @param \RuleModel $ruleModel
     * @param UserNoteModel $userNoteModel
     * @param Gdn_Session $session
     */
    public function __construct(
        \DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \UserModel $userModel,
        FormatService $formatService,
        EmbedService $embedService,
        RuleModel $ruleModel,
        UserNoteModel $userNoteModel,
        Gdn_Session $session
    ) {
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->formatService = $formatService;
        $this->embedService = $embedService;
        $this->ruleModel = $ruleModel;
        $this->userNoteModel = $userModel;
        parent::__construct();

        $this->session = $session;
        $this->fireEvent('Init');
    }

    /**
     * {@inheritdoc}
     */
    public function setup() {
        $this->structure();
    }

    /**
     * {@inheritdoc}
     */
    public function structure() {
        require __DIR__ . '/structure.php';

        if (Gdn::addonManager()->isEnabled('Warnings', \Vanilla\Addon::TYPE_ADDON)) {
            Gdn::pluginManager()->disablePlugin('Warnings');
        }
    }

    /**
     * Create a new endpoint on the SettingsController.
     *
     * @param SettingsController $sender Sending controller instance.
     */
    public function settingsController_warnings_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s Settings'), t('Warnings & Notes')));
        $sender->addSideMenu('settings/warnings');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $warningTypeModel = new WarningTypeModel();
        $sender->setData('Warnings', $warningTypeModel->getAll());

        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/Warnings2'));
    }

    /**
     * Return the HTML for a warning reaction button.
     *
     * @param array $row The record to warn the user for.
     * @param string $recordType The type of record to warn the user for.
     * @param integer $recordID The ID of the record to warn the user for.
     *
     * @return string Returns a string of HTML that represents the warning button.
     */
    public function warnButton($row, $recordType, $recordID) {
        $args = [
            'userid' => val('InsertUserID', $row),
            'recordtype' => $recordType,
            'recordid' => $recordID
        ];

        $result = anchor(
            '<span class="ReactSprite ReactWarn"></span> ' . t('Warn'),
            '/profile/warn?' . http_build_query($args),
            'ReactButton ReactButton-Warn Popup',
            ['title' => t('Warn')]
        );
        return $result;
    }

    /// Event Handlers ///

    /**
     * Process expired warning(s) before sign-in if banned.
     *
     * @param EntryController $sender The Event sender.
     * @param array $args Array of arguments.
     */
    public function base_beforeSignIn_handler($sender, $args) {
        if (isset($args['UserID'])) {
            $warningModel = new WarningModel();
            $warningModel->processWarnings($args['UserID']);
        }
    }

    /**
     * Process expired warning(s) after sign-in.
     */
    public function base_afterSignIn_handler() {
        if (Gdn::session()->UserID) {
            $warningModel = new WarningModel();
            $warningModel->processWarnings(Gdn::session()->UserID);
        }
    }

    /**
     * Process warnings when a user visits.
     */
    public function userModel_visit_handler() {
        if (Gdn::session()->UserID) {
            $warningModel = new WarningModel();
            $warningModel->processWarnings(Gdn::session()->UserID);
        }
    }

    /**
     * Hijack ban notifications and add a note instead.
     *
     * @param ActivityModel $sender The activity model sending the ban notification.
     * @param array $args Event arguments.
     */
    public function activityModel_beforeSave_handler($sender, $args) {
        if (!isset($args['Activity'])) {
            return;
        }

        $activity = &$args['Activity'];
        if (!is_array($activity)) {
            return;
        }

        $activityType = strtolower(val('ActivityType', $activity));
        if (strcasecmp($activityType, 'ban') !== 0) {
            return;
        }

        $data = $activity['Data'];
        if (is_string($data)) {
            $data = dbdecode($data);
        }

        $body = val('Story', $activity);
        if (val('Unban', $data)) {
            $type = 'unban';
            if (!$body) {
                $body = t('User was unbanned.');
            }
        } else {
            $type = 'ban';
            if (!$body) {
                $body = t('User was banned.');
            }
        }

        $model = new UserNoteModel();
        $row = [
            'Type' => $type,
            'UserID' => val('ActivityUserID', $activity),
            'Body' => $body,
            'Format' => val('Format', $activity, 'text'),
            'InsertUserID' => val('RegardingUserID', $activity, Gdn::session()->UserID),
        ];
        $model->save($row);

        // Don't save the activity.
        $args['Handled'] = true;
    }

    /**
     * Show if this post triggered a warning to give moderators context.
     *
     * @param object $sender The event sender.
     * @param array $args The event arguments.
     */
    public function base_beforeCommentBody_handler($sender, $args) {
        if (isset($args['Comment'])) {
            $row = $args['Comment'];
        } else {
            $row = $args['Discussion'];
        }

        if (!$this->PublicPostWarnings) {
            // Only show warnings to moderators
            $permissionCheck = !checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);
            if (val('InsertUserID', $row) != Gdn::session()->UserID && $permissionCheck) {
                return;
            }
        }

        $row->Attributes = dbdecode($row->Attributes);
        if (isset($row->Attributes['WarningID']) && $row->Attributes['WarningID']) {
            //Check if warning has been reversed.
            $noteModel = new UserNoteModel();
            $warning = $noteModel->getID($row->Attributes['WarningID']);

            if (!isset($warning['Reversed']) || !$warning['Reversed']) {
                // Make inline warning message link to specific warning text.
                // It will only be readable by the warned user or moderators.
                $wordWarn = 'warned';
                if (!empty($row->Attributes['WarningID'])) {
                    $warningID = $row->Attributes['WarningID'];
                    $wordWarn = '<a href="' . url("profile/viewnote/$warningID") . '" class="Popup">' . $wordWarn . '</a>';
                }
                echo '<div class="DismissMessage Warning">' .
                    sprintf(t('%s was %s for this.'), htmlspecialchars(val('InsertName', $row)), $wordWarn) .
                    '</div>';
            }
        }
    }

    /**
     * Show the warning context for private messages.
     *
     * @param MessagesController $sender The event sender.
     */
    public function messagesController_conversationWarning_handler($sender) {
        $foreignID = $sender->data('Conversation.ForeignID');
        if (!stristr($foreignID, '-')) {
            return;
        }

        list($warningKey, $warningID) = explode('-', $foreignID);
        $warningModel = new WarningModel;
        $warning = $warningModel->getID($warningID);
        if (!$warning) {
            return;
        }

        $rule = [];
        if (isset($warning['RuleID'])) {
            $rule = $this->ruleModel->getID($warning['RuleID']);
        }

        $issuer = Gdn::userModel()->getID($warning['InsertUserID'], DATASET_TYPE_ARRAY);

        $content = "";
        if (!empty($rule['Name']) && !empty($rule['Description'])) {
            $name = htmlspecialchars($rule['Name']);
            $description = htmlspecialchars($rule['Description']);

            $content .= wrap(t('Infringed rule'), 'strong') . ' ' . wrap($name);
            $content .= "<br>";
            $content .= wrap($description, 'p');
        }
        $content .= wrap(t('Moderator'), 'strong') . ' ' . userAnchor($issuer);
        $content .= "<br>";
        $content .= wrap(t('Points'), 'strong') . ' ' . $warning['Points'];

        echo $content;
    }

    /**
     * Show the warning context in the email.
     *
     * @param ActivityModel $sender The event sender.
     * @param array $args Event arguments.
     */
    public function activityModel_beforeSendNotification_handler($sender, $args) {
        if (!isset($args['Email'])) {
            return;
        }
        $request = Gdn::request();
        $path = $request->path();
        if (strpos($path, 'profile/warn') === false) {
            return;
        }

        $recordID = $request->get('recordid');
        $recordType = $request->get('recordtype');
        if (!$recordID || !$recordType) {
            return;
        }

        $recordType = strtolower($recordType);
        if (in_array($recordType, ['comment', 'discussion'])) {
            $modelName = $recordType . 'Model';
            $model = new $modelName();
            $record = $model->getID($recordID);

            if ($record) {
                /**
                 * @var $email Gdn_Email
                 */
                $email = $args['Email'];
                $emailTemplate = $email->getEmailTemplate();
                $message = $emailTemplate->getMessage();

                $quotedRecord = formatQuote($record, false);
                // Transform the HTML to Markdown
                $quotedRecord = strip_tags($quotedRecord, '<blockquote>');

                $i = 0;
                // Replace all blockquotes with no other blockquote as a child, one at the time (starting by the last one)!
                while (preg_match('/\n?<blockquote[^>]*>(?!.*<blockquote[^>]*>)(.+?)<\/blockquote>/is', $quotedRecord, $matches)) {
                    $indented = "\n> " . implode("\n> ", explode("\n", trim($matches[1])));
                    $quotedRecord = str_replace($matches[0], $indented, $quotedRecord);
                    if ($i++ > 1000) {
                        break; // The parsing went wrong :)
                    }
                }
                $quotedRecord = trim($quotedRecord);

                $message .= '<br>' . t('Post that triggered the warning:') . $quotedRecord;
                $emailTemplate->setMessage($message);
            }
        }
    }

    /**
     * Add the warning to the list of flags.
     *
     * @param Gdn_Controller $sender The event sender.
     * @param array $args The event arguments.
     */
    public function base_flags_handler($sender, $args) {
        if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false)) {
            $args['Flags']['warn'] = [$this, 'WarnButton'];
        }
    }

    /**
     * Add Warn option to profile options.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function profileController_beforeProfileOptions_handler($sender, $args) {
        if (!val('EditMode', Gdn::controller())) {
            if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.Add'], false)) {
                $sender->EventArguments['ProfileOptions'][] = [
                    'Text' => t('Add Note'),
                    'Url' => '/profile/note?userid=' . $args['UserID'],
                    'CssClass' => 'Popup UserNoteButton'
                ];
            }
            $checkPermission = Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);

            if ($checkPermission && Gdn::session()->UserID != $sender->EventArguments['UserID']) {
                $sender->EventArguments['ProfileOptions'][] = [
                    'Text' => sprite('SpWarn') . ' ' . t('Warn'),
                    'Url' => '/profile/warn?userid=' . $args['UserID'],
                    'CssClass' => 'Popup WarnButton'
                ];
            }
        }
    }

    /**
     * Add a warn button to a user card.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function profileController_card_render($sender, $args) {
        $userID = $sender->data('Profile.UserID');

        if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false)) {
            $sender->setData('Actions.Warn', [
                'Text' => sprite('SpWarn'),
                'Title' => t('Warn'),
                'Url' => '/profile/warn?userid=' . $userID,
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.Add'], false)) {
            $sender->setData('Actions.Note', [
                'Text' => sprite('SpNote'),
                'Title' => t('Add Note'),
                'Url' => '/profile/note?userid=' . $userID,
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.View'], false)) {
            $sender->setData('Actions.Notes', [
                'Text' => '<span class="Count">notes</span>',
                'Title' => t('Notes & Warnings'),
                'Url' => userUrl($sender->data('Profile'), '', 'notes'),
                'CssClass' => 'Popup'
            ]);
        }

        if (Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.View'], false)) {
            $userAlertModel = new UserAlertModel();
            $alert = $userAlertModel->getID($userID, DATASET_TYPE_ARRAY);
            $sender->setData('Alert', $alert);
        }
    }

    /**
     * Create note delete endpoint.
     *
     * @param ProfileController $sender The event sender.
     * @param int $noteID The ID of the note to delete.
     */
    public function profileController_deleteNote_create($sender, $noteID) {
        $sender->permission(['Garden.Moderation.Manage', 'Moderation.UserNotes.Add'], false);

        $form = new Gdn_Form();

        if ($form->authenticatedPostBack()) {
            // Delete the note.
            $noteModel = new UserNoteModel();
            $noteModel->delete(['UserNoteID' => $noteID]);

            $sender->jsonTarget("#UserNote_{$noteID}", '', 'SlideUp');
        }

        $sender->title(sprintf(t('Delete %s'), t('Note')));
        $sender->render('deletenote', '', 'plugins/Warnings2');
    }

    /**
     * Add punishment CSS to users who are punished.
     *
     * @param UserModel $sender The event sender.
     * @param array $args The event arguments.
     */
    public function userModel_setCalculatedFields_handler($sender, $args) {
        if (val('Banned', $args['User'])) {
            setValue('Punished', $args['User'], 0);
        }
        $punished = val('Punished', $args['User']);
        if ($punished) {
            $cssClass = val('_CssClass', $args['User']);
            $cssClass .= ' Jailed';
            setValue('_CssClass', $args['User'], trim($cssClass));
        }
    }

    /**
     * Render a banner detailing punishment information for a user.
     *
     * @param ProfileController $sender The event sender.
     * @param array $args The event arguments.
     */
    public function profileController_beforeUserInfo_handler($sender, $args) {
        echo Gdn_Theme::module('UserWarningModule');
        return;

        if (!Gdn::controller()->data('Profile.Punished')) {
            return;
        }

        echo '<div class="Hero Hero-Jailed Message">';

        echo '<b>';
        if (Gdn::controller()->data('Profile.UserID') == Gdn::session()->UserID) {
            echo t("You've been Jailed.");
        } else {
            echo sprintf(t("%s has been Jailed."), htmlspecialchars(Gdn::controller()->data('Profile.Name')));
        }
        echo '</b>';

        echo "<ul>";
        echo wrap(t("Can't post discussions.") . "\n", 'li');
        echo wrap(t("Can't post as often.") . "\n", 'li');
        echo wrap(t("Signature hidden.") . "\n", 'li');
        echo "</ul>";

        echo '</div>';
    }

    /**
     * Create endpoint for writing notes.
     *
     * @param ProfileController $sender The controller being attached to.
     * @param int ? $userID The ID of the user to create the note for.
     * @param int ? $noteID The ID of the note to edit.
     */
    public function profileController_note_create($sender, $userID = null, $noteID = null) {
        $sender->permission(['Garden.Moderation.Manage', 'Moderation.UserNotes.Add'], false);

        $model = new UserNoteModel();

        if ($noteID) {
            $note = $model->getID($noteID);
            if (!$note) {
                throw notFoundException('Note');
            }

            $userID = $note['UserID'];
            $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            if (!$user) {
                throw notFoundException('User');
            }
        } elseif ($userID) {
            $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
            if (!$user) {
                throw notFoundException('User');
            }
        } else {
            throw new Gdn_UserException('User or note id is required');
        }

        $form = new Gdn_Form();
        $sender->Form = $form;

        if ($form->authenticatedPostBack()) {
            $form->setModel($model);

            $form->setFormValue('Type', 'note');

            if (!$noteID) {
                $form->setFormValue('UserID', $userID);
            } else {
                $form->setFormValue('UserNoteID', $noteID);
            }

            if ($form->save()) {
                $sender->informMessage(t('Your note was added.'));
                $sender->jsonTarget('', '', 'Refresh');
            }
        } else {
            if (isset($note)) {
                $form->setData($note);
            }
        }

        $sender->setData('Profile', $user);
        $sender->setData('Title', $noteID ? t('Edit Note') : t('Add Note'));
        $sender->render('note', '', 'plugins/Warnings2');
    }

    /**
     * An endpoint for reversing a warning.
     *
     * @param ProfileController $sender The controller the endpoint is attached to.
     * @param int $id The ID of the warning to reverse.
     */
    public function profileController_reverseWarning_create($sender, $id) {
        $sender->permission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);

        $form = new Gdn_Form();

        if ($form->authenticatedPostBack()) {
            // Delete the note.
            $warningModel = new WarningModel();
            $warningModel->reverse($id);

//         $Sender->jsonTarget("#UserNote_{$ID}", '', 'SlideUp');
            $sender->jsonTarget('', '', 'Refresh');
        }

        $sender->title(sprintf(t('Reverse %s'), t('Warning')));
        $sender->render('reversewarning', '', 'plugins/Warnings2');
    }

    /**
     * Add the warnings CSS to the application.
     *
     * @param AssetModel $sender The event sender.
     */
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('warnings.css', 'plugins/Warnings2');
    }

    /**
     * Process a user's jailed status on startup.
     */
    public function gdn_dispatcher_appStartup_handler() {
        if (!Gdn::session()->UserID || !val('Punished', Gdn::session()->User)) {
            return;
        }

        // The user has been punished so strip some abilities.
        Gdn::session()->setPermission('Vanilla.Discussions.Add', []);

        // Reduce posting speed to 1 per 150 sec
        saveToConfig([
            'Vanilla.Comment.SpamCount' => 0,
            'Vanilla.Comment.SpamTime' => 150,
            'Vanilla.Comment.SpamLock' => 150
        ], null, false);
    }

    /**
     * Add a profile tab to view a user's notes.
     *
     * @param ProfileController $sender The event sender.
     */
    public function profileController_addProfileTabs_handler($sender) {
        $isPrivileged = Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);

        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$isPrivileged && Gdn::session()->UserID != valr('User.UserID', $sender)) {
            return;
        }

        $class = 'UserNotes';
        if (strcasecmp($sender->RequestMethod, 'notes') === 0) {
            $class .= ' Active';
        }
        $sender->addProfileTab(t('Moderation'), userUrl($sender->User, '', 'notes'), $class);
    }

    /**
     * Add a link to a user's notes in the nav module.
     *
     * @param SiteNavModule $sender The event sender.
     */
    public function siteNavModule_init_handler($sender) {
        $isPrivileged = Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);
        // We can choose to allow regular users to see warnings or not. Default not.
        if (!$isPrivileged && Gdn::session()->UserID != valr('User.UserID', $sender)) {
            return;
        }
        $user = Gdn::controller()->data('Profile');
        $sender->addLinkToSection('Profile', t('Notes'), userUrl($user, '', 'notes'), 'main.notes', '', ['sort' => 100], ['icon' => 'edit']);
    }

    /**
     *
     * @param ProfileController $sender
     * @param mixed $userReference
     * @param string $username
     * @param string $page
     */
    public function profileController_notes_create($sender, $userReference, $username = '', $page = '') {
        $sender->editMode(false);
        $sender->getUserInfo($userReference, $username);

        $isPrivileged = Gdn::session()->checkPermission(['Garden.Moderation.Manage', 'Moderation.UserNotes.View'], false);

        $sender->setData('IsPrivileged', $isPrivileged);

        // Users should only be able to see their own warnings
        if (!$isPrivileged && Gdn::session()->UserID != val('UserID', $sender->User)) {
            throw permissionException('Garden.Moderation.Manage');
        }

        $sender->_setBreadcrumbs(t('Notes'), userUrl($sender->User, '', 'notes'));
        $sender->setTabView('Notes', 'Notes', '', 'plugins/Warnings2');

        list($offset, $limit) = offsetLimit($page, $this->pageSize);

        $userNoteModel = new UserNoteModel();
        $where = ['UserID' => $sender->User->UserID];
        if (!$isPrivileged) {
            $where['Type'] = 'warning';
        }
        $notes = $userNoteModel->getWhere(
            $where,
            'DateInserted',
            'desc',
            $limit,
            $offset
        )->resultArray();
        $userNoteModel->calculate($notes);

        // Join the user records into the warnings
        joinRecords($notes, 'Record', false, true);

        // If HideWarnerIdentity is true, do not let view render that data.
        $warningModel = new WarningModel();
        $hideWarnerIdentity = $warningModel->HideWarnerIdentity;
        array_walk($notes, function (&$value, $key) use ($hideWarnerIdentity) {
            $value['HideWarnerIdentity'] = $hideWarnerIdentity;
        });

        PagerModule::$DefaultPageSize = $this->pageSize;

        $sender->setData('Notes', $notes);

        $sender->render();
    }

    /**
     * View individual note for given user.
     *
     * @param ProfileController $sender
     * @param $noteID
     */
    public function profileController_viewNote_create($sender, $noteID) {
        $userNoteModel = new UserNoteModel();
        $note = $userNoteModel->getID($noteID);

        $userID = (count($note) && !empty($note['UserID']))
            ? $note['UserID']
            : null;

        if (!$userID || !count($note)) {
            throw notFoundException('Warning');
        }

        $sender->editMode(false);

        $sender->getUserInfo($userID, '', $userID);

        $isPrivileged = Gdn::session()->checkPermission(
            ['Garden.Moderation.Manage', 'Moderation.UserNotes.View'],
            false
        );

        $sender->setData('IsPrivileged', $isPrivileged);

        // Users should only be able to see their own warnings
        if (!$isPrivileged && Gdn::session()->UserID != val('UserID', $sender->User)) {
            throw permissionException('Garden.Moderation.Manage');
        }

        // Build breadcrumbs.
        $sender->_setBreadcrumbs();
        $breadcrumbs = $sender->data('Breadcrumbs');
        $notesUrl = userUrl($sender->User, '', 'notes');
        $noteUrl = url("/profile/viewnote/{$sender->User->UserID}/$noteID");
        $breadcrumbs = array_merge($breadcrumbs, [
            ['Name' => 'Notes', 'Url' => $notesUrl],
            ['Name' => 'Note', 'Url' => $noteUrl]
        ]);
        $sender->setData('Breadcrumbs', $breadcrumbs);

        // Add side menu.
        $sender->setTabView('ViewNote', 'ViewNote', '', 'plugins/Warnings2');

        // If HideWarnerIdentity is true, do not let view render that data.
        $warningModel = new WarningModel();
        $note['HideWarnerIdentity'] = $warningModel->HideWarnerIdentity;

        if (!empty($note['RuleID'])) {
            $note['Rule'] = $this->ruleModel->getID($note['RuleID']);
        }

        // Join record in question with note.
        $notes = [];
        $notes[] = $note;
        joinRecords($notes, 'Record', false, false);

        $sender->setData('Notes', $notes);
        $sender->render('viewnote', '', 'plugins/Warnings2');
    }

    /**
     * Add Rules option to dashboard.
     *
     * @param dashboardNavModule $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        $sender->addLinkToSectionIf(
            'Garden.Settings.Manage',
            'Settings',
            Gdn::translate('Rules'),
            '/settings/rules',
            'forum.rules'
        );
    }

    /**
     * Endpoint for rule creation and modification.
     *
     * @param SettingsController $sender
     */
    public function settingsController_addEditRule_create(SettingsController $sender) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            $formValues = $sender->Form->formValues();

            if (isset($formValues['RuleID']) && ctype_digit($formValues['RuleID'])) {
                $ruleID = $formValues['RuleID'] ?? null;
                unset($formValues['RuleID']);
                $this->ruleModel->update($formValues, ['RuleID' => $ruleID]);
            } else {
                $this->ruleModel->insert($formValues);
            }

            redirectTo('dashboard/settings/rules');
        }

        $ruleID = $sender->RequestArgs[0] ?? null;

        $modalTitle = Gdn::translate('Add rule');
        if (isset($ruleID) && ctype_digit($ruleID)) {
            $rule = $this->ruleModel->getID($ruleID);
            $sender->Form->setData($rule);
            $modalTitle = Gdn::translate('Edit rule');
        }

        $sender->title($modalTitle);
        $sender->setData('Form', $sender->Form);

        $sender->render('rules/addeditrule', '', 'plugins/Warnings2');
    }

    /**
     * Endpoint for rule deletion.
     *
     * @param SettingsController $sender
     * @param int $ruleID
     */
    public function settingsController_deleteRule_create(SettingsController $sender, $ruleID) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            if ($this->ruleModel->delete(['RuleID' => $ruleID])) {
                $sender->jsonTarget("#Rule_$ruleID", null, 'SlideUp');
            }
        }

        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Rules endpoint creation.
     *
     * @param SettingsController $sender
     */
    public function settingsController_rules_create(SettingsController $sender) {
        $sender->permission('Garden.Settings.Manage');

        $rules = $this->ruleModel->get();

        if (!empty($rules)) {
            foreach ($rules as &$rule) {
                $rule['EditUrl'] = url('/dashboard/settings/add-edit-rule/' . $rule['RuleID']);
                $rule['DeleteUrl'] = url('/dashboard/settings/delete-rule?ruleid=' . $rule['RuleID']);
            }
        }

        $sender->setData('Title', Gdn::translate('Rules'));
        $sender->setData('Form', new TwigFormWrapper($sender->Form));
        $sender->setData('Rules', $rules);

        $sender->setHighlightRoute('dashboard/settings/rules');

        $sender->render('rules/settings', '', 'plugins/Warnings2');
    }

    /**
     * Endpoint for multiple records selected
     *
     * @param \ProfileController $sender
     * @param string|null $userIDs
     * @param string|null $recordType
     * @param string|null $recordIDs
     */
    public function profileController_multipleWarnings_create(
        \ProfileController $sender,
        ?string $userIDs,
        ?string $recordType = null,
        ?string $recordIDs = null
    ) {
        $sender->permission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);

        if (empty($userIDs) || empty($recordType) || empty($recordIDs)) {
            $errorMsg = $this->getErrorMsg($userIDs, $recordType, $recordIDs);
            throw new Gdn_UserException($errorMsg);
        }

        if (count(explode(',', $userIDs)) === 1) {
            $this->profileController_warn_create($sender, $userIDs, $recordType, $recordIDs);
        } else {
            $this->chooseUser($sender, $userIDs, $recordType);   // more than one userID has been passed, prompt the user to reselect records
        }
    }

    /**
     * Warn popup form endpoint
     *
     * @param ProfileController $sender
     * @param string|null $userID
     * @param string|null $recordType
     * @param string|null $recordID
     */
    public function profileController_warn_create(
        \ProfileController $sender,
        ?string $userID,
        ?string $recordType = null,
        ?string $recordID = null
    ) {
        $sender->permission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false);

        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException('User');
        }
        // Check to make sure the session user has a higher permission level than the user to be warned.
        $userPermissions = $this->userModel->getPermissions($userID);
        $rankCompare = $this->session->getPermissions()->compareRankTo($userPermissions);
        if ($rankCompare < 0) {
            throw forbiddenException('@'.t('You are not allowed to warn a user with higher permissions than you.'));
        }
        if ($rankCompare === 0) {
            throw forbiddenException('@'.t('You are not allowed to warn a user with the same permission level as you.'));
        }

        $sender->User = $user;

        $sender->_setBreadcrumbs(t('Warn'), '/profile/warn?userid=' . $user['UserID']);

//      $Meta = Gdn::userMetaModel()->getUserMeta($UserID, 'Warnings.%');
//      $CurrentLevel = val('Warnings.Level', $Meta, 0);

        $form = new Gdn_Form();
        $sender->Form = $form;
        $form->addHidden('UserID', $userID);

        // Get the warning types.
        $warningTypes = Gdn::sql()->getWhere('WarningType', [], 'Points')->resultArray();
        $sender->setData('WarningTypes', $warningTypes);

        // Get the record.
        if ($recordType && $recordID) {
            $recordID = $this->normalizeRecordIDs($recordID);

            //if the user has already been warned, let the mod know and move on.
            if ($this->checkAlreadyWarned($sender, $recordID, $recordType)) {
                return;
            }

            $row = getRecord($recordType, end($recordID));
            $sender->setData('RecordType', $recordType);
            $sender->setData('Record', $row);

            $form->addHidden('RecordBody', $row['Body']);
            $form->addHidden('RecordFormat', $row['Format']);
            $form->addHidden('RecordInsertTime', $row['DateInserted']);

            $this->applyWarningBodyAndFormat($form, $recordID, $recordType);
        }

        if ($form->authenticatedPostBack()) {
            $model = new WarningModel();
            $form->setModel($model);
            $form->setFormValue('UserID', $userID);

            if ($form->getFormValue('AttachRecord')) {
                $form->setFormValue('RecordType', $recordType);
                $form->setFormValue('RecordID', end($recordID));
            }

            if ($form->save()) {
                $sender->informMessage(t('Your warning was added.'));
                $sender->jsonTarget('', '', 'Refresh');
            }
        } else {
            $type = reset($warningTypes);
            $form->setValue('WarningTypeID', val('WarningTypeID', $type));
            $form->setValue('AttachRecord', true);
        }

        $ruleOptions = [];
        $rules = $this->ruleModel->get();
        foreach ($rules as $rule) {
            $ruleOptions[$rule['RuleID']] = [
                'Text' => $rule['Name'] ?? null,
            ];
        }

        $sender->setData('Profile', $user);
        $sender->setData('Title', sprintf(t('Warn %s'), htmlspecialchars(val('Name', $user))));
        $sender->setData('RuleOptions', $ruleOptions);

        $sender->View = 'Warn';
        $sender->ApplicationFolder = 'plugins/Warnings2';
        $sender->render('', '');
    }

    /**
     * Return error message
     *
     * @param string|null $userIDs
     * @param string|null $recordType
     * @param string|null $recordIDs
     * @return string
     */
    private function getErrorMsg(?string $userIDs, ?string $recordType, ?string $recordIDs) {
        $params = [];
        if (empty($userIDs)) {
            $params[] = 'User ID';
        }

        if (empty($recordType)) {
            $params[] = 'Record Type';
        }

        if (empty($recordIDs)) {
            $params[] = 'Record ID';
        }

        return implode($params, ', ') . plural(count($params), t(' parameter is invalid.'), t(' parameters are invalid.'));
    }

    /**
     * Check if record has warning ID
     *
     * @param \ProfileController $sender
     * @param array $recordIDs
     * @param string $recordType
     * @return bool
     */
    private function checkAlreadyWarned(\ProfileController $sender, array $recordIDs, string $recordType): bool {
        $warningModule = new WarningModel();
        $warnedPostIDs = [];
        $model = $warningModule->getModel(strtolower($recordType));
        if ($model) {
            $sender->setData('RecordIDs', $recordIDs);
            foreach ($recordIDs as $recordID) {
                $record = $model->getID($recordID);
                if (isset($record->Attributes['WarningID']) && $record->Attributes['WarningID']) {
                    $warnedPostIDs[] = $recordID;
                }
            }
        }

        if (count($warnedPostIDs) > 0) {
            $records = $this->getRecords($warnedPostIDs, $recordType);
            $recordUrls = array_map(function ($record) {
                return $record['Url'];
            }, $records);
            $sender->setData('WarnedPostUrls', $recordUrls);
            $sender->title(t('Already Warned'));
            $sender->render('alreadywarned', '', 'plugins/Warnings2');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Convert $recordIDs to array
     *
     * @param string|array $recordIDs
     * @return array
     */
    private function normalizeRecordIDs($recordIDs): array {
        return gettype($recordIDs) === 'string' ? explode(',', $recordIDs) : $recordIDs;
    }

    /**
     * Return records urls
     *
     * @param array $recordIDs
     * @param string $recordType
     * @return array
     */
    private function getRecords(array $recordIDs, string $recordType): array {
        $records = [];

        foreach ($recordIDs as $recordID) {
            $records[] = getRecord($recordType, $recordID);
        }
        return $records;
    }

    /**
     * Apply a warning body and format to the form.
     *
     * - Forces to rich to allow embeds where possible.
     * - Otherwise only displays links (not ideal).
     *
     * @param Gdn_Form $form
     * @param array $recordIDs
     * @param string $recordType
     * @return void
     */
    private function applyWarningBodyAndFormat(Gdn_Form $form, array $recordIDs, string $recordType) {
        $postTitle = plural(
            count($recordIDs),
            t('You are being warned for the following post:'),
            t('You are being warned for the following posts:')
        );

        $records = $this->getRecords($recordIDs, $recordType);

        // we want to apply Rich, even if it's not the default format, but it might have been disabled.
        if (!class_exists(RichEditorPlugin::class)) {
            $body = $postTitle . "\n";
            foreach ($records as $record) {
                $body .=  $record['Url'] . "\n";
            }
            $form->setValue('Body', $body);
            $form->setValue('Format', 'Markdown');
            return;
        }

        $data = [
            ["insert" => plural(
                count($recordIDs),
                t('You are being warned for the following post:'),
                t('You are being warned for the following posts:')
            )],
            ["insert" => "\n"],
        ];

        foreach ($records as $record) {
            $bodyRaw = $record["Body"];
            $userID = $record['InsertUserID'] ?? $record['ActivityUserID'];
            $userRecord = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
            $userRecord = UserFragmentSchema::normalizeUserFragment($userRecord);
            $name = $record['Name'] ?? null;
            $recordID = $recordType === 'Comment' ? $record['CommentID'] : $record['DiscussionID'];
            $recordUrl = $record['Url'];

            $quoteEmbed = new QuoteEmbed([
                "name" => $name,
                "embedType" => QuoteEmbed::TYPE,
                "recordType" => strtolower($recordType),
                "recordID" => $recordID,
                "body" => $this->formatService->renderHTML($bodyRaw, $record['Format']),
                "format" => $record['Format'],
                "dateInserted" =>  $record["DateInserted"],
                "bodyRaw" => $bodyRaw,
                "userID" => $userID,
                "insertUser" => $userRecord,
                "displayOptions" => new QuoteEmbedDisplayOptions(
                    true,
                    false,
                    true,
                    true,
                    true,
                    true,
                    false
                ),
                "url" => $recordUrl,
            ]);

            $quoteEmbed = $this->embedService->filterEmbedData($quoteEmbed->getData());

            $embedData = [
                "insert" => [
                    "embed-external" => [
                        "data" => $quoteEmbed,
                        "loaderData" => [
                            "type" => "link",
                            "link" => $recordUrl
                        ]
                    ],
                ]
            ];

            array_push($data, $embedData);
        }

        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        $form->setValue('Body', $body);
        $form->setValue('Format', 'Rich');
    }

    /**
     * Returns either comments or discussions array selected on provided IDs
     *
     * @param array $recordIDs
     * @param string $recordType
     * @return array
     */
    private function getSelectedRecords(array $recordIDs, string $recordType): array {
        if (strtolower($recordType) === 'comment') {
            $records = $this->commentModel->lookup(['CommentID' => $recordIDs])->resultArray();
        } else {
            $records = $this->discussionModel->getIn($recordIDs)->resultArray();
        }

        return $records;
    }

    /**
     * Hide signatures for people in the pokey.
     *
     * @param SignaturesPlugin $sender
     */
    public function signaturesPlugin_beforeDrawSignature_handler($sender) {
        $userID = $sender->EventArguments['UserID'];
        $user = Gdn::userModel()->getID($userID);
        if (!val('Punished', $user)) {
            return;
        }
        $sender->EventArguments['Signature'] = null;
    }

    public function utilityController_processWarnings_create($sender) {
        $warningModel = new WarningModel();
        $result = $warningModel->processAllWarnings();

        $sender->setData('Result', $result);
        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Add multiplewarnings endpoint
     *
     * @param \ModerationController $sender
     */
    public function base_beforeCheckComments_handler(\Gdn_Controller $sender) {
        if (!checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false)) {
            return;
        }

        $actionMessage = &$sender->EventArguments['ActionMessage'];
        $discussion = $sender->EventArguments['Discussion'];
        $commentIDs = $this->getCommentIDs($discussion->DiscussionID);
        $authorIDs = $this->getAuthorIDs($commentIDs, 'comment');

        $actionMessage .= ' '.anchor(t('Warn'), 'profile/multiplewarnings?userids='.join($authorIDs, ',').'&recordtype=Comment&recordids='.join($commentIDs, ','), 'Warn Popup');
    }

    /**
     * Add multiplewarnings endpoint
     *
     * @param \ModerationController $sender
     */
    public function base_beforeCheckDiscussions(\Gdn_Controller  $sender) {
        if (!checkPermission(['Garden.Moderation.Manage', 'Moderation.Warnings.Add'], false)) {
            return;
        }

        $actionMessage = &$sender->EventArguments['ActionMessage'];
        $discussionIDs = Gdn::userModel()->getAttribute(Gdn::session()->UserID, 'CheckedDiscussions', []);
        $authorIDs = $this->getAuthorIDs($discussionIDs, 'discussion');

        $actionMessage .= ' '.anchor(t('Warn'), 'profile/multiplewarnings?userids='.join($authorIDs, ',').'&recordtype=Discussion&recordids='.join($discussionIDs, ','), 'Warn Popup');
    }

    /**
     * Remove data when deleting a user.
     *
     * @param \UserModel $sender
     * @param array $args
     */
    public function userModel_beforeDeleteUser_handler(\UserModel $sender, array $args) {
        $this->userModel->getDelete('UserNote', ['UserID' => $args['UserID']], $args['Content']);
    }

    /**
     * Render Choose User view
     *
     * @param \ProfileController $sender
     * @param string $userIDs
     * @param string string $recordType
     */
    private function chooseUser(\ProfileController $sender, string $userIDs, string $recordType) {
        $users = [];

        //set users to view
        foreach (explode(',', $userIDs) as $userID) {
            $user = Gdn::userModel()->getID($userID);
            if (!empty($user)) {
                $users[] = $user;
            }
        }
        $sender->setData('Users', $users);

        // set record type to view
        $recordType = strtolower($recordType) === 'discussion' ? t('discussions') : t('comments');
        $sender->setData('RecordType', $recordType);

        $this->render('chooseuser');
    }

    /**
     * Return Comments IDs
     *
     * @param int $discussionID
     * @return array
     */
    private function getCommentIDs(int $discussionID): array {
        $commentIDs = Gdn::userModel()->getAttribute(Gdn::session()->UserID, 'CheckedComments', []);
        $commentIDs = $commentIDs[$discussionID];
        return $commentIDs;
    }

    /**
     * Return records author IDs
     *
     * @param array $recordIDs
     * @param string $recordType
     * @return array
     */
    private function getAuthorIDs(array $recordIDs, string $recordType): array {
        $authorIDs = [];

        switch ($recordType) {
            case 'comment':
                $recordModel = new CommentModel();
                break;
            case 'discussion':
                $recordModel = new DiscussionModel();
                break;
        }

        foreach ($recordIDs as $recordID) {
            $row = $recordModel->getID($recordID, DATASET_TYPE_ARRAY);
            $authorID = $row['InsertUserID'];
            if (!in_array($authorID, $authorIDs)) {
                $authorIDs[] = $authorID;
            }
        }

        return $authorIDs;
    }
}

/*
 * Global Functions
 */

if (!function_exists('FormatQuote')):

    /**
     * Build our warned content quote for PMs.
     *
     * @param $content
     * @param $user
     *
     * @return string
     */
    function formatQuote($content, $user = null) {
        if (is_object($content)) {
            $content = (array)$content;
        } elseif (is_string($content)) {
            return $content;
        }

        if (is_null($user)) {
            $user = Gdn::userModel()->getID(val('InsertUserID', $content));
        }

        if ($user) {
            $result = '<blockquote class="Quote Media">'.
                '<div class="Img">'.userPhoto($user).'</div>'.
                '<div class="Media-Body">'.
                '<div>'.userAnchor($user).' - '.Gdn_Format::dateFull($content['DateInserted'], 'html').'</div>'.
                Gdn_Format::to($content['Body'], $content['Format']).
                '</div>'.
                '</blockquote>';
        } else {
            $result = '<blockquote class="Quote">'.
                Gdn_Format::to($content['Body'], $content['Format']).
                '</blockquote>';
        }

        return $result;
    }

endif;

if (!function_exists('WarningContext')):

    /**
     * Create a linked sentence about the context of the warning.
     *
     * @param $context array or object being warned.
     *
     * @return string Html message to direct moderators to the content.
     */
    function warningContext($context, $discussion = null, $category = null) {
        if (is_object($context)) {
            $context = (array)$context;
        }

        if ($activityID = val('ActivityID', $context)) {

            // Point to an activity
            $type = val('ActivityType', $context);
            if ($type == 'Status') {
                // Link to author's wall
                $contextHtml = sprintf(
                    t('Warning Status Context', '%1$s by <a href="%2$s">%3$s</a>'),
                    t('Activity Status', 'Status'),
                    userUrl($context, 'Activity').'#Activity_'.$activityID,
                    Gdn_Format::text($context['ActivityName'])
                );
            } elseif ($type == 'WallPost') {
                // Link to recipient's wall
                $contextHtml = sprintf(
                    t('Warning WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'),
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
            if (is_null($discussion)) {
                $discussionModel = new DiscussionModel();
                $discussion = (array)$discussionModel->getID(val('DiscussionID', $context));
            }
            $contextHtml = sprintf(
                t('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                commentUrl($context),
                t('Comment'),
                strtolower(t('Discussion')),
                discussionUrl($discussion),
                Gdn_Format::text($discussion['Name'])
            );
        } elseif (val('DiscussionID', $context)) {

            // Point to discussion & its category
            if (is_null($category)) {
                $discussionModel = new DiscussionModel();
                $category = CategoryModel::categories($context['CategoryID']);
            }
            $contextHtml = sprintf(
                t('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
                discussionUrl($context),
                t('Discussion'),
                strtolower(t('Category')),
                categoryUrl($category),
                Gdn_Format::text($category['Name']),
                Gdn_Format::text($context['Name']) // In case folks want the full discussion name
            );
        } else {
            return null;
        }

        return $contextHtml;
    }
endif;
