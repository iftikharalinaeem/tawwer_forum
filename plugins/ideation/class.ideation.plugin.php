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
 * @copyright 2016 (c) Becky Van Bussel
 * @license   Proprietary
 * @since     1.0.0
 */
class IdeationPlugin extends Gdn_Plugin {

    protected $defaultStageID = 1;
    protected $tagModel;

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

    public function structure() {
        require dirname(__FILE__).'/structure.php';
    }

    public function base_render_before($sender) {
        $sender->addJsFile('ideation.js', 'plugins/ideation');
    }

    // CATEGORY

    public function settingsController_addEditCategory_handler($sender) {
        $categoryID = val('CategoryID', $sender->Data);
        $categoryModel = new CategoryModel();
        $category = $categoryModel->getID($categoryID);
        $options = array();

        if (in_array('Idea', val('AllowedDiscussionTypes', $category))) {
            $options = array('checked' => 'checked');
        }

        $sender->Data['_ExtendedFields']['IsIdea'] = array('Name' => 'Idea Category', 'Control' => 'CheckBox', 'Description' => '<strong>Ideation</strong> <small><a href="#">Learn more about Ideas</a></small>', 'Options' => $options);

        if ($sender->Form->isPostBack()) {
            $isIdea = $sender->Form->getValue('Idea_Category');

            if ($isIdea) {
//                $types = $sender->Form->getValue('AllowedDiscussionTypes');
//                $types['Idea'] = true;
                $types[] = 'Idea';
                $sender->Form->setFormValue('AllowedDiscussionTypes', $types);
            } else {
                $types = $sender->Form->getValue('AllowedDiscussionTypes');
                if (($key = array_search('Idea', $types)) !== false) {
                    unset($types[$key]);
                }
                $sender->Form->setFormValue('AllowedDiscussionTypes', $types);
            }
        }
    }

    // SETTINGS

    public function settingsController_editStage_create($sender, $stageID) {
        $sender->title(sprintf(T('Edit %s'), T('Stage')));
        $this->addEdit($sender, $stageID);
    }

    public function settingsController_addStage_create($sender) {
        $sender->title(sprintf(T('Add %s'), T('Stage')));
        $this->addEdit($sender);
    }

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

