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
        saveToConfig('Garden.AttachmentsEnabled', true);
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

        if ($this->isIdeaCategory($category)) {
            $options = array('checked' => 'checked');
        }

        $sender->Data['_ExtendedFields']['IsIdea'] = array('Name' => 'Idea Category', 'Control' => 'CheckBox', 'Description' => '<strong>Ideation</strong> <small><a href="#">Learn more about Ideas</a></small>', 'Options' => $options);

        if ($sender->Form->isPostBack()) {
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

    public function postController_editidea_create($sender, $args) {
        if (!sizeof($args)) {
            // No discussion was specified.
        }

        $discussionID = $args[0];
        $discussionModel = $sender->DiscussionModel;
        $discussionModel->getID($discussionID);
        $sender->setData('Type', 'Idea');
        $sender->Form->setFormValue('Type', 'Idea');
        $sender->Form->setFormValue('Tags', val('TagID', StageModel::getStage($this->defaultStageID)));
        $sender->View = 'discussion';
        $sender->editDiscussion($discussionID);
    }

    public function postController_beforeDiscussionRender_handler($sender, $args) {
        if (val('Type', $sender->Data) === 'Idea') {
            $sender->Discussion = 'Idea'; // Kludge to set 'Post Discussion' button to 'Save'
            $sender->setData('Title', sprintf(t('New %s'), t('Idea')));
            $sender->ShowCategorySelector = false;

            if ($sender->data('Discussion')) {
                $sender->setData('Title', sprintf(t('Edit %s'), t('Idea')));
            } else {
                $sender->setData('Title', sprintf(t('New %s'), t('Idea')));
            }
        }
    }

    public function postController_discussionFormOptions_handler($sender, $args) {
        // Clear out announcements options. Kludge.
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

    public function discussionController_render_before($sender, $args) {
        // Don't display tags on a idea discussion.
        if (val('Discussion', $sender) && val('Type', $sender->Discussion) == 'Idea') {
            saveToConfig('Plugins.Tagging.DisableInline', true, true);
        } else {
            saveToConfig('Plugins.Tagging.DisableInline', false, true);
        }
    }


    // Print tags on discussionlist
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!$this->isIdea($discussion)) {
            return;
        }
        $stage = StageModel::getStageByDiscussion(val('DiscussionID', $discussion));
        if ($stage) {
            echo ' <a href="'.url('/discussions/tagged/'.urlencode(val('Name', $stage))).'"><span class="Tag Stage-Tag-'.urlencode(val('Name', $stage)).'"">'.val('Name', $stage).'</span></a> ';
        }

        $counter = IdeaCounterModule::instance();
        $counter->setDiscussion($discussion);
        echo $counter->toString();

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

    // ATTACHMENTS

    public function discussionController_fetchAttachmentViews_handler($sender) {
        require_once $sender->fetchViewLocation('attachment', '', 'plugins/ideation');
    }

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

    // HELPERS

    public function getStageNotes($discussion, $discussionModel = null) {
        if (!$discussionModel) {
            $discussionModel = new DiscussionModel();
        }
        return $discussionModel->getRecordAttribute($discussion, 'StageNotes');
    }

    public function isIdea($discussion) {
        if (is_numeric($discussion)) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussion);
        }
        return (strtolower(val('Type', $discussion)) == 'idea');
    }

    public function isIdeaCategory($category) {
        return in_array('Idea', val('AllowedDiscussionTypes', $category));
    }

    public function updateDiscussionStage($discussion, $stageID, $notes) {
        if (!$this->isIdea($discussion) || !is_numeric($stageID)) {
            return;
        }

        $discussionID = val('DiscussionID', $discussion);

        $this->updateDiscussionStageTag($discussionID, $stageID);
        if ($notes) {
            $this->updateDiscussionStageNotes($discussionID, $notes);
        }

        $this->updateAttachment($discussionID, $stageID, $notes);
    }

    private function updateDiscussionStageTag($discussionID, $stageID) {
        // TODO:  Decrement tag discussion count
        $oldStage = StageModel::getStageByDiscussion($discussionID);
        if (val('StageID', $oldStage) != $stageID) {
            $stage = StageModel::getStage($stageID);
            $tags = array(val('TagID', $stage));
            $tagModel = $this->getTagModel();
            $tagModel->saveDiscussion($discussionID, $tags, array('Stage'));
        }
    }

    private function updateDiscussionStageNotes($discussionID, $notes) {
        $discussionModel = new DiscussionModel();
        $discussionModel->saveToSerializedColumn('Attributes', $discussionID, 'StageNotes', $notes);
    }
}
