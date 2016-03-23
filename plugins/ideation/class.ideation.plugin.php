<?php if (!defined('APPLICATION')) exit;

$PluginInfo['ideation'] = [
    'Name'            => "Ideation",
    'Description'     => "Let users vote on discussions in a Idea category",
    'Version'         => '1.0.0',
    'RequiredPlugins' => array(
        'Reactions'   => '1.4.0',
        'Tagging'     => '1.8.12'
    ),
    'Author'          => "Becky Van Bussel",
    'AuthorEmail'     => 'becky@vanillaforums.com',
    'License'         => 'Proprietary'
];

/**
 * Ideation Plugin
 *
 * @author    Becky Van Bussel <becky@vanillaforums.com>
 * @license   Proprietary
 * @since     2.2
 */
class IdeationPlugin extends Gdn_Plugin {

    /**
     * The Reaction name of the upvote.
     */
    const REACTION_UP = 'Up';

    /**
     * The Reaction name of the downvote.
     */
    const REACTION_DOWN = 'Down';

    /**
     * The Ideation category type name for allowing only up votes.
     */
    const CATEGORY_TYPE_UP = 'up';

    /**
     * The Ideation category type name for allowing up and down votes.
     */
    const CATEGORY_TYPE_UP_AND_DOWN = 'up-down';

    /**
     * Ideation column name in the Category table.
     */
    const CATEGORY_IDEATION_COLUMN_NAME = 'IdeationType';

    /**
     * @var int The tag ID of the upvote reaction.
     */
    protected static $upTagID;

    /**
     * @var int The tag ID of the downvote reaction.
     */
    protected static $downTagID;

    /**
     * This will run when you "Enable" the plugin.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs structure.php on /utility/update and on enabling the plugin.
     */
    public function structure() {
        require dirname(__FILE__).'/structure.php';
    }

    /**
     * Adds the ideation CSS asset.
     *
     * @param $sender
     */
    public function base_render_before($sender) {
        $sender->addCssFile('ideation.css', 'plugins/ideation');
    }

    /**
     * Gets the upvote reaction tag ID.
     *
     * @return int The upvote reaction tag ID.
     */
    public static function getUpTagID() {
        if (!self::$upTagID) {
            $reactionUp = ReactionModel::ReactionTypes(self::REACTION_UP);
            self::setUpTagID(val('TagID', $reactionUp));
        }
        return self::$upTagID;
    }

    /**
     * Sets the upvote reaction tag ID.
     *
     * @param int $upTagID The upvote reaction tag ID.
     */
    protected static function setUpTagID($upTagID) {
        self::$upTagID = $upTagID;
    }

    /**
     * Gets the downvote reaction tag ID.
     *
     * @return int The downvote reaction tag ID.
     */
    public static function getDownTagID() {
        if (!self::$downTagID) {
            $reactionDown = ReactionModel::ReactionTypes(self::REACTION_DOWN);
            self::setDownTagID(val('TagID', $reactionDown));
        }
        return self::$downTagID;
    }

    /**
     * Sets the downvote reaction tag ID.
     *
     * @param int $downTagID The downvote reaction tag ID.
     */
    protected static function setDownTagID($downTagID) {
        self::$downTagID = $downTagID;
    }

    /**
     * CATEGORY SETTINGS
     * -----------------
     */

    /**
     * Adds Statuses link to Dashboard menu.
     *
     * @param Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Forum', t('Idea Statuses'), '/dashboard/settings/statuses', 'Garden.Settings.Manage', array('class' => 'nav-statuses'));
    }

    /**
     * Adds ideation options to the categories setting page -> enabling ideation on a category and enabling downvotes.
     * Also manipulates the allowed discussion types options when ideation is enabled on a category.
     * Ideas are the only discussion type allowed in an ideation category. Existing idea categories cannot be changed
     * into normal categories and existing normal categories cannot be changed into ideation categories.
     *
     * @param SettingsController $sender
     */
    public function settingsController_addEditCategory_handler($sender) {
        $categoryID = val('CategoryID', $sender->Data);
        $sender->Head->AddString(
            <<<EOT
<script>
	jQuery(document).ready(function($) {
    $('input[value="Idea"]').parents('label').hide();
    });
</script>'
EOT
        );
        if (!$sender->Form->authenticatedPostBack()) {
            $category = CategoryModel::categories($categoryID);
            if ($categoryID && !$this->isIdeaCategory($category)) {
                // Don't let the ideation state of existing categories be changed.
                return;
            }

            $sender->addJsFile('ideation.js', 'plugins/ideation'); // Show/hide allowed discussions and downvote option

            if (!$categoryID) {
                $sender->Data['_ExtendedFields']['IsIdea'] = ['Name' => 'Idea Category', 'Control' => 'CheckBox', 'Description' => '<strong>' . t('Ideation') . '</strong> <small><a href="http://docs.vanillaforums.com/addons/ideation/">' . sprintf(t('Learn more about %s'), t('ideas')) . '</a></small>'];
            }

            $downVoteOptions = [];
            if ($this->isIdeaCategory($category)) {
                $sender->title('Edit Idea Category');
                $sender->Form->addHidden('Idea_Category', true);
                $downVoteOptions = $this->allowDownVotes($category) ? ['checked' => 'checked'] : [];
            }

            $sender->Data['_ExtendedFields']['UseDownVotes'] = ['Name' => 'UseDownVotes', 'Control' => 'CheckBox', 'Options' => $downVoteOptions];

        } else {
            if ($sender->Form->getValue('Idea_Category')) {
                $sender->Form->setFormValue(self::CATEGORY_IDEATION_COLUMN_NAME, $sender->Form->getFormValue('UseDownVotes') ? self::CATEGORY_TYPE_UP_AND_DOWN : self::CATEGORY_TYPE_UP);
            }
        }
    }

