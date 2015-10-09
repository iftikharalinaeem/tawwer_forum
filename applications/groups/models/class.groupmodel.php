<?php

class GroupModel extends Gdn_Model {

   /**
    * @var int The maximum number of groups a regular user is allowed to create.
    */
   public $MaxUserGroups = 0;

   /**
    * @var int The number of members per page.
    */
   public $MemberPageSize = 30;


   /**
    * Class constructor. Defines the related database table name.
    *
    * @access public
    */
   public function __construct() {
      parent::__construct('Group');
      $this->FireEvent('Init');
   }

   /**
    * Calculate the rows in a groups dataset.
    * @param Gdn_DataSet $Result
    */
   public function Calc(&$Result) {
      foreach ($Result as &$Row) {
         $Row['Url'] = GroupUrl($Row, NULL, '//');
         $Row['DescriptionHtml'] = Gdn_Format::To($Row['Description'], $Row['Format']);

         if ($Row['Icon']) {
            $Row['IconUrl'] = Gdn_Upload::Url($Row['Icon']);
         }
         if ($Row['Banner']) {
            $Row['BannerUrl'] = Gdn_Upload::Url($Row['Banner']);
         }
      }
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
            if ($Group['Privacy'] != 'Public') {
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
               case 'invitation':
                  $Perms['Join'] = TRUE;
                  unset($Perms['Join.Reason']);
                  break;
            }
         }

         // Moderators can view and edit all groups.
         if ($UserID == Gdn::Session()->UserID && Gdn::Session()->CheckPermission('Groups.Moderation.Manage')) {
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
         case 'CountDiscussions':
            $this->Database->Query(DBAModel::GetCountSQL('count', 'Group', 'Discussion', $Column, 'GroupID'));
            break;
         case 'CountMembers':
            $this->Database->Query(DBAModel::GetCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID'));
            break;
         case 'DateLastComment':
            $this->Database->Query(DBAModel::GetCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment'));
            break;
         case 'LastDiscussionID':
            $this->SQL->Update('Group g')
               ->Join('Discussion d', 'd.DateLastComment = g.DateLastComment and g.GroupID = d.GroupID')
               ->Set('g.LastDiscussionID', 'd.DiscussionID', FALSE, FALSE)
               ->Set('g.LastCommentID', 'd.LastCommentID', FALSE, FALSE)
               ->Put();
            break;
         default:
            throw new Gdn_UserException("Unknown column $Column");
      }
      return $Result;
   }

   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $PageNumber = FALSE) {
      $Result = parent::Get($OrderFields, $OrderDirection, $Limit, $PageNumber);
      $Result->DatasetType(DATASET_TYPE_ARRAY);
      $this->Calc($Result->Result());
      return $Result;
   }

   public function GetByUser($UserID, $OrderFields = '', $OrderDirection = 'desc', $Limit = 9, $Offset = false) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');

      $Result = $this->GetWhere(array('GroupID' => $IDs), $OrderFields, $OrderDirection, $Limit, $Offset)->ResultArray();
      $this->Calc($Result);
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

    public function GetApplicantIds($GroupID, $Where = array(), $Limit = FALSE, $Offset = FALSE) {
        // First grab the members.
        $users = $this->SQL
          ->From('GroupApplicant')
          ->Where('GroupID', $GroupID)
          ->Where($Where)
          ->OrderBy('DateInserted')
          ->Limit($Limit, $Offset)
          ->Get()->ResultArray();

        $ids = array();
        foreach ($users as $user) {
            $ids[] = val('UserID', $user);
        }
        return $ids;    }

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

    public function GetMemberIds($GroupID, $Where = array(), $Limit = FALSE, $Offset = FALSE) {
        // First grab the members.
        $users = $this->SQL
          ->From('UserGroup')
          ->Where('GroupID', $GroupID)
          ->Where($Where)
          ->OrderBy('DateInserted')
          ->Limit($Limit, $Offset)
          ->Get()->ResultArray();

        $ids = array();
        foreach ($users as $user) {
            $ids[] = val('UserID', $user);
        }
        return $ids;
    }

