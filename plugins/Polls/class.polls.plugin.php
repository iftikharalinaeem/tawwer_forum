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
     * @param Gdn_Controller $Sender
     */
    public function base_beforeCommentBody_handler($Sender) {
        $Comment = val('Comment', $Sender->EventArguments);
        $PollVote = val('PollVote', $Comment);
        if ($PollVote) {
            $this->EventArguments['String'] = &$PollVote;
            $this->fireEvent('FilterContent');

            echo '<div class="PollVote">';
            // Use the sort as the color indicator (it should match up)
            echo '<span class="PollColor PollColor'.val('Sort', $PollVote).'"></span>';
            echo '<span class="PollVoteAnswer">'.Gdn_Format::to(val('Body', $PollVote), val('Format', $PollVote)).'</span>';
            echo '</div>';
        }
    }

    /**
     *
     *
     * @param Gdn_Controller $Sender
     * @param array $Args
     */
    public function base_discussionTypes_handler($Sender, $Args) {
        $Args['Types']['Poll'] = [
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
     * @param DiscussionController $Sender
     */
    public function discussionController_pollVote_create($Sender) {
        $Session = Gdn::session();
        $Form = new Gdn_Form();
        $PollModel = new PollModel();
        $PollOptionModel = new Gdn_Model('PollOption');

        // Get values from the form
        $PollID = $Form->getFormValue('PollID', 0);
        $PollOptionID = $Form->getFormValue('PollOptionID', 0);
        $PollOption = $PollOptionModel->getID($PollOptionID);
        $VotedForPollOptionID = 0;

        // If this is a valid form postback, poll, poll option, and user session, record the vote.
        if ($Form->authenticatedPostback() && $PollOption && $Session->isValid()) {
            $VotedForPollOptionID = $PollModel->vote($PollOptionID);
        }

        if ($VotedForPollOptionID == 0) {
            $Sender->informMessage(t("You didn't select an option to vote for!"));
        }

        // What should we return?
        $Return = '/';
        if ($PollID > 0) {
            $Poll = $PollModel->getID($PollID);
            $Discussion = $Sender->DiscussionModel->getID(GetValue('DiscussionID', $Poll));
            if ($Discussion) {
                $Return = discussionUrl($Discussion);
            }
        }

        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            redirect($Return);
        }

        // Otherwise get the poll html & return it.
        $PollModule = new PollModule();
        $Sender->setData('PollID', $PollID);
        $Sender->setJson('PollHtml', $PollModule->toString());
        $Sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Load comment votes on discussion.
     *
     * @param type $Sender
     */
    public function discussionController_render_before($Sender) {
        $this->_loadVotes($Sender);
    }

    /**
     * Load user votes data based on the discussion in the controller data.
     *
     * @param type $Sender
     * @return type
     */
    private function _loadVotes($Sender) {
        // Does this discussion have an associated poll?
        $Discussion = $Sender->data('Discussion');
        if (!$Discussion) {
            $Discussion = val('Discussion', $Sender->EventArguments);
        }
        if (!$Discussion) {
            $Discussion = val('Discussion', $Sender);
        }

        if (strtolower(GetValue('Type', $Discussion)) == 'poll') {
            // Load css/js files
            $Sender->addJsFile('polls.js', 'plugins/Polls');

            // Load the poll based on the discussion id.
            $PollModel = new PollModel();
            $Poll = $PollModel->getByDiscussionID(val('DiscussionID', $Discussion));
            if (!$Poll) {
                return;
            }

            // Don't get user votes if this poll is anonymous.
            if (val('Anonymous', $Poll) || c('Plugins.Polls.AnonymousPolls')) {
                return;
            }

            // Look at all of the users in the comments, and load their associated
            // poll vote for displaying on their comments.
            $Comments = $Sender->data('Comments');
            if ($Comments) {
                // Grab all of the user fields that need to be joined.
                $UserIDs = array();
                foreach ($Comments as $Row) {
                    $UserIDs[] = val('InsertUserID', $Row);
                }

                // Get the user votes.
                $Votes = $PollModel->getVotesByUserID($Poll->PollID, $UserIDs);

                // Place the user votes on the comment data.
                foreach ($Comments as &$Row) {
                    $UserID = val('InsertUserID', $Row);
                    setValue('PollVote', $Row, val($UserID, $Votes));
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
     * @param Gdn_Controller $Sender
     */
    public function base_beforeDiscussionMeta_handler($Sender) {
        $Discussion = $Sender->EventArguments['Discussion'];
        if (strcasecmp(val('Type', $Discussion), 'Poll') == 0) {
            echo tag($Discussion, 'Type', 'Poll');
        }
    }

    /**
     * Add a css class to discussions in the discussion list if they have polls attached.
     */
    public function discussionsController_render_before($Sender) {
        $this->_addCss($Sender);
    }

    /**
     *
     *
     * @param $Sender
     */
    protected function _addCss($Sender) {
        $Discussions = $Sender->data('Discussions');
        if ($Discussions) {
            foreach ($Discussions as &$Row) {
                if (strtolower(val('Type', $Row)) == 'poll') {
                    setValue('_CssClass', $Row, trim(val('_CssClass', $Row).' ItemPoll'));
                }
            }
        }
    }

    /**
     *
     *
     * @param $Sender
     */
    public function categoriesController_render_before($Sender) {
        $this->_addCss($Sender);
    }

    /**
     * Add our CSS.
     *
     * @param AssetModel $Sender
     */
    public function assetModel_styleCss_handler($Sender, $Args) {
        $Sender->addCssFile('polls.css', 'plugins/Polls');
    }

    /**
     * Add the poll form to vanilla's post page.
     *
     * @param PostController $Sender
     */
    public function PostController_AfterForms_Handler($Sender) {
        $Forms = $Sender->data('Forms');
        $Forms[] = ['Name' => 'Poll', 'Label' => sprite('SpPoll').t('New Poll'), 'Url' => 'post/poll'];
        $Sender->setData('Forms', $Forms);
    }

    /**
     * Create the new poll method on post controller.
     */
    public function postController_poll_create($Sender) {
        $CategoryUrlCode = val(0, $Sender->RequestArgs);
        // Override CategoryID if categories are disabled
        $UseCategories = $this->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        if (!$UseCategories) {
            $CategoryUrlCode = '';
        }

        $PollModel = new PollModel();
        $Category = false;
        if ($CategoryUrlCode != '') {
            $CategoryModel = new CategoryModel();
            $Category = $CategoryModel->getByCode($CategoryUrlCode);
            $Sender->CategoryID = $Category->CategoryID;
        }

        if ($Category) {
            $Sender->Category = (object)$Category;
            $Sender->setData('Category', $Category);
            $Sender->Form->addHidden('CategoryID', $Sender->Category->CategoryID);
            if (val('DisplayAs', $Sender->Category) == 'Discussions') {
                $Sender->ShowCategorySelector = false;
            } else {
                // Get all our subcategories to add to the category if we are in a Header or Categories category.
                $Sender->Context = CategoryModel::getSubtree($this->CategoryID);
            }
        } else {
            $Sender->CategoryID = 0;
            $Sender->Category = null;
        }

        // Check permission
        $Sender->permission('Vanilla.Discussions.Add');
        $Sender->permission('Plugins.Polls.Add');

        // Polls are not compatible with pre-moderation
        if (checkRestriction('Vanilla.Approval.Require') && !val('Verified', Gdn::session()->User)) {
            throw PermissionException();
        }

        // Set the model on the form
        $Sender->Form->setModel($PollModel);
        if ($Sender->Form->authenticatedPostBack() === false) {
            if ($Sender->Category !== null) {
                $Sender->Form->setData(['CategoryID' => $Sender->Category->CategoryID]);
            }
        } else { // Form was submitted
            $FormValues = $Sender->Form->formValues();
            $DiscussionID = $PollModel->save($FormValues, $Sender->CommentModel);
            $Sender->Form->setValidationResults($PollModel->validationResults());
            if ($Sender->Form->errorCount() == 0) {
                $Discussion = $Sender->DiscussionModel->getID($DiscussionID);
                redirect(discussionUrl($Discussion));
            }
        }

        // Set up the page and render
        $Sender->title(t('New Poll'));

        // New poll page should show category in breadcrumb, just like new discussion form.
        $crumb = [];
        if ($Sender->Category) {
            $crumb[] = ['Name' => $Sender->Category->Name, 'Url' => categoryUrl($Sender->Category)];
        }
        $crumb[] = ['Name' => $Sender->data('Title'), 'Url' => '/post/poll'];

        $Sender->setData('Breadcrumbs', $crumb);
        $Sender->setData('_AnonymousPolls', c('Plugins.Polls.AnonymousPolls'));
        $Sender->addJsFile('jquery.duplicate.js');
        $Sender->addJsFile('polls.js', 'plugins/Polls');
        $this->_addCss($Sender);
        $Sender->render('add', '', 'plugins/Polls');
    }

    /**
     *
     *
     * @param PromotedContentModule $Sender
     * @param array $Args
     */
    public function promotedContentModule_afterBody_handler($Sender, $Args) {
        $Type = valr('Content.Type', $Sender->EventArgs);
        if (strcasecmp($Type, 'poll') === 0 && strlen(valr('Content.Body', $Sender->EventArgs)) === 0) {
            echo ' '.anchor(t('Click here to vote.'), $Sender->EventArgs['ContentUrl']).' ';
        }
    }

    /**
     * Display the poll on the discussion.
     *
     * @param DiscussionController $Sender
     */
    public function discussionController_afterDiscussionBody_handler($Sender) {
        $Discussion = $Sender->data('Discussion');
        if (strtolower(val('Type', $Discussion)) == 'poll') {
            echo Gdn_Theme::module('PollModule');
        }
    }
}