    public function settingsController_stages_create($sender, $stageID = NULL) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Stages', StageModel::getStages());
        $sender->setData('Title', sprintf(t('All %s'), t('Stages')));
        $sender->addSideMenu();
        $sender->render('stages', '', 'plugins/ideation');
    }

    protected function addEdit($sender, $stageID = 0) {
        $sender->permission('Garden.Settings.Manage');
        $stageModel = new StageModel();
        if ($sender->Form->authenticatedPostBack()) {
            $data = $sender->Form->formValues();
            $result = $stageModel->save(val('Name', $data), val('Status', $data), val('Description', $data), array(), $stageID);
            $sender->Form->setValidationResults($stageModel->validationResults());
            if ($result) {
                $sender->informMessage(t('Your changes have been saved.'));
                $sender->RedirectUrl = url('/settings/stages');
                $sender->setData('Stage', StageModel::getStage($result));
            }
        } elseif ($stageID) {
            // We're editting
            $data = StageModel::getStage($stageID);
            if (!$data) {
                throw NotFoundException('Stage');
            }
            $sender->Form->setData($data);
            $sender->Form->addHidden('StageID', $stageID);
        }

        $sender->addSideMenu();
        $sender->render('stage', '', 'plugins/ideation');
    }

    // POST

    public function base_discussionTypes_handler($sender, $args) {
        $args['Types']['Idea'] = array(
            'Singular' => 'Idea',
            'Plural' => 'Ideas',
            'AddUrl' => '/post/idea',
            'AddText' => 'New Idea',
            'CategoryDependent' => true
        );
    }

    public function postController_idea_create($sender, $args) {
        if (!sizeof($args)) {
            // No category was specified.
            throw new Exception(t('An idea can only be posted in an Idea category.'), 401);
        }
        $categoryCode = $args[0];
        $categoryModel = new CategoryModel();
        $category = $categoryModel->getByCode($categoryCode);
        if (!in_array('Idea', unserialize(val('AllowedDiscussionTypes', $category)))) {
            // This isn't an idea category.
            throw new Exception(t('An idea can only be posted in an Idea category.'), 401);
        }
        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $sender->Form->setFormValue('StageID', $this->defaultStageID);
        $sender->Form->setFormValue('CategoryID', val('CategoryID', $category));
        $sender->Form->setFormValue('Tags', val('TagID', StageModel::getStage($this->defaultStageID)));
        $sender->View = 'discussion';
        $sender->discussion($categoryCode);
    }

    public function postController_beforeDiscussionRender_handler($sender, $args) {
        if (val('Type', $sender->Data) === 'Idea') {
            $sender->Discussion = 'Idea'; // Kludge to set 'Post Discussion' button to 'Save'
            $sender->setData('Title', sprintf(t('New %s'), t('Idea')));
            $sender->ShowCategorySelector = false;
        }
    }

    public function postController_discussionFormOptions_handler($sender, $args) {
        // Clear out announcements. Kludge.
        if (val('Options', $args)) {
            $args['Options'] = '';
        }
    }

    // TAGS

    public function getTagModel() {
        if ($this->tagModel == null) {
            $this->tagModel = new TagModel();
        }
        return $this->tagModel;
    }

    public function tagModel_types_handler($sender) {
        $sender->addType('Stage', array(
            'key' => 'Stage',
            'name' => 'Stage',
            'plural' => 'Stages',
            'addtag' => false,
            'default' => false
        ));
    }

    // Print tags
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!$this->isIdea($discussion)) {
            return;
        }
        $stage = $this->getStageByDiscussion(val('DiscussionID', $discussion));
        if ($stage) {
            echo ' <span class="Tag Stage-Tag-'.urlencode(val('Name', $stage)).'"">'.val('Name', $stage).'</span> ';
        }
    }

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
            $args['DiscussionOptions']['Stage'] = array('Label' => T('Stage').'...', 'Url' => '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup');
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor(t('Stage').'...', '/discussion/stageoptions?discussionid='.$discussion->DiscussionID, 'Popup QnAOptions') . '</li>';
        }
    }

    public function discussionController_stageOptions_create($sender, $discussionID = '') {
        if ($discussionID) {
            $discussion = $sender->DiscussionModel->GetID($discussionID);
            if (!$discussion || !$this->isIdea($discussion)) {
                throw NotFoundException('Idea');
            }

            // TODO permission
//            $sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion));

            $sender->Form = new Gdn_Form();
            if ($sender->Form->authenticatedPostBack()) {
                $this->updateDiscussionStage($discussion, $sender->Form->getFormValue('Stage'), $sender->Form->getFormValue('StageNotes'));
//                $this->updateStageNotes($discussion, $sender->Form->getFormValue(''));
                Gdn::controller()->jsonTarget('', '', 'Refresh');
            } else {
            }

            $stages = StageModel::getStages();
            foreach($stages as &$stage) {
                $stage = val('Name', $stage);
            }

            $sender->setData('Discussion', $discussion);
            $sender->setData('Stages', $stages);
//            $sender->DiscussionModel = new DiscussionModel();
            $notes = $sender->DiscussionModel->getRecordAttribute($discussion, 'StageNotes');
            $sender->setData('StageNotes', $notes);
            $sender->setData('CurrentStageID', val('StageID', $this->getStageByDiscussion($discussionID)));
            $sender->setData('Title', T('Idea Options'));
            $sender->render('StageOptions', '', 'plugins/ideation');
        }
    }

    // HELPERS

    public function getStageByDiscussion($discussionID) {
        $tagModel = $this->getTagModel();
        $tags = $tagModel->getDiscussionTags($discussionID);
        if (val('Stage', $tags)) {
            $tag = $tags['Stage'][0];
            return StageModel::getStageByTagID(val('TagID', $tag));
        }

        return null;
    }

    public function isIdea($discussion) {
        if (is_numeric($discussion)) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussion);
        }
        return (strtolower(val('Type', $discussion)) == 'idea');
    }

    public function isIdeaCategory($category) {

    }

    public function updateDiscussionStage($discussion, $stageID, $notes) {
        if (!$this->isIdea($discussion) || !is_numeric($stageID)) {
            return;
        }

        $discussionID = val('DiscussionID', $discussion);
        $oldStage = $this->getStageByDiscussion($discussionID);
        if (val('StageID', $oldStage) != $stageID) {
            $stage = StageModel::getStage($stageID);
            $tags = array(val('TagID', $stage));
            $tagModel = $this->getTagModel();
            $tagModel->saveDiscussion($discussionID, $tags, array('Stage'));
        }

        if ($notes) {
            $discussionModel = new DiscussionModel();
            $discussionModel->saveToSerializedColumn('Attributes', $discussionID, 'StageNotes', $notes);
        }
    }

    // ATTACHMENT

    protected function updateAttachment($attachment) {

    }

}