    /**
     * Removes the idea type from the allowed discussion types of non-idea categories and enforces the idea type on idea categories.
     *
     * @param Controller $sender
     * @param $args
     */
    public function base_allowedDiscussionTypes_handler($sender, $args) {
        $category = val('Category', $args);
        if (empty($category)) {
            $category = val('PermissionCategory', $args);
        }
        if ($this->isIdeaCategory($category)) {
            $args['AllowedDiscussionTypes'] = ['Idea' => $this->getIdeaDiscussionType()];
        } elseif (isset($args['AllowedDiscussionTypes']['Idea'])) {
            unset($args['AllowedDiscussionTypes']['Idea']);
        }
    }


    /**
     * Removes non-idea categories from the category dropdown on the new idea form.
     *
     * @param Controller $sender
     * @param $args
     */
    public function base_beforeCategoryDropDown_handler($sender, $args) {
        $type = val('Type', $sender->formValues());
        if ($type !== 'Idea') {
            return;
        }
        $Value = arrayValueI('Value', $Options = $args['Options']); // The selected category id
        $categoryData = CategoryModel::GetByPermission(
            'Discussions.View',
            $Value,
            val('Filter', $Options, array('Archived' => 0)),
            val('PermFilter', $Options, array())
        );
        $ideaCategoryIDs = $this->getIdeaCategoryIDs();
        $ideaCategories = [];
        foreach($categoryData as $id => $category) {
            if (in_array($id, $ideaCategoryIDs)) {
                $ideaCategories[$id] = $category;
            }
        }
        $args['Options']['CategoryData'] = $ideaCategories;
    }

    /**
     * STATUS SETTINGS
     * --------------
     */

    /**
     * Adds endpoint for editing a status, renders the form and performs the update operation.
     *
     * @param SettingsController $sender
     * @param $statusID The ID of the status to edit.
     * @throws Exception
     */
    public function settingsController_editStatus_create($sender, $statusID) {
        if (!$statusID) {
            throw NotFoundException('Status');
        }
        $sender->title(sprintf(t('Edit %s'), t('Idea Status')));
        $this->addEdit($sender, $statusID);
    }

    /**
     * Adds endpoint for adding a status, renders the form and performs the insert operation.
     *
     * @param SettingsController $sender
     * @throws Exception
     */
    public function settingsController_addStatus_create($sender) {
        $sender->title(sprintf(t('Add %s'), t('Idea Status')));
        $this->addEdit($sender);
    }

    /**
     * Renders the add/edit status form and performs the corresponding operation.
     *
     * @param SettingsController $sender
     * @param int $statusID The ID of the status to edit.
     * @throws Exception
     */
    protected function addEdit($sender, $statusID = 0) {
        $sender->permission('Garden.Settings.Manage');
        $statusModel = new StatusModel();

        if ($sender->Form->authenticatedPostBack()) {
            $data = $sender->Form->formValues();
            $result = $statusModel->save(val('Name', $data), val('State', $data), val('IsDefault', $data), $statusID);
            $sender->Form->setValidationResults($statusModel->validationResults());
            if ($result) {
                $sender->informMessage(t('Your changes have been saved.'));
                $sender->RedirectUrl = url('/settings/statuses');
                $sender->setData('Status', StatusModel::instance()->getStatus($result));
            }
        } elseif ($statusID) {
            // We're about to edit, set up the data from the status.
            $data = StatusModel::instance()->getStatus($statusID);
            if (!$data) {
                throw NotFoundException('Status');
            }
            $sender->Form->setData($data);
            $sender->Form->addHidden('StatusID', $statusID);
        }
        $sender->addSideMenu();
        $sender->render('status', '', 'plugins/ideation');
    }

