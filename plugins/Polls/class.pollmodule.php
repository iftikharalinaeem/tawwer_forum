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

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
	}

	public function AssetTarget() {
		return 'Panel';
	}

   private function LoadPoll() {
      $PollModel = new PollModel();
      $Poll = FALSE;
      $PollID = Gdn::Controller()->Data('PollID');
      $Discussion = Gdn::Controller()->Data('Discussion');

      // Look in the controller for a PollID
      if ($PollID > 0)
         $Poll = $PollModel->GetID($PollID);

      // Failing that, look for a DiscussionID
      if (!$Poll && $Discussion)
         $Poll = $PollModel->GetByDiscussionID(GetValue('DiscussionID', $Discussion));

      if ($Poll) {
         // Load the poll options
         $PollID = GetValue('PollID', $Poll);
         $OptionData = $this->getPollOptions($PollID);
         $PollOptions = $this->joinPollVotes($OptionData, $Poll, $PollModel);

         // Has this user voted?
         $this->SetData('UserHasVoted', $PollModel->SQL
            ->Select()
            ->From('PollVote pv')
            ->Where(array(
               'pv.UserID' => Gdn::Session()->UserID,
               'pv.PollOptionID' => array_column($OptionData, 'PollOptionID')
            ))
            ->Get()
            ->NumRows() > 0
         );
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
            $votes = val($option['PollOptionID'], $voteData, array());
            $option['Votes'] = $votes;
         }
         $pollOptions[] = $option;
      }

      return $pollOptions;
   }


    public function ToString() {
        $this->LoadPoll();
        $String = '';
		ob_start();
        include(PATH_PLUGINS.'/Polls/views/poll.php');
		$String = ob_get_contents();
		@ob_end_clean();
		return $String;
	}

   /**
    * Gets the users that voted for poll options.
    *
    * @param array $pollOptions The poll option data to query.
    * @return array Returns an array of arrays of users indexed by poll option ID.
    */
   private function GetPollVotes($pollOptions) {
      $optionIDs = array();
      $votes = array();
      $voteThreshold = self::LIMIT_THRESHOLD / count($pollOptions);

      // Go through the options to see which ones have too many votes to get as a group.
      foreach ($pollOptions as $option) {
         if (val('CountVotes', $option, 0) < $voteThreshold) {
            $optionIDs[] = $option['PollOptionID'];
         } else {
            // The option has too many votes so get the users for it separately.
            $ID = $option['PollOptionID'];
            $optionVotes = Gdn::SQL()->GetWhere(
               'PollVote',
               array('PollOptionID' => $ID),
               '', '',
               $this->MaxVoteUsers)->ResultArray();
            // Join the users.
            Gdn::UserModel()->JoinUsers($optionVotes, array('UserID'));
            $votes[$ID] = $optionVotes;
         }
      }

      // Join the rest of the poll option IDs.
      if (!empty($optionIDs)) {
         $otherVoteData = Gdn::SQL()->GetWhere('PollVote',
            array('PollOptionID' => $optionIDs),
            '', '',
            self::LIMIT_THRESHOLD)->ResultArray();
         // Join the users.
         Gdn::UserModel()->JoinUsers($otherVoteData, array('UserID'));

         $otherVoteData = Gdn_DataSet::Index($otherVoteData, 'PollOptionID', array('Unique' => false));
         foreach ($otherVoteData as $ID => $Users) {
            $votes[$ID] = array_slice($Users, 0, $this->MaxVoteUsers);
         }
      }

      return $votes;
   }
}
