<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Poll Model
 *
 * @package Vanilla
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
    * @param int $DiscussionID
    * @return array 
    */
   public function GetByDiscussionID($DiscussionID) {
      return $this->GetWhere(array('DiscussionID' => $DiscussionID))->FirstRow();
   }
   
   /**
    * Returns an array of UserID => PollVote/PollOption info. Used to display a 
    * users vote on their comment in a discussion.
    * @param int $PollID
    * @param array $UserIDs 
    * @return array array of UserID => PollVote/PollOptions.
    */
   public function GetVotesByUserID($PollID, $UserIDs) {
      $Data = $this->SQL
         ->Select('pv.UserID, po.*')
         ->From('PollVote pv')
         ->Join('PollOption po', 'po.PollOptionID = pv.PollOptionID')
         ->WhereIn('pv.UserID', $UserIDs)
         ->Where('po.PollID', $PollID)
         ->Get();
      
      $Return = array();
      foreach ($Data as $Row) {
         $Return[GetValue('UserID', $Row)] = array(
             'PollOptionID' => GetValue('PollOptionID', $Row),
             'Body' => GetValue('Body', $Row),
             'Format' => GetValue('Format', $Row),
             'Sort' => GetValue('Sort', $Row)
         );
      }
      return $Return;
   }
   
   /**
    * Inserts a new poll returns the discussion id.
    */
   public function Save($FormPostValues) {
      $FormPostValues = $this->FilterForm($FormPostValues);
      $this->AddInsertFields($FormPostValues);
      $this->AddUpdateFields($FormPostValues);
      if (C('Plugins.Polls.AnonymousPolls'))
         $FormPostValues['Anonymous'] = 1;
      $Session = Gdn::Session();
      $FormPostValues['Type'] = 'poll'; // Force the "poll" discussion type.
      $DiscussionID = 0;
      $DiscussionModel = new DiscussionModel();
      
      // Make the discussion body not required while creating a new poll.
      // This saves in memory, but not to the file:
      SaveToConfig('Vanilla.DiscussionBody.Required', FALSE, array('Save' => FALSE)); 
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:    
      
      // New poll? Set default category ID if none is defined.
      if (!ArrayValue('DiscussionID', $FormPostValues, '')) {
         if (!GetValue('CategoryID', $FormPostValues) && !C('Vanilla.Categories.Use')) {
            $FormPostValues['CategoryID'] = GetValue('CategoryID', CategoryModel::DefaultCategory(), -1);
         }
      }
      
      // This should have been done in discussion model: 
      // Validate category permissions.
      $CategoryID = GetValue('CategoryID', $FormPostValues);
      if ($CategoryID > 0) {
         $Category = CategoryModel::Categories($CategoryID);
         if ($Category && !$Session->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', GetValue('PermissionCategoryID', $Category)))
            $this->Validation->AddValidationResult('CategoryID', 'You do not have permission to create polls in this category');
      }
      
      // This should have been done in discussion model:
      // Make sure that the title will not be invisible after rendering
      $Name = trim(GetValue('Name', $FormPostValues, ''));
      if ($Name != '' && Gdn_Format::Text($Name) == '')
         $this->Validation->AddValidationResult('Name', 'You have entered an invalid poll title.');
      else {
         // Trim the name.
         $FormPostValues['Name'] = $Name;
      }
      
      $this->EventArguments['FormPostValues'] = &$FormPostValues;
		$this->EventArguments['DiscussionID'] = $DiscussionID;
		$this->FireAs('DiscussionModel')->FireEvent('BeforeSaveDiscussion');

      // Validate the discussion model's form fields
      $DiscussionModel->Validate($FormPostValues, TRUE);
      
      // Unset the body validation results (they're not required).
      $DiscussionValidationResults = $DiscussionModel->Validation->Results();
      if (array_key_exists('Body', $DiscussionValidationResults))
         unset($DiscussionValidationResults['Body']);
      
      // And add the results to this validation object so they bubble up to the form.
      $this->Validation->AddValidationResult($DiscussionValidationResults); 
      
      // Are there enough non-empty poll options?
      $PollOptions = GetValue('PollOption', $FormPostValues);
      $ValidPollOptions = array();
      if (is_array($PollOptions))
         foreach ($PollOptions as $PollOption) {
            $PollOption = trim(Gdn_Format::PlainText($PollOption));
            if ($PollOption != '')
               $ValidPollOptions[] = $PollOption;
         }
      
      $CountValidOptions = count($ValidPollOptions);
      if ($CountValidOptions < 2)
         $this->Validation->AddValidationResult('PollOption', 'You must provide at least 2 valid poll options.');
      if ($CountValidOptions > 10)
         $this->Validation->AddValidationResult('PollOption', 'You can not specify more than 10 poll options.');
      
      // If all validation passed, create the discussion with discmodel, and then insert all of the poll data.
      if (count($this->Validation->Results()) == 0) {
         $DiscussionID = $DiscussionModel->Save($FormPostValues);
         if ($DiscussionID > 0) {
            $Discussion = $DiscussionModel->GetID($DiscussionID);
            // Save the poll record.
            $Poll = array(
               'Name' => $Discussion->Name,
               'Anonymous' => GetValue('Anonymous', $FormPostValues),
               'DiscussionID' => $DiscussionID,
               'CountOptions' => $CountValidOptions,
               'CountVotes' => 0
            );
            $PollID = $this->Insert($Poll);
            
            // Save the poll options.
            $PollOptionModel = new Gdn_Model('PollOption');
            $i = 0;
            foreach ($ValidPollOptions as $Option) {
               $i++;
               $PollOption = array(
                  'PollID' => $PollID,
                  'Body' => $Option,
                  'Format' => 'Text',
                  'Sort' => $i
               );
               $PollOptionModel->Save($PollOption);
            }
            
            // Update the discussion attributes with info 
         }
      }
      
      return $DiscussionID;
   }
   
   public function Vote($PollOptionID) {
      // Get objects from the database.
      $Session = Gdn::Session();
      $PollOptionModel = new Gdn_Model('PollOption');
      $PollOption = $PollOptionModel->GetID($PollOptionID);
      
      // If this is a valid poll option and user session, record the vote.
      if ($PollOption && $Session->IsValid()) {
         // Has this user voted on this poll before?
         $HasVoted = $this->SQL->Select()->From('PollVote')->Where(array('UserID' => $Session->UserID, 'PollOptionID' => $PollOptionID))->Get()->NumRows() > 0;
         if (!$HasVoted) {
            // Insert the vote
            $PollVoteModel = new Gdn_Model('PollVote');
            $PollVoteModel->Insert(array('UserID' => $Session->UserID, 'PollOptionID' => $PollOptionID));

            // Update the vote counts
            $PollOptionModel->Update(array('CountVotes' => GetValue('CountVotes', $PollOption, 0)+1), array('PollOptionID' => $PollOptionID));
            $Poll = $this->GetID(GetValue('PollID', $PollOption));
            $this->Update(array('CountVotes' => GetValue('CountVotes', $Poll, 0)+1), array('PollID' => GetValue('PollID', $PollOption)));
            return $PollOptionID;
         }
      }
      return FALSE;
   }
}
