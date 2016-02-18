<?php if (!defined('APPLICATION')) exit;

$PluginInfo['ideation'] = array(
    'Name'        => "Ideation",
    'Description' => "Let users vote on discussions in a Idea category",
    'Version'     => '1.0.0',
    'Author'      => "Becky Van Bussel",
    'AuthorEmail' => 'becky@vanillaforums.com',
    'License'     => 'Proprietary'
);

/**
 * Ideation Plugin
 *
 * @author    Becky Van Bussel <becky@vanillaforums.com>
 * @license   Proprietary
 * @since     2.2
 */
class IdeationPlugin extends Gdn_Plugin {

    /**
     *
     */
    const REACTION_UP = 'IdeaUp';
    /**
     *
     */
    const REACTION_DOWN = 'IdeaDown';

    /**
     * @var
     */
    protected static $upTagID;
    /**
     * @var
     */
    protected static $downTagID;

    /**
     * @var
     */
    protected $defaultStageID;

    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        $this->structure();
    }

    /**
     *
     */
    public function structure() {
        require dirname(__FILE__).'/structure.php';
    }

    /**
     * @param $sender
     */
    public function base_render_before($sender) {
        $sender->addJsFile('ideation.js', 'plugins/ideation');
        $sender->addCssFile('ideation.css', 'plugins/ideation');
    }

    /**
     * @return mixed
     */
    public function getDefaultStageID() {
        if (!$this->defaultStageID) {
            $this->defaultStageID = c('Plugins.Ideation.DefaultStageID', 1);
        }
        return $this->defaultStageID;
    }

    /**
     * @return int
     */
    public static function getUpTagID() {
        if (!self::$upTagID) {
            $reactionUp = ReactionModel::ReactionTypes(self::REACTION_UP);
            self::setUpTagID(val('TagID', $reactionUp));
        }
        return self::$upTagID;
    }

    /**
     * @param int $upTagID
     */
    protected static function setUpTagID($upTagID) {
        self::$upTagID = $upTagID;
    }

    /**
     * @return int
     */
    public static function getDownTagID() {
        if (!self::$downTagID) {
            $reactionDown = ReactionModel::ReactionTypes(self::REACTION_DOWN);
            self::setDownTagID(val('TagID', $reactionDown));
        }
        return self::$downTagID;
    }

    /**
     * @param int $downTagID
     */
    protected static function setDownTagID($downTagID) {
        self::$downTagID = $downTagID;
    }

    /**
     * CATEGORY SETTINGS
     * -----------------
     */

    /**
     * Adds ideation options to the categories setting page -> enabling ideation on a category and enabling downvotes.
     * Also manipulates the allowed discussion types options when ideation is enabled on a category.
     * Ideas are the only discussion type allowed in an ideation category.
     *
     * @param SettingsController $sender
     */
    public function settingsController_addEditCategory_handler($sender) {
        $categoryID = val('CategoryID', $sender->Data);
        $category = CategoryModel::categories($categoryID);
        $ideaOptions = array();

        if ($this->isIdeaCategory($category)) {
            $ideaOptions = array('checked' => 'checked');
        }

        $sender->Data['_ExtendedFields']['IsIdea'] = array('Name' => 'Idea Category', 'Control' => 'CheckBox', 'Description' => '<strong>Ideation</strong> <small><a href="#">Learn more about ideas</a></small>', 'Options' => $ideaOptions);
        $sender->Data['_ExtendedFields']['UseDownVotes'] = array('Name' => 'UseDownVotes', 'Control' => 'CheckBox');

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
        $sender->title(sprintf(T('Edit %s'), T('Stage')));
        $this->addEdit($sender, $stageID);
    }

    /**
     * Adds endpoint for adding a stage, renders the form and performs the insert operation.
     *
     * @param SettingsController $sender
     * @throws Exception
     */
    public function settingsController_addStage_create($sender) {
        $sender->title(sprintf(T('Add %s'), T('Stage')));
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
            $result = $stageModel->save(val('Name', $data), val('Status', $data), val('Description', $data), array(), $stageID);
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
                $stageModel->delete(array('StageID' => $stageID));
                $sender->jsonTarget("#Stage_$stageID", NULL, 'SlideUp');
            }
        }

        $sender->title(sprintf(T('Delete %s'), T('Stage')));
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
        $args['Types']['Idea'] = array(
            'Singular' => 'Idea',
            'Plural' => 'Ideas',
            'AddUrl' => '/post/idea',
            'AddText' => 'New Idea',
            'Global' => false // Don't show in the global new discussion module.
        );
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
        //TODO: Permission to post idea?

        if (!sizeof($args)) {
            // No category was specified.
            throw NotFoundException('Category');
        }
        $categoryCode = $args[0];
        $category = CategoryModel::categories($categoryCode);

        if (!$this->isIdeaCategory($category)) {
            throw new Exception(t('An idea can only be posted in an Idea category.'), 401);
        }
        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $sender->Form->setFormValue('StageID', $this->getDefaultStageID());
        $sender->Form->setFormValue('CategoryID', val('CategoryID', $category));
        $sender->Form->setFormValue('Tags', val('TagID', StageModel::getStage($this->getDefaultStageID())));
        $sender->View = 'discussion';
        $sender->discussion($categoryCode);
    }

    /**
     * Adds a post/editidea endpoint
     *
     * @param PostController $sender
     * @param $args
     * @throws Exception
     */
    public function postController_editidea_create($sender, $args) {
        // TODO: Permission... Who can edit an idea?

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
            $sender->ShowCategorySelector = false;

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
        $sender->addType('Stage', array(
            'key' => 'Stage',
            'name' => 'Stage',
            'plural' => 'Stages',
            'addtag' => false,
            'default' => false
        ));
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
            echo ' <a href="'.url('/discussions/tagged/'.urlencode(val('Name', $stage))).'"><span class="Tag Stage-Tag-'.urlencode(val('Name', $stage)).'"">'.val('Name', $stage).'</span></a> ';
//            echo ' <span class="MItem MCount IdeaVoteCount"><span class="Number">'.self::getTotalVotes($discussion).'</span> '.t('votes').'</span>';
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
            $this->renderIdeaCounter($discussion);
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
            $this->renderIdeaCounter($discussion);
        }
    }

    /**
     * Renders the idea counter module for a discussion.
     *
     * @param object $discussion
     */
    public function renderIdeaCounter($discussion) {
        $ideaCounterModule = IdeaCounterModule::instance();
        $userVote = '';
        if ($tagID = val('UserVote', $discussion)) {
            $userVote = $this->getReactionFromTagID($tagID);
        }
        $ideaCounterModule->setUserVote($userVote);
        $useDownVotes = $this->allowDownVotes($discussion, 'discussion');
        $ideaCounterModule->setUseDownVotes($useDownVotes);
        $ideaCounterModule->setDiscussion($discussion);
        echo $ideaCounterModule->toString();
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

        $userVote = $this->getUserVoteReaction();
        $useDownVotes = $this->allowDownVotes($discussion, 'discussion');

        // Set counter module for rendering in attachment
        $ideaCounterModule = IdeaCounterModule::instance();
        $ideaCounterModule->setDiscussion($discussion);
        $ideaCounterModule->setShowVotes(true);
        $ideaCounterModule->setUseDownVotes($useDownVotes);
        $ideaCounterModule->setUserVote($userVote);
        $sender->setData('IdeaCounterModule', $ideaCounterModule);
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
     * @param $sender
     * @param $args
     */
    public function base_discussionOptions_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!$this->isIdea($discussion)) {
            return;
        }

        // TODO permission
        if (!Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['Stage'] = array('Label' => T('Edit Stage'), 'Url' => '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup');
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor(t('Edit Stage'), '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Popup') . '</li>';
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
     * @param $sender
     * @param string $discussionID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function discussionController_stageOptions_create($sender, $discussionID = '') {
        if ($discussionID) {
            $discussion = $sender->DiscussionModel->GetID($discussionID);
            if (!$discussion || !$this->isIdea($discussion)) {
                throw NotFoundException('Idea');
            }

            // TODO permission
            // $sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion));

            $sender->Form = new Gdn_Form();
            if ($sender->Form->authenticatedPostBack()) {
                $this->updateDiscussionStage($discussion, $sender->Form->getFormValue('Stage'), $sender->Form->getFormValue('StageNotes'));
                Gdn::controller()->jsonTarget('', '', 'Refresh');
            } else {
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
            $sender->setData('Title', t('Edit Stage'));

            $sender->render('StageOptions', '', 'plugins/ideation');
        }
    }

    /**
     * ATTACHMENTS
     * -----------
     */

    /**
     * @param $sender
     */
    public function discussionController_fetchAttachmentViews_handler($sender) {
        require_once $sender->fetchViewLocation('attachment', '', 'plugins/ideation');
    }

    /**
     * @param $sender
     * @param $args
     */
    public function discussionModel_AfterSaveDiscussion_handler($sender, $args) {
        if ($this->isIdea($discussionID = val('DiscussionID', $args))) {
            $stage = StageModel::getStageByDiscussion($discussionID);
            if (!$stage) {
                // We've got a new idea, add an attachment.
                $this->updateAttachment(val('DiscussionID', $args), self::getDefaultStageID(), '');
            }
        }
    }

    /**
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
        $attachment['ForeignID'] = 'd-'.$discussionID;
        $attachment['ForeignUserID'] = 2;
        $attachment['DateUpdated'] = Gdn_Format::toDateTime();

        // Kludge. Not Null fields
        $attachment['Source'] = 'none';
        $attachment['SourceID'] = 'none';
        $attachment['SourceURL'] = 'none';

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->GetID($discussionID);

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
     * Close down any reacting to ideas with closed statuses.
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

        $args['Button'] = getIdeaReactionButton($discussion, $urlCode, $reaction, array('cssClass' => $cssClass));

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
            $args['ReactionTypes'] = array();
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
                $args['Set'] = array();

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
     * @param array $args
     */
    public function discussionsController_render_before($sender, $args) {
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
     * @param array $args
     */
    public function categoriesController_render_before($sender, $args) {
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

        $userVotes = array();
        $tagIDs = array($this->getUpTagID(), $this->getDownTagID());

        $limit = 50; // TODO: Set this to be the same length of the discussions page.
        $user = Gdn::session();
        $userID = val('UserID', $user);

        if ($userID) {
            $reactionModel = new ReactionModel();

            // TODO: Cache this thing.
            $data = $reactionModel->GetRecordsWhere(array('TagID' => $tagIDs, 'RecordType' => array('Discussion'), 'UserID' => $userID, 'Total >' => 0),
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
    public static function getIdeaReactionButton($discussion, $urlCode, $reaction = null, $options = array()) {
        if (!$reaction) {
            $reaction = ReactionModel::ReactionTypes($urlCode);
        }

        $name = $reaction['Name'];
        $label = T($name);
        $id = GetValue('DiscussionID', $discussion);
        $linkClass = 'ReactButton-'.$urlCode.' '.val('cssClass', $options);
        $urlCode2 = strtolower($urlCode);
        $url = Url("/react/discussion/$urlCode2?id=$id&selfreact=true");
        $dataAttr = "data-reaction=\"$urlCode2\"";

        return getReactionButtonHtml($linkClass, $url, $label, $dataAttr);
    }

    // SORT/FILTER

    /**
     * @param $sender
     */
    public function categoriesController_index_before($sender) {
        $categoryCode = val('CategoryIdentifier', val('ReflectArgs', $sender, false));
        if (!$categoryCode || !$this->isIdeaCategory(CategoryModel::categories($categoryCode))) {
            DiscussionsSortFilterModule::addFilter('discussion-type', 'Discussions', array('d.Type' => null, 'd.Announce' => 0), 'type');
            DiscussionsSortFilterModule::addFilter('announcement-type', 'Announcements', array('d.Announce >' => 0), 'type');
            DiscussionsSortFilterModule::addFilter('question-type', 'Questions', array('d.Type' => 'Question'), 'type');
            DiscussionsSortFilterModule::addFilter('poll-type', 'Polls', array('d.Type' => 'poll'), 'type');
            return;
        }
        $openStages = StageModel::getOpenStages();
        $openTags = array();
        foreach ($openStages as $openStage) {
            $openTags[] = val('TagID', $openStage);
        }
        DiscussionsSortFilterModule::addFilter('open', 'Status: Open',
            array('d.Tags' => $openTags), 'status'
        );

        $closedStages = StageModel::getClosedStages();
        $closedTags = array();
        foreach ($closedStages as $closedStage) {
            $closedTags[] = val('TagID', $closedStage);
        }
        DiscussionsSortFilterModule::addFilter('closed', 'Status: Closed',
            array('d.Tags' => $closedTags), 'status'
        );
        foreach(StageModel::getStages() as $stage) {
            DiscussionsSortFilterModule::addFilter(strtolower(val('Name', $stage)).'-stage' , val('Name', $stage),
                array('d.Tags' => val('TagID', $stage)), 'stage'
            );
        }
    }

    /**
     * @param $sender
     */
    public function categoriesController_pageControls_handler($sender) {
        $categoryID = val('CategoryID', $sender);
        if (!$categoryID || !$this->isIdeaCategory(CategoryModel::categories($categoryID))) {
//            return;
        }
        $discussionSortFilterModule = new DiscussionsSortFilterModule();
        echo $discussionSortFilterModule;
    }

    // ACTIVITY

    /**
     * @param $authorID
     * @param $discussionID
     * @param $discussionName
     * @param $newStage
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

        $activity = array(
            'ActivityType' => 'AuthorStage',
            'NotifyUserID' => $authorID,
            'HeadlineFormat' => $headline,
            'Story' => $lead.' '.$story,
            'RecordType' => 'Discussion',
            'RecordID' => $discussionID,
            'Route' => '/discussion/'.$discussionID,
            'Format' => 'HTML'
        );

        $activityModel = new ActivityModel();
        $activityModel->queue($activity, 'AuthorStage');
        $activityModel->saveQueue();
    }


    /**
     * @param $discussionID
     * @param $discussionName
     * @param $newStage
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
            $activity = array(
                'ActivityType' => 'VoterStage',
                'NotifyUserID' => $voter,
                'HeadlineFormat' => $headline,
                'Story' => $lead.' '.$story,
                'RecordType' => 'Discussion',
                'RecordID' => $discussionID,
                'Route' => '/discussion/'.$discussionID,
                'Format' => 'HTML'
            );

            $activityModel = new ActivityModel();
            $activityModel->queue($activity, 'VoterStage');
            $activityModel->saveQueue();
        }
    }

    /**
     * Adds Stage notification options to profiles.
     *
     * @param object $sender ProfileController.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.AuthorStage'] = t('Notify me when my ideas\' stages change.');
        $sender->Preferences['Notifications']['Popup.AuthorStage'] = t('Notify me when my ideas\' stages change.');

        $sender->Preferences['Notifications']['Email.VoterStage'] = t('Notify me when the stage changes on an idea I\'ve voted on.');
        $sender->Preferences['Notifications']['Popup.VoterStage'] = t('Notify me when the stage changes on an idea I\'ve voted on.');
    }

    // HELPERS

    /**
     * @param $discussion
     * @param null $discussionModel
     * @return mixed
     */
    public function getStageNotes($discussion, $discussionModel = null) {
        if (!$discussionModel) {
            $discussionModel = new DiscussionModel();
        }
        return $discussionModel->getRecordAttribute($discussion, 'StageNotes');
    }

    /**
     * @param $tagID
     * @return string
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
     * @param $discussionID
     * @return array
     */
    public function getVoterIDs($discussionID) {
        $userTagModel = new Gdn_Model('UserTag');
        $userTags = $userTagModel->getWhere(array(
            'RecordType' => 'Discussion',
            'RecordID' => $discussionID,
            'TagID' => array(self::getUpTagID(), self::getDownTagID())
        ))->resultArray();
        $users = array();
        if ($userTags && !empty($userTags)) {
            foreach($userTags as $userTag) {
                $users[] = val('UserID', $userTag);
            }
        }
        return $users;
    }

    /**
     * @param $discussion
     * @return bool
     */
    public function isIdea($discussion) {
        if (is_numeric($discussion)) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussion);
        }
        return (strtolower(val('Type', $discussion)) === 'idea');
    }

    /**
     * @param $category
     * @return bool
     */
    public function isIdeaCategory($category) {
        return in_array('Idea', val('AllowedDiscussionTypes', $category, array()));
    }

    /**
     * @param $data
     * @param string $datatype
     * @return bool|mixed
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
                $category = array();
        }

        return val('UseDownVotes', $category);
    }

    /**
     * @param $discussion
     * @param $stageID
     * @param $notes
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
     * @param $discussionID
     * @param $stageID
     */
    protected function updateDiscussionStageTag($discussionID, $stageID) {
        // TODO:  Decrement tag discussion count
        $oldStage = StageModel::getStageByDiscussion($discussionID);
        if (val('StageID', $oldStage) != $stageID) {
            $stage = StageModel::getStage($stageID);
            $tags = array(val('TagID', $stage));
            TagModel::instance()->saveDiscussion($discussionID, $tags, array('Stage'));

            // Save tags in discussions table
            $discussionModel = new DiscussionModel();
            $discussionModel->setField($discussionID, 'Tags', val('TagID', $stage));
        }
    }

    /**
     * @param $discussionID
     * @param $notes
     * @throws Exception
     */
    protected function updateDiscussionStageNotes($discussionID, $notes) {
        $discussionModel = new DiscussionModel();
        $discussionModel->saveToSerializedColumn('Attributes', $discussionID, 'StageNotes', $notes);
    }

    /**
     * @param $discussion
     * @return bool|int|mixed
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
 * Renders ideation reaction buttons (for upvotes and downvotes).
 *
 * @param string $cssClass The reaction-specific css class
 * @param string $url The url for the reaction
 * @param string $label The reaction's label
 * @param string $dataAttr The data attribute for the reaction (used in reaction javascript)
 * @return string HTML representation of the ideation reactions (up and down votes)
 */
function getReactionButtonHtml($cssClass, $url, $label, $dataAttr = '') {
    return '<a class="Hijack idea-button '.$cssClass.'" href="'.$url.'" title="'.$label.'" '.$dataAttr.' rel="nofollow"><span class="arrow arrow-'.strtolower($label).'"></span> <span class="idea-label">'.$label.'</span></a>';
}

/**
 * Renders the score block, used in the idea counter.
 *
 * @param int|string $score The score
 * @return string HTML representation of the score block
 */
function getScoreHtml($score) {
    return '<div class="score">'.$score.'</div>';
}

/**
 * Renders the votes block, used in the idea counter.
 *
 * @param int|string $votes The number of votes
 * @return string HTML representation of the votes block
 */
function getVotesHtml($votes) {
    return '<div class="votes meta">'.sprintf(t('%s votes'), $votes).'</div>';
}


