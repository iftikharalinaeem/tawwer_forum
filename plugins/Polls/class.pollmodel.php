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

    const MAX_POLL_OPTION = 10;

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
     * Returns an array of UserID => PollVote/PollOption info. Used to display a
     * users vote on their comment in a discussion.
     *
     * @param array $where A filter suitable for passing to Gdn_SQLDriver::where().
     * @param string $orderFields A comma delimited string to order the data.
     * @param string $orderDirection One of **asc** or **desc**.
     * @param int|bool $limit The database limit.
     * @param int|bool $offset The database offset.
     * @return Gdn_DataSet
     */
    public function getVotesWhere($where = [], $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        return $this->SQL
            ->select('pv.*')
            ->from('PollVote pv')
            ->where($where)
            ->get();
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
     * Save a poll option.
     *
     * @param $pollID
     * @param $data
     * @return bool|int PollOptionID
     */
    public function saveOption($pollID, $data) {
        $pollOptionModel = new Gdn_Model('PollOption');
        $pollOptionID = $data['PollOptionID'] ?? false;
        $insert = !$pollOptionID;

        if ($insert) {
            if (!isset($data['Body'])) {
                $this->Validation->addValidationResult('PollOption', 'Missing PollOption Body');
            }
            if ($pollOptionModel->getCount(['PollID' => $pollID]) == self::MAX_POLL_OPTION) {
                $this->Validation->addValidationResult('PollOption', 'You can not specify more than 10 poll options.');
            }
        }

        if (isset($data['Body'])) {
            $data['Body'] = trim(Gdn_Format::plainText($data['Body']));

            if (!$data['Body']) {
                $this->Validation->addValidationResult('PollOption', 'Poll option body cannot be empty.');
            }
        }

        if (count($this->Validation->results()) == 0) {

            unset($data['Sort'], $data['Format']);
            if ($insert) {
                $data['PollID'] = $pollID;
                $data['Format'] = 'Text';
                $data['Sort'] = $pollOptionModel->getCount(['PollID' => $pollID]) + 1;
            }

            $pollOptionID = $pollOptionModel->save($data);
        }

        return $pollOptionID;
    }

    /**
     *
     * @param int $pollOptionID
     * @param int $userID
     * @return bool
     * @throws Exception
     */
    public function vote($pollOptionID, $userID = null) {
        if ($userID === null) {
            // Get objects from the database.
            $userID = Gdn::session()->UserID;
        }

        $pollOptionModel = new Gdn_Model('PollOption');
        $pollOption = $pollOptionModel->getID($pollOptionID);

        // If this is a valid poll option and user session, record the vote.
        if ($userID && $pollOption) {
            // Has this user voted on this poll before?
            $hasVoted = $this->hasUserVoted($userID, $pollOption->PollID);

            if ($hasVoted) {
                throw new Gdn_UserException(t('Users may only vote once per poll.'));
            }

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

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteID($id, $options = null) {
        $poll = $this->getID($id, DATASET_TYPE_ARRAY);
        $success = parent::deleteID($id);

        // Clean up
        if ($success) {
            $options = $this->getOptions($id);
            $optionIDs = array_keys($options);
            $this->SQL->delete('PollVote', ['PollOptionID' => $optionIDs]);
            $this->SQL->delete('PollOption', ['PollOptionID' => $optionIDs]);

            $discussionModel = new DiscussionModel();
            $discussionModel->update(['Type' => null], ['DiscussionID' => $poll['DiscussionID']]);
        }

        return $success;
    }

    /**
     * Delete an option.
     *
     * @param $id The Option ID.
     */
    public function deleteOptionID($id) {
        $option = $this->getOptionID($id);
        if (!$option) {
            return;
        }

        $this->SQL->delete('PollVote', ['PollOptionID' => $id]);
        $this->SQL->delete('PollOption', ['PollOptionID' => $id]);

        $this->SQL->update('PollOption', ['Sort-' => 1], ['PollOptionID' => $id])->put();

        $poll = $this->getID($option['PollID'], DATASET_TYPE_ARRAY);
        $this->update(['CountVotes' => ($poll['CountVotes'] ?? 0)-$option['CountVotes']], ['PollID' => $option['PollID']]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteVote($pollID, $userID) {
        $options = $this->getOptions($pollID);
        $optionIDs = array_keys($options);

        if (!$optionIDs) {
            return;
        }

        $vote = $this->getVotesWhere(['PollOptionID' => $optionIDs, 'UserID' => $userID])->firstRow(DATASET_TYPE_ARRAY);

        if (!$vote) {
            return;
        }

        $this->SQL->delete('PollVote', ['PollOptionID' => $optionIDs, 'UserID' => $userID]);

        $pollOptionID = $vote['PollOptionID'];
        $this->SQL->update('PollOption', ['CountVotes-' => 1], ['PollOptionID' => $pollOptionID])->put();
        $this->SQL->update('Poll', ['CountVotes-' => 1], ['PollID' => $pollID])->put();
    }

    /**
     * Get poll's options.
     *
     * @param $pollID
     * @return array Options indexed by PollOptionID.
     */
    public function getOptions($pollID) {
        $result = $this->SQL
            ->where('PollID', $pollID)
            ->get('PollOption', 'Sort')
            ->resultArray();

        return Gdn_DataSet::index($result, ['PollOptionID']);
    }

    /**
     * Get poll's option.
     *
     * @param $pollOptionID
     * @return array
     */
    public function getOptionID($pollOptionID) {
        return $this->SQL
            ->where('PollOptionID', $pollOptionID)
            ->get('PollOption')->firstRow(DATASET_TYPE_ARRAY);
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

    /**
     * Checks if the user has voted on the poll
     *
     * @param int $userID
     * @param int $pollID
     * @return bool
     */
    public function hasUserVoted($userID, $pollID) {
        $hasVoted = ($this->SQL
            ->select()
            ->from('PollVote pv')
            ->join('PollOption po', 'pv.PollOptionID = po.PollOptionID')
            ->where(['pv.UserID' => $userID, 'po.PollID' => $pollID])
            ->get()->numRows() > 0);

        return $hasVoted;
    }
}
