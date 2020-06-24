<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Vanilla\Polls\Models\SearchRecordTypePoll;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Garden\Schema\Schema;

/**
 * Class PollsPlugin
 */
class PollsPlugin extends Gdn_Plugin {

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Database updates.
     */
    public function structure() {
        include dirname(__FILE__).'/structure.php';
    }

    /**
     * @param Container $dic
     */
    public function container_init(Container $dic) {
        /*
         * Register additional advanced search sphinx record type Poll
         */
        $dic
            ->rule(SearchRecordTypeProviderInterface::class)
            ->addCall('setType', [new SearchRecordTypePoll()])
        ;
    }

    /**
     * Display a user's vote in their author info.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCommentBody_handler($sender) {
        $comment = val('Comment', $sender->EventArguments);
        $pollVote = val('PollVote', $comment);

        if ($pollVote) {
            $this->EventArguments['String'] = &$pollVote['Body'];
            $this->fireEvent('FilterContent');

            echo '<div class="PollVote">';
            // Use the sort as the color indicator (it should match up)
            echo '<span class="PollColor PollColor'.val('Sort', $pollVote).'"></span>';
            echo '<span class="PollVoteAnswer">'.Gdn_Format::to(val('Body', $pollVote), val('Format', $pollVote)).'</span>';
            echo '</div>';
        }
    }

    /**
     *
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_discussionTypes_handler($sender, $args) {
        $args['Types']['Poll'] = [
            'Singular' => 'Poll',
            'Plural' => 'Polls',
            'AddUrl' => '/post/poll',
            'AddText' => 'New Poll',
            'AddPermission' => 'Plugins.Polls.Add'
        ];
    }

    /**
     * Allows users to vote on a poll. Redirects them back to poll discussion, or
     * returns the module html if ajax request.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_pollVote_create($sender) {
        $form = new Gdn_Form();

        // Lookup the poll option, based on form values.
        $pollOptionModel = new Gdn_Model('PollOption');
        $pollOptionID = $form->getFormValue('PollOptionID');
        $pollOption = $pollOptionModel->getID($pollOptionID);
        if (!$pollOption) {
            throw notFoundException('Poll Option');
        }

        // Lookup the poll, based on the resolved poll option.
        $pollModel = new PollModel();
        $pollID = val('PollID', $pollOption);
        $poll = $pollModel->getID($pollID);
        if (!$poll) {
            throw notFoundException('Poll');
        }

        // Resolve the category ID from the poll's discussion.
        $discussion = $sender->DiscussionModel->getID(val('DiscussionID', $poll));
        $categoryID = val('CategoryID', $discussion);
        $category = CategoryModel::categories($categoryID);

        // Verify the user has permission to add comments in this poll's category.
        $sender->permission('Vanilla.Comments.Add', true, 'Category', val('PermissionCategoryID', $category));

        $votedForPollOptionID = 0;

        // Record the vote.
        if ($form->authenticatedPostback() && $pollOption) {
            $votedForPollOptionID = $pollModel->vote($pollOptionID);
        }

        if ($votedForPollOptionID == 0) {
            $sender->informMessage(t("You didn't select an option to vote for!"));
        }

        // What should we return?
        $return = '/';
        if ($pollID > 0) {
            $poll = $pollModel->getID($pollID);
            $discussion = $sender->DiscussionModel->getID(val('DiscussionID', $poll));
            if ($discussion) {
                $return = discussionUrl($discussion);
            }
        }

        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo($return);
        }

        // Otherwise get the poll html & return it.
        $pollModule = new PollModule();
        $sender->setData('PollID', $pollID);
        $sender->setJson('PollHtml', $pollModule->toString());
        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Load comment votes on discussion.
     *
     * @param type $sender
     */
    public function discussionController_render_before($sender) {
        $this->_loadVotes($sender);
    }

    /**
     * Load user votes data based on the discussion in the controller data.
     *
     * @param type $sender
     * @return type
     */
    private function _loadVotes($sender) {
        // Does this discussion have an associated poll?
        $discussion = $sender->data('Discussion');
        if (!$discussion) {
            $discussion = val('Discussion', $sender->EventArguments);
        }
        if (!$discussion) {
            $discussion = val('Discussion', $sender);
        }

        if (strtolower(val('Type', $discussion)) == 'poll') {
            // Load css/js files
            $sender->addJsFile('polls.js', 'plugins/Polls');

            // Load the poll based on the discussion id.
            $pollModel = new PollModel();
            $poll = $pollModel->getByDiscussionID(val('DiscussionID', $discussion));
            if (!$poll) {
                return;
            }

            // Don't get user votes if this poll is anonymous.
            if (val('Anonymous', $poll) || c('Plugins.Polls.AnonymousPolls')) {
                return;
            }

            // Look at all of the users in the comments, and load their associated
            // poll vote for displaying on their comments.
            $comments = $sender->data('Comments');
            if ($comments) {
                // Grab all of the user fields that need to be joined.
                $userIDs = [];
                foreach ($comments as $row) {
                    $userIDs[] = val('InsertUserID', $row);
                }

                // Get the user votes.
                $votes = $pollModel->getVotesByUserID($poll->PollID, $userIDs);

                // Place the user votes on the comment data.
                foreach ($comments as &$row) {
                    $userID = val('InsertUserID', $row);
                    setValue('PollVote', $row, val($userID, $votes));
                }
            }
        }
    }

