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
     * @param int $DiscussionID
     * @return array
     */
    public function getByDiscussionID($DiscussionID) {
        return $this->getWhere(array('DiscussionID' => $DiscussionID))->firstRow();
    }

    /**
     * Returns an array of UserID => PollVote/PollOption info. Used to display a
     * users vote on their comment in a discussion.
     *
     * @param int $PollID
     * @param array $UserIDs
     * @return array array of UserID => PollVote/PollOptions.
     */
    public function getVotesByUserID($PollID, $UserIDs) {
        if (empty($UserIDs)) {
            return [];
        }

        $Data = $this->SQL
            ->select('pv.UserID, po.*')
            ->from('PollVote pv')
            ->join('PollOption po', 'po.PollOptionID = pv.PollOptionID')
            ->whereIn('pv.UserID', $UserIDs)
            ->where('po.PollID', $PollID)
            ->get();

        $Return = [];
        foreach ($Data as $Row) {
            $Return[val('UserID', $Row)] = [
                 'PollOptionID' => val('PollOptionID', $Row),
                 'Body' => val('Body', $Row),
                 'Format' => val('Format', $Row),
                 'Sort' => val('Sort', $Row)
            ];
        }
        return $Return;
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
        $Session = Gdn::session();
        $formPostValues['Type'] = 'poll'; // Force the "poll" discussion type.
        $DiscussionID = 0;
        $DiscussionModel = new DiscussionModel();

        // Make the discussion body not required while creating a new poll.
        // This saves in memory, but not to the file:
        saveToConfig('Vanilla.DiscussionBody.Required', false, ['Save' => false]);

        // Define the primary key in this model's table.
        $this->defineSchema();

        // Add & apply any extra validation rules:

        // New poll? Set default category ID if none is defined.
        if (!arrayValue('DiscussionID', $formPostValues, '')) {
            if (!val('CategoryID', $formPostValues) && !c('Vanilla.Categories.Use')) {
                $formPostValues['CategoryID'] = val('CategoryID', CategoryModel::defaultCategory(), -1);
            }
        }

        // This should have been done in discussion model:
        // Validate category permissions.
        $CategoryID = val('CategoryID', $formPostValues);
        if ($CategoryID > 0) {
            $Category = CategoryModel::categories($CategoryID);
            if ($Category && !$Session->checkPermission('Vanilla.Discussions.Add', true, 'Category', val('PermissionCategoryID', $Category))) {
                $this->Validation->addValidationResult('CategoryID', 'You do not have permission to create polls in this category');
            }
        }

        // This should have been done in discussion model:
        // Make sure that the title will not be invisible after rendering
        $Name = trim(val('Name', $formPostValues, ''));
        if ($Name != '' && Gdn_Format::text($Name) == '') {
            $this->Validation->addValidationResult('Name', 'You have entered an invalid poll title.');
        } else {
            // Trim the name.
            $formPostValues['Name'] = $Name;
        }

        $this->EventArguments['FormPostValues'] = &$formPostValues;
		$this->EventArguments['DiscussionID'] = $DiscussionID;
		$this->fireAs('DiscussionModel')->fireEvent('BeforeSaveDiscussion');

        // Validate the discussion model's form fields
        $DiscussionModel->validate($formPostValues, true);

        // Unset the body validation results (they're not required).
        $DiscussionValidationResults = $DiscussionModel->Validation->results();
        if (array_key_exists('Body', $DiscussionValidationResults)) {
            unset($DiscussionValidationResults['Body']);
        }

        // And add the results to this validation object so they bubble up to the form.
        $this->Validation->addValidationResult($DiscussionValidationResults);

        // Are there enough non-empty poll options?
        $PollOptions = val('PollOption', $formPostValues);
        $ValidPollOptions = array();
        if (is_array($PollOptions))
            foreach ($PollOptions as $PollOption) {
                $PollOption = trim(Gdn_Format::plainText($PollOption));
                if ($PollOption != '') {
                    $ValidPollOptions[] = $PollOption;
                }
            }

        $CountValidOptions = count($ValidPollOptions);
        if ($CountValidOptions < 2) {
            $this->Validation->addValidationResult('PollOption', 'You must provide at least 2 valid poll options.');
        }
        if ($CountValidOptions > 10) {
            $this->Validation->addValidationResult('PollOption', 'You can not specify more than 10 poll options.');
        }
        $DiscussionModel->EventArguments['PollOptions'] = $ValidPollOptions;

        // If all validation passed, create the discussion with discmodel, and then insert all of the poll data.
        if (count($this->Validation->results()) == 0) {
            $DiscussionID = $DiscussionModel->save($formPostValues);
            if ($DiscussionID > 0) {
                $Discussion = $DiscussionModel->getID($DiscussionID);
                // Save the poll record.
                $Poll = [
                    'Name' => $Discussion->Name,
                    'Anonymous' => val('Anonymous', $formPostValues),
                    'DiscussionID' => $DiscussionID,
                    'CountOptions' => $CountValidOptions,
                    'CountVotes' => 0
                ];
                $Poll = $this->coerceData($Poll);
                $PollID = $this->insert($Poll);

                // Save the poll options.
                $PollOptionModel = new Gdn_Model('PollOption');
                $i = 0;
                foreach ($ValidPollOptions as $Option) {
                    $i++;
                    $PollOption = [
                        'PollID' => $PollID,
                        'Body' => $Option,
                        'Format' => 'Text',
                        'Sort' => $i
                    ];
                    $PollOptionModel->save($PollOption);
                }
                // Update the discussion attributes with info
            }
        }

        return $DiscussionID;
    }

    /**
     *
     *
     * @param $PollOptionID
     * @return bool
     * @throws Exception
     */
    public function vote($PollOptionID) {
        // Get objects from the database.
        $Session = Gdn::session();
        $PollOptionModel = new Gdn_Model('PollOption');
        $PollOption = $PollOptionModel->getID($PollOptionID);

        // If this is a valid poll option and user session, record the vote.
        if ($PollOption && $Session->isValid()) {
            // Has this user voted on this poll before?
            $HasVoted = ($this->SQL
                ->select()
                ->from('PollVote')
                ->where(array('UserID' => $Session->UserID, 'PollOptionID' => $PollOptionID))
                ->get()->numRows() > 0);
            if (!$HasVoted) {
                // Insert the vote
                $PollVoteModel = new Gdn_Model('PollVote');
                $PollVoteModel->insert(['UserID' => $Session->UserID, 'PollOptionID' => $PollOptionID]);

                // Update the vote counts
                $PollOptionModel->update(['CountVotes' => val('CountVotes', $PollOption, 0)+1], ['PollOptionID' => $PollOptionID]);
                $Poll = $this->getID(val('PollID', $PollOption));
                $this->update(['CountVotes' => val('CountVotes', $Poll, 0)+1], ['PollID' => val('PollID', $PollOption)]);

                $this->EventArguments['Poll'] = (array)$Poll;
                $this->EventArguments['PollOption'] = (array)$PollOption;
                $this->fireEvent('Vote');

                return $PollOptionID;
            }
        }
        return false;
    }
}
