<?php

/**
 * Groups Application - Event Module
 * 
 * Shows a small events list based on the provided Group or User context.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 * @since 1.0
 */

class EventModule extends Gdn_Module {
   
   protected $Filter = NULL;
   protected $FilterBy = NULL;
   protected $Type = NULL;
   protected $Button = NULL;
   
   public function __construct($Type = NULL, $FilterBy = NULL, $Filter = NULL, $Button = NULL) {
      parent::__construct();
      $this->_ApplicationFolder = 'groups';
      
      if (!is_null($Type))
         $this->Type = $Type;
      
      if (!is_null($FilterBy))
         $this->FilterBy = $FilterBy;
      
      if (!is_null($Filter))
         $this->Filter = $Filter;
      
      if (!is_null($Button))
         $this->Button = $Button;
   }
      
   public function __set($Name, $Value) {
      $Name = strtolower($Name);
      switch ($Name) {
         case 'groupid':
            $this->Filter = $Value;
            $this->FilterBy = 'group';
            break;
         
         case 'userid':
            $this->Filter = $Value;
            $this->FilterBy = 'user';
            break;
         
         case 'type':
            $this->Type = $Value;
            break;
         
         case 'button':
            $this->Button = $Value;
            break;
      }
      
      return $this;
   }
   
   public function GetData() {
      
      // Only callable if configured
      if (!$this->Type) return;
      
      // Callable multiple times
      if (!is_null($this->Data('Events', NULL))) return;
      
      $EventCriteria = array();
      switch ($this->FilterBy) {
         case 'group':
            $GroupModel = new GroupModel();
            $Group = $GroupModel->GetID($this->Filter, DATASET_TYPE_ARRAY);
            $this->SetData('Group', $Group);
            $EventCriteria['GroupID'] = $Group['GroupID'];
            break;
         
         case 'user':
            $User = Gdn::UserModel()->GetID($this->Filter, DATASET_TYPE_ARRAY);
            $this->SetData('User', $User);
            $EventCriteria['Invited'] = $User['UserID'];
            break;
      }
      
      switch ($this->Type) {
         case 'upcoming':
            $FilterDate = C('Groups.Events.UpcomingRange', '+30 days');
            $Ended = FALSE;
            $this->SetData('Title', T('Upcoming Events'));
            break;
         
         case 'recent':
            $FilterDate = C('Groups.Events.RecentRange', '-10 days');
            $Ended = TRUE;
            $this->SetData('Title', T('Recent Events'));
            break;
      }
      
      $EventModel = new EventModel();
      $this->SetData('Events', $EventModel->GetUpcoming($FilterDate, $EventCriteria, $Ended));
      
   }
   
   public function ToString() {
      $this->GetData();
      if (!is_null($this->Button))
         $this->SetData('Button', $this->Button);
      return $this->FetchView();
   }
   
}