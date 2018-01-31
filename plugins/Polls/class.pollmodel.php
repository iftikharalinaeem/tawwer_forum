<?php
/**
 * Poll Model
 *
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Manages poll discussions.
 */
class PollModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct('Poll');
    }

    /**
     * Get the poll info based on the associated discussion id.
     *
     * @param int $discussionID
     * @return array
     */
    public function getByDiscussionID($discussionID) {
        return $this->getWhere(['DiscussionID' => $discussionID])->firstRow();
    }

    /**
     * Returns an array of UserID => PollVote/PollOption info. Used to display a
     * users vote on their comment in a discussion.
     *
     * @param int $pollID
     * @param array $userIDs
     * @return array array of UserID => PollVote/PollOptions.
     */
    public function getVotesByUserID($pollID, $userIDs) {
        if (empty($userIDs)) {
            return [];
        }

        $data = $this->SQL
            ->select('pv.UserID, po.*')
            ->from('PollVote pv')
            ->join('PollOption po', 'po.PollOptionID = pv.PollOptionID')
            ->whereIn('pv.UserID', $userIDs)
            ->where('po.PollID', $pollID)
            ->get();

        $return = [];
        foreach ($data as $row) {
            $return[val('UserID', $row)] = [
                 'PollOptionID' => val('PollOptionID', $row),
                 'Body' => val('Body', $row),
                 'Format' => val('Format', $row),
                 'Sort' => val('Sort', $row)
            ];
        }
        return $return;
    }

    /**
     * Save a poll returns the poll id.
     *
     * @param array $formPostValues The data to save.
     * @param array $settings Not used.
     * @return int|false Returns the ID of the poll or **false** on error.
     */
    public function save($formPostValues, $settings = []) {
        $formPostValues = $this->filterForm($formPostValues);

        $pollID = $formPostValues['PollID'] ?? false;
        $insert = !$pollID;

        if ($insert) {
            // Force anonymous polls.
            if (c('Plugins.Polls.AnonymousPolls')) {
                $formPostValues['Anonymous'] = 1;
            }
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        $discussionID = val('DiscussionID', $formPostValues, null);
        $discussionModel = new DiscussionModel();
        if ($discussionID) {
            $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            if (!$discussion) {
                $this->Validation->addValidationResult('DiscussionID', 'Polls must belong to an existing discussion.');
            } else {
                // Check if the discussion is already a poll.
                if ($discussion['Type'] === 'Poll') {
                    // Make sure that there are no polls attached to it. (This can happen if the poll is created by the UI)
                    if ($this->getWhere(['DiscussionID' => $discussionID])->firstRow()) {
                        $this->Validation->addValidationResult('DiscussionID', 'Only one poll per discussion is allowed.');
                    }
                } else {
                    $discussionModel->setField($discussionID, 'Type', 'Poll');
                }
            }
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        $pollOptions = val('PollOption', $formPostValues, []);

        $this->validate($formPostValues, $insert);

        // If all validation passed, create the discussion with discmodel, and then insert all of the poll data.
        if (count($this->Validation->results()) == 0) {
            if ($insert) {
                // Save the poll record.
                $poll = [
                    'Name' => val('Name', $formPostValues),
                    'Anonymous' => val('Anonymous', $formPostValues),
                    'DiscussionID' => $discussionID,
                    'CountOptions' => count($pollOptions),
                    'CountVotes' => 0
                ];
                $poll = $this->coerceData($poll);
                $pollID = $this->insert($poll);

                // Save the poll options.
                $pollOptionModel = new Gdn_Model('PollOption');
                $i = 0;
                foreach ($pollOptions as $option) {
                    $i++;
                    $pollOption = [
                        'PollID' => $pollID,
                        'Body' => $option,
                        'Format' => 'Text',
                        'Sort' => $i
                    ];
                    $pollOptionModel->save($pollOption);
                }
            } else {
                $newPollData = $this->coerceData($formPostValues);

                if (isset($discussionID)) {
                    $currentPoll = $this->getID($pollID, DATASET_TYPE_ARRAY);
                    if ($currentPoll['DiscussionID'] !== $discussionID) {
                        $discussionModel->setField($currentPoll['DiscussionID'], 'Type', null);
                    }
                }

                $this->update($newPollData, ['PollID' => $pollID]);
            }
        }

        return $pollID;
    }

    /**
     *
     *
     * @param $pollOptionID
     * @return bool
     * @throws Exception
     */
    public function vote($pollOptionID) {
        // Get objects from the database.
        $userID = Gdn::session()->UserID;
        $pollOptionModel = new Gdn_Model('PollOption');
        $pollOption = $pollOptionModel->getID($pollOptionID);

        // If this is a valid poll option and user session, record the vote.
        if ($userID && $pollOption) {
            // Has this user voted on this poll before?
            $hasVoted = ($this->SQL
                ->select()
                ->from('PollVote')
                ->where(['UserID' => $userID, 'PollOptionID' => $pollOptionID])
                ->get()->numRows() > 0);
            if (!$hasVoted) {
                // Insert the vote
                $pollVoteModel = new Gdn_Model('PollVote');
                $pollVoteModel->insert(['UserID' => $userID, 'PollOptionID' => $pollOptionID]);

                // Update the vote counts
                $pollOptionModel->update(['CountVotes' => val('CountVotes', $pollOption, 0)+1], ['PollOptionID' => $pollOptionID]);
                $poll = $this->getID(val('PollID', $pollOption));
                $this->update(['CountVotes' => val('CountVotes', $poll, 0)+1], ['PollID' => val('PollID', $pollOption)]);

                $this->EventArguments['Poll'] = (array)$poll;
                $this->EventArguments['PollOption'] = (array)$pollOption;
                $this->fireEvent('Vote');

                return $pollOptionID;
            }
        }
        return false;
    }

    /**
     * Expose Gdn_Model::addInsertFields()
     * We need to expose this function so that we can pass pre validation in save().
     *
     * @param array $fields
     */
    public function addInsertFields(&$fields) {
        parent::addInsertFields($fields);
    }
}