   public function GetUserCount($UserID) {
      $Count = $this->SQL
         ->Select('InsertUserID', 'count', 'CountGroups')
         ->From('Group')
         ->Where('InsertUserID', $UserID)
         ->Get()->Value('CountGroups');
      return $Count;
   }

   public static function ParseID($ID) {
      $Parts = explode('-', $ID, 2);
      return $Parts[0];
   }

   public function IncrementDiscussionCount($GroupID, $Inc, $DiscussionID = 0, $DateLastComment = '') {
      $Group = $this->GetID($GroupID);
      $Set = array();

      if ($DiscussionID) {
         $Set['LastDiscussionID'] = $DiscussionID;
         $Set['LastCommentID'] = null;
      }
      if ($DateLastComment) {
         $Set['DateLastComment'] = $DateLastComment;
      }

      if (val('CountDiscussions', $Group) < 100) {
         $countDiscussions = $this->SQL->Select('DiscussionID', 'count', 'CountDiscussions')
            ->From('Discussion')
            ->Where('GroupID', $GroupID)
            ->Get()->Value('CountDiscussions', 0);

         $Set['CountDiscussions'] = $countDiscussions;
         $this->SetField($GroupID, $Set);
         return;
      }
      $SQLInc = sprintf('%+d', $Inc);
      $this->SQL
         ->Update('Group')
         ->Set('CountDiscussions', "CountDiscussions " . $SQLInc, false, false)
         ->Where('GroupID', $GroupID);
      if (!empty($Set)) {
         $this->SQL->Set($Set);
      }
      $this->SQL->Put();
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

   public function Invite($Data) {
      $Valid = $this->ValidateJoin($Data);
      if (!$Valid) {
         return FALSE;
      }

      $Group = $this->GetID(GetValue('GroupID', $Data));
      Trace($Group, 'Group');

      $UserIDs = (array)$Data['UserID'];
      $ValidUserIDs = array();

      foreach ($UserIDs as $UserID) {
         // Make sure the user hasn't already been invited.
         $Application = $this->SQL->GetWhere('GroupApplicant', array(
            'GroupID' => $Group['GroupID'],
            'UserID' => $UserID
         ))->FirstRow(DATASET_TYPE_ARRAY);

         if ($Application) {
            if ($Application['Type'] == 'Invitation') {
               continue;
            } else {
               $this->SQL->Put('GroupApplicant',
                  array('Type' => 'Invitation'),
                  array(
                     'GroupID' => $Group['GroupID'],
                     'UserID' => $UserID
                  ));
            }
         } else {
            $Data['Type'] = 'Invitation';
            $Data['UserID'] = $UserID;
            $Model = new Gdn_Model('GroupApplicant');
            $Model->Options('Ignore', TRUE)->Insert($Data);
            $this->Validation = $Model->Validation;
         }
         $ValidUserIDs[] = $UserID;
      }

      // If Conversations are disabled; Improve notification with a link to group.
      if (!class_exists('ConversationModel') && count($ValidUserIDs) > 0) {
         foreach ($ValidUserIDs as $UserID) {
            $Activity = array(
               'ActivityType' => 'Group',
               'ActivityUserID' => GDN::Session()->UserID,
               'HeadlineFormat' => T('HeadlineFormat.GroupInvite', 'Please join my <a href="{Url,html}">group</a>.'),
               'RecordType' => 'Group',
               'RecordID' => $Group['GroupID'],
               'Route' => GroupUrl($Group, false, '/'),
               'Story' => FormatString(T("You've been invited to join {Name}."), array('Name' => htmlspecialchars($Group['Name']))),
               'NotifyUserID' => $UserID,
               'Data' => array(
                  'Name' => $Group['Name']
               )
            );
            $ActivityModel = new ActivityModel();
            $ActivityModel->Save($Activity, 'Groups');

         }

      }

      // Send a message for the invite.
      if (class_exists('ConversationModel') && count($ValidUserIDs) > 0) {
         $Model = new ConversationModel();
         $MessageModel = new ConversationMessageModel();

         $Args = array(
            'Name' => htmlspecialchars($Group['Name']),
            'Url' => GroupUrl($Group, '/')
         );

         $Row = array(
            'Subject' => FormatString(T("Please join my group."), $Args),
            'Body' => FormatString(T("You've been invited to join {Name}."), $Args),
            'Format' => 'Html',
            'RecipientUserID' => $ValidUserIDs,
            'Type' => 'ginvite',
            'RegardingID' => $Group['GroupID'],
         );
         if (!$Model->Save($Row, $MessageModel)) {
            throw new Gdn_UserException($Model->Validation->ResultsText());
         }
      }

      return count($this->ValidationResults()) == 0;
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

   public function JoinInvite($GroupID, $UserID, $Accept = true) {
      // Grab the application.
      $Row = $this->SQL->GetWhere('GroupApplicant', array('GroupID' => $GroupID, 'UserID' => $UserID))->FirstRow(DATASET_TYPE_ARRAY);
      if (!$Row || $Row['Type'] != 'Invitation') {
         throw NotFoundException('Invitation');
      }

      $Data = array(
         'GroupApplicantID' => $Row['GroupApplicantID'],
         'Type' => $Accept ? 'Approved' : 'Denied'
      );
      return $this->JoinApprove($Data);
   }

   /**
    * Approve a membership application.
    *
    * @param array $Data
    * @return bool
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

         if ($Row['Type'] == 'Invitation') {
            $Model->Delete(array('GroupApplicantID' => $ID));
            $Saved = TRUE;
         } else {
            $Saved = $Model->Save(array(
               'GroupApplicantID' => $ID,
               'Type' => $Value
               ));
         }

         return $Saved;
      }
   }

   /**
    * Join the recent discussions/comments to a given set of groups.
    *
    * @param array $Data The groups to join to.
    */
   public function JoinRecentPosts(&$Data, $JoinUsers = true) {
      $DiscussionIDs = array();
      $CommentIDs = array();

      foreach ($Data as &$Row) {
         if (isset($Row['LastTitle']) && $Row['LastTitle'])
            continue;

         if ($Row['LastDiscussionID'])
            $DiscussionIDs[] = $Row['LastDiscussionID'];

         if ($Row['LastCommentID']) {
            $CommentIDs[] = $Row['LastCommentID'];
         }
      }

      // Create a fresh copy of the Sql object so as not to pollute.
      $Sql = clone Gdn::SQL();
      $Sql->Reset();

      // Grab the discussions.
      if (count($DiscussionIDs) > 0) {
         $Discussions = $Sql->WhereIn('DiscussionID', $DiscussionIDs)->Get('Discussion')->ResultArray();
         $Discussions = Gdn_DataSet::Index($Discussions, array('DiscussionID'));
      }

      if (count($CommentIDs) > 0) {
         $Comments = $Sql->WhereIn('CommentID', $CommentIDs)->Get('Comment')->ResultArray();
         $Comments = Gdn_DataSet::Index($Comments, array('CommentID'));
      }

      foreach ($Data as &$Row) {
         $Discussion = GetValue($Row['LastDiscussionID'], $Discussions);
         if ($Discussion) {
            $Row['LastTitle'] = Gdn_Format::Text($Discussion['Name']);
            $Row['LastDiscussionUserID'] = $Discussion['InsertUserID'];
            $Row['LastDateInserted'] = $Discussion['DateInserted'];
            $Row['LastUrl'] = DiscussionUrl($Discussion, FALSE, '/').'#latest';
         }
         $Comment = GetValue($Row['LastCommentID'], $Comments);
         if ($Comment) {
            $Row['LastCommentUserID'] = $Comment['InsertUserID'];
            $Row['LastDateInserted'] = $Comment['DateInserted'];
         } else {
            $Row['NoComment'] = TRUE;
         }

         TouchValue('LastTitle', $Row, '');
         TouchValue('LastDiscussionUserID', $Row, NULL);
         TouchValue('LastCommentUserID', $Row, NULL);
         TouchValue('LastDateInserted', $Row, NULL);
         TouchValue('LastUrl', $Row, NULL);
      }

      // Now join the users.
      if ($JoinUsers) {
         Gdn::UserModel()->JoinUsers($Data, array('LastCommentUserID', 'LastDiscussionUserID'));
      }
   }

   public function Leave($Data) {
      $this->SQL->Delete('UserGroup', array(
         'UserID' => GetValue('UserID', $Data),
         'GroupID' => GetValue('GroupID', $Data)));

      $this->UpdateCount($Data['GroupID'], 'CountMembers');
   }

   public function OverridePermissions($Group) {
      $CategoryID = GetValue('CategoryID', $Group);
      if (!$CategoryID) {
         return;
      }
      $Category = CategoryModel::Categories($CategoryID);
      if (!$Category) {
         return;
      }

//      print_r($Category);
      $CategoryID = GetValue('PermissionCategoryID', $Category);

      if ($this->CheckPermission('Moderate', $Group)) {
         Gdn::Session()->SetPermission('Vanilla.Discussions.Announce', array($CategoryID));
         Gdn::Session()->SetPermission('Vanilla.Discussions.Close', array($CategoryID));
         Gdn::Session()->SetPermission('Vanilla.Discussions.Edit', array($CategoryID));
         Gdn::Session()->SetPermission('Vanilla.Discussions.Delete', array($CategoryID));
      }

      if ($this->CheckPermission('View', $Group)) {
         Gdn::Session()->SetPermission('Vanilla.Discussions.View', array($CategoryID));
         CategoryModel::SetLocalField($CategoryID, 'PermsDiscussionsView', TRUE);
      }

      if ($this->CheckPermission('Member', $Group)) {
         Gdn::Session()->SetPermission('Vanilla.Discussions.Add', array($CategoryID));
         Gdn::Session()->SetPermission('Vanilla.Comments.Add', array($CategoryID));
      }

//      Trace(Gdn::Session()->CheckPermission('Vanilla.Discussions.View', TRUE, 'Category', $CategoryID));
//      Trace(DiscussionModel::CategoryPermissions());
   }

   public function Save($Data, $Settings = FALSE) {
      $this->EventArguments['Fields'] =& $Data;
      $this->FireEvent('BeforeSave');

      if ($this->MaxUserGroups && !GetValue('GroupID', $Data)) {
         $CountUserGroups = $this->GetUserCount(Gdn::Session()->UserID);
         if ($CountUserGroups >= $this->MaxUserGroups) {
            $this->Validation->AddValidationResult('Count', "You've already created the maximum number of groups.");
            return FALSE;
         }
      }

      // Set the visibility and registration based on the privacy.
      switch (strtolower(GetValue('Privacy', $Data))) {
         case 'private':
            $Data['Visibility'] = 'Members';
            $Data['Registration'] = 'Approval';
            break;
         case 'public':
            $Data['Visibility'] = 'Public';
            $Data['Registration'] = 'Public';
            break;
      }

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
         case 'CountDiscussions':
            $Sql = DBAModel::GetCountSQL('count', 'Group', 'Discussion', $Column, 'GroupID');
            break;
         case 'CountMembers':
            $Sql = DBAModel::GetCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID');
            break;
         case 'DateLastComment':
            $Sql = DBAModel::GetCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment');
            break;
         default:
            throw new Gdn_UserException("Unknown column $Column");
      }
      $Sql .= " where p.GroupID = ".$this->Database->Connection()->quote($GroupID);
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

         switch (strtolower($Group['Privacy'])) {
            case 'private':
               if (!$this->CheckPermission('Leader', $Group)) {
                  $this->ValidateRule('Reason', $Data, 'ValidateRequired', 'Why do you want to join?');
               }
               break;
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
