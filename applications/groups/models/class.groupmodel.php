<?php

class GroupModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @access public
    */
   public function __construct() {
      parent::__construct('Group');
   }
   
   /**
    * Check permission on a group.
    * 
    * @param string $Permission The permission to check. Valid values are:
    *  - Member: User is a member of the group.
    *  - Leader: User is a leader of the group.
    *  - Join: User can join the group.
    *  - Leave: User can leave the group.
    *  - Edit: The user may edit the group.
    *  - Delete: User can delete the group.
    *  - View: The user may view the group's contents.
    *  - Moderate: The user may moderate the group.
    * @param int $GroupID
    * @return boolean
    */
   public function CheckPermission($Permission, $GroupID) {
      static $Permissions = array();
      
      $UserID = Gdn::Session()->UserID;
      
      if (!isset($Permissions[$UserID])) {
         // Get the data for the group.
         if (is_array($GroupID)) {
            $Group = $GroupID;
            $GroupID = $Group['GroupID'];
         } else
            $Group = $this->GetID($GroupID);
         $UserGroup = Gdn::SQL()->GetWhere('UserGroup', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
         $GroupApplicant = Gdn::SQL()->GetWhere('GroupApplicant', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
         
         
         // Set the default permissions.
         $Perms = array(
            'Member' => FALSE,
            'Leader' => FALSE,
            'Join' => Gdn::Session()->IsValid(),
            'Leave' => FALSE,
            'Edit' => FALSE,
            'Delete' => FALSE,
            'Moderate' => FALSE,
            'View' => TRUE);
         
         // The group creator is always a member and leader.
         if ($UserID == $Group['InsertUserID']) {
            $Perms['Delete'] = TRUE;
            
            if (!$UserGroup)
               $UserGroup = array('Role' => 'Leader');
         }
            
         if ($UserGroup) {
            $Perms['Join'] = FALSE;
            $Perms['Join.Reason'] = T('You are already a member of this group.');
            
            $Perms['Member'] = TRUE;
            $Perms['Leader'] = ($UserGroup['Role'] == 'Leader');
            $Perms['Edit'] = $Perms['Leader'];
            $Perms['Moderate'] = $Perms['Leader'];
            
            if ($UserID != $Group['InsertUserID']) {
               $Perms['Leave'] = TRUE;
            } else {
               $Perms['Leave.Reason'] = T("You can't leave the group you started.");
            }
         } else {
            if ($Group['Visibility'] != 'Public') {
               $Perms['View'] = FALSE;
               $Perms['View.Reason'] = T('Join this group to view its content.');
            }
         }
         
         if ($GroupApplicant) {
            $Perms['Join'] = FALSE; // Already applied or banned.
            switch (strtolower($GroupApplicant['Type'])) {
               case 'application':
                  $Perms['Join.Reason'] = T("You've applied to join this group.");
                  break;
               case 'ban':
                  $Perms['Join.Reason'] = T("You're banned from joining this group.");
                  break;
            }
         }
         
         // Moderators can view and edit all groups.
         if ($UserID == Gdn::Session()->UserID && Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            $Perms['Edit'] = TRUE;
            $Perms['Delete'] = TRUE;
            $Perms['View'] = TRUE;
            unset($Perms['View.Reason']);
            $Perms['Moderate'] = TRUE;
         }
         
         $Permissions[$UserID] = $Perms;
      }
      
      $Perms = $Permissions[$UserID];
      
      if (!$Permission)
         return $Perms;
      
      if (!isset($Perms[$Permission])) {
         if (strpos($Permission, '.Reason') === FALSE) {
            trigger_error("Invalid group permission $Permission.");
            return FALSE;
         } else {
            $Permission = StringEndsWith($Permission, '.Reason', TRUE, TRUE);
            if (in_array($Permission, array('Member', 'Leader'))) {
               $Message = T(sprintf("You aren't a %s of this group.", strtolower($Permission)));
            } else {
               $Message = sprintf(T("You aren't allowed to %s this group."), T(strtolower($Permission)));
            }
            
            return $Message;
         }
      } else {
         return $Perms[$Permission];
      }
   }
   
   public function GetByUser($UserID) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');
      
      $Result = $this->GetWhere(array('GroupID' => $IDs), 'Name')->ResultArray();
      return $Result;
   }
   
   public function GetID($ID, $DatasetType = DATASET_TYPE_ARRAY) {
      static $Cache = array();
      
      $ID = self::ParseID($ID);
      if (isset($Cache[$ID]))
         return $Cache[$ID];
      
      $Row = parent::GetID($ID, $DatasetType);
      $Cache[$ID] = $Row;
      
      return $Row;
   }
   
   public static function ParseID($ID) {
      $Parts = explode('-', $ID, 2);
      return $Parts[0];
   }
   
   /**
    * Check if a User is a member of a Group
    * 
    * @param integer $UserID
    * @param integer $GroupID
    */
   public function IsMember($UserID, $GroupID) {
      $IsMember = $this->SQL->GetCount('UserGroup', array(
         'UserID'    => $UserID,
         'GroupID'   => $GroupID
      ));
      return $IsMember > 0;
   }
   
   public function Join($Data) {
      $Valid = $this->ValidateJoin($Data);
      if (!$Valid) {
         return FALSE;
      }
      
      $Group = $this->GetID(GetValue('GroupID', $Data));
      Trace($Group, 'Group');
      
      switch (strtolower($Group['Registration'])) {
         case 'public':
            // This is a public group, go ahead and add the user.
            TouchValue('Role', $Data, 'Member');
            $Model = new Gdn_Model('UserGroup');
            $Model->Insert($Data);
            $this->Validation = $Model->Validation;
            return count($this->ValidationResults()) == 0;
            
         case 'approval':
            // The user must apply to this group.
            $Data['Type'] = 'Application';
            $Model = new Gdn_Model('GroupApplicant');
            $Model->Insert($Data);
            $this->Validation = $Model->Validation;
            return count($this->ValidationResults()) == 0;
            
         case 'invite':
         default:
            throw new Gdn_UserException("Registration type {$Group['Registration']} not supported.");
            // TODO: The user must be invited.
            return FALSE;
      }
   }
   
   /**
    * Approve a membership application.
    * 
    * @param type $Data
    */
   public function JoinApprove($Data) {
      
   }
   
   public function Leave($Data) {
      $this->SQL->Delete('UserGroup', array(
         'UserID' => GetValue('UserID', $Data),
         'GroupID' => GetValue('GroupID', $Data)));
   }
   
   protected function ValidateRule($FieldName, $Data, $Rule, $CustomError = FALSE) {
      $Value = GetValue($FieldName, $Data);
      $Valid = $this->Validation->ValidateRule($Value, $FieldName, $Rule, $CustomError);
      if ($Valid !== TRUE)
         $this->Validation->AddValidationResult($FieldName, $Valid.$Value);
   }
   
   public function ValidateJoin($Data) {
      $this->ValidateRule('UserID', $Data, 'ValidateRequired');
      $this->ValidateRule('GroupID', $Data, 'ValidateRequired');
      
      $GroupID = GetValue('GroupID', $Data);
      if ($GroupID) {
         $Group = $this->GetID($GroupID);
         
         switch (strtolower($Group['Registration'])) {
            case 'approval':
               $this->ValidateRule('Reason', $Data, 'ValidateRequired', 'Why do you want to join?');
         }
      }
      
      // First validate the basic field requirements.
      $Valid = $this->Validation->Validate($Data);
      return $Valid;
   }
}
