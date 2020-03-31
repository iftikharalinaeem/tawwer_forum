<?php if (!defined('APPLICATION')) exit;

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;

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
     * Ideation cache key.
     */
    const IDEATION_CACHE_KEY = 'ideaCategoryIDs';

    /**
     * @var int The tag ID of the upvote reaction.
     */
    protected static $upTagID;

    /**
     * @var int The tag ID of the downvote reaction.
     */
    protected static $downTagID;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var StatusModel */
    private $statusModel;

    /** @var UserModel */
    private $userModel;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var BestOfIdeationModel */
    private $bestOfIdeationModel;

    /**
     * IdeationPlugin constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param StatusModel $statusModel
     * @param UserModel $userModel
     * @param CategoryModel $categoryModel
     * @param BestOfIdeationModel $bestOfIdeationModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        StatusModel $statusModel,
        UserModel $userModel,
        CategoryModel $categoryModel,
        BestOfIdeationModel $bestOfIdeationModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->statusModel = $statusModel;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
        $this->bestOfIdeationModel = $bestOfIdeationModel;
        parent::__construct();
    }

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
            $reactionUp = ReactionModel::reactionTypes(self::REACTION_UP);
            self::setUpTagID(val('TagID', $reactionUp));
        }
        return self::$upTagID;
    }

    /**
     * Get idea metadata for a discussion.
     *
     * @param array $discussion
     * @return array|null
     */
    private function getIdeaMetadata(array $discussion) {
        $result = null;

        $discussionID = $discussion['discussionID'] ?? $discussion['DiscussionID'] ?? null;
        $categoryID = $discussion['categoryID'] ?? $discussion['CategoryID'] ?? null;

        if (!$discussionID || !$categoryID) {
            return null;
        }

        $category = CategoryModel::categories($categoryID);
        if (!$this->isIdeaCategory($category)) {
            return null;
        }

        $type = $category['IdeationType'];
        $status = $this->statusModel->getStatusByDiscussion($discussionID);
        $statusNotes = $discussion['Attributes']['StatusNotes'] ?? $discussion['attributes']['statusNotes'] ?? null;
        $result = [
            'statusID' => val('StatusID', $status),
            'status' => [
                'name' => val('Name', $status),
                'state' => lcfirst(val('State', $status))
            ],
            'statusNotes' => $statusNotes,
            'type' => $type
        ];

        return $result;
    }

    /**
     * Set a idea metadata on a discussion row.
     *
     * @param array $row
     * @return array
     */
    private function setIdeaMetadata(array $row) {
        $type = $row['type'] ?? $row['Type'] ?? '';
        $type = strtolower($type);

        if ($type === 'idea') {
            $schema = $this->getIdeaMetadataFragment();
            $metadata = $this->getIdeaMetadata($row);

            if ($metadata === null || $metadata['statusID'] === false) {
                // We added this to relax the schema so that we don't end up with a 422 error when an idea is in a
                // non-ideation category.
                $metadata = [
                    'statusID' => 0,
                    'status' => ['name' => 'Invalid', 'state' => 'open'],
                    'statusNotes' => 'This idea is in an invalid category.',
                    'type' => 'up-down'
                ];
            }
            $metadata = $schema->validate($metadata);
            if (is_array($metadata)) {
                $key = array_key_exists('attributes', $row) ? 'attributes.idea' : 'Attributes.Idea';
                setvalr($key, $row, $metadata);
            }
        }

        return $row;
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
            $reactionDown = ReactionModel::reactionTypes(self::REACTION_DOWN);
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
        $menu->addLink('Forum', t('Idea Statuses'), '/dashboard/settings/statuses', 'Garden.Settings.Manage', ['class' => 'nav-statuses']);
    }

    /**
     * Hook in when reacting to a post to validate votes on an idea.
     *
     * @param array $data
     * @param ReactionModel $sender
     * @param array $reactionType
     * @return array
     */
    public function reactionModel_react_saveData(array $data, ReactionModel $sender, array $reactionType) {
        if (strtolower($data['RecordType']) === 'discussion') {
            $discussion = $this->discussionModel->getID($data['RecordID'], DATASET_TYPE_ARRAY);
            if (val('Type', $discussion) === 'Idea') {
                $discussionID = $discussion['DiscussionID'];
                $categoryID = $discussion['CategoryID'];
                $category = CategoryModel::categories($categoryID);
                if (!$this->isIdeaCategory($category)) {
                    throw new Gdn_UserException("Category is not configured for ideation.");
                }
                $allowDownVotes = $this->allowDownVotes($discussion, 'discussion');

                $status = $this->statusModel->getStatusByDiscussion($discussionID);
                if ($status['State'] === 'Closed') {
                    throw new Gdn_UserException('This idea is closed.');
                }

                $vote = $this->getReactionFromTagID($data['TagID']);
                if (empty($vote)) {
                    // If this isn't a valid vote reaction, let the user know what the valid reactions are for this  idea.
                    $voteReactions = [self::REACTION_UP];
                    if ($allowDownVotes) {
                        $voteReactions[] = self::REACTION_DOWN;
                    }
                    throw new Gdn_UserException('Reactions to this idea must be one of the following: ' . implode(', ', $voteReactions));
                } elseif ($vote === self::REACTION_DOWN && !$allowDownVotes) {
                    throw new Gdn_UserException('Down votes are not allowed on this idea.');
                }
            }
        }

        return $data;
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
        $sender->Head->addString(
            <<<EOT
<script>
	jQuery(document).ready(function($) {
    $('input[value="Idea"]').parents('label').hide();
    });
</script>
EOT
        );
        if (!$sender->Form->authenticatedPostBack()) {
            $category = CategoryModel::categories($categoryID);

            if ($categoryID && !$this->isIdeaCategory($category)) {
                return; // Don't let the ideation state of existing categories be changed.
            }

            // Show/hide allowed discussions and downvote option
            $sender->addJsFile('ideation.js', 'plugins/ideation');

            $isIdeaOptions = [];
            if ($this->isIdeaCategory($category)) {
                $isIdeaOptions['checked'] = 'checked';
            }
            $sender->Data['_ExtendedFields']['IsIdea'] = [
                'Name' => 'Idea Category',
                'Control' => 'CheckBox',
                'Options' => $isIdeaOptions,
                'Description' => Gdn::translate('Ideation') .
                    '<div class="info"><a href="https://docs.vanillaforums.com/help/ideation/">' .
                    sprintf(Gdn::translate('Learn more about %s'), Gdn::translate('ideas')) .
                    '</a></div>'
            ];

            $downVoteOptions = [];
            if ($this->isIdeaCategory($category)) {
                $sender->title('Edit Idea Category');
                $sender->Form->addHidden('Idea_Category', true);
                $downVoteOptions = $this->allowDownVotes($category) ? ['checked' => 'checked'] : [];
            }

            $sender->Data['_ExtendedFields']['UseDownVotes'] = [
                'Name' => 'UseDownVotes',
                'Control' => 'CheckBox',
                'Description' => t('Down Votes').
                    ' <div class="info">'.t('Let users vote up or down.').'</div>',
                'Options' => $downVoteOptions
            ];

            //Obtain BestOfIdeations module's settings
            $boiSettings = $this->bestOfIdeationModel->loadConfiguration($categoryID);

            //Is the bestOfIdeation feature used?
            $useBestOfIdeationOptions = [];
            if ($boiSettings['IsEnabled']) {
                $useBestOfIdeationOptions['checked'] = 'checked';
            }
            $sender->Data['_ExtendedFields']['UseBestOfIdeation'] = [
                'Name' => 'UseBestOfIdeation',
                'LabelCode' => Gdn::translate('Show the "Best of Ideation"'),
                'Control' => 'CheckBox',
                'Description' => Gdn::translate('Show the "Best of" ideas').
                    ' <div class="info">'.Gdn::translate('Will show a scoreboard of the best ideas.').'</div>',
                'Options' => $useBestOfIdeationOptions,
            ];

            //The amount of ideas to include in this bestOfIdeation implementation?
            $bestOfIdeationLimitOptions = [
                'type' => "number",
                'value' => (
                    isset($boiSettings['Limit'])
                        ?$boiSettings['Limit']
                        :BestOfIdeationModule::DEFAULT_AMOUNT
                ),
                'step' => "1",
                'min' => "1",
                'max' => BestOfIdeationModule::MAX_AMOUNT
            ];
            $sender->Data['_ExtendedFields']['BestOfIdeationLimit'] = [
                'Name' => 'BestOfIdeationSettings[Limit]',
                'LabelCode' => Gdn::translate('How many ideas shown'),
                'Control' => 'textbox',
                'Description' => Gdn::translate('How many top ideas should be show in the "Best of Idea" module'),
                'Options' => $bestOfIdeationLimitOptions,
            ];

            //The earliest date at which an idea can be considered in this bestOfIdeation implementation?
            $bestOfIdeationDatesFromOptions = [
                'type' => "date",
                'value' => (
                    isset($boiSettings['Dates']['From'])
                        ?$boiSettings['Dates']['From']
                        :''
                ),
            ];
            $sender->Data['_ExtendedFields']['BestOfIdeationFrom'] = [
                'Name' => 'BestOfIdeationSettings[Dates][From]',
                'LabelCode' => Gdn::translate('Consider ideas added after'),
                'Control' => 'textbox',
                'Description' => Gdn::translate('The earliest an idea can be considered.'),
                'Options' => $bestOfIdeationDatesFromOptions,
            ];

            //The latest date at which an idea can be considered in this bestOfIdeation implementation?
            $bestOfIdeationDatesToOptions = [
                'type' => "date",
                'value' => (
                isset($boiSettings['Dates']['To'])
                    ?$boiSettings['Dates']['To']
                    :''
                ),
            ];
            $sender->Data['_ExtendedFields']['BestOfIdeationTo'] = [
                'Name' => 'BestOfIdeationSettings[Dates][To]',
                'LabelCode' => Gdn::translate('Consider ideas added before'),
                'Control' => 'textbox',
                'Description' => Gdn::translate('The latest an idea can be considered.'),
                'Options' => $bestOfIdeationDatesToOptions,
            ];

        } else {
            if ($sender->Form->getValue('Idea_Category')) {
                $sender->Form->setFormValue(
                    self::CATEGORY_IDEATION_COLUMN_NAME,
                    $sender->Form->getFormValue('UseDownVotes')
                        ? self::CATEGORY_TYPE_UP_AND_DOWN
                        : self::CATEGORY_TYPE_UP
                );
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
        if (empty($category) || val("DisplayAs", $category) === "Categories") {
            // We're on Recent Discussions;
            // Hitting post/idea from here is fine;
            // We might not want the "Idea" type in the drop down depending on the user/category permissions
            //
            // Alternatively we are in a nested category and currently aren't going to recursively check all child categories
            // This is particularly necessary to make this work with the top level subcommunities without
            // recursively checking categories. This is a STOPGAP solution until we have a better way to handle this
            // or create workflows that do not require handling it.

            $ideaCategoryIDs = $this->getIdeaCategoryIDs();
            foreach ($ideaCategoryIDs as $categoryID) {
                if (CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Add')) {
                    return;
                }
            }
            unset($args['AllowedDiscussionTypes']['Idea']);
            return;
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
        $isIdea = false;
        if (array_key_exists('DiscussionType', $args)) {
            $isIdea = $args['Options']['DiscussionType'] === 'Idea';
        }
        if ($type !== 'Idea' && !$isIdea) {
            return;
        }
        $value = arrayValueI('Value', $options = $args['Options']); // The selected category id
        $categoryData = CategoryModel::getByPermission(
            'Discussions.View',
            $value,
            val('Filter', $options, ['Archived' => 0]),
            val('PermFilter', $options, [])
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
            throw notFoundException('Status');
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
            $result = $statusModel->upsert(val('Name', $data), val('State', $data), val('IsDefault', $data), $statusID);
            $sender->Form->setValidationResults($statusModel->validationResults());
            if ($result) {
                $statusModel->clearStatusesCache();
                $sender->informMessage(t('Your changes have been saved.'));
                $sender->setRedirectTo('/settings/statuses');
                $sender->setData('Status', StatusModel::instance()->getStatus($result));
            } else {
                $sender->informMessage(t('An error occurred.'));
            }
        } elseif ($statusID) {
            // We're about to edit, set up the data from the status.
            $data = StatusModel::instance()->getStatus($statusID);
            if (!$data) {
                throw notFoundException('Status');
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
            $statusModel = new StatusModel();
            $result = $statusModel->delete(['StatusID' => $statusID]);
            if ($result) {
                $statusModel->clearStatusesCache();
                $sender->jsonTarget("#Status_$statusID", NULL, 'SlideUp');
                $sender->informMessage(sprintf(t('%s deleted.'), t('Status')));
            } else {
                $sender->informMessage(t('An error occurred.'));
            }
        }
        $sender->render('blank', 'utility', 'dashboard');
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
        // Prevent force browsing to post an idea in a group.
        $groupID = Gdn::request()->get('groupid');
        if (isset($groupID)) {
            throw forbiddenException('@'.t(" You cannot post an idea inside a group."));
        }

        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $categoryCode = val(0, $args, '');
        $sender->View = 'discussion';
        $ideaTitle = t('Idea Title');
        Gdn::locale()->setTranslation('Discussion Title', $ideaTitle, false);
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
            throw notFoundException('Idea');
        }
        $discussionID = $args[0];

        if (!$this->isIdea($discussionID)) {
            throw notFoundException('Idea');
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
        // If this is an idea, clear out options.
        if ($sender->data('Type') === 'Idea' && val('Options', $args)) {
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
     * Handle setting default tags for Idea-type discussions
     *
     * @param Gdn_PluginManager $sender
     * @param array $args
     */
    public function taggingPlugin_saveDiscussion_handler($sender, $args) {
        $type = $args['Data']['Type'] ?? '';
        $isNew = $args['Data']['IsNewDiscussion'] ?? '';
        if (strcasecmp($type, 'idea') === 0 && $isNew) {
            $args['Tags'][] = val('TagID', StatusModel::instance()->getDefaultStatus());
        }
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
     * Returns the "Best of" module for a discussion category.
     *
     * @param int $categoryId
     * @return BestOfIdeationModule The categories's "Best of" module.
     */
    public function getBestOfIdeation(int $categoryId) {
        return new BestOfIdeationModule($categoryId);
    }


    /**
     * Hooks to "After a category's title" is shown
     *
     * @param CategoriesController $sender
     * @return string (html) corresponding to the bestOfIdeation implementation for the current category
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function categoriesController_afterPageTitle_handler(CategoriesController $sender) {
        if (is_array($sender->Category->AllowedDiscussionTypes)
            && in_array('Idea', $sender->Category->AllowedDiscussionTypes)) {
            $categoryID = $sender->CategoryID;

            $bestOfIdeation = $this->getBestOfIdeation($categoryID);

            echo $bestOfIdeation->toString();
        }
    }

    /**
     * Sets up the idea counter module for the discussion attachment.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        $discussion = val('Discussion', $sender);

        $isAnIdea = $this->isIdea($discussion);
        if (!$isAnIdea) {
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

        if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')
            && !Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['Ideation'] = ['Label' => t('Ideation'), 'Url' => '/discussion/ideationoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup'];
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor(t('Ideation'), '/discussion/ideationoptions?discussionid='.$discussion->DiscussionID, 'Popup IdeationOptions') . '</li>';
        }

        if (!$this->isIdea($discussion)) {
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
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param string|int $discussionID Identifier of the discussion
     * @throws Exception if discussion isn't found.
     */
    public function discussionController_ideationOptions_create($sender, $discussionID = '') {
        $sender->Form = new Gdn_Form();

        $discussion = $sender->DiscussionModel->getID($discussionID);

        if (!$discussion) {
            throw notFoundException('Discussion');
        }

        $sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion));

        // We're only allowing conversion between idea and discussion.  If it isn't an idea, make it a discussion.
        if (val('Type', $discussion) !== 'Idea') {
            setValue('Type', $discussion, 'Discussion');
        }

        if ($sender->Form->isPostBack()) {
            $type = $sender->Form->getFormValue('Type');
            switch ($type) {
                case 'Idea':
                    $statusID = val('StatusID', StatusModel::instance()->getDefaultStatus());
                    // Update the type
                    $sender->DiscussionModel->setField(
                        $discussionID,
                        'Type',
                        $type
                    );

                    // Override score on the discussion.
                    $this->recalculateIdeaScore($discussion);

                    // Setup the default idea status
                    $this->updateDiscussionStatusTag($discussionID, $statusID);

                    // Add the status attachment
                    $this->updateAttachment(
                        $discussionID,
                        $statusID,
                        ''
                    );
                    break;
                default:
                    // Recalculate the discussion score when an idea is converted back to a reaction.
                    $reactionModel = new ReactionModel();
                    $reactionModel->recalculateRecordTotal($discussionID, $type);

                    // Prune away any ideation status attachments, since this isn't an idea.
                    AttachmentModel::instance()->delete([
                        'ForeignID' => "d-{$discussionID}",
                        'Type' => 'status'
                    ]);
            }

            $sender->DiscussionModel->setField($discussionID, 'Type', $type);
            $sender->Form->setValidationResults($sender->DiscussionModel->validationResults());
            Gdn::controller()->jsonTarget('', '', 'Refresh');
        } else {
            $sender->Form->setData($discussion);
        }

        $sender->setData('Discussion', $discussion);
        $sender->setData('_Types', ['Idea' => '@'.t('Ideation Type', 'Idea'), 'Discussion' => '@'.t('Discussion Type', 'Discussion')]);
        $sender->setData('Title', t('Ideation Options'));
        $sender->render('DiscussionOptions', '', 'plugins/ideation');
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
            $discussion = $sender->DiscussionModel->getID($discussionID);
            if (!$discussion || !$this->isIdea($discussion)) {
                throw notFoundException('Idea');
            }

            if (!Gdn::session()->checkPermission('Garden.Moderation.Manage')
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
        if (!$this->isIdea($discussion) || filter_var($statusID, FILTER_VALIDATE_INT) === false) {
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
     * Calculates discussion score base only vote reactions and overrides previous discussion score.
     *
     * @param object|array $discussion
     */
    private function recalculateIdeaScore($discussion) {
        $discussionModel = new DiscussionModel();

        // If voting reactions exist, overwrite the score.
        if (valr('Attributes.React', $discussion) ) {
            $countUp = valr('Attributes.React.'.self::REACTION_UP, $discussion, 0);
            $countDown = valr('Attributes.React.'.self::REACTION_DOWN, $discussion, 0);
            $score = $countUp - $countDown;
            $discussionModel->setField($discussion->DiscussionID, 'Score', $score);
        }
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
        $this->addAttachment(val('DiscussionID', $args));
    }

    /**
     * Initialize an idea properly if it comes from the moderation queue.
     *
     * @param LogModel $sender
     * @param array $args Event's arguments
     */
    public function logModel_afterRestore_handler($sender, $args) {
        $log = val('Log', $args);
        $discussionID = val('RecordType', $log) === 'Discussion' ? val('InsertID', $args) : false;
        if ($discussionID && val('Operation', $log) === 'Pending' && $this->isIdea($discussionID)) {
            $this->addAttachment($discussionID);

            $statusModel = new StatusModel();
            $discussionModel = new DiscussionModel();
            $defaultStatus = $statusModel->getDefaultStatus();
            $defaultStatusID = null;
            if ($defaultStatus) {
                $defaultStatusID = val('StatusID', $defaultStatus);
            }
            $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            $this->updateDiscussionStatus($discussion, $defaultStatusID, '');
        }
    }

    /**
     * Add the necessary attachment to a discussion.
     *
     * @param int $discussionID
     */
    protected function addAttachment($discussionID) {
        if ($this->isIdea($discussionID)) {
            $attachmentModel = AttachmentModel::instance();
            $attachment = $attachmentModel->getWhere(['ForeignID' => 'd-'.$discussionID])->resultArray();
            if (empty($attachment)) {
                // We've got a new idea, add an attachment.
                $this->updateAttachment($discussionID, val('StatusID', StatusModel::instance()->getDefaultStatus()), '');
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
        $discussion = $discussionModel->getID($discussionID);

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
     * Filters out the status tags so that they will not be displayed.
     *
     * @param TagModule $sender
     * @param array $args
     */
    public function tagModule_getData_handler($sender, $args) {
        if ($args['ParentType'] != 'Discussion') {
            return;
        }

        $row = $this->discussionModel->getID($args['ParentID']);
        if (val('Type', $row) != 'Idea') {
            return;
        }

        //Get the tags associated to the discussion
        $tagModel = new TagModel();
        $tags = $tagModel->getDiscussionTags($args['ParentID'], false);

        //Get the ID's for status tags
        $statusModel = new StatusModel();
        $statusTags = $statusModel->getStatuses();
        $statusTagIDs = array_column($statusTags, 'TagID');

        // Filter out the status tags
        foreach ($tags as $key => $tag) {
            if (in_array($tag['TagID'], $statusTagIDs)) {
                unset($tags[$key]);
            }
        }

        $args['tagData'] = $tags;
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
     * Modify the types filter when getting a summary of reactions on a post.
     *
     * @param array $filter
     * @param array $record
     * @return array Returns the new filter (where clause).
     */
    public function reactionModel_getReactionTypesFilter_handler(array $filter, array $record) {
        $discussionID = $record['discussionID'] ?? $record['DiscussionID'] ?? null;
        $categoryID = $record['categoryID'] ?? $record['CategoryID'] ?? null;

        if ($discussionID && $categoryID && $categoryID > 0) {
            $category = CategoryModel::categories($categoryID);
            $discussionType = $record['type'] ?? $record['Type'] ?? '';
            $discussionType = strtolower($discussionType);

            if ($discussionType === 'idea' && $this->isIdeaCategory($category)) {
                // Only report vote reactions on an idea.
                $type = $category['IdeationType'] ?? null;
                $filter = ['UrlCode' => [self::REACTION_UP]];
                if ($type === 'up-down') {
                    // Include down votes on categories that allow them.
                    $filter['UrlCode'][] = self::REACTION_DOWN;
                }
            }
        }

        return $filter;
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

        $reaction = ReactionModel::reactionTypes($urlCode);
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
     * @param array $args
     */
    public function base_beforeReactionsScore_handler($sender, $args) {
        if (val('ReactionType', $args) && (val('Type', val('Record', $args)) == 'Idea')) {
            $reaction = val('ReactionType', $args);
            if ((val('UrlCode', $reaction) != self::REACTION_UP) && (val('UrlCode', $reaction) != self::REACTION_DOWN)) {
                $args['Set'] = [];
            } else {
                if (!isset($args['RecordID'])) {
                    return;
                }
                $upVote = valr(self::REACTION_UP, $args['reactionTotals'], 0);
                $downVote = valr(self::REACTION_DOWN, $args['reactionTotals'],  0);
                $newVoteTotal = $upVote - $downVote;
                $args['Set'] = ['score' => $newVoteTotal];
                $discussionModel = new DiscussionModel();
                $discussionModel->setField($args['RecordID'], 'Score', $newVoteTotal);
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
        // We need to extract the discussion IDs to only query relevant user votes.
        $discussionIDs = [];
        foreach ($discussions as $discussion) {
            $discussionIDs[] = val('DiscussionID', $discussion);
        }

        // Only query the votes for the current discussions.
        $userVotes = $this->getUserVotes($discussionIDs);

        if (!$userVotes) {
            return;
        }

        foreach ($discussions as &$discussion) {
            $discussionID = $discussion->DiscussionID;
            if ($userVote = val($discussionID, $userVotes)) {
                $discussion->UserVote = $userVote;
            }
        }
    }

    /**
     * Modify the data on /api/v2/discussions index to include ideation metadata..
     *
     * @param array $discusion.
     * @param DiscussionsApiController $sender
     * @param array $options
     * @param array $rows Raw result.
     */
    public function discussionsApiController_normalizeOutput(array $discussion, DiscussionsApiController $sender, array $options) {
        if ($discussion['type'] === 'idea') {
            $discussion = $this->setIdeaMetadata($discussion);
        }

        return $discussion;
    }

    /**
     * Update idea metadata on a discussion through the discussions API endpoint.
     *
     * @param DiscussionsApiController $sender
     * @param int $id
     * @param array $body
     * @return array
     * @throws ClientException if the discussion is not a valid idea.
     * @throws ClientException if the status ID is not associated with a valid idea status.
     * @throws ServerException if, after saving, the status cannot be retrievd from an idea.
     */
    public function discussionsApiController_patch_idea(DiscussionsApiController $sender, $id, array $body) {
        $sender->permission('Garden.Moderation.Manage');

        $in = $sender->schema($this->statusFragment(), 'in')
            ->setDescription('Update idea metadata on a discussion.');
        $out = $sender->schema($this->statusFragment(), 'out');

        $body = $in->validate($body, true);

        // Verify the discussion is valid.
        $discussion = $sender->discussionByID($id);
        if (!$this->isIdea($discussion)) {
            throw new ClientException('Discussion is not an idea.');
        }

        // Grab the current idea state.
        $currentStatus = $this->statusModel->getStatusByDiscussion($id);
        if (empty($currentStatus)) {
            throw new ServerException('An error was encountered while getting the status of the idea.', 500);
        }
        $currentStatusNotes = $this->getStatusNotes($discussion) ?: null;

        // Coalesce values for convenience.
        $statusID = $body['statusID'] ?? null;
        $statusNotes = $body['statusNotes'] ?? null;

        if ($statusID) {
            // Verify the new status.
            $status = $this->statusModel->getStatus($statusID);
            if (!is_array($status) || !array_key_exists('StatusID', $status)) {
                throw new ClientException('Invalid status ID.');
            }
            // Updating the status can potentially trigger notices to the user.
            $this->updateDiscussionStatus($discussion, $statusID, $statusNotes ?: $currentStatusNotes ?: '');
        } elseif ($statusNotes) {
            // Only update the notes. No user notifications.
            $this->updateDiscussionStatusNotes($id, $statusNotes);
            $this->updateAttachment($id, $currentStatus['StatusID'], $statusNotes);
        }

        // Grab the updated values.
        $updatedStatus = $this->statusModel->getStatusByDiscussion($id);
        if (empty($updatedStatus)) {
            throw new ServerException('An error was encountered while getting the status of the idea.', 500);
        }
        $updatedDiscussion = $sender->discussionByID($id);
        $updatedStatusNotes = $this->getStatusNotes($updatedDiscussion) ?: null;

        $row = [
            'statusID' => $updatedStatus['StatusID'],
            'statusNotes' => $updatedStatusNotes
        ];
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create an idea through the discussions API endpoint.
     *
     * @param DiscussionsApiController $sender
     * @param array $body
     * @return array
     * @throws ClientException if the category is not configured for ideation.
     */
    public function discussionsApiController_post_idea(DiscussionsApiController $sender, array $body) {
        $sender->permission('Garden.SignIn.Allow');

        $in = $sender->schema($sender->discussionPostSchema(), 'in')->setDescription('Add an idea.');
        $out = $sender->schema($sender->discussionSchema(), 'out');

        $body = $in->validate($body);
        $categoryID = $body['categoryID'];
        $this->discussionModel->categoryPermission('Vanilla.Discussions.Add', $categoryID);
        $sender->fieldPermission($body, 'closed', 'Vanilla.Discussions.Close', $categoryID);
        $sender->fieldPermission($body, 'pinned', 'Vanilla.Discussions.Announce', $categoryID);
        $sender->fieldPermission($body, 'sink', 'Vanilla.Discussions.Sink', $categoryID);

        $category = CategoryModel::categories($categoryID);
        if (!$this->isIdeaCategory($category)) {
            throw new ClientException('Category is not configured for ideation.');
        }

        $discussionData = ApiUtils::convertInputKeys($body);

        $discussionData['Type'] = 'Idea';
        $defaultStatus = $this->statusModel->getDefaultStatus();
        $discussionData['Tags'] = $defaultStatus['TagID'];

        $id = $this->discussionModel->save($discussionData);
        $sender->validateModel($this->discussionModel);

        if (!$id) {
            throw new ServerException('Unable to insert idea.', 500);
        }

        $row = $sender->discussionByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID', 'LastUserID']);
        $row = $sender->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Adds the sessioned user's Idea* reaction to the discussion data in the form [UserVote] => TagID
     * where TagID is the TagID of the reaction.
     *
     * @param DiscussionsController $sender
     */
    public function discussionsController_render_before($sender) {
        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $discussionsData = $sender->data('Discussions', false);
            if ($discussionsData !== false && $discussionsData instanceof Gdn_DataSet) {
                $discussions = $discussionsData->result();
                $this->addUserVotesToDiscussions($discussions);
            }
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
     * Update the /discussions/get input schema.
     *
     * @param Schema $schema
     */
    public function discussionSchema_init(Schema $schema) {
        $this->updateSchemaAttributes($schema);
    }

    /**
     * Get a schema object representing idea metadata on a discussion.
     *
     * @return Schema
     */
    private function getIdeaMetadataFragment() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'statusNotes:s|n' => 'Status update notes.',
                'statusID:i' => 'Unique numeric ID of a status.',
                'status:o' => [
                    'name:s' => 'Label for the status.',
                    'state:s' => [
                        'description' => 'The open/closed state of an idea.',
                        'enum' => ['open', 'closed']
                    ]
                ],
                'type:s' => [
                    'description' => 'Voting type for this idea: up-only or up and down.',
                    'enum' => ['up', 'up-down']
                ]
            ]);
        }

        return $schema;
    }

    /**
     * Returns an array of the sessioned user's votes where the key is the discussion ID and the value is the reaction's tag ID.
     *
     * @param int|array $discussionIDs Discussion iD(s) to filter the results by.
     * @return array The sessioned user's votes
     */
    public function getUserVotes($discussionIDs = []) {
        $userVotes = [];
        $tagIDs = [$this->getUpTagID(), $this->getDownTagID()];

        $user = Gdn::session();
        $userID = val('UserID', $user);

        if (!is_array($discussionIDs)) {
            $discussionIDs = [$discussionIDs];
        }

        if ($userID) {
            $userTag = new UserTag();
            $where = [
                'RecordType' => 'Discussion',
                'TagID' => $tagIDs,
                'UserID' => $userID,
                'Total >' => 0
            ];

            if (count($discussionIDs)) {
                $where['RecordID'] = $discussionIDs;
            }

            // TODO: Cache this thing.
            $data = $userTag->getWhere(
                $where,
                'DateInserted',
                'desc'
            )->resultArray();

            foreach ($data as $discussion) {
                $discussionID = $discussion['RecordID'];
                $tagID = $discussion['TagID'];

                $userVotes[$discussionID] = $tagID;
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

        $discussionID = val('DiscussionID', $discussion);
        $votes = $this->getUserVotes($discussionID);
        $tagID = val($discussionID, $votes);

        switch ($tagID) {
            case self::getUpTagID():
                return self::REACTION_UP;
            case self::getDownTagID():
                return self::REACTION_DOWN;
            default:
                return '';
        }
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
            $reaction = ReactionModel::reactionTypes($urlCode);
        }

        $name = $reaction['Name'];
        $label = t($name);
        $id = getValue('DiscussionID', $discussion);
        $linkClass = 'ReactButton-'.$urlCode.' '.val('cssClass', $options);
        $urlCode2 = strtolower($urlCode);
        $url = url("/react/discussion/$urlCode2?id=$id&selfreact=true");
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
            ['td.TagID' => $openTags], 'state', 'status'
        );

        // Closed state
        $closedStatuses = StatusModel::instance()->getClosedStatuses();
        $closedTags = [];
        foreach ($closedStatuses as $closedStatus) {
            $closedTags[] = val('TagID', $closedStatus);
        }
        DiscussionModel::addFilter('closed', t('Closed'),
            ['td.TagID' => $closedTags], 'state', 'status'
        );

        // Statuses
        foreach(StatusModel::instance()->getStatuses() as $status) {
            DiscussionModel::addFilter(strtolower(val('Name', $status)).'-status' , val('Name', $status),
                ['td.TagID' => val('TagID', $status)], 'status', 'status'
            );
        }
    }

    /**
     * Update Discussion query to when filtering by idea statuses.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeGet_handler($sender, $args) {
        $this->ideaQueryFiltering($sender, $args);
    }

    /**
     * Update Discussion query to when filtering by idea statuses.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeGetCount_handler($sender, $args) {
        $this->ideaQueryFiltering($sender, $args);
    }

    /**
     * Update Discussion query to when filtering by idea statuses.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeGetAnnouncements_handler($sender, $args) {
        $this->ideaQueryFiltering($sender, $args);
    }

    /**
     * Join TagDiscussion table filtering idea statuses.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    private function ideaQueryFiltering($sender, $args) {
        if (isset($args['Wheres']['td.TagID'])) {
            $sender->SQL->join('TagDiscussion td', "td.DiscussionID = d.DiscussionID", 'inner');
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
        if (strlen($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }
        $headline = sprintf(t('The status has changed for %s.'),
            anchor($discussionName, '/discussion/'.$discussionID, '', [], true)
        );

        $story = sprintf(t("Voting for the idea is %s."), strtolower(val('State', $newStatus)));

        $activity = [
            'ActivityType' => 'AuthorStatus',
            'NotifyUserID' => $authorID,
            'HeadlineFormat' => $headline,
            'Story' => $story,
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
        if (strlen($discussionName) > 200) {
            $discussionName = substr($discussionName, 0, 100).'…';
        }

        $voters = $this->getVoterIDs($discussionID);
        $headline = sprintf(t('The status has changed for %s.'),
            anchor($discussionName, '/discussion/'.$discussionID, '', [], true)
        );

        $story = sprintf(t("Voting for the idea is %s."), strtolower(val('State', $newStatus)));

        foreach($voters as $voter) {
            $activity = [
                'ActivityType' => 'VoterStatus',
                'NotifyUserID' => $voter,
                'HeadlineFormat' => $headline,
                'Story' => $story,
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
     * MESSAGES
     * ---------
     */

    /**
     * Allow to choose
     *
     * @param MessageController $sender Sending controller
     * @param array $args Event's arguments
     */
    public function messageController_afterGetLocationData_handler($sender, $args) {
        $args['ControllerData']['Vanilla/Post/Idea'] = t('New Idea Form');
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
        if (filter_var($discussion, FILTER_VALIDATE_INT) !== false) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussion);
        }
        return (strtolower(val('Type', $discussion)) === 'idea');
    }

    /**
     * Returns an array of Idea-type category IDs.
     */
    public function getIdeaCategoryIDs() {
        $ideaCategoryIDs = Gdn::cache()->get(self::IDEATION_CACHE_KEY);
        if ($ideaCategoryIDs === Gdn_Cache::CACHEOP_FAILURE) {
            $ideaCategoryIDs = [];
            $categories = CategoryModel::categories();
            foreach ($categories as $category) {
                if ($this->isIdeaCategory($category)) {
                    $ideaCategoryIDs[] = val('CategoryID', $category);
                }
            }
            Gdn::cache()->store(self::IDEATION_CACHE_KEY, $ideaCategoryIDs, [Gdn_Cache::FEATURE_EXPIRY => 300]);
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

    /**
     * Get a schema object representing a simple subset of idea status metadata on a discussion.
     *
     * @return mixed
     */
    private function statusFragment() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'statusID:i' => 'Idea status ID.',
                'statusNotes:s|n' => 'Notes on a status change. Notes will persist until overwritten.'
            ]);
        }

        return $schema;
    }

    /**
     * Update the attributes field of a post schema.
     *
     * @param Schema $schema
     */
    private function updateSchemaAttributes(Schema $schema) {
        $attributes = $schema->getField('properties.attributes');

        // Add to an existing "attributes" field or create a new one?
        if ($attributes instanceof Schema) {
            $attributes->merge(Schema::parse([
                'idea?' => $this->getIdeaMetadataFragment()
            ]));
        } else {
            $schema->merge(Schema::parse([
                'attributes?' => Schema::parse([
                    'idea?' => $this->getIdeaMetadataFragment()
                ])
            ]));
        }
    }

    /**
     * Hooks before saving an ideation.
     *
     * Flushing the ideation cache on this hook to prevent people creating a new ideation category
     * not being able to see/post in it right away. See getIdeaCategoryIDs().
     *
     * @param CategoryModel $sender
     * @param array $args
     */
    public function categoryModel_beforeSaveCategory_handler(CategoryModel &$sender, array $args) {
        Gdn::cache()->remove(self::IDEATION_CACHE_KEY);
    }

    /**
     * Saves BestOfIdeation settings after a category has been saved.
     *
     * @param CategoryModel $sender
     * @param array $args
     */
    public function categoryModel_afterSaveCategory_handler(CategoryModel &$sender, array $args) {
        if (isset($args['CategoryID'])) {
            $bestOfIdeationSettings = [];

            //Look for bestOfIdeation settings
            if ((isset($args['FormPostValues']['UseBestOfIdeation'])) &&
                ($args['FormPostValues']['UseBestOfIdeation']==1) &&
                (isset($args['FormPostValues'][BestOfIdeationModel::SETTINGS_COL_NAME]))) {
                $bestOfIdeationSettings = $args['FormPostValues'][BestOfIdeationModel::SETTINGS_COL_NAME];

                //If there are empty date fields, we remove them to the data to be saved.
                foreach ($bestOfIdeationSettings['Dates'] as $dateIdx => $date) {
                    if (empty($date)) {
                        unset($bestOfIdeationSettings['Dates'][$dateIdx]);
                    }
                }

                //Save BestOfIdeation settings.
                $this->bestOfIdeationModel->saveConfiguration($args['CategoryID'], $bestOfIdeationSettings);
            }
        }
    }

    /**
     * Delete the BestOfIdeation settings upon a category deletion.
     *
     * @param CategoryModel $sender
     * @param array $args
     * @throws Exception If an error is encountered while performing the query.
     */
    public function categoryModel_afterDeleteCategory(CategoryModel &$sender, array $args) {
        $this->bestOfIdeationModel->deleteConfiguration($args['CategoryID']);
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
            $statusCode = htmlspecialchars($statusName);
        }
        $statusCssClass = slugify($statusName);
        return ' <a href="'.url('/discussions/tagged/'.$statusCode).'" class="MItem MItem-'.$statusCssClass.' IdeationTag"><span class="Tag Status-Tag-'.$statusCssClass.'">'.$statusName.'</span></a> ';
    }
}
