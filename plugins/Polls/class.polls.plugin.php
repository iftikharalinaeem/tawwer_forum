<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

$PluginInfo['Polls'] = [
    'Name' => 'Polls',
    'Description' => "Allow users to create and vote on polls.",
    'Version' => '1.2.3',
    'RequiredApplications' => ['Vanilla' => '2.1'],
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://markosullivan.ca',
    'MobileFriendly' => true,
    'RegisterPermissions' => ['Plugins.Polls.Add' => 'Garden.Profiles.Edit'],
    'Icon' => 'polls.png'
];

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
     * Display a user's vote in their author info.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCommentBody_handler($sender) {
        $comment = val('Comment', $sender->EventArguments);
        $pollVote = val('PollVote', $comment);
        if ($pollVote) {
            $this->EventArguments['String'] = &$pollVote;
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
        $session = Gdn::session();
        $form = new Gdn_Form();
        $pollModel = new PollModel();
        $pollOptionModel = new Gdn_Model('PollOption');

        // Get values from the form
        $pollID = $form->getFormValue('PollID', 0);
        $pollOptionID = $form->getFormValue('PollOptionID', 0);
        $pollOption = $pollOptionModel->getID($pollOptionID);
        $votedForPollOptionID = 0;

        // If this is a valid form postback, poll, poll option, and user session, record the vote.
        if ($form->authenticatedPostback() && $pollOption && $session->isValid()) {
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
            redirect($return);
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
                $userIDs = array();
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
        $poll = $model->getWhere(array('DiscussionID' => $args['DiscussionID']))->firstRow(DATASET_TYPE_ARRAY);
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
    public function PostController_AfterForms_Handler($sender) {
        $forms = $sender->data('Forms');
        $forms[] = ['Name' => 'Poll', 'Label' => sprite('SpPoll').t('New Poll'), 'Url' => 'post/poll'];
        $sender->setData('Forms', $forms);
    }

    /**
     * Create the new poll method on post controller.
     */
    public function postController_poll_create($sender) {
        $categoryUrlCode = val(0, $sender->RequestArgs);
        // Override CategoryID if categories are disabled
        $useCategories = $this->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        if (!$useCategories) {
            $categoryUrlCode = '';
        }

        $pollModel = new PollModel();
        $category = false;
        if ($categoryUrlCode != '') {
            $categoryModel = new CategoryModel();
            $category = $categoryModel->getByCode($categoryUrlCode);
            $sender->CategoryID = $category->CategoryID;
        }

        if ($category) {
            $sender->Category = (object)$category;
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
            throw PermissionException();
        }

        // Set the model on the form
        $sender->Form->setModel($pollModel);
        if ($sender->Form->authenticatedPostBack() === false) {
            if ($sender->Category !== null) {
                $sender->Form->setData(['CategoryID' => $sender->Category->CategoryID]);
            }
        } else { // Form was submitted
            $formValues = $sender->Form->formValues();
            $discussionID = $pollModel->save($formValues, $sender->CommentModel);
            $sender->Form->setValidationResults($pollModel->validationResults());
            if ($sender->Form->errorCount() == 0) {
                $discussion = $sender->DiscussionModel->getID($discussionID);
                redirect(discussionUrl($discussion));
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
        $sender->addJsFile('polls.js', 'plugins/Polls');
        $this->_addCss($sender);
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