    /**
     * Adds endpoint for deleting a status, renders the form and performs the delete operation.
     *
     * @param SettingsController $sender
     * @param $statusID
     */
    public function settingsController_deleteStatus_create($sender, $statusID) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            if ($sender->Form->getFormValue('Yes')) {
                $statusModel = new StatusModel();
                $statusModel->delete(['StatusID' => $statusID]);
                $sender->jsonTarget("#Status_$statusID", NULL, 'SlideUp');
            }
        }

        $sender->title(sprintf(t('Delete %s'), t('Status')));
        $sender->render('DeleteStatus', '', 'plugins/ideation');
    }

    /**
     * Adds endpoint in the dashboard for viewing all statuses and renders the view.
     *
     * @param SettingsController $sender
     */
    public function settingsController_statuses_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Statuses', StatusModel::instance()->getStatuses());
        $sender->setData('Title', sprintf(t('All %s'), t('Statuses')));
        $sender->addSideMenu();
        $sender->render('statuses', '', 'plugins/ideation');
    }

    /**
     * IDEA POST FORM
     * --------------
     * Adds a new, stripped-down post type: 'Idea', without announcing or tagging capabilities beyond status-type tags.
     */

    /**
     * Add an Idea discussion type.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_discussionTypes_handler($sender, $args) {
        $args['Types']['Idea'] = $this->getIdeaDiscussionType();
    }

    /**
     * Returns an array consisting of the Idea discussion type data.
     *
     * @return array The Idea discussion type.
     */
    public function getIdeaDiscussionType() {
        return [
            'Singular' => 'Idea',
            'Plural' => 'Ideas',
            'AddUrl' => '/post/idea',
            'AddText' => 'New Idea'
        ];
    }

    /**
     * Adds a post/idea endpoint, ensures there's a category and that the category is an idea category,
     * and then sets the default status and Idea type on the discussion post.
     *
     * @param PostController $sender
     * @param array $args The event arguments. The first argument should be the category slug.
     * @throws Exception
     */
    public function postController_idea_create($sender, $args) {
        $categoryCode = val(0, $args, '');
        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $sender->Form->setFormValue('Tags', val('TagID', StatusModel::instance()->getDefaultStatus()));
        $sender->View = 'discussion';
        $sender->discussion($categoryCode);
    }

    /**
     * Adds a post/editidea endpoint. Permission to edit an idea is the same for editting a discussion.
     *
     * @param PostController $sender
     * @param $args
     * @throws Exception
     */
    public function postController_editIdea_create($sender, $args) {
        if (!sizeof($args)) {
            throw NotFoundException('Idea');
        }
        $discussionID = $args[0];

        if (!$this->isIdea($discussionID)) {
            throw NotFoundException('Idea');
        }
        $sender->View = 'discussion';
        $sender->editDiscussion($discussionID);
    }

    /**
     * Makes changes to the default discussion post form for an idea:
     * Removes category selector, adds idea-specific title and changes text on the Save button.
     *
     * @param PostController $sender
     */
    public function postController_beforeDiscussionRender_handler($sender) {
        if (val('Type', $sender->Data) === 'Idea') {
            $sender->Discussion = 'Idea'; // Kludge to set text on 'Post Discussion' button to 'Save'

            if ($sender->data('Discussion')) {
                $sender->setData('Title', sprintf(t('Edit %s'), t('Idea')));
            } else {
                $sender->setData('Title', sprintf(t('New %s'), t('Idea')));
            }
        }
    }

    /**
     * The idea post form is a stripped down version of the discussion form. This removes any options from the form
     * including announcing option.
     *
     * @param PostController $sender
     * @param array $args
     */
    public function postController_discussionFormOptions_handler($sender, $args) {
        // Clear out options.
        if (val('Options', $args)) {
            $args['Options'] = '';
        }
    }

    /**
     * STATUS TAGGING
     * -------------
     */

    /**
     * Register a new tag type: Status
     *
     * @param TagModel $sender
     */
    public function tagModel_types_handler($sender) {
        $sender->addType('Status', [
            'key' => 'Status',
            'name' => 'Status',
            'plural' => 'Statuses',
            'addtag' => false
        ]);
    }

    /**
     * Registers reserved Status-type tags.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_reservedTags_handler($sender, $args) {
        if (isset($args['ReservedTags'])) {
            $statuses = StatusModel::instance()->getStatuses();
            foreach ($statuses as $status) {
                $tagName = val('Name', TagModel::instance()->getID(val('TagID', $status)));
                $args['ReservedTags'][] = $tagName;
            }
        }
    }

    /**
     * Prints Status-type tags on discussions in a discussion list
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = val('Discussion', $args);
        if (!$this->isIdea($discussion)) {
            return;
        }
        $status = StatusModel::instance()->getStatusByDiscussion(val('DiscussionID', $discussion));
        if ($status) {
            echo getStatusTagHtml(val('Name', $status));
        }
    }

    /**
     * IDEA COUNTER
     * ------------
     */

    /**
     * Modern layout discussion list counter placement.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionContent_handler($sender, $args) {
        $discussion = val('Discussion', $args);
        if (!$this->isIdea($discussion)) {
            return;
        }
        if (c('Vanilla.Discussions.Layout') == 'modern') {
            $userVote = '';
            if ($tagID = val('UserVote', $discussion)) {
                $userVote = $this->getReactionFromTagID($tagID);
            }
            echo $this->getIdeaCounter($discussion, $userVote);
        }
    }

    /**
     * Table layout discussion list counter placement.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionTitle_handler($sender, $args) {
        $discussion = val('Discussion', $args);
        if (!$this->isIdea($discussion)) {
            return;
        }
        if (c('Vanilla.Discussions.Layout') == 'table') {
            $userVote = '';
            if ($tagID = val('UserVote', $discussion)) {
                $userVote = $this->getReactionFromTagID($tagID);
            }
            echo $this->getIdeaCounter($discussion, $userVote);
        }
    }

    /**
     * Returns the idea counter module for a discussion.
     *
     * @param object $discussion The discussion that the idea counter belongs to.
     * @param string $userVoteReactionName The name of the user's Idea-type reaction.
     * @return IdeaCounterModule The discussion's idea counter module.
     */
    public function getIdeaCounter($discussion, $userVoteReactionName) {
        $ideaCounterModule = IdeaCounterModule::instance();
        $useDownVotes = $this->allowDownVotes($discussion, 'discussion');
        $ideaCounterModule->setUseDownVotes($useDownVotes);
        $ideaCounterModule->setUserVote($userVoteReactionName);
        $ideaCounterModule->setDiscussion($discussion);

        return $ideaCounterModule;
    }

    /**
     * Disables rendering of tags in a discussion and sets up the idea counter module for the discussion attachment.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        $discussion = val('Discussion', $sender);

        if ($this->isIdea($discussion)) {
            // Don't display tags on a idea discussion.
            saveToConfig('Plugins.Tagging.DisableInline', true, true);
        } else {
            // Display tags otherwise and return.
            saveToConfig('Plugins.Tagging.DisableInline', false, true);
            return;
        }

        // Get Counter for discussion.
        $userVote = $this->getUserVoteReaction($discussion);
        $ideaCounterModule = $this->getIdeaCounter($discussion, $userVote);
        $ideaCounterModule->setShowVotes(true);

        $sender->setData('IdeaCounter', $ideaCounterModule);
    }

    /**
     * Adds the ItemIdea CSS class to a Idea-type discussion in a discussion list.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionName_handler($sender, $args) {
        if ($this->isIdea(val('Discussion', $args))) {
            $args['CssClass'] .= ' ItemIdea';
        }
    }

    /**
     * Handles the discussion options (the links in the cog dropdown) for an idea. Adds a link to edit the status,
     * changes the url for the edit option from /editdiscussion to /editidea and changes the delete label
     * from 'Delete Discussion' to 'Delete Idea'.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_discussionOptions_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!$this->isIdea($discussion)) {
            return;
        }

        if (!Gdn::session()->checkPermission('Vanilla.Moderation.Manage')
            && !Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['Status'] = ['Label' => sprintf(t('Edit %s'), t('Idea Status')), 'Url' => '/discussion/statusoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup'];
        } elseif(isset($sender->Options) && is_string($sender->Options)) {
            $sender->Options .= '<li>'.anchor(sprintf(t('Edit %s'), t('Idea Status')), '/discussion/statusoptions?discussionid='.$discussion->DiscussionID, 'Popup').'</li>';
        }

        if (isset($args['DiscussionOptions']['EditDiscussion'])) {
            $args['DiscussionOptions']['EditDiscussion']['Url'] = str_replace('editdiscussion', 'editidea', $args['DiscussionOptions']['EditDiscussion']['Url']);
        } elseif(isset($sender->Options) && is_string($sender->Options)) {
            $sender->Options = str_replace('editdiscussion', 'editidea', $sender->Options);
        }

        if (isset($args['DiscussionOptions']['DeleteDiscussion'])) {
            $args['DiscussionOptions']['DeleteDiscussion']['Label'] = sprintf(t('Delete %s'), t('Idea'));
        }
    }

    /**
     * Renders status options form and handles editting the status and/or status notes.
     *
     * @param DiscussionController $sender
     * @param string|int $discussionID The ID of the Idea-type discussion
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function discussionController_statusOptions_create($sender, $discussionID = '') {
        if ($discussionID) {
            $discussion = $sender->DiscussionModel->GetID($discussionID);
            if (!$discussion || !$this->isIdea($discussion)) {
                throw NotFoundException('Idea');
            }

            if (!Gdn::session()->checkPermission('Vanilla.Moderation.Manage')
                && !Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
                return;
            }

            $sender->Form = new Gdn_Form();
            if ($sender->Form->authenticatedPostBack()) {
                $this->updateDiscussionStatus($discussion, $sender->Form->getFormValue('Status'), $sender->Form->getFormValue('StatusNotes'));
                Gdn::controller()->jsonTarget('', '', 'Refresh');
            }

            $statuses = StatusModel::instance()->getStatuses();
            foreach($statuses as &$status) {
                $status = val('Name', $status);
            }
            $notes = $this->getStatusNotes($discussion, $sender->DiscussionModel);

            $sender->setData('Discussion', $discussion);
            $sender->setData('Statuses', $statuses);
            $sender->setData('StatusNotes', $notes);
            $sender->setData('CurrentStatusID', val('StatusID', StatusModel::instance()->getStatusByDiscussion($discussionID)));
            $sender->setData('Title', sprintf(t('Edit %s'), t('Idea Status')));

            $sender->render('StatusOptions', '', 'plugins/ideation');
        }
    }

    /**
     * DISCUSSION STATUS
     * ----------------
     * Status notes are serialized and stored in the Attributes column. We find out what status a discussion has
     * by its tag (in the TagDiscussion table). Any Idea-type discussion will also have its status tag as the only TagID
     * in the Discussion table. There are attachments on a discussion that display the status info that need to be
     * updated when the StatusNotes or StatusTag is changed.
     */

    /**
     * Update a discussion's status. Handles notifications, updating the status tag, status notes, and attachments.
     *
     * @param object|array $discussion The discussion to update
     * @param int $statusID The new status for the discussion
     * @param string $notes The status notes
     */
    public function updateDiscussionStatus($discussion, $statusID, $notes) {
        if (!$this->isIdea($discussion) || !is_numeric($statusID)) {
            return;
        }
        $discussionID = val('DiscussionID', $discussion);
        $newStatus = StatusModel::instance()->getStatus($statusID);
        $this->updateDiscussionStatusTag($discussionID, $statusID);
        if ($notes) {
            $this->updateDiscussionStatusNotes($discussionID, $notes);
        }
        $this->updateAttachment($discussionID, $statusID, $notes);
        $this->notifyIdeaAuthor(val('InsertUserID', $discussion), $discussionID, val('Name', $discussion), $newStatus, $notes);
        $this->notifyVoters($discussionID, val('Name', $discussion), $newStatus, $notes);
    }

    /**
     * Updates the tag on a discussion. Updates the TagDiscussion table and the TagID column of the Discussion table.
     *
     * @param int $discussionID The ID of the discussion to update
     * @param int $statusID The new status ID
     */
    protected function updateDiscussionStatusTag($discussionID, $statusID) {
        // TODO: Logging. We shoud probably keep a record of this.

        $oldStatus = StatusModel::instance()->getStatusByDiscussion($discussionID);
        // Don't change anything if nothing's changed.
        if (val('StatusID', $oldStatus) != $statusID) {

            // Save tag info in TagDiscussion
            $status = StatusModel::instance()->getStatus($statusID);
            $tags = [val('TagID', $status)];
            TagModel::instance()->saveDiscussion($discussionID, $tags, ['Status']);

            // Save tags in discussions table
            $discussionModel = new DiscussionModel();
            $discussionModel->setField($discussionID, 'Tags', val('TagID', $status));
        }
    }

    /**
     * Saves the discussion status notes in its Attributes.
     *
     * @param int $discussionID The ID of the discussion to update
     * @param string $notes The new notes to save
     * @throws Exception
     */
    protected function updateDiscussionStatusNotes($discussionID, $notes) {
        $discussionModel = new DiscussionModel();
        $discussionModel->saveToSerializedColumn('Attributes', $discussionID, 'StatusNotes', $notes);
    }

    /**
     * ATTACHMENTS
     * -----------
     * Attachments appear in the discussion view. They include the status, status description and status notes.
     * The view also renders the idea discussion model from the discussion controller's data array.
     */

    /**
     * Get the ideation attachment view.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_fetchAttachmentViews_handler($sender) {
        require_once $sender->fetchViewLocation('attachment', '', 'plugins/ideation');
    }

    /**
     * Add an attachment to a new idea.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        if ($this->isIdea($discussionID = val('DiscussionID', $args))) {
            $attachmentModel = AttachmentModel::instance();
            $attachment = $attachmentModel->getWhere(['ForeignID' => 'd-'.$discussionID])->resultArray();
            if (empty($attachment)) {
                // We've got a new idea, add an attachment.
                $this->updateAttachment(val('DiscussionID', $args), val('StatusID', StatusModel::instance()->getDefaultStatus()), '');
            }
        }
    }

    /**
     * Updates the attachment for a discussion. Attachments include the status info (name, description, state, notes).
     *
     * @param $discussionID
     * @param $statusID
     * @param $statusNotes
     */
    protected function updateAttachment($discussionID, $statusID, $statusNotes) {

        $status = StatusModel::instance()->getStatus($statusID);
        $attachment['Type'] = 'status';
        $attachment['StatusName'] = val('Name', $status);
        $attachment['StatusDescription'] = val('Description', $status);
        $attachment['StatusState'] = val('State', $status);
        $attachment['StatusNotes'] = $statusNotes;
        $attachment['StatusUrl'] = url('/discussions/tagged/'.urlencode(val('StatusName', $attachment)));
        $attachment['DateUpdated'] = Gdn_Format::toDateTime();

        // Kludge. Not Null fields
        $attachment['Source'] = 'none';
        $attachment['SourceID'] = 'none';
        $attachment['SourceURL'] = 'none';

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->GetID($discussionID);

        $attachment['ForeignID'] = 'd-'.$discussionID;
        $attachment['ForeignUserID'] = val('InsertUserID', $discussion); // Not used.

        //Save to Attachments
        $attachmentModel = AttachmentModel::instance();

        $attachmentModel->joinAttachments($discussion);

        // Check if there's already an attachment and override.
        if ($attachments = val('Attachments', $discussion)) {
            foreach($attachments as $oldAttachment) {
                if (val('Type', $oldAttachment) == 'status') {
                    $attachment['AttachmentID'] = val('AttachmentID', $oldAttachment);
                }
            }
        }
        $attachmentModel->save($attachment);
    }

    /**
     * REACTIONS
     * ---------
     */

    /**
     * Shuts down any reacting to ideas with closed states and make upvote/downvotes active nomatter what it says
     * in the reaction list dashboard.
     *
     * @param ReactionModel $sender
     * @param array $args
     */
    public function reactionModel_getReaction_handler($sender, $args) {
        if ($reaction = val('ReactionType', $args)) {
            if ((val('UrlCode', $reaction) == self::REACTION_UP) || (val('UrlCode', $reaction) == self::REACTION_DOWN)) {
                $statusModel = new StatusModel();
                if (strtolower(val('RecordType', $args) == 'discussion')
                    && (val('State', $statusModel->getStatusByDiscussion(val('RecordID', $args))) == 'Closed')) {
                    $args['ReactionType']['Active'] = false;
                } else {
                    $args['ReactionType']['Active'] = true;
                }
            }
        }
    }

    /**
     * Each reaction that is changed runs through this event. Some votes change 2 reactions.
     * For example, if a user has previously downvoted something and then upvotes it, then we remove the downvote and
     * insert the upvote. This checks to see if the changed reaction is an insert and then appends the 'uservote' css
     * class to it and removes the css class from any reaction that is not inserted. It also recalculates and updates
     * the score and vote count and replaces them in the view.
     *
     * @param ReactionsPlugin $sender
     * @param array $args
     */
    public function reactionsPlugin_reactionsButtonReplacement_handler($sender, $args) {
        $discussion = val('Record', $args);
        if (val('Type', $discussion) != 'Idea') {
            return;
        }

        if ($urlCode = val('UrlCode', $args)) {
            if ($urlCode != self::REACTION_UP && $urlCode != self::REACTION_DOWN) {
                return;
            }
        }

        $reactionTagID = val('TagID', $args); // This id is of the reaction that was selected

        if ($reactionTagID == $this->getDownTagID()) {
            $vote = self::REACTION_DOWN;
        } else {
            $vote = self::REACTION_UP;
        }

        $reaction = ReactionModel::ReactionTypes($urlCode);
        $cssClass = '';

        // If the changed reaction is the one that was selected and if we're inserting (not removing) the reaction, then add the css class.
        if ($urlCode == $vote && val('Insert', $args)) {
            $cssClass = 'uservote';
        }

        $args['Button'] = $this->getIdeaReactionButton($discussion, $urlCode, $reaction, ['cssClass' => $cssClass]);

        $countUp = getValueR('Attributes.React.'.self::REACTION_UP, $discussion, 0);
        $countDown = getValueR('Attributes.React.'.self::REACTION_DOWN, $discussion, 0);

        $score = $countUp - $countDown;
        $votes = $countUp + $countDown;

        Gdn::controller()->jsonTarget(
            '#Discussion_'.val('DiscussionID', $discussion).' .score',
            getScoreHtml($score),
            'ReplaceWith'
        );

        Gdn::controller()->jsonTarget(
            '#Discussion_'.val('DiscussionID', $discussion).' .votes',
            getVotesHtml($votes),
            'ReplaceWith'
        );
    }

    /**
     * Removes non-flag-type reactions from any Idea-type discussion.
     *
     * @param Gdn_Controller $sender
     * @param $args
     */
    public function base_afterFlag_handler($sender, $args) {
        if (val('Type', $args) == 'Discussion' && val('Type', val('Discussion', $args)) == 'Idea') {
            $args['ReactionTypes'] = [];
        }
    }

    /**
     * Stops the reactions model from scoring non-idea-type reactions in idea discussions.
     * Only the up and down reactions should contribute to the score.
     *
     * @param Gdn_Controller $sender
     * @param $args
     */
    public function base_beforeReactionsScore_handler($sender, $args) {
        if (val('ReactionType', $args) && (val('Type', val('Record', $args)) == 'Idea')) {
            $reaction = val('ReactionType', $args);
            if ((val('UrlCode', $reaction) != self::REACTION_UP) && (val('UrlCode', $reaction) != self::REACTION_DOWN)) {
                $args['Set'] = [];

            }
        }
    }

    /**
     * Adds the sessioned user's Idea* reaction to the discussion data in the form [UserVote] => TagID
     * where TagID is the TagID of the reaction.
     *
     * @param Gdn_DataSet $discussions
     */
    protected function addUserVotesToDiscussions($discussions) {
        $userVotes = $this->getUserVotes();
        if (!$userVotes) {
            return;
        }
        foreach ($discussions as &$discussion) {
            $discussionID = val('DiscussionID', $discussion);
            if (val($discussionID, $userVotes)) {
                $discussion->UserVote = $userVotes[$discussionID];
            }
        }
    }

    /**
     * Adds the sessioned user's Idea* reaction to the discussion data in the form [UserVote] => TagID
     * where TagID is the TagID of the reaction.
     *
     * @param DiscussionsController $sender
     */
    public function discussionsController_render_before($sender) {
        if ($sender->DeliveryType() == DELIVERY_TYPE_ALL) {
            $discussions = $sender->data('Discussions')->result();
            $this->addUserVotesToDiscussions($discussions);
        }
    }

    /**
     * Adds the sessioned user's Idea* reaction to the discussion data in the form [UserVote] => TagID
     * where TagID is the TagID of the reaction.
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before($sender) {
        if ($sender->data('Discussions') && $this->isIdeaCategory(val('Category', $sender))) {
            $discussions = $sender->data('Discussions')->result();
            $this->addUserVotesToDiscussions($discussions);
        }
    }

    /**
     * Returns an array of the sessioned user's votes where the key is the discussion ID and the value is the reaction's tag ID.
     *
     * @return array The sessioned user's votes
     */
    public function getUserVotes() {

        $userVotes = [];
        $tagIDs = [$this->getUpTagID(), $this->getDownTagID()];

        $limit = c('Vanilla.Discussions.PerPage', 30);
        $user = Gdn::session();
        $userID = val('UserID', $user);

        if ($userID) {
            $reactionModel = new ReactionModel();

            // TODO: Cache this thing.
            $data = $reactionModel->GetRecordsWhere(['TagID' => $tagIDs, 'RecordType' => ['Discussion'], 'UserID' => $userID, 'Total >' => 0],
                'DateInserted', 'desc',
                $limit + 1);

            foreach ($data as $discussion) {
                $userVotes[val('RecordID', $discussion)] = val('TagID', $discussion);
            }
        }

        return $userVotes;
    }

    /**
     * Gets the user's Idea-type reaction on a discussion. Returns the reaction URL code.
     *
     * @param array|object $discussion The discussion to test. If not provided, tries to get from the Data array.
     * @param array|object $user The user to get the reaction from. If not provided, gets the sessioned user.
     * @return string The urlCode of the Idea* reaction of the user's on a discussion
     */
    protected function getUserVoteReaction($discussion = null, $user = null) {
        if(!$user) {
            $user = Gdn::session();
        }
        if (!$discussion) {
            $discussion = Gdn::controller()->data('Discussion');
        }

        if (!$discussion || !$user) {
            return '';
        }

        $votes = $this->getUserVotes();
        $discussionID = val('DiscussionID', $discussion);

        if (val($discussionID, $votes) == self::getUpTagID()) {
            return self::REACTION_UP;
        }

        if (val($discussionID, $votes) == self::getDownTagID()) {
            return self::REACTION_DOWN;
        }

        return '';
    }

    /**
     * Returns an HTML string of an Idea type reaction button.
     *
     * @param array|object $discussion
     * @param string $urlCode
     * @param array $reaction
     * @param array $options
     * @return string An HTML string representing an idea-type reaction button.
     */
    protected function getIdeaReactionButton($discussion, $urlCode, $reaction = null, $options = []) {
        if (!$reaction) {
            $reaction = ReactionModel::ReactionTypes($urlCode);
        }

        $name = $reaction['Name'];
        $label = t($name);
        $id = GetValue('DiscussionID', $discussion);
        $linkClass = 'ReactButton-'.$urlCode.' '.val('cssClass', $options);
        $urlCode2 = strtolower($urlCode);
        $url = Url("/react/discussion/$urlCode2?id=$id&selfreact=true");
        $dataAttr = "data-reaction=\"$urlCode2\"";

        return getReactionButtonHtml($linkClass, $url, $label, $urlCode2, $dataAttr);
    }

    /**
     * SORT/FILTER
     * -----------
     */

    /**
     * Adds status and state filtering to the DiscussionFilterModule.
     */
    public function discussionModel_initStatic_handler() {
        $categories = $this->getIdeaCategoryIDs();
        DiscussionModel::addFilterSet('status', sprintf(t('All %s'), t('Statuses')), $categories);

        // Open state
        $openStatuses = StatusModel::instance()->getOpenStatuses();
        $openTags = [];
        foreach ($openStatuses as $openStatus) {
            $openTags[] = val('TagID', $openStatus);
        }
        DiscussionModel::addFilter('open', t('Open'),
            ['d.Tags' => $openTags], 'state', 'status'
        );

        // Closed state
        $closedStatuses = StatusModel::instance()->getClosedStatuses();
        $closedTags = [];
        foreach ($closedStatuses as $closedStatus) {
            $closedTags[] = val('TagID', $closedStatus);
        }
        DiscussionModel::addFilter('closed', t('Closed'),
            ['d.Tags' => $closedTags], 'state', 'status'
        );

        // Statuses
        foreach(StatusModel::instance()->getStatuses() as $status) {
            DiscussionModel::addFilter(strtolower(val('Name', $status)).'-status' , val('Name', $status),
                ['d.Tags' => val('TagID', $status)], 'status', 'status'
            );
        }
    }

    /**
     * Renders the DiscussionFilterModule on the idea categories discussion listings pages.
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_pageControls_handler($sender) {
        $categoryID = val('CategoryID', $sender);
        if (!$categoryID || !$this->isIdeaCategory(CategoryModel::categories($categoryID))) {
            return;
        }
        $discussionSortFilterModule = new DiscussionsSortFilterModule($categoryID, $sender->data('Sort', ''), $sender->data('Filters', []));
        echo $discussionSortFilterModule;
    }

    /**
     * NOTIFICATIONS/ACTIVITY
     * ----------------------
     * Uses activity notifications to notify idea creators and voters of status changes.
     */

    /**
     * Notifies the author of an idea of a status change.
     *
     * @param int $authorID The ID of the idea author
     * @param int $discussionID The discussion whose status has changed
     * @param string $discussionName The Idea-type discussion name
     * @param array $newStatus An array representation of the status
     * @param string $statusNotes The notes on the discussion's status.
     * @throws Exception
     */
    public function notifyIdeaAuthor($authorID, $discussionID, $discussionName, $newStatus, $statusNotes = '') {
        if (sizeof($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }
        $headline = t("Progress on your idea!");
        $lead = sprintf(t('The status for "%s" has changed to %s.'),
            $discussionName,
            '<strong>'.val('Name', $newStatus).'</strong>'
        );

        $story = ($statusNotes) ? '<br/><br/>'.sprintf(t('%s: %s'), t('Notes'), $statusNotes) : '';
        $story .= '<br/><br/>'.sprintf(t("Voting for the idea is now %s."), strtolower(val('State', $newStatus)));

        $activity = [
            'ActivityType' => 'AuthorStatus',
            'NotifyUserID' => $authorID,
            'HeadlineFormat' => $headline,
            'Story' => $lead.' '.$story,
            'RecordType' => 'Discussion',
            'RecordID' => $discussionID,
            'Route' => '/discussion/'.$discussionID,
            'Format' => 'HTML'
        ];

        $activityModel = new ActivityModel();
        $activityModel->queue($activity, 'AuthorStatus', ['Force' => true]);
        $activityModel->saveQueue();
    }


    /**
     * Notifies the voters on an idea of a status change.
     *
     * @param int $discussionID The discussion whose status has changed
     * @param string $discussionName The Idea-type discussion name
     * @param array $newStatus An array representation of the status
     * @param string $statusNotes The notes on the discussion's status.
     * @throws Exception
     */
    public function notifyVoters($discussionID, $discussionName, $newStatus, $statusNotes = '') {
        if (sizeof($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }

        $voters = $this->getVoterIDs($discussionID);
        $headline = t('Progress on an idea you voted on!');
        $lead = sprintf(t('The status for "%s" has changed to %s.'),
            $discussionName,
            '<strong>'.val('Name', $newStatus).'</strong>'
        );

        $story = ($statusNotes) ? '<br/><br/>'.sprintf(t('%s: %s'), t('Notes'), $statusNotes) : '';
        $story .= '<br/><br/>'.sprintf(t("Voting for the idea is %s."), strtolower(val('State', $newStatus)));

        foreach($voters as $voter) {
            $activity = [
                'ActivityType' => 'VoterStatus',
                'NotifyUserID' => $voter,
                'HeadlineFormat' => $headline,
                'Story' => $lead.' '.$story,
                'RecordType' => 'Discussion',
                'RecordID' => $discussionID,
                'Route' => '/discussion/'.$discussionID,
                'Format' => 'HTML'
            ];

            $activityModel = new ActivityModel();
            $activityModel->queue($activity, 'VoterStatus');
            $activityModel->saveQueue();
        }
    }

    /**
     * Adds status notification options to profiles.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.AuthorStatus'] = t('Notify me when my ideas\' statuses change.');
        $sender->Preferences['Notifications']['Popup.AuthorStatus'] = t('Notify me when my ideas\' statuses change.');

        $sender->Preferences['Notifications']['Email.VoterStatus'] = t('Notify me when the status changes on an idea I\'ve voted on.');
        $sender->Preferences['Notifications']['Popup.VoterStatus'] = t('Notify me when the status changes on an idea I\'ve voted on.');
    }

    /**
     * HELPERS
     * -------
     */

    /**
     * Gets the status notes for a given discussion array.
     *
     * @param object|array $discussion The discussion to get the notes for.
     * @param DiscussionModel|null $discussionModel If it exists, pass it in.
     * @return string The notes on the discussion's status.
     */
    public function getStatusNotes($discussion, $discussionModel = null) {
        if (!$discussionModel) {
            $discussionModel = new DiscussionModel();
        }
        return $discussionModel->getRecordAttribute($discussion, 'StatusNotes');
    }

    /**
     * Gets the Idea-type reaction name from a tag ID.
     *
     * @param int $tagID The tag ID to get the reaction name from.
     * @return string The reaction name of the reaction with the corresponding tag ID.
     */
    public function getReactionFromTagID($tagID) {
        if ($tagID == self::getUpTagID()) {
            return self::REACTION_UP;
        }
        if ($tagID ==self::getDownTagID()) {
            return self::REACTION_DOWN;
        }
        return '';
    }

    /**
     * Returns an array of all the users who have voted on a given discussion.
     *
     * @param int $discussionID The discussion ID.
     * @return array The users who have voted on a discussion.
     */
    public function getVoterIDs($discussionID) {
        $userTagModel = new Gdn_Model('UserTag');
        $userTags = $userTagModel->getWhere([
            'RecordType' => 'Discussion',
            'RecordID' => $discussionID,
            'TagID' => [self::getUpTagID(), self::getDownTagID()]
        ])->resultArray();
        $users = [];
        if ($userTags && !empty($userTags)) {
            foreach($userTags as $userTag) {
                $users[] = val('UserID', $userTag);
            }
        }
        return $users;
    }

    /**
     * Determines whether a discussion is an Idea-type.
     *
     * @param int|object|array $discussion The discussion ID, object or array.
     * @return bool Whether a discussion is an Idea-type.
     */
    public function isIdea($discussion) {
        if (is_numeric($discussion)) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussion);
        }
        return (strtolower(val('Type', $discussion)) === 'idea');
    }

    /**
     * Returns an array of Idea-type category IDs.
     */
    public function getIdeaCategoryIDs() {
        $ideaCategoryIDs = [];
        $categories = CategoryModel::categories();
        foreach($categories as $category) {
            if ($this->isIdeaCategory($category)) {
                $ideaCategoryIDs[] = val('CategoryID', $category);
            }
        }
        return $ideaCategoryIDs;
    }

    /**
     * Determines whether a category is an Idea category.
     *
     * @param object|array $category The category to check.
     * @return bool Whether the category is an Idea category.
     */
    public function isIdeaCategory($category) {
        return val(self::CATEGORY_IDEATION_COLUMN_NAME, $category, false);
    }

    /**
     * Determines whether a given discussion or category uses downvotes.
     *
     * @param object|array $data The category or discussion to check.
     * @param string $datatype What datatype we're checking, either 'category' or 'discussion'.
     * @return bool Whether the given discussion or category uses downvotes.
     */
    public function allowDownVotes($data, $datatype = 'category') {
        switch(strtolower($datatype)) {
            case 'category' :
                $category = $data;
                break;
            case 'discussion' :
                $category = CategoryModel::categories(val('CategoryID', $data));
                break;
            default:
                $category = [];
        }

        return val(self::CATEGORY_IDEATION_COLUMN_NAME, $category, false) === self::CATEGORY_TYPE_UP_AND_DOWN;
    }

    /**
     * Returns the total number of votes on an idea.
     *
     * @param object $discussion The discussion to get the total votes for.
     * @return int The total number of votes on a discussion.
     */
    public static function getTotalVotes($discussion) {
        if (val('Attributes', $discussion) && $reactions = val('React', $discussion->Attributes)) {
            $noUp = val(self::REACTION_UP, $reactions, 0);
            $noDown = val(self::REACTION_DOWN, $reactions, 0);
            return $noUp + $noDown;
        }
        return 0;
    }
}

