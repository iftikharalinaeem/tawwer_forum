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
        $String = '';
        ob_start();
        include(PATH_PLUGINS.'/Polls/views/poll.php');
        $String = ob_get_contents();
        @ob_end_clean();
        return $String;
    }

    /**
     *
     *
     * @throws Exception
     */
    private function loadPoll() {
        $PollModel = new PollModel();
        $Poll = false;
        $PollID = Gdn::controller()->data('PollID');
        $Discussion = Gdn::controller()->data('Discussion');

        // Look in the controller for a PollID
        if ($PollID > 0) {
            $Poll = $PollModel->getID($PollID);
        }

        // Failing that, look for a DiscussionID
        if (!$Poll && $Discussion) {
            $Poll = $PollModel->getByDiscussionID(val('DiscussionID', $Discussion));
        }

        if ($Poll) {
            // Load the poll options
            $PollID = val('PollID', $Poll);
            $OptionData = $this->getPollOptions($PollID);
            $PollOptions = $this->joinPollVotes($OptionData, $Poll, $PollModel);

            // Has this user voted?
            $countVotes = $PollModel->SQL
                ->select()
                ->from('PollVote pv')
                ->where(['pv.UserID' => Gdn::session()->UserID, 'pv.PollOptionID' => array_column($OptionData, 'PollOptionID')])
                ->get()
                ->numRows();
            $this->setData('UserHasVoted',  ($countVotes > 0));
        }

        $this->EventArguments['Poll'] = &$Poll;
        $this->EventArguments['PollOptions'] = &$PollOptions;
        $this->fireEvent('AfterLoadPoll');

        $this->setData('PollOptions', $PollOptions);
        $this->setData('Poll', $Poll);
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
        $anonymous = val('Anonymous', $poll) || C('Plugins.Polls.AnonymousPolls');
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
            foreach ($otherVoteData as $ID => $Users) {
                $votes[$ID] = array_slice($Users, 0, $this->MaxVoteUsers);
            }
        }

        return $votes;
    }
}
