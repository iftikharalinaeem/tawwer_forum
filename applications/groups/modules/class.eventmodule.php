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
   
   protected $Filter;
   protected $FilterBy;
   
   protected $Events = NULL;
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'groups';
   }
      
   public function __set($Name, $Value) {
      switch ($Name) {
         case 'GroupID':
            $this->Filter = $Value;
            $this->FilterBy = 'group';
            break;
         
         case 'UserID':
            $this->Filter = $Value;
            $this->FilterBy = 'user';
            break;
      }
   }
   
   public function GetData() {
      
      // Callable multiple times
      if (!is_null($this->Events)) return;
      
      switch ($this->FilterBy) {
         case 'group':
            $GroupModel = new GroupModel();
            $Group = $GroupModel->GetID($this->Filter, DATASET_TYPE_ARRAY);
            break;
         
         case 'user':
            $User = Gdn::UserModel()->GetID($this->Filter, DATASET_TYPE_ARRAY);
            break;
      }
      
      $this->Events = array();
      
      $Query = array();
      $EventModule = new EventModel();
      $Events = $EventModule->GetWhere($Query);
      
      $this->Events = $Events;
   }
   
   public function ToString() {
      $this->GetData();
   }
   
}