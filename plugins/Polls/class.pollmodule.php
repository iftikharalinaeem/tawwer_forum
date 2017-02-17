<?php if (!defined('APPLICATION')) exit();

class PollModule extends Gdn_Module {

    /**
     * The maximum limit for all user vote queries.
     * This prevents scaling issues when polls have a lot of votes.
     */
    const LIMIT_THRESHOLD = 100;

    /**
     * @var int The maximum number of users that will displayed below each vote option.
     */
    public $MaxVoteUsers = 20;

    /**
     * Initialize a new instance of the {@link PollModule}.
     *
     * @param Gdn_Controller $sender The controller responsible for the module.
     */
    public function __construct($sender = null) {
        parent::__construct($sender);
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        $this->loadPoll();
        $this->setData('ShowForm', $this->showForm());
        ob_start();
        include(PATH_PLUGINS.'/Polls/views/poll.php');
        $string = ob_get_contents();
        @ob_end_clean();
        return $string;
    }

    /**
     *
     *
     * @throws Exception
     */
    private function loadPoll() {
        $pollModel = new PollModel();
        $poll = false;
        $pollID = Gdn::controller()->data('PollID');
        $discussion = Gdn::controller()->data('Discussion');

        // Look in the controller for a PollID
        if ($pollID > 0) {
            $poll = $pollModel->getID($pollID);
        }

        // Failing that, look for a DiscussionID
        if (!$poll && $discussion) {
            $poll = $pollModel->getByDiscussionID(val('DiscussionID', $discussion));
        }

        if ($poll) {
            // Load the poll options
            $pollID = val('PollID', $poll);
            $optionData = $this->getPollOptions($pollID);
            $pollOptions = $this->joinPollVotes($optionData, $poll, $pollModel);

            // Has this user voted?
            $countVotes = $pollModel->SQL
                ->select()
                ->from('PollVote')
                ->where([
                    'UserID' => Gdn::session()->UserID,
                    'PollOptionID' => array_column($optionData, 'PollOptionID')
                ])->get()->numRows();
            $this->setData('UserHasVoted',  ($countVotes > 0));
        }

        $this->EventArguments['Poll'] = &$poll;
        $this->EventArguments['PollOptions'] = &$pollOptions;
        $this->fireEvent('AfterLoadPoll');

        $this->setData('PollOptions', $pollOptions);
        $this->setData('Poll', $poll);
    }

    /**
     * Get poll options for a given poll.
     *
     * @param $pollID The ID of the poll to retrieve.
     * @return array|null An array representation of the Poll.
     */
    public function getPollOptions($pollID) {
        $pollOptionModel = new Gdn_Model('PollOption');
        return $pollOptionModel->getWhere(array('PollID' => $pollID), 'Sort', 'asc')->resultArray();
    }

    /**
     * Add voting info to the poll options array and return.
     *
     * @param array $optionData The poll options.
     * @param object $poll The poll to join the votes in on.
     * @param PollModel|null $pollModel The poll model. Instantiates if not passed in.
     * @return array The poll options array with voting info joined in.
     */
    public function joinPollVotes($optionData, $poll, $pollModel = null) {
        // Load the poll votes
        $anonymous = val('Anonymous', $poll) || c('Plugins.Polls.AnonymousPolls');
        if (!$anonymous) {
            if (!is_a($pollModel, 'PollModel')) {
                $pollModel = new PollModel();
            }
            $voteData = $this->getPollVotes($optionData, $pollModel);
        }

        // Build the result set to deliver to the page
        $pollOptions = array();
        foreach ($optionData as $option) {
            if (!$anonymous) {
                $votes = val($option['PollOptionID'], $voteData, []);
                $option['Votes'] = $votes;
            }
            $pollOptions[] = $option;
        }

        return $pollOptions;
    }

    /**
     * Gets the users that voted for poll options.
     *
     * @param array $pollOptions The poll option data to query.
     * @return array Returns an array of arrays of users indexed by poll option ID.
     */
    private function getPollVotes($pollOptions) {
        $optionIDs = [];
        $votes = [];
        $voteThreshold = self::LIMIT_THRESHOLD / count($pollOptions);

        // Go through the options to see which ones have too many votes to get as a group.
        foreach ($pollOptions as $option) {
            if (val('CountVotes', $option, 0) < $voteThreshold) {
                $optionIDs[] = $option['PollOptionID'];
            } else {
                // The option has too many votes so get the users for it separately.
                $ID = $option['PollOptionID'];
                $optionVotes = Gdn::sql()->getWhere(
                    'PollVote',
                    array('PollOptionID' => $ID),
                    '', '',
                    $this->MaxVoteUsers)->resultArray();

                // Join the users.
                Gdn::userModel()->joinUsers($optionVotes, array('UserID'));
                $votes[$ID] = $optionVotes;
            }
        }

        // Join the rest of the poll option IDs.
        if (!empty($optionIDs)) {
            $otherVoteData = Gdn::sql()
                ->getWhere('PollVote', ['PollOptionID' => $optionIDs], '', '', self::LIMIT_THRESHOLD)
                ->resultArray();

            // Join the users.
            Gdn::userModel()->joinUsers($otherVoteData, ['UserID']);

            $otherVoteData = Gdn_DataSet::index($otherVoteData, 'PollOptionID', ['Unique' => false]);
            foreach ($otherVoteData as $ID => $users) {
                $votes[$ID] = array_slice($users, 0, $this->MaxVoteUsers);
            }
        }

        return $votes;
    }

    /*
     * Should the voting form be shown?
     *
     * @return bool
     */
    private function showForm() {
        $categoryID = Gdn::controller()->data('CategoryID');

        if (!$categoryID) {
            $discussion = Gdn::controller()->data('Discussion');
            $categoryID = val('CategoryID', $discussion);

        }

        $category = CategoryModel::categories($categoryID);

        $canVote = Gdn::session()->checkPermission('Vanilla.Comments.Add', true, 'Category', val('PermissionCategoryID', $category));
        return $canVote && !$this->data('UserHasVoted');
    }
}
