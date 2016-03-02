<?php if (!defined('APPLICATION')) exit;

$PluginInfo['ideation'] = [
    'Name'        => "Ideation",
    'Description' => "Let users vote on discussions in a Idea category",
    'Version'     => '1.0.0',
    'Author'      => "Becky Van Bussel",
    'AuthorEmail' => 'becky@vanillaforums.com',
    'License'     => 'Proprietary'
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
    const REACTION_UP = 'IdeaUp';

    /**
     * The Reaction name of the downvote.
     */
    const REACTION_DOWN = 'IdeaDown';

    /**
     * @var int The tag ID of the upvote reaction.
     */
    protected static $upTagID;

    /**
     * @var int The tag ID of the downvote reaction.
     */
    protected static $downTagID;

    /**
     * @var int The ID of the default stage for new ideas.
     */
    protected $defaultStageID;

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
     * Get the default stage ID. Fetches from config if not set.
     *
     * @return int|string The default stage ID.
     */
    public function getDefaultStageID() {
        if (!$this->defaultStageID) {
            $this->defaultStageID = c('Plugins.Ideation.DefaultStageID', 1);
        }
        return $this->defaultStageID;
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
     * Adds Stages link to Dashboard menu.
     *
     * @param Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Forum', t('Stages'), '/dashboard/settings/stages', 'Garden.Settings.Manage', array('class' => 'nav-stages'));
    }

    /**
     * Adds ideation options to the categories setting page -> enabling ideation on a category and enabling downvotes.
     * Also manipulates the allowed discussion types options when ideation is enabled on a category.
     * Ideas are the only discussion type allowed in an ideation category.
     *
     * @param SettingsController $sender
     */
    public function settingsController_addEditCategory_handler($sender) {

        $sender->addJsFile('ideation.js', 'plugins/ideation'); // Show/hide allowed discussions and downvote option

        $categoryID = val('CategoryID', $sender->Data);
        $category = CategoryModel::categories($categoryID);
        $ideaOptions = [];

        if ($this->isIdeaCategory($category)) {
            $ideaOptions = ['checked' => 'checked'];
        }

        $sender->Data['_ExtendedFields']['IsIdea'] = ['Name' => 'Idea Category', 'Control' => 'CheckBox', 'Description' => '<strong>Ideation</strong> <small><a href="#">Learn more about ideas</a></small>', 'Options' => $ideaOptions];
        $sender->Data['_ExtendedFields']['UseDownVotes'] = ['Name' => 'UseDownVotes', 'Control' => 'CheckBox'];

        if ($sender->Form->authenticatedPostBack()) {
            $isIdea = $sender->Form->getValue('Idea_Category');

            if ($isIdea) {
                // If it's an idea category, ideas are the only discussion type allowed.
                $types[] = 'Idea';
                $sender->Form->setFormValue('AllowedDiscussionTypes', $types);
            } else {
                // We don't allow users to explicitly set the idea discussion type as allowed.
                // If we're not an idea category, ensure that the idea discussion type is not allowed.
                $types = $sender->Form->getValue('AllowedDiscussionTypes');
                if (($key = array_search('Idea', $types)) !== false) {
                    unset($types[$key]);
                }
                $sender->Form->setFormValue('AllowedDiscussionTypes', $types);
            }

            // Strict mode compliant.
            $sender->Form->setFormValue('IsIdea', forceBool($sender->Form->getFormValue('IsIdea'), '0', '1', '0'));
            $sender->Form->setFormValue('UseDownVotes', forceBool($sender->Form->getFormValue('UseDownVotes'), '0', '1', '0'));
        }
    }

    /**
     * STAGE SETTINGS
     * --------------
     */

    /**
     * Adds endpoint for editing a stage, renders the form and performs the update operation.
     *
     * @param SettingsController $sender
     * @param $stageID The ID of the stage to edit.
     * @throws Exception
     */
    public function settingsController_editStage_create($sender, $stageID) {
        if (!$stageID) {
            throw NotFoundException('Stage');
        }
        $sender->title(sprintf(t('Edit %s'), t('Stage')));
        $this->addEdit($sender, $stageID);
    }

    /**
     * Adds endpoint for adding a stage, renders the form and performs the insert operation.
     *
     * @param SettingsController $sender
     * @throws Exception
     */
    public function settingsController_addStage_create($sender) {
        $sender->title(sprintf(t('Add %s'), t('Stage')));
        $this->addEdit($sender);
    }

    /**
     * Renders the add/edit stage form and performs the corresponding operation.
     *
     * @param SettingsController $sender
     * @param int $stageID The ID of the stage to edit.
     * @throws Exception
     */
    protected function addEdit($sender, $stageID = 0) {
        $sender->permission('Garden.Settings.Manage');
        $stageModel = new StageModel();

        if ($sender->Form->authenticatedPostBack()) {
            $data = $sender->Form->formValues();
            $result = $stageModel->save(val('Name', $data), val('Status', $data), val('Description', $data), $stageID);
            $sender->Form->setValidationResults($stageModel->validationResults());
            if ($result) {
                if (val('IsDefaultStage', $data, false)) {
                    saveToConfig('Plugins.Ideation.DefaultStageID', $stageID);
                }
                $sender->informMessage(t('Your changes have been saved.'));
                $sender->RedirectUrl = url('/settings/stages');
                $sender->setData('Stage', StageModel::getStage($result));
            }
        } elseif ($stageID) {
            // We're about to edit, set up the data from the stage.
            $data = StageModel::getStage($stageID);
            if (!$data) {
                throw NotFoundException('Stage');
            }
            $data['IsDefaultStage'] = (c('Plugins.Ideation.DefaultStageID') == $stageID);
            $sender->Form->setData($data);
            $sender->Form->addHidden('StageID', $stageID);
        }
        $sender->addSideMenu();
        $sender->render('stage', '', 'plugins/ideation');
    }

    /**
     * Adds endpoint for deleting a stage, renders the form and performs the delete operation.
     *
     * @param SettingsController $sender
     * @param $stageID
     */
    public function settingsController_deleteStage_create($sender, $stageID) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            if ($sender->Form->getFormValue('Yes')) {
                $stageModel = new StageModel();
                $stageModel->delete(['StageID' => $stageID]);
                $sender->jsonTarget("#Stage_$stageID", NULL, 'SlideUp');
            }
        }

        $sender->title(sprintf(t('Delete %s'), t('Stage')));
        $sender->render('DeleteStage', '', 'plugins/ideation');
    }

    /**
     * Adds endpoint in the dashboard for viewing all stages and renders the view.
     *
     * @param SettingsController $sender
     */
    public function settingsController_stages_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Stages', StageModel::getStages());
        $sender->setData('DefaultStageID', c('Plugins.Ideation.DefaultStageID', 1));
        $sender->setData('Title', sprintf(t('All %s'), t('Stages')));
        $sender->addSideMenu();
        $sender->render('stages', '', 'plugins/ideation');
    }

    /**
     * IDEA POST FORM
     * --------------
     * Adds a new, stripped-down post type: 'Idea', without announcing or tagging capabilities beyond stage-type tags.
     */

    /**
     * Add an Idea discussion type.
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_discussionTypes_handler($sender, $args) {
        $args['Types']['Idea'] = [
            'Singular' => 'Idea',
            'Plural' => 'Ideas',
            'AddUrl' => '/post/idea',
            'AddText' => 'New Idea'
        ];
    }

    /**
     * Adds a post/idea endpoint, ensures there's a category and that the category is an idea category,
     * and then sets the default stage and Idea type on the discussion post.
     *
     * @param PostController $sender
     * @param array $args The event arguments. The first argument should be the category slug.
     * @throws Exception
     */
    public function postController_idea_create($sender, $args) {
        $categoryCode = val(0, $args, '');
        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $sender->Form->setFormValue('Tags', val('TagID', StageModel::getStage($this->getDefaultStageID())));
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
     * STAGE TAGGING
     * -------------
     */

    /**
     * Register a new tag type: Stage
     *
     * @param TagModel $sender
     */
    public function tagModel_types_handler($sender) {
        $sender->addType('Stage', [
            'key' => 'Stage',
            'name' => 'Stage',
            'plural' => 'Stages',
            'addtag' => false,
            'default' => false
        ]);
    }

    /**
     * Registers reserved Stage-type tags.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_reservedTags_handler($sender, $args) {
        if (isset($args['ReservedTags'])) {
            $stages = StageModel::getStages();
            foreach ($stages as $stage) {
                $tagName = val('Name', TagModel::instance()->getID(val('TagID', $stage)));
                $args['ReservedTags'][] = $tagName;
            }
        }
    }

    /**
     * Prints Stage-type tags on discussions in a discussion list
     *
     * @param Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = val('Discussion', $args);
        if (!$this->isIdea($discussion)) {
            return;
        }
        $stage = StageModel::getStageByDiscussion(val('DiscussionID', $discussion));
        if ($stage) {
            echo getStageTagHtml(val('Name', $stage));
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
     * Handles the discussion options (the links in the cog dropdown) for an idea. Adds a link to edit the stage,
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
            $args['DiscussionOptions']['Stage'] = ['Label' => sprintf(t('Edit %s'), t('Stage')), 'Url' => '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup'];
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor(sprintf(t('Edit %s'), t('Stage')), '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Popup').'</li>';
        }

        if (isset($args['DiscussionOptions']['EditDiscussion'])) {
            $args['DiscussionOptions']['EditDiscussion']['Url'] = str_replace('editdiscussion', 'editidea', $args['DiscussionOptions']['EditDiscussion']['Url']);
        } else {
            $sender->Options = str_replace('editdiscussion', 'editidea', $sender->Options);
        }

        if (isset($args['DiscussionOptions']['DeleteDiscussion'])) {
            $args['DiscussionOptions']['DeleteDiscussion']['Label'] = sprintf(t('Delete %s'), t('Idea'));
        }
    }

    /**
     * Renders stage options form and handles editting the stage and/or stage notes.
     *
     * @param DiscussionController $sender
     * @param string|int $discussionID The ID of the Idea-type discussion
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function discussionController_stageOptions_create($sender, $discussionID = '') {
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
                $this->updateDiscussionStage($discussion, $sender->Form->getFormValue('Stage'), $sender->Form->getFormValue('StageNotes'));
                Gdn::controller()->jsonTarget('', '', 'Refresh');
            }

            $stages = StageModel::getStages();
            foreach($stages as &$stage) {
                $stage = val('Name', $stage);
            }
            $notes = $this->getStageNotes($discussion, $sender->DiscussionModel);

            $sender->setData('Discussion', $discussion);
            $sender->setData('Stages', $stages);
            $sender->setData('StageNotes', $notes);
            $sender->setData('CurrentStageID', val('StageID', StageModel::getStageByDiscussion($discussionID)));
            $sender->setData('Title', sprintf(t('Edit %s'), t('Stage')));

            $sender->render('StageOptions', '', 'plugins/ideation');
        }
    }

    /**
     * DISCUSSION STAGE
     * ----------------
     * Stage notes are serialized and stored in the Attributes column. We find out what stage a discussion has
     * by its tag (in the TagDiscussion table). Any Idea-type discussion will also have its stage tag as the only TagID
     * in the Discussion table. There are attachments on a discussion that display the stage info that need to be
     * updated when the StageNotes or StageTag is changed.
     */

    /**
     * Update a discussion's stage. Handles notifications, updating the stage tag, stage notes, and attachments.
     *
     * @param object|array $discussion The discussion to update
     * @param int $stageID The new stage for the discussion
     * @param string $notes The stage notes
     */
    public function updateDiscussionStage($discussion, $stageID, $notes) {
        if (!$this->isIdea($discussion) || !is_numeric($stageID)) {
            return;
        }
        $discussionID = val('DiscussionID', $discussion);
        $newStage = StageModel::getStage($stageID);
        $this->updateDiscussionStageTag($discussionID, $stageID);
        if ($notes) {
            $this->updateDiscussionStageNotes($discussionID, $notes);
        }
        $this->updateAttachment($discussionID, $stageID, $notes);
        $this->notifyIdeaAuthor(val('InsertUserID', $discussion), $discussionID, val('Name', $discussion), $newStage);
        $this->notifyVoters($discussionID, val('Name', $discussion), $newStage);
    }

    /**
     * Updates the tag on a discussion. Updates the TagDiscussion table and the TagID column of the Discussion table.
     *
     * @param int $discussionID The ID of the discussion to update
     * @param int $stageID The new stage ID
     */
    protected function updateDiscussionStageTag($discussionID, $stageID) {
        // TODO: Logging. We shoud probably keep a record of this.

        $oldStage = StageModel::getStageByDiscussion($discussionID);
        // Don't change anything if nothing's changed.
        if (val('StageID', $oldStage) != $stageID) {

            // Save tag info in TagDiscussion
            $stage = StageModel::getStage($stageID);
            $tags = [val('TagID', $stage)];
            TagModel::instance()->saveDiscussion($discussionID, $tags, ['Stage']);

            // Save tags in discussions table
            $discussionModel = new DiscussionModel();
            $discussionModel->setField($discussionID, 'Tags', val('TagID', $stage));
        }
    }

    /**
     * Saves the discussion stage notes in its Attributes.
     *
     * @param int $discussionID The ID of the discussion to update
     * @param string $notes The new notes to save
     * @throws Exception
     */
    protected function updateDiscussionStageNotes($discussionID, $notes) {
        $discussionModel = new DiscussionModel();
        $discussionModel->saveToSerializedColumn('Attributes', $discussionID, 'StageNotes', $notes);
    }

    /**
     * ATTACHMENTS
     * -----------
     * Attachments appear in the discussion view. They include the stage, stage description and stage notes.
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
                $this->updateAttachment(val('DiscussionID', $args), self::getDefaultStageID(), '');
            }
        }
    }

    /**
     * Updates the attachment for a discussion. Attachments include the stage info (name, description, status, notes).
     *
     * @param $discussionID
     * @param $stageID
     * @param $stageNotes
     */
    protected function updateAttachment($discussionID, $stageID, $stageNotes) {

        $stage = StageModel::getStage($stageID);
        $attachment['Type'] = 'stage';
        $attachment['StageName'] = val('Name', $stage);
        $attachment['StageDescription'] = val('Description', $stage);
        $attachment['StageStatus'] = val('Status', $stage);
        $attachment['StageNotes'] = $stageNotes;
        $attachment['StageUrl'] = url('/discussions/tagged/'.urlencode(val('StageName', $attachment)));
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
                if (val('Type', $oldAttachment) == 'stage') {
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
     * Shuts down any reacting to ideas with closed statuses.
     *
     * @param ReactionModel $sender
     * @param array $args
     */
    public function reactionModel_getReaction_handler($sender, $args) {
        if ($reaction = val('ReactionType', $args)) {
            if ((val('UrlCode', $reaction) == self::REACTION_UP) || (val('UrlCode', $reaction) == self::REACTION_DOWN)) {
                $stageModel = new StageModel();
                if (strtolower(val('RecordType', $args) == 'discussion')
                    && (val('Status', $stageModel->getStageByDiscussion(val('RecordID', $args))) == 'Closed')) {
                    $args['ReactionType']['Active'] = false;
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

        $discussion = val('Record', $args);
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
    public function addUserVotesToDiscussions($discussions) {
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
    public function getUserVoteReaction($discussion = null, $user = null) {
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
    public static function getIdeaReactionButton($discussion, $urlCode, $reaction = null, $options = []) {
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

        return getReactionButtonHtml($linkClass, $url, $label, $dataAttr);
    }

    /**
     * SORT/FILTER
     * -----------
     */

    /**
     * Adds stage and status filtering to the DiscussionFilterModule.
     */
    public function discussionModel_discussionFilters_handler() {
        $categories = $this->getIdeaCategoryIDs();
        DiscussionModel::addFilterSet('stage', sprintf(t('All %s'), t('Stages')), $categories);

        // Open status
        $openStages = StageModel::getOpenStages();
        $openTags = [];
        foreach ($openStages as $openStage) {
            $openTags[] = val('TagID', $openStage);
        }
        DiscussionModel::addFilter('open', 'Status: Open',
            ['d.Tags' => $openTags], 'status', 'stage'
        );

        // Closed status
        $closedStages = StageModel::getClosedStages();
        $closedTags = [];
        foreach ($closedStages as $closedStage) {
            $closedTags[] = val('TagID', $closedStage);
        }
        DiscussionModel::addFilter('closed', 'Status: Closed',
            ['d.Tags' => $closedTags], 'status', 'stage'
        );

        // Stages
        foreach(StageModel::getStages() as $stage) {
            DiscussionModel::addFilter(strtolower(val('Name', $stage)).'-stage' , val('Name', $stage),
                ['d.Tags' => val('TagID', $stage)], 'stage', 'stage'
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
        $discussionSortFilterModule = new DiscussionsSortFilterModule($categoryID);
        echo $discussionSortFilterModule;
    }

    /**
     * NOTIFICATIONS/ACTIVITY
     * ----------------------
     * Uses activity notifications to notify idea creators and voters of stage changes.
     */

    /**
     * Notifies the author of an idea of a stage change.
     *
     * @param int $authorID The ID of the idea author
     * @param int $discussionID The discussion whose stage has changed
     * @param string $discussionName The Idea-type discussion name
     * @param array $newStage An array representation of the stage
     * @throws Exception
     */
    public function notifyIdeaAuthor($authorID, $discussionID, $discussionName, $newStage) {
        if (sizeof($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }
        $headline = t("Progress on your idea!");
        $lead = sprintf(t('The stage for "%s" has changed to %s.'),
            $discussionName,
            '<strong>'.val('Name', $newStage).'</strong>'
        );
        $story = ' '.sprintf(t("Voting for the idea is now %s."), strtolower(val('Status', $newStage)));

        $activity = [
            'ActivityType' => 'AuthorStage',
            'NotifyUserID' => $authorID,
            'HeadlineFormat' => $headline,
            'Story' => $lead.' '.$story,
            'RecordType' => 'Discussion',
            'RecordID' => $discussionID,
            'Route' => '/discussion/'.$discussionID,
            'Format' => 'HTML'
        ];

        $activityModel = new ActivityModel();
        $activityModel->queue($activity, 'AuthorStage', ['Force' => true]);
        $activityModel->saveQueue();
    }


    /**
     * Notifies the voters on an idea of a stage change.
     *
     * @param int $discussionID The discussion whose stage has changed
     * @param string $discussionName The Idea-type discussion name
     * @param array $newStage An array representation of the stage
     * @throws Exception
     */
    public function notifyVoters($discussionID, $discussionName, $newStage) {
        if (sizeof($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }

        $voters = $this->getVoterIDs($discussionID);
        $headline = t('Progress on an idea you voted on!');
        $lead = sprintf(t('The stage for "%s" has changed to %s.'),
            $discussionName,
            '<strong>'.val('Name', $newStage).'</strong>'
        );
        $story = ' '.sprintf(t("Voting for the idea is now %s."), strtolower(val('Status', $newStage)));

        foreach($voters as $voter) {
            $activity = [
                'ActivityType' => 'VoterStage',
                'NotifyUserID' => $voter,
                'HeadlineFormat' => $headline,
                'Story' => $lead.' '.$story,
                'RecordType' => 'Discussion',
                'RecordID' => $discussionID,
                'Route' => '/discussion/'.$discussionID,
                'Format' => 'HTML'
            ];

            $activityModel = new ActivityModel();
            $activityModel->queue($activity, 'VoterStage');
            $activityModel->saveQueue();
        }
    }

    /**
     * Adds stage notification options to profiles.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.AuthorStage'] = t('Notify me when my ideas\' stages change.');
        $sender->Preferences['Notifications']['Popup.AuthorStage'] = t('Notify me when my ideas\' stages change.');

        $sender->Preferences['Notifications']['Email.VoterStage'] = t('Notify me when the stage changes on an idea I\'ve voted on.');
        $sender->Preferences['Notifications']['Popup.VoterStage'] = t('Notify me when the stage changes on an idea I\'ve voted on.');
    }

    /**
     * HELPERS
     * -------
     */

    /**
     * Gets the stage notes for a given discussion array.
     *
     * @param object|array $discussion The discussion to get the notes for.
     * @param DiscussionModel|null $discussionModel If it exists, pass it in.
     * @return string The notes on the discussion's stage.
     */
    public function getStageNotes($discussion, $discussionModel = null) {
        if (!$discussionModel) {
            $discussionModel = new DiscussionModel();
        }
        return $discussionModel->getRecordAttribute($discussion, 'StageNotes');
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
            if (val('AllowedDiscussionTypes', $category) && in_array('Idea', val('AllowedDiscussionTypes', $category, []))) {
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
        return val('AllowedDiscussionTypes', $category) && in_array('Idea', val('AllowedDiscussionTypes', $category));
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

        return val('UseDownVotes', $category, false);
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
     * @param string $dataAttr The data attribute for the reaction (used in reaction javascript).
     * @return string HTML representation of the ideation reactions (up and down votes).
     */
    function getReactionButtonHtml($cssClass, $url, $label, $dataAttr = '') {
        return '<a class="Hijack idea-button '.$cssClass.'" href="'.$url.'" title="'.$label.'" '.$dataAttr.' rel="nofollow"><span class="arrow arrow-'.strtolower($label).'"></span> <span class="idea-label">'.$label.'</span></a>';
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

if (!function_exists('getStageTagHtml')) {
    /**
     * Renders the stage tags for the discussion list.
     *
     * @param string $stageName The name of the stage.
     * @param string $stageCode The url-code of the stage.
     * @return string The stage tag.
     */
    function getStageTagHtml($stageName, $stageCode = '') {
        if (empty($stageCode)) {
            $stageCode = urlencode($stageName);
        }
        return ' <a href="'.url('/discussions/tagged/'.$stageCode).'"><span class="Tag Stage-Tag-'.$stageCode.'"">'.$stageName.'</span></a> ';
    }
}