    /**
     * Update the poll name after a discussion has been edited.
     *
     * @param DiscussionModel $sender The discussion model doing the save.
     * @param array $args Additional event details.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        if ($args['Insert'] || valr('Fields.Name', $args, null) === null) {
            return;
        }

        $model = new PollModel();
        $poll = $model->getWhere(['DiscussionID' => $args['DiscussionID']])->firstRow(DATASET_TYPE_ARRAY);
        if ($poll) {
            $model->setField($poll['PollID'], ['Name' => valr('Fields.Name', $args)]);
        }
    }

    /**
     * Display the Poll label on the discussion list.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeDiscussionMeta_handler($sender) {
        $discussion = $sender->EventArguments['Discussion'];
        if (strcasecmp(val('Type', $discussion), 'Poll') == 0) {
            echo tag($discussion, 'Type', 'Poll');
        }
    }

    /**
     * Add QnA fields to the search schema.
     *
     * @param Schema $schema
     */
    public function searchResultSchema_init(Schema $schema) {
        $types = $schema->getField('properties.type.enum');
        $types[] = 'poll';
        $schema->setField('properties.type.enum', $types);
    }

    /**
     * Add a css class to discussions in the discussion list if they have polls attached.
     */
    public function discussionsController_render_before($sender) {
        $this->_addCss($sender);
    }

    /**
     *
     *
     * @param $sender
     */
    protected function _addCss($sender) {
        $discussions = $sender->data('Discussions');
        if ($discussions) {
            foreach ($discussions as &$row) {
                if (strtolower(val('Type', $row)) == 'poll') {
                    setValue('_CssClass', $row, trim(val('_CssClass', $row).' ItemPoll'));
                }
            }
        }
    }

    /**
     *
     *
     * @param $sender
     */
    public function categoriesController_render_before($sender) {
        $this->_addCss($sender);
    }

    /**
     * Add our CSS.
     *
     * @param AssetModel $sender
     */
    public function assetModel_styleCss_handler($sender, $args) {
        $sender->addCssFile('polls.css', 'plugins/Polls');
    }

    /**
     * Add the poll form to vanilla's post page.
     *
     * @param PostController $sender
     */
    public function postController_afterForms_handler($sender) {
        $forms = $sender->data('Forms');
        $forms[] = ['Name' => 'Poll', 'Label' => sprite('SpPoll').t('New Poll'), 'Url' => 'post/poll'];
        $sender->addJsFile('jquery.duplicate.js');
        $sender->addJsFile('polls.js', 'plugins/Polls');
        $sender->setData('Forms', $forms);
    }

