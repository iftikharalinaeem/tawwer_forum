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
      $this->FireEvent('AfterLoadPoll');

      $this->setData('PollOptions', $PollOptions);
      $this->SetData('Poll', $Poll);
   }

    /**
     * Get poll options for a given poll.
     *
     * @param $PollID
     * @param null $PollModel
     * @return array|null
     */
   public function getPollOptions($PollID) {
       $PollOptionModel = new Gdn_Model('PollOption');
       return $PollOptionModel->GetWhere(array('PollID' => $PollID), 'Sort', 'asc')->ResultArray();
   }

    /**
     * Add voting info to the poll options array
     *
     * @param $Poll
     * @param $OptionData
     * @param null $PollModel
     * @return array
     */
   public function joinPollVotes($OptionData, $Poll, $PollModel = null) {
      // Load the poll votes
      $Anonymous = val('Anonymous', $Poll) || C('Plugins.Polls.AnonymousPolls');
      if (!$Anonymous) {
         if (!is_a($PollModel, 'PollModel')) {
            $PollModel = new PollModel();
         }
         $VoteData = $this->GetPollVotes($OptionData, $PollModel);
      }

      // Build the result set to deliver to the page
      $PollOptions = array();
      foreach ($OptionData as $Option) {
         if (!$Anonymous) {
            $Votes = val($Option['PollOptionID'], $VoteData, array());
            $Option['Votes'] = $Votes;
         }
         $PollOptions[] = $Option;
      }

      return $PollOptions;
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
