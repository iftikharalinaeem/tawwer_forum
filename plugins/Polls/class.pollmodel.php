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
        return $this->getWhere(array('DiscussionID' => $discussionID))->firstRow();
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
     * Inserts a new poll returns the discussion id.
     *
     * @param array $formPostValues The data to save.
     * @param array $settings Not used.
     * @return int|false Returns the ID of the poll or **false** on error.
     */
    public function save($formPostValues, $settings = []) {
        $formPostValues = $this->filterForm($formPostValues);
        $this->addInsertFields($formPostValues);
        $this->addUpdateFields($formPostValues);

        if (c('Plugins.Polls.AnonymousPolls')) {
            $formPostValues['Anonymous'] = 1;
        }
        $session = Gdn::session();
        $formPostValues['Type'] = 'poll'; // Force the "poll" discussion type.
        $discussionID = 0;
        $discussionModel = new DiscussionModel();

        // Make the discussion body not required while creating a new poll.
        // This saves in memory, but not to the file:
        saveToConfig('Vanilla.DiscussionBody.Required', false, ['Save' => false]);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:

        // New poll? Set default category ID if none is defined.
        if (!val('DiscussionID', $formPostValues, '')) {
            if (!val('CategoryID', $formPostValues) && !c('Vanilla.Categories.Use')) {
                $formPostValues['CategoryID'] = val('CategoryID', CategoryModel::defaultCategory(), -1);
            }
        }

        // This should have been done in discussion model:
        // Validate category permissions.
        $categoryID = val('CategoryID', $formPostValues);
        if ($categoryID > 0) {
            $category = CategoryModel::categories($categoryID);
            if ($category && !$session->checkPermission('Vanilla.Discussions.Add', true, 'Category', val('PermissionCategoryID', $category))) {
                $this->Validation->addValidationResult('CategoryID', 'You do not have permission to create polls in this category');
            }
        }

        // This should have been done in discussion model:
        // Make sure that the title will not be invisible after rendering
        $name = trim(val('Name', $formPostValues, ''));
        if ($name != '' && Gdn_Format::text($name) == '') {
            $this->Validation->addValidationResult('Name', 'You have entered an invalid poll title.');
        } else {
            // Trim the name.
            $formPostValues['Name'] = $name;
        }

        $this->EventArguments['FormPostValues'] = &$formPostValues;
		$this->EventArguments['DiscussionID'] = $discussionID;
		$this->fireAs('DiscussionModel')->fireEvent('BeforeSaveDiscussion');

        // Validate the discussion model's form fields
        $discussionModel->validate($formPostValues, true);

        // Unset the body validation results (they're not required).
        $discussionValidationResults = $discussionModel->Validation->results();
        if (array_key_exists('Body', $discussionValidationResults)) {
            unset($discussionValidationResults['Body']);
        }

        // And add the results to this validation object so they bubble up to the form.
        $this->Validation->addValidationResult($discussionValidationResults);

        // Are there enough non-empty poll options?
        $pollOptions = val('PollOption', $formPostValues);
        $validPollOptions = array();
        if (is_array($pollOptions))
            foreach ($pollOptions as $pollOption) {
                $pollOption = trim(Gdn_Format::plainText($pollOption));
                if ($pollOption != '') {
                    $validPollOptions[] = $pollOption;
                }
            }

        $countValidOptions = count($validPollOptions);
        if ($countValidOptions < 2) {
            $this->Validation->addValidationResult('PollOption', 'You must provide at least 2 valid poll options.');
        }
        if ($countValidOptions > 10) {
            $this->Validation->addValidationResult('PollOption', 'You can not specify more than 10 poll options.');
        }
        $discussionModel->EventArguments['PollOptions'] = $validPollOptions;

        // If all validation passed, create the discussion with discmodel, and then insert all of the poll data.
        if (count($this->Validation->results()) == 0) {
            $discussionID = $discussionModel->save($formPostValues);
            if ($discussionID > 0) {
                $discussion = $discussionModel->getID($discussionID);
                // Save the poll record.
                $poll = [
                    'Name' => $discussion->Name,
                    'Anonymous' => val('Anonymous', $formPostValues),
                    'DiscussionID' => $discussionID,
                    'CountOptions' => $countValidOptions,
                    'CountVotes' => 0
                ];
                $poll = $this->coerceData($poll);
                $pollID = $this->insert($poll);

                // Save the poll options.
                $pollOptionModel = new Gdn_Model('PollOption');
                $i = 0;
                foreach ($validPollOptions as $option) {
                    $i++;
                    $pollOption = [
                        'PollID' => $pollID,
                        'Body' => $option,
                        'Format' => 'Text',
                        'Sort' => $i
                    ];
                    $pollOptionModel->save($pollOption);
                }
                // Update the discussion attributes with info
            }
        }

        return $discussionID;
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
                ->where(array('UserID' => $userID, 'PollOptionID' => $pollOptionID))
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
}