    /**
     * Create the new poll method on post controller.
     *
     * @param PostController $sender
     */
    public function postController_poll_create($sender) {
        $categoryUrlCode = val(0, $sender->RequestArgs);
        // Override CategoryID if categories are disabled
        $useCategories = $this->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        if (!$useCategories) {
            $categoryUrlCode = '';
        }

        Gdn_Theme::section('PostDiscussion');
        Gdn_Theme::section('PostPoll');

        $pollModel = new PollModel();
        $category = false;
        if ($categoryUrlCode != '') {
            $categoryModel = new CategoryModel();
            $category = $categoryModel->getByCode($categoryUrlCode);
            $sender->CategoryID = $category->CategoryID;
        }

        if ($category) {
            $sender->Category = (object)$category;
            // Ensure AllowedDiscussionTypes is dbdecoded.
            $category = (object)$categoryModel::permissionCategory($category);
            $isAllowedPolls = in_array('Poll', $category->AllowedDiscussionTypes);
            $isAllowedTypes = isset($category->AllowedDiscussionTypes);
            if (!$isAllowedPolls && $isAllowedTypes) {
                $sender->Form->addError(t('You are not allowed to post a poll in this category.'));
            }
            $sender->setData('Category', $category);
            $sender->Form->addHidden('CategoryID', $sender->Category->CategoryID);
            if (val('DisplayAs', $sender->Category) == 'Discussions') {
                $sender->ShowCategorySelector = false;
            } else {
                // Get all our subcategories to add to the category if we are in a Header or Categories category.
                $sender->Context = CategoryModel::getSubtree($this->CategoryID);
            }
        } else {
            $sender->CategoryID = 0;
            $sender->Category = null;
        }

        // Check permission
        $sender->permission('Vanilla.Discussions.Add');
        $sender->permission('Plugins.Polls.Add');

        // Polls are not compatible with pre-moderation
        if (checkRestriction('Vanilla.Approval.Require') && !val('Verified', Gdn::session()->User)) {
            throw permissionException();
        }

        // Set the model on the form
        $sender->Form->setModel($pollModel);
        if ($sender->Form->authenticatedPostBack() === false) {
            if ($sender->Category !== null) {
                $sender->Form->setData(['CategoryID' => $sender->Category->CategoryID]);
            }
        } else { // Form was submitted
            $formPostValues = $sender->Form->formValues();
            $formPostValues['Type'] = 'poll'; // Force the "poll" discussion type.

            $discussionModel = new DiscussionModel();

            // New poll? Set default category ID if none is defined.
            if (!val('DiscussionID', $formPostValues, '')) {
                if (!val('CategoryID', $formPostValues) && !c('Vanilla.Categories.Use')) {
                    $formPostValues['CategoryID'] = val('CategoryID', CategoryModel::defaultCategory(), -1);
                }
            }

            $pollModel->addInsertFields($formPostValues);

            // Validate the discussion/poll model's form fields
            $discussionModel->validate($formPostValues, true);
            $pollModel->validate($formPostValues, true);

            $discussionValidationResults = $discussionModel->Validation->results();
            // Unset the body validation results (they're not required).
            if (array_key_exists('Body', $discussionValidationResults)) {
                unset($discussionValidationResults['Body']);
            }

            // And add the results to this validation object so they bubble up to the form.
            $pollModel->Validation->addValidationResult($discussionValidationResults);

            // Are there enough non-empty poll options?
            $pollOptions = val('PollOption', $formPostValues);
            $validPollOptions = [];
            if (is_array($pollOptions)) {
                foreach ($pollOptions as $pollOption) {
                    $pollOption = trim(Gdn_Format::plainText($pollOption));
                    if (!empty($pollOption)) {
                        $validPollOptions[] = $pollOption;
                    }
                }
            }
            // Assign back the filtered data.
            $formPostValues['PollOption'] = $validPollOptions;

            $countValidOptions = count($validPollOptions);
            if ($countValidOptions < 2) {
                $pollModel->Validation->addValidationResult('PollOption', 'You must provide at least 2 valid poll options.');
            }
            if ($countValidOptions > PollModel::MAX_POLL_OPTION) {
                $pollModel->Validation->addValidationResult('PollOption', 'You can not specify more than 10 poll options.');
            }
            $discussionModel->EventArguments['PollOptions'] = $validPollOptions;

            if (count($pollModel->Validation->results()) == 0) {
                // Make the discussion body not required while creating a new poll.
                // This saves in memory, but not to the file:
                saveToConfig('Vanilla.DiscussionBody.Required', false, ['Save' => false]);

                $discussionID = $discussionModel->save($formPostValues);
                $discussion = $discussionModel->getID($discussionID);

                $formPostValues['Name'] = val('Name', $discussion);
                $formPostValues['DiscussionID'] = $discussionID;

                $pollModel->save($formPostValues);

                if ($sender->Form->errorCount() == 0) {
                    redirectTo(discussionUrl($discussion));
                }
            } else {
                $validationResults = $discussionModel->validationResults() + $pollModel->validationResults();
                $sender->Form->setValidationResults($validationResults);
            }
        }

        // Set up the page and render
        $sender->title(t('New Poll'));

        // New poll page should show category in breadcrumb, just like new discussion form.
        $crumb = [];
        if ($sender->Category) {
            $crumb[] = ['Name' => $sender->Category->Name, 'Url' => categoryUrl($sender->Category)];
        }
        $crumb[] = ['Name' => $sender->data('Title'), 'Url' => '/post/poll'];

        $sender->setData('Breadcrumbs', $crumb);
        $sender->setData('_AnonymousPolls', c('Plugins.Polls.AnonymousPolls'));
        $sender->addJsFile('jquery.duplicate.js');
        $sender->addJsFile('post.js');
        $sender->addJsFile('polls.js', 'plugins/Polls');
        $this->_addCss($sender);

        if ($sender->Form->errorCount() > 0) {
            // Return the form errors
            $sender->errorMessage($sender->Form->errors());
        }

        $sender->render('add', '', 'plugins/Polls');
    }

    /**
     *
     *
     * @param PromotedContentModule $sender
     * @param array $args
     */
    public function promotedContentModule_afterBody_handler($sender, $args) {
        $type = valr('Content.Type', $sender->EventArgs);
        if (strcasecmp($type, 'poll') === 0 && strlen(valr('Content.Body', $sender->EventArgs)) === 0) {
            echo ' '.anchor(t('Click here to vote.'), $sender->EventArgs['ContentUrl']).' ';
        }
    }

    /**
     * Display the poll on the discussion.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_afterDiscussionBody_handler($sender) {
        $discussion = $sender->data('Discussion');
        if (strtolower(val('Type', $discussion)) == 'poll') {
            echo Gdn_Theme::module('PollModule');
        }
    }
}
