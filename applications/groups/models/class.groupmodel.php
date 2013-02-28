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
      
      if (is_array($GroupID)) {
         $Group = $GroupID;
         $GroupID = $Group['GroupID'];
      }

      $Key = "$UserID-$GroupID";
      
      if (!isset($Permissions[$Key])) {
         // Get the data for the group.
         if (!isset($Group))
            $Group = $this->GetID($GroupID);
         
         if ($UserID) {
            $UserGroup = Gdn::SQL()->GetWhere('UserGroup', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
            $GroupApplicant = Gdn::SQL()->GetWhere('GroupApplicant', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
         } else {
            $UserGroup = FALSE;
            $GroupApplicant = FALSE;
         }
         
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
               case 'denied':
                  $Perms['Join.Reason'] = T("You're application for this group was denied.");
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
         
         $Permissions[$Key] = $Perms;
      }
      
      $Perms = $Permissions[$Key];
      
      if (!$Permission)
         return $Perms;
      
      if (!isset($Perms[$Permission])) {
         if (strpos($Permission, '.Reason') === FALSE) {
            trigger_error("Invalid group permission $Permission.");
            return FALSE;
         } else {
            $Permission = StringEndsWith($Permission, '.Reason', TRUE, TRUE);
            if ($Perms[$Permission])
               return '';
            
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
   
   public function Counts($Column, $From = FALSE, $To = FALSE, $Max = FALSE) {
      $Result = array('Complete' => TRUE);
      switch ($Column) {
         case 'CountMembers':
            $this->Database->Query(DBAModel::GetCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID'));
            break;
         case 'DateLastComment':
            $this->Database->Query(DBAModel::GetCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment'));
            break;
         default:
            throw new Gdn_UserException("Unknown column $Column");
      }
      return $Result;
   }
   
   public function GetByUser($UserID) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');
      
      $Result = $this->GetWhere(array('GroupID' => $IDs), 'Name')->ResultArray();
      return $Result;
   }
   
   public function GetCount($Wheres = '') {
      if ($Wheres)
         return parent::GetCount($Wheres);
      
      $Key = 'Group.Count';
      
      if ($Wheres === NULL) {
         Gdn::Cache()->Remove($Key);
         return NULL;
      }
         
      $Count = Gdn::Cache()->Get($Key);
      if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
         $Count = parent::GetCount();
         Gdn::Cache()->Store($Key, $Count);
      }
      
      return $Count;
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
   
   public function GetApplicants($GroupID, $Where = array(), $Limit = FALSE, $Offset = FALSE) {
      // First grab the members.
      $Users = $this->SQL
         ->From('GroupApplicant')
         ->Where('GroupID', $GroupID)
         ->Where($Where)
         ->OrderBy('DateInserted')
         ->Limit($Limit, $Offset)
         ->Get()->ResultArray();
      
      Gdn::UserModel()->JoinUsers($Users, array('UserID'));
      return $Users;
   }
   
   public function GetMembers($GroupID, $Where = array(), $Limit = FALSE, $Offset = FALSE) {
      // First grab the members.
      $Users = $this->SQL
         ->From('UserGroup')
         ->Where('GroupID', $GroupID)
         ->Where($Where)
         ->OrderBy('DateInserted')
         ->Limit($Limit, $Offset)
         ->Get()->ResultArray();
      
      Gdn::UserModel()->JoinUsers($Users, array('UserID'));
      return $Users;
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
            $this->UpdateCount($Group['GroupID'], 'CountMembers');
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
    * @param array $Data
    */
   public function JoinApprove($Data) {
      // Grab the applicant row.
      $ID = $Data['GroupApplicantID'];
      $Row = $this->SQL->GetWhere('GroupApplicant', array('GroupApplicantID' => $ID))->FirstRow(DATASET_TYPE_ARRAY);
      if (!$Row)
         throw NotFoundException('Applicant');
      
      $Value = GetValue('Type', $Data);
      if (!in_array($Value, array('Approved', 'Denied')))
         throw new Gdn_UserException(T('Type must be either approved or denied.'));
      
      if ($Value == 'Approved') {
         // Add the user to the group.
         $Model = new Gdn_Model('UserGroup');
         $Inserted = $Model->Insert(array(
            'UserID' => $Row['UserID'],
            'GroupID' => $Row['GroupID'],
            'Role' => GetValue('Role', $Data, 'Member')
            ));
         $this->Validation = $Model->Validation;
         
         if ($Inserted) {
            $this->UpdateCount($Row['GroupID'], 'CountMembers');
            $this->SQL->Delete('GroupApplicant', array('GroupApplicantID' => $ID));
            
            // TODO: Notify the user.
         }
         
         return $Inserted;
      } else {
         $Model = new Gdn_Model('GroupApplicant');
         $Saved = $Model->Save(array(
            'GroupApplicantID' => $ID,
            'Type' => $Value
            ));
         
         return $Saved;
      }
   }
   
   public function Leave($Data) {
      $this->SQL->Delete('UserGroup', array(
         'UserID' => GetValue('UserID', $Data),
         'GroupID' => GetValue('GroupID', $Data)));
      
      $this->UpdateCount($Data['GroupID'], 'CountMembers');
   }
   
   public function OverridePermissions($Group) {
      if ($this->CheckPermission('Moderate', $Group)) {
         $CategoryID = GetValue('CategoryID', $Group);
         $Category = CategoryModel::Categories($CategoryID);
         if ($Category) {
            $CategoryID = $Category['PermissionCategoryID'];
            Gdn::Session()->SetPermission('Vanilla.Discussions.Announce', array($CategoryID));
            Gdn::Session()->SetPermission('Vanilla.Discussions.Close', array($CategoryID));
            Gdn::Session()->SetPermission('Vanilla.Discussions.Edit', array($CategoryID));
            Gdn::Session()->SetPermission('Vanilla.Discussions.Delete', array($CategoryID));
         }
      }
   }
   
   public function Save($Data, $Settings = FALSE) {
      $GroupID = parent::Save($Data, $Settings);
      
      if ($GroupID) {
         // Make sure the group owner is a member.
         $Group = $this->GetID($GroupID);
         $InsertUserID = $Group['InsertUserID'];
         $Row = $this->SQL->GetWhere('UserGroup', array('GroupID' => $GroupID, 'UserID' => $InsertUserID))->FirstRow(DATASET_TYPE_ARRAY);
         if (!$Row) {
            $Row = array(
               'GroupID' => $GroupID,
               'UserID' => $InsertUserID,
               'Role' => 'Leader');
            $Model = new Gdn_Model('UserGroup');
            $Model->Insert($Row);
            $this->Validation = $Model->Validation;
         }
         $this->UpdateCount($GroupID, 'CountMembers');
         $this->GetCount(NULL); // clear cache.
      }
      return $GroupID;
   }
   
   public function SetRole($GroupID, $UserID, $Role) {
      $this->SQL->Put('UserGroup', array(
            'Role' => $Role
         ), array(
            'UserID' => $UserID,
            'GroupID' => $GroupID
         ));
   }
   
   public function RemoveMember($GroupID, $UserID, $Type = FALSE) {
      // Remove the member.
      $this->SQL->Delete('UserGroup', array('GroupID' => $GroupID, 'UserID' => $UserID));
      
      // If the user was banned then let's add the ban.
      if (in_array($Type, array('Banned', 'Denied'))) {
         $Model = new Gdn_Model('GroupApplicant');
         $Model->Delete(array('GroupID' => $GroupID, 'UserID' => $UserID));
         $Model->Insert(array(
            'GroupID' => $GroupID,
            'UserID' => $UserID,
            'Type' => $Type
         ));
      }
   }
   
   public function UpdateCount($GroupID, $Column) {
      switch ($Column) {
         case 'CountMembers':
            $Sql = DBAModel::GetCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID');
            break;
         case 'DateLastComment':
            $Sql = DBAModel::GetCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment');
            break;
         default:
            throw new Gdn_UserException("Unknown column $Column");
      }
      $Sql .= " where p.GroupID = $GroupID";
      $this->Database->Query($Sql);
   }
   
   public function Validate($FormPostValues, $Insert = FALSE) {
      $Valid = parent::Validate($FormPostValues, $Insert);
      
      // Check to see if there is another group with the same name.
      if (trim(GetValue('Name', $FormPostValues))) {
         $Rows = $this->SQL->GetWhere('Group', array('Name' => $FormPostValues['Name']))->ResultArray();
         
         $GroupID = GetValue('GroupID', $FormPostValues);
         foreach ($Rows as $Row) {
            if (!$GroupID || $GroupID != $Row['GroupID']) {
               $Valid = FALSE;
               $this->Validation->AddValidationResult('Name', '@'.sprintf(T("There's already a %s with the name %s."), T('group'), htmlspecialchars($FormPostValues['Name'])));
            }
         }
      }
      return $Valid;
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
   
   /**
    * Delete a group
    * 
    * @param array $Where
    * @param integer $Limit
    * @param boolean $ResetData
    * @return Gdn_DataSet
    */
   public function Delete($Where = '', $Limit = FALSE, $ResetData = FALSE) {
      // Get list of matching groups
      $MatchGroups = $this->GetWhere($Where,'','',$Limit);
      
      // Delete groups
      $Deleted = parent::Delete($Where, $Limit, $ResetData);
      
      // Clean up UserGroups
      $GroupIDs = array();
      foreach ($MatchGroups as $Event)
         $GroupIDs[] = GetValue('GroupID', $Event);
      $this->SQL->Delete('UserGroup', array(
         'GroupID'   => $GroupIDs
      ));
      $this->SQL->Delete('GroupApplicant', array(
         'GroupID'   => $GroupIDs
      ));
      
      return $Deleted;
   }
   
}
