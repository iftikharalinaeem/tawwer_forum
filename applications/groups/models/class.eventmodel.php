<?php

class EventModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @access public
    */
   public function __construct() {
      parent::__construct('Event');
   }
   
   public function GetByUser($UserID) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');
      
      $Result = $this->GetWhere(array('GroupID' => $IDs), 'Name')->ResultArray();
      return $Result;
   }
   
   public function GetID($ID, $DatasetType = DATASET_TYPE_ARRAY) {
      $ID = self::ParseID($ID);
      
      $Row = parent::GetID($ID, $DatasetType);
      return $Row;
   }
   
   public static function ParseID($ID) {
      $Parts = explode('-', $ID, 2);
      return $Parts[0];
   }
   
   /**
    * Check if a User is invited to an Event
    * 
    * @param integer $UserID
    * @param integer $EventID
    */
   public function IsInvited($UserID, $EventID) {
      $IsMember = $this->SQL->GetCount('UserEvent', array(
         'UserID'    => $UserID,
         'EventID'   => $EventID
      ));
      return $IsMember > 0;
   }
   
}
