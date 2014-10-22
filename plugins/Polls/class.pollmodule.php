<?php if (!defined('APPLICATION')) exit();
class PollModule extends Gdn_Module {

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
      $PollOptions = array();

      // Look in the controller for a PollID
      if ($PollID > 0)
         $Poll = $PollModel->GetID($PollID);
      
      // Failing that, look for a DiscussionID
      if (!$Poll && $Discussion)
         $Poll = $PollModel->GetByDiscussionID(GetValue('DiscussionID', $Discussion));

      if ($Poll) {
         // Load the poll options
         $PollID = GetValue('PollID', $Poll);
         $PollOptionModel = new Gdn_Model('PollOption');
         $OptionData = $PollOptionModel->GetWhere(array('PollID' => $PollID), 'Sort', 'asc');
         
         // Load the poll votes
         $Anonymous = GetValue('Anonymous', $Poll);
         if (!$Anonymous) {
            $PollOptionIDs = array();
            foreach ($OptionData->ResultArray() as $PollOption) {
               $PollOptionIDs[] = $PollOption['PollOptionID'];
            }
            $VoteData = $PollModel->SQL
               ->Select('pv.*')
               ->From('PollVote pv')
               ->Join('PollOption po', 'pv.PollOptionID = po.PollOptionID')
               ->WhereIn('pv.PollOptionID', $PollOptionIDs)
               ->OrderBy('po.Sort', 'asc')
               ->Get();
            // Join the users.
            Gdn::UserModel()->JoinUsers($VoteData, array('UserID'));
         }

         // Build the resultset to deliver to the page
         foreach ($OptionData->ResultArray() as $Option) {
            if (!$Anonymous) {
               $Votes = array();
               foreach ($VoteData->ResultArray() as $Vote) {
                  if ($Vote['PollOptionID'] == $Option['PollOptionID'])
                     $Votes[] = $Vote;
               }
               $Option['Votes'] = $Votes;
            }
            $PollOptions[] = $Option;
         }

         // Has this user voted?
         $this->SetData('UserHasVoted', $PollModel->SQL
            ->Select()
            ->From('PollVote pv')
            ->Join('PollOption po', 'pv.PollOptionID = po.PollOptionID')
            ->Where(array(
               'pv.UserID' => Gdn::Session()->UserID, 
               'po.PollID' => $PollID
            ))
            ->Get()
            ->NumRows() > 0
         );


      }
      $this->EventArguments['Poll'] =& $Poll;
      $this->EventArguments['PollOptions'] =& $PollOptions;
      $this->FireEvent('AfterLoadPoll');

      $this->SetData('Poll', $Poll);
      $this->SetData('PollOptions', $PollOptions);


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
}