/**
 * RENDERING FUNCTIONS
 * -------------------
 * Set apart so they can be overridden.
 */


if (!function_exists('getReactionButtonHtml')) {
    /**
     * Renders ideation reaction buttons (for upvotes and downvotes).
     *
     * @param string $cssClass The reaction-specific css class.
     * @param string $url The url for the reaction.
     * @param string $label The reaction's label.
     * @param string $urlCode The url code of the reaction to be appended to the arrow css class.
     * @param string $dataAttr The data attribute for the reaction (used in reaction javascript).
     * @return string HTML representation of the ideation reactions (up and down votes).
     */
    function getReactionButtonHtml($cssClass, $url, $label, $urlCode, $dataAttr = '') {
        return '<a class="Hijack idea-button '.$cssClass.'" href="'.$url.'" title="'.$label.'" '.$dataAttr.' rel="nofollow"><span class="arrow arrow-'.$urlCode.'"></span> <span class="idea-label">'.$label.'</span></a>';
    }
}

if (!function_exists('getScoreHtml')) {
    /**
     * Renders the score block, used in the idea counter.
     *
     * @param int|string $score The score.
     * @return string HTML representation of the score block.
     */
    function getScoreHtml($score) {
        return '<div class="score">'.$score.'</div>';
    }
}

if (!function_exists('getVotesHtml')) {
    /**
     * Renders the votes block, used in the idea counter.
     *
     * @param int|string $votes The number of votes.
     * @return string HTML representation of the votes block.
     */
    function getVotesHtml($votes) {
        return '<div class="votes meta">'.sprintf(t('%s votes'), $votes).'</div>';
    }
}

if (!function_exists('getStatusTagHtml')) {
    /**
     * Renders the status tags for the discussion list.
     *
     * @param string $statusName The name of the status.
     * @param string $statusCode The url-code of the status.
     * @return string The status tag.
     */
    function getStatusTagHtml($statusName, $statusCode = '') {
        if (empty($statusCode)) {
            $statusCode = urlencode($statusName);
        }
        return ' <a href="'.url('/discussions/tagged/'.$statusCode).'"><span class="Tag Status-Tag-'.$statusCode.'"">'.$statusName.'</span></a> ';
    }
}


