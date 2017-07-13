<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupModel
 */
class GroupModel extends Gdn_Model {

    /** @var int The maximum number of groups a regular user is allowed to create. */
    public $MaxUserGroups = 0;

    /** @var int The number of members per page. */
    public $MemberPageSize = 30;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('Group');
        $this->fireEvent('Init');
    }

    /**
     * Calculate the rows in a groups dataset.
     *
     * @param Gdn_DataSet $Result
     */
    public function calc(&$Result) {
        foreach ($Result as &$Row) {
            $Row['Url'] = GroupUrl($Row, null, '//');
            $Row['DescriptionHtml'] = Gdn_Format::to($Row['Description'], $Row['Format']);

            if ($Row['Icon']) {
                $Row['IconUrl'] = Gdn_Upload::url($Row['Icon']);
            }
            if ($Row['Banner']) {
                $Row['BannerUrl'] = Gdn_Upload::url($Row['Banner']);
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
    public function checkPermission($Permission, $GroupID) {
        static $Permissions = [];

        $UserID = Gdn::session()->UserID;

        if (is_array($GroupID)) {
            $Group = $GroupID;
            $GroupID = $Group['GroupID'];
        }

        $Key = "$UserID-$GroupID";

        if (!isset($Permissions[$Key])) {
            // Get the data for the group.
            if (!isset($Group)) {
                $Group = $this->getID($GroupID);
            }

            if ($UserID) {
                $UserGroup = Gdn::sql()->getWhere('UserGroup', ['GroupID' => $GroupID, 'UserID' => Gdn::session()->UserID])->firstRow(DATASET_TYPE_ARRAY);
                $GroupApplicant = Gdn::sql()->getWhere('GroupApplicant', ['GroupID' => $GroupID, 'UserID' => Gdn::session()->UserID])->firstRow(DATASET_TYPE_ARRAY);
            } else {
                $UserGroup = false;
                $GroupApplicant = false;
            }

            // Set the default permissions.
            $Perms = [
                'Member' => false,
                'Leader' => false,
                'Join' => Gdn::session()->isValid(),
                'Leave' => false,
                'Edit' => false,
                'Delete' => false,
                'Moderate' => false,
                'View' => true];

            // The group creator is always a member and leader.
            if ($UserID == $Group['InsertUserID']) {
                $Perms['Delete'] = true;

                if (!$UserGroup) {
                    $UserGroup = ['Role' => 'Leader'];
                }
            }

            if ($UserGroup) {
                $Perms['Join'] = false;
                $Perms['Join.Reason'] = t('You are already a member of this group.');

                $Perms['Member'] = true;
                $Perms['Leader'] = ($UserGroup['Role'] == 'Leader');
                $Perms['Edit'] = $Perms['Leader'];
                $Perms['Moderate'] = $Perms['Leader'];

                if ($UserID != $Group['InsertUserID']) {
                    $Perms['Leave'] = true;
                } else {
                    $Perms['Leave.Reason'] = t("You can't leave the group you started.");
                }
            } else {
                if ($Group['Privacy'] != 'Public') {
                    $Perms['View'] = false;
                    $Perms['View.Reason'] = t('Join this group to view its content.');
                }
            }

            if ($GroupApplicant) {
                $Perms['Join'] = false; // Already applied or banned.
                switch (strtolower($GroupApplicant['Type'])) {
                    case 'application':
                        $Perms['Join.Reason'] = t("You've applied to join this group.");
                        break;
                    case 'denied':
                        $Perms['Join.Reason'] = t("You're application for this group was denied.");
                        break;
                    case 'ban':
                        $Perms['Join.Reason'] = t("You're banned from joining this group.");
                        break;
                    case 'invitation':
                        $Perms['Join'] = true;
                        unset($Perms['Join.Reason']);
                        break;
                }
            }

            // Moderators can view and edit all groups.
            $canManage = Gdn::session()->checkPermission([
                'Garden.Settings.Manage',
                'Garden.Community.Manage',
                'Groups.Moderation.Manage'
            ], false);

            if ($UserID == Gdn::session()->UserID && $canManage) {
                $managerOverrides = [
                    'Delete' => true,
                    'Edit' => true,
                    'Leader' => true,
                    'Moderate' => true,
                    'View' => true,
                ];

                unset($Perms['View.Reason']);
                $Perms = array_merge($Perms, $managerOverrides);
            }

            $Permissions[$Key] = $Perms;
        }

        $Perms = $Permissions[$Key];

        if (!$Permission) {
            return $Perms;
        }

        if (!isset($Perms[$Permission])) {
            if (strpos($Permission, '.Reason') === false) {
                trigger_error("Invalid group permission $Permission.");
                return false;
            } else {
                $Permission = stringEndsWith($Permission, '.Reason', true, true);
                if ($Perms[$Permission]) {
                    return '';
                }

                if (in_array($Permission, ['Member', 'Leader'])) {
                    $Message = t(sprintf("You aren't a %s of this group.", strtolower($Permission)));
                } else {
                    $Message = sprintf(t("You aren't allowed to %s this group."), t(strtolower($Permission)));
                }

                return $Message;
            }
        } else {
            return $Perms[$Permission];
        }
    }

    /**
     *
     *
     * @param $Column
     * @param bool $From
     * @param bool $To
     * @param bool $Max
     * @return array
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function counts($Column, $From = false, $To = false, $Max = false) {
        $Result = ['Complete' => true];
        switch ($Column) {
            case 'CountDiscussions':
                $this->Database->query(DBAModel::getCountSQL('count', 'Group', 'Discussion', $Column, 'GroupID'));
                break;
            case 'CountMembers':
                $this->Database->query(DBAModel::getCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID'));
                break;
            case 'DateLastComment':
                $this->Database->query(DBAModel::getCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment'));
                break;
            case 'LastDiscussionID':
                $this->SQL->update('Group g')
                    ->join('Discussion d', 'd.DateLastComment = g.DateLastComment and g.GroupID = d.GroupID')
                    ->set('g.LastDiscussionID', 'd.DiscussionID', false, false)
                    ->set('g.LastCommentID', 'd.LastCommentID', false, false)
                    ->put();
                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }
        return $Result;
    }

    /**
     *
     *
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $PageNumber
     * @return Gdn_Dataset
     */
    public function get($OrderFields = '', $OrderDirection = 'asc', $Limit = false, $PageNumber = false) {
        $Result = parent::get($OrderFields, $OrderDirection, $Limit, $PageNumber);
        $Result->datasetType(DATASET_TYPE_ARRAY);
        $this->calc($Result->result());
        return $Result;
    }

    /**
     * @param $UserID
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param int $Limit
     * @param bool $Offset
     * @return array
     */
    public function getByUser($UserID, $OrderFields = '', $OrderDirection = 'desc', $Limit = 9, $Offset = false) {
        $UserGroups = $this->SQL->getWhere('UserGroup', ['UserID' => $UserID])->resultArray();
        $IDs = array_column($UserGroups, 'GroupID');

        $Result = $this->getWhere(['GroupID' => $IDs], $OrderFields, $OrderDirection, $Limit, $Offset)->resultArray();
        $this->calc($Result);
        return $Result;
    }

    /**
     *
     *
     * @param string $Wheres
     * @return Gdn_Dataset|mixed|null
     */
    public function getCount($Wheres = '') {
        if ($Wheres) {
            return parent::getCount($Wheres);
        }

        $Key = 'Group.Count';

        if ($Wheres === null) {
            Gdn::cache()->remove($Key);
            return null;
        }

        $Count = Gdn::cache()->get($Key);
        if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
            $Count = parent::getCount();
            Gdn::cache()->store($Key, $Count);
        }

        return $Count;
    }

    /**
     *
     *
     * @param int|string $ID The ID or slug of the group.
     * @param bool|string $DatasetType The type of return.
     * @param array $options Base class compatibility.
     * @return array|mixed|object
     */
    public function getID($ID, $DatasetType = DATASET_TYPE_ARRAY, $options = []) {
        static $Cache = [];

        $ID = self::parseID($ID);
        if (isset($Cache[$ID])) {
            return $Cache[$ID];
        }

        $Row = parent::getID($ID, $DatasetType);
        $Cache[$ID] = $Row;

        return $Row;
    }

    /**
     *
     *
     * @param $groupID
     * @return bool|mixed|string
     */
    public function getApplicantType($groupID) {
        $applicants = $this->getApplicants($groupID);
        foreach ($applicants as $applicant) {
            if (val('UserID', $applicant) == Gdn::session()->UserID) {
                return val('Type', $applicant);
            }
        }
        return '';
    }

    /**
     *
     *
     * @param $GroupID
     * @param array $Where
     * @param bool $Limit
     * @param bool $Offset
     * @return array
     * @throws Exception
     */
    public function getApplicants($GroupID, $Where = [], $Limit = false, $Offset = false) {
        // First grab the members.
        $Users = $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $GroupID)
            ->where($Where)
            ->orderBy('DateInserted')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        Gdn::userModel()->joinUsers($Users, ['UserID']);
        return $Users;
    }

    /**
     *
     *
     * @param $GroupID
     * @param array $Where
     * @param bool $Limit
     * @param bool $Offset
     * @return array
     * @throws Exception
     */
    public function getApplicantIds($GroupID, $Where = [], $Limit = false, $Offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $GroupID)
            ->where($Where)
            ->orderBy('DateInserted')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        $ids = [];
        foreach ($users as $user) {
            $ids[] = val('UserID', $user);
        }
        return $ids;
    }

    /**
     *
     *
     * @param $GroupID
     * @param array $Where
     * @param bool $Limit
     * @param bool $Offset
     * @return array
     * @throws Exception
     */
    public function getMembers($GroupID, $Where = [], $Limit = false, $Offset = false) {
        // Little fix since UserID is now ambiguous
        $duplicatedColumns = ['UserID', 'DateInserted'];
        foreach($duplicatedColumns as $columnName) {
            if (isset($Where[$columnName])) {
                $Where['ug.'.$columnName] = $Where[$columnName];
                unset($Where[$columnName]);
            }
        }

        // First grab the members.
        $Users = $this->SQL
            ->select('ug.*')
            ->from('UserGroup ug')
                ->join('User u', 'ug.UserID = u.UserID')
            ->where('ug.GroupID', $GroupID)
            ->where($Where)
            ->orderBy('DateInserted')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        Gdn::userModel()->joinUsers($Users, ['UserID']);
        return $Users;
    }

    /**
     *
     *
     * @param $GroupID
     * @param array $Where
     * @param bool $Limit
     * @param bool $Offset
     * @return array
     * @throws Exception
     */
    public function getMemberIds($GroupID, $Where = [], $Limit = false, $Offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->from('UserGroup')
            ->where('GroupID', $GroupID)
            ->where($Where)
            ->orderBy('DateInserted')
            ->limit($Limit, $Offset)
            ->get()->resultArray();

        $ids = [];
        foreach ($users as $user) {
            $ids[] = val('UserID', $user);
        }
        return $ids;
    }

    /**
     *
     *
     * @param $UserID
     * @return mixed
     */
    public function getUserCount($UserID) {
        $Count = $this->SQL
            ->select('InsertUserID', 'count', 'CountGroups')
            ->from('Group')
            ->where('InsertUserID', $UserID)
            ->get()->value('CountGroups');
        return $Count;
    }

    /**
     *
     *
     * @param $ID
     * @return mixed
     */
    public static function parseID($ID) {
        $Parts = explode('-', $ID, 2);
        return $Parts[0];
    }

    /**
     *
     *
     * @param $GroupID
     * @param $Inc
     * @param int $DiscussionID
     * @param string $DateLastComment
     * @throws Exception
     */
    public function incrementDiscussionCount($GroupID, $Inc, $DiscussionID = 0, $DateLastComment = '') {
        $Group = $this->getID($GroupID);
        $Set = [];

        if ($DiscussionID) {
            $Set['LastDiscussionID'] = $DiscussionID;
            $Set['LastCommentID'] = null;
        }
        if ($DateLastComment) {
            $Set['DateLastComment'] = $DateLastComment;
        }

        if (val('CountDiscussions', $Group) < 100) {
            $countDiscussions = $this->SQL
                ->select('DiscussionID', 'count', 'CountDiscussions')
                ->from('Discussion')
                ->where('GroupID', $GroupID)
                ->get()->value('CountDiscussions', 0);

            $Set['CountDiscussions'] = $countDiscussions;
            $this->setField($GroupID, $Set);
            return;
        }
        $SQLInc = sprintf('%+d', $Inc);
        $this->SQL
            ->update('Group')
            ->set('CountDiscussions', "CountDiscussions " . $SQLInc, false, false)
            ->where('GroupID', $GroupID);

        if (!empty($Set)) {
            $this->SQL->set($Set);
        }
        $this->SQL->put();
    }

    /**
     * Check if a User is a member of a Group.
     *
     * @param integer $UserID
     * @param integer $GroupID
     * @return bool
     */
    public function isMember($UserID, $GroupID) {
        $IsMember = $this->SQL->getCount('UserGroup', [
            'UserID' => $UserID,
            'GroupID' => $GroupID
        ]);
        return $IsMember > 0;
    }

    /**
     *
     *
     * @param $Data
     * @return bool
     * @throws Gdn_UserException
     */
    public function invite($Data) {
        $Valid = $this->validateJoin($Data);
        if (!$Valid) {
            return false;
        }

        $Group = $this->getID(val('GroupID', $Data));
        trace($Group, 'Group');

        $UserIDs = (array)$Data['UserID'];
        $ValidUserIDs = [];

        foreach ($UserIDs as $UserID) {
            // Make sure the user hasn't already been invited.
            $Application = $this->SQL->getWhere('GroupApplicant', [
                'GroupID' => $Group['GroupID'],
                'UserID' => $UserID
            ])->firstRow(DATASET_TYPE_ARRAY);

            if ($Application) {
                if ($Application['Type'] == 'Invitation') {
                    continue;
                } else {
                    $this->SQL->put('GroupApplicant',
                        ['Type' => 'Invitation'],
                        [
                            'GroupID' => $Group['GroupID'],
                            'UserID' => $UserID
                        ]);
                }
            } else {
                $Data['Type'] = 'Invitation';
                $Data['UserID'] = $UserID;
                $Model = new Gdn_Model('GroupApplicant');
                $Model->options('Ignore', true)->insert($Data);
                $this->Validation = $Model->Validation;
            }
            $ValidUserIDs[] = $UserID;
        }

        // If Conversations are disabled; Improve notification with a link to group.
        if (!class_exists('ConversationModel') && count($ValidUserIDs) > 0) {
            foreach ($ValidUserIDs as $UserID) {
                $Activity = [
                    'ActivityType' => 'Group',
                    'ActivityUserID' => Gdn::session()->UserID,
                    'HeadlineFormat' => t('HeadlineFormat.GroupInvite', 'Please join my <a href="{Url,html}">group</a>.'),
                    'RecordType' => 'Group',
                    'RecordID' => $Group['GroupID'],
                    'Route' => groupUrl($Group, false, '/'),
                    'Story' => formatString(t("You've been invited to join {Name}."), ['Name' => htmlspecialchars($Group['Name'])]),
                    'NotifyUserID' => $UserID,
                    'Data' => ['Name' => $Group['Name']]
                ];
                $ActivityModel = new ActivityModel();
                $ActivityModel->save($Activity, 'Groups');
            }
        }

        // Send a message for the invite.
        if (class_exists('ConversationModel') && count($ValidUserIDs) > 0) {
            $Model = new ConversationModel();
            $MessageModel = new ConversationMessageModel();

            $Args = [
                'Name' => htmlspecialchars($Group['Name']),
                'Url' => groupUrl($Group, '/')
            ];
            $Row = [
                'Subject' => formatString(t("Please join my group."), $Args),
                'Body' => formatString(t("You've been invited to join {Name}."), $Args),
                'Format' => 'Html',
                'RecipientUserID' => $ValidUserIDs,
                'Type' => 'ginvite',
                'RegardingID' => $Group['GroupID'],
            ];

            if (!$Model->save($Row, $MessageModel)) {
                throw new Gdn_UserException($Model->Validation->resultsText());
            }
        }

        return count($this->validationResults()) == 0;
    }

    /**
     *
     *
     * @param $Data
     * @return bool
     * @throws Gdn_UserException
     */
    public function join($Data) {
        $Valid = $this->validateJoin($Data);
        if (!$Valid) {
            return false;
        }

        $Group = $this->getID(GetValue('GroupID', $Data));
        trace($Group, 'Group');

        switch (strtolower($Group['Registration'])) {
            case 'public':
                // This is a public group, go ahead and add the user.
                touchValue('Role', $Data, 'Member');
                $Model = new Gdn_Model('UserGroup');
                $Model->insert($Data);
                $this->Validation = $Model->Validation;
                $this->updateCount($Group['GroupID'], 'CountMembers');
                return count($this->validationResults()) == 0;

            case 'approval':
                // The user must apply to this group.
                $Data['Type'] = 'Application';
                $Model = new Gdn_Model('GroupApplicant');
                $Model->insert($Data);
                $this->Validation = $Model->Validation;
                return count($this->validationResults()) == 0;

            case 'invite':
            default:
                throw new Gdn_UserException("Registration type {$Group['Registration']} not supported.");
                // TODO: The user must be invited.
                return false;
        }
    }

    /**
     *
     *
     * @param $GroupID
     * @param $UserID
     * @param bool $Accept
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function joinInvite($GroupID, $UserID, $Accept = true) {
        // Grab the application.
        $Row = $this->SQL
            ->getWhere('GroupApplicant',['GroupID' => $GroupID, 'UserID' => $UserID])
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$Row || $Row['Type'] != 'Invitation') {
            throw NotFoundException('Invitation');
        }

        $Data = [
            'GroupApplicantID' => $Row['GroupApplicantID'],
            'Type' => $Accept ? 'Approved' : 'Denied'
        ];

        return $this->joinApprove($Data);
    }

    /**
     * Approve a membership application.
     *
     * @param array $Data
     * @return bool
     */
    public function joinApprove($Data) {
        // Grab the applicant row.
        $ID = $Data['GroupApplicantID'];
        $Row = $this->SQL->getWhere('GroupApplicant', ['GroupApplicantID' => $ID])->firstRow(DATASET_TYPE_ARRAY);
        if (!$Row) {
            throw NotFoundException('Applicant');
        }

        $Value = val('Type', $Data);
        if (!in_array($Value, ['Approved', 'Denied'])) {
            throw new Gdn_UserException(t('Type must be either approved or denied.'));
        }

        if ($Value == 'Approved') {
            // Add the user to the group.
            $Model = new Gdn_Model('UserGroup');
            $Inserted = $Model->insert([
                'UserID' => $Row['UserID'],
                'GroupID' => $Row['GroupID'],
                'Role' => val('Role', $Data, 'Member')
            ]);
            $this->Validation = $Model->Validation;

            if ($Inserted) {
                $this->updateCount($Row['GroupID'], 'CountMembers');
                $this->SQL->delete('GroupApplicant', ['GroupApplicantID' => $ID]);

                // TODO: Notify the user.
            }

            return $Inserted;
        } else {
            $Model = new Gdn_Model('GroupApplicant');

            if ($Row['Type'] == 'Invitation') {
                $Model->delete(['GroupApplicantID' => $ID]);
                $Saved = true;
            } else {
                $Saved = $Model->save([
                    'GroupApplicantID' => $ID,
                    'Type' => $Value
                ]);
            }

            return $Saved;
        }
    }

    /**
     * Join the recent discussions/comments to a given set of groups.
     *
     * @param array $Data The groups to join to.
     * @param bool $JoinUsers
     */
    public function JoinRecentPosts(&$Data, $JoinUsers = true) {
        $DiscussionIDs = [];
        $CommentIDs = [];

        foreach ($Data as &$Row) {
            if (isset($Row['LastTitle']) && $Row['LastTitle']) {
                continue;
            }

            if ($Row['LastDiscussionID']) {
                $DiscussionIDs[] = $Row['LastDiscussionID'];
            }

            if ($Row['LastCommentID']) {
                $CommentIDs[] = $Row['LastCommentID'];
            }
        }

        // Create a fresh copy of the Sql object so as not to pollute.
        $Sql = clone Gdn::sql();
        $Sql->reset();

        // Grab the discussions.
        if (count($DiscussionIDs) > 0) {
            $Discussions = $Sql->whereIn('DiscussionID', $DiscussionIDs)->get('Discussion')->resultArray();
            $Discussions = Gdn_DataSet::index($Discussions, ['DiscussionID']);
        }

        if (count($CommentIDs) > 0) {
            $Comments = $Sql->whereIn('CommentID', $CommentIDs)->get('Comment')->resultArray();
            $Comments = Gdn_DataSet::index($Comments, ['CommentID']);
        }

        foreach ($Data as &$Row) {
            $Discussion = val($Row['LastDiscussionID'], $Discussions);
            if ($Discussion) {
                $Row['LastTitle'] = Gdn_Format::text($Discussion['Name']);
                $Row['LastDiscussionUserID'] = $Discussion['InsertUserID'];
                $Row['LastDateInserted'] = $Discussion['DateInserted'];
                $Row['LastUrl'] = discussionUrl($Discussion, false, '/').'#latest';
            }
            $Comment = val($Row['LastCommentID'], $Comments);
            if ($Comment) {
                $Row['LastCommentUserID'] = $Comment['InsertUserID'];
                $Row['LastDateInserted'] = $Comment['DateInserted'];
            } else {
                $Row['NoComment'] = true;
            }

            touchValue('LastTitle', $Row, '');
            touchValue('LastDiscussionUserID', $Row, null);
            touchValue('LastCommentUserID', $Row, null);
            touchValue('LastDateInserted', $Row, null);
            touchValue('LastUrl', $Row, null);
        }

        // Now join the users.
        if ($JoinUsers) {
            Gdn::userModel()->joinUsers($Data, ['LastCommentUserID', 'LastDiscussionUserID']);
        }
    }

    /**
     *
     *
     * @param $Data
     * @throws Gdn_UserException
     */
    public function leave($Data) {
        $this->SQL->delete('UserGroup', [
            'UserID' => val('UserID', $Data),
            'GroupID' => val('GroupID', $Data)]);

        $this->updateCount($Data['GroupID'], 'CountMembers');
    }

    /**
     *
     *
     * @param $Group
     */
    public function overridePermissions($Group) {
        $CategoryID = val('CategoryID', $Group);
        if (!$CategoryID) {
            return;
        }
        $Category = CategoryModel::categories($CategoryID);
        if (!$Category) {
            return;
        }
        $CategoryID = val('PermissionCategoryID', $Category);

        if ($this->checkPermission('Moderate', $Group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.Announce', [$CategoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Close', [$CategoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Edit', [$CategoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Delete', [$CategoryID]);
        }

        if ($this->checkPermission('View', $Group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.View', [$CategoryID]);
            CategoryModel::setLocalField($CategoryID, 'PermsDiscussionsView', true);
        }

        if ($this->checkPermission('Member', $Group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.Add', [$CategoryID]);
            Gdn::session()->setPermission('Vanilla.Comments.Add', [$CategoryID]);
        }
    }

    /**
     *
     *
     * @param array $Data
     * @param bool $Settings
     * @return unknown
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function save($Data, $Settings = false) {
        $this->EventArguments['Fields'] = &$Data;
        $this->fireEvent('BeforeSave');

        if ($this->MaxUserGroups && !val('GroupID', $Data)) {
            $CountUserGroups = $this->getUserCount(Gdn::session()->UserID);
            if ($CountUserGroups >= $this->MaxUserGroups) {
                $this->Validation->addValidationResult('Count', "You've already created the maximum number of groups.");
                return false;
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

        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $PrimaryKeyVal = val($this->PrimaryKey, $Data, false);
        $Insert = $PrimaryKeyVal == false ? true : false;
        if ($Insert) {
            $this->addInsertFields($Data);
        } else {
            $this->addUpdateFields($Data);
        }

        // Validate the form posted values
        $isValid = $this->validate($Data, $Insert) === true;
        $this->EventArguments['IsValid'] = &$isValid;
        $this->fireEvent('AfterValidateGroup');

        if (!$isValid) {
            return false;
        }

        $GroupID = parent::save($Data, $Settings);

        if ($GroupID) {
            // Make sure the group owner is a member.
            $Group = $this->getID($GroupID);
            $InsertUserID = $Group['InsertUserID'];
            $Row = $this->SQL
                ->getWhere('UserGroup', ['GroupID' => $GroupID, 'UserID' => $InsertUserID])
                ->firstRow(DATASET_TYPE_ARRAY);
            if (!$Row) {
                $Row = [
                    'GroupID' => $GroupID,
                    'UserID' => $InsertUserID,
                    'Role' => 'Leader'
                ];
                $Model = new Gdn_Model('UserGroup');
                $Model->insert($Row);
                $this->Validation = $Model->Validation;
            }
            $this->updateCount($GroupID, 'CountMembers');
            $this->getCount(null); // clear cache.
        }
        return $GroupID;
    }

    /**
     *
     *
     * @param $GroupID
     * @param $UserID
     * @param $Role
     */
    public function setRole($GroupID, $UserID, $Role) {
        $this->SQL->put('UserGroup', ['Role' => $Role], ['UserID' => $UserID, 'GroupID' => $GroupID]);
    }

    /**
     *
     *
     * @param $GroupID
     * @param $UserID
     * @param bool $Type
     */
    public function removeMember($GroupID, $UserID, $Type = false) {
        // Remove the member.
        $this->SQL->delete('UserGroup', ['GroupID' => $GroupID, 'UserID' => $UserID]);

        // If the user was banned then let's add the ban.
        if (in_array($Type, ['Banned', 'Denied'])) {
            $Model = new Gdn_Model('GroupApplicant');
            $Model->delete(['GroupID' => $GroupID, 'UserID' => $UserID]);
            $Model->insert([
                'GroupID' => $GroupID,
                'UserID' => $UserID,
                'Type' => $Type
            ]);
        }
    }

    /**
     *
     *
     * @param $GroupID
     * @param $Column
     * @throws Gdn_UserException
     */
    public function updateCount($GroupID, $Column) {
        switch ($Column) {
            case 'CountDiscussions':
                $Sql = DBAModel::getCountSQL('count', 'Group', 'Discussion', $Column, 'GroupID');
                break;
            case 'CountMembers':
                $Sql = DBAModel::getCountSQL('count', 'Group', 'UserGroup', $Column, 'UserGroupID');
                break;
            case 'DateLastComment':
                $Sql = DBAModel::getCountSQL('max', 'Group', 'Discussion', $Column, 'DateLastComment');
                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }
        $Sql .= " where p.GroupID = ".$this->Database->connection()->quote($GroupID);
        $this->Database->query($Sql);
    }

    /**
     *
     *
     * @param array $FormPostValues
     * @param bool $Insert
     * @return bool
     */
    public function validate($FormPostValues, $Insert = false) {
        $Valid = parent::validate($FormPostValues, $Insert);

        // Check to see if there is another group with the same name.
        if (trim(GetValue('Name', $FormPostValues))) {
            $Rows = $this->SQL->getWhere('Group', ['Name' => $FormPostValues['Name']])->resultArray();

            $GroupID = GetValue('GroupID', $FormPostValues);
            foreach ($Rows as $Row) {
                if (!$GroupID || $GroupID != $Row['GroupID']) {
                    $Valid = false;
                    $this->Validation->addValidationResult(
                        'Name',
                        '@'.sprintf(t("There's already a %s with the name %s."), t('group'), htmlspecialchars($FormPostValues['Name']))
                    );
                }
            }
        }
        return $Valid;
    }

    /**
     *
     *
     * @param $FieldName
     * @param $Data
     * @param $Rule
     * @param bool $CustomError
     */
    protected function validateRule($FieldName, $Data, $Rule, $CustomError = false) {
        $Value = val($FieldName, $Data);
        $Valid = $this->Validation->validateRule($Value, $FieldName, $Rule, $CustomError);
        if ($Valid !== true) {
            $this->Validation->addValidationResult($FieldName, $Valid.$Value);
        }
    }

    /**
     *
     *
     * @param $Data
     * @return bool
     */
    public function validateJoin($Data) {
        $this->validateRule('UserID', $Data, 'ValidateRequired');
        $this->validateRule('GroupID', $Data, 'ValidateRequired');

        $GroupID = val('GroupID', $Data);
        if ($GroupID) {
            $Group = $this->getID($GroupID);

            switch (strtolower($Group['Privacy'])) {
                case 'private':
                    if (!$this->checkPermission('Leader', $Group)) {
                        $this->validateRule('Reason', $Data, 'ValidateRequired', 'Why do you want to join?');
                    }
                    break;
            }
        }

        // First validate the basic field requirements.
        $Valid = $this->Validation->validate($Data);
        return $Valid;
    }

    /**
     * Delete a group.
     *
     * @param array|string $Where
     * @param integer|bool $Limit
     * @param boolean $ResetData Unused.
     * @return Gdn_DataSet
     */
    public function delete($Where = '', $Limit = false, $ResetData = false) {
        // Get list of matching groups
        $matchGroups = $this->getWhere($Where,'','',$Limit);
        // Clean up UserGroups
        $groupIDs = [];
        foreach ($matchGroups as $event) {
            $groupIDs[] = val('GroupID', $event);
        }

        // Start by deleting the content! If the query times out the groups will be intact and the user will be
        // able to try again! This is the "best" we can do until we have a Queue for tasks.
        $discussionIDs = $this->SQL
                ->select('DiscussionID')
                ->getWhere('Discussion', ['GroupID' => $groupIDs])
                ->resultArray();

        $discussionModel = new DiscussionModel();
        foreach ($discussionIDs as $discussionID) {
            $discussionModel->deleteID($discussionID);
        }

        // Add Logging on deletion of groups
        LogModel::beginTransaction();
        // Get the row(s) of the group(s) being deleted to save to the log.
        $groups = $this->SQL->getWhere('Group', ['GroupID' => $groupIDs])->resultArray();
        // Include the first 20 applicants and users in the log
        $groupMembers = $this->SQL->getWhere('UserGroup', ['GroupID' => $groupIDs], "", "asc", 20)->resultArray();
        $groupApplicants = $this->SQL->getWhere('GroupApplicant', ['GroupID' => $groupIDs], "", "asc", 20)->resultArray();

        $data['_Data']['Group'] = $groups;
        $data['_Data']['GroupMembers'] = $groupMembers;
        $data['_Data']['GroupApplicants'] = $groupApplicants;
        LogModel::insert('Delete', 'Group', $data);

        LogModel::endTransaction();

        // Delete groups
        $deleted = parent::delete($Where, $Limit ? ['limit' => $Limit] : []);

        $this->SQL->delete('UserGroup', ['GroupID'=> $groupIDs]);
        $this->SQL->delete('GroupApplicant', ['GroupID' => $groupIDs]);

        return $deleted;
    }

    /**
     * Retrieves group category IDs.
     *
     * @return array An array of category IDs.
     */
    public static function getGroupCategoryIDs() {
        $GroupCategoryIDs = Gdn::Cache()->Get('GroupCategoryIDs');
        if ($GroupCategoryIDs === Gdn_Cache::CACHEOP_FAILURE) {
            $CategoryModel = new CategoryModel();
            $GroupCategories = $CategoryModel->GetWhere(['AllowGroups' => 1])->ResultArray();
            $GroupCategoryIDs = [];
            foreach ($GroupCategories as $GroupCategory) {
                $GroupCategoryIDs[] = $GroupCategory['CategoryID'];
            }

            Gdn::Cache()->Store('GroupCategoryIDs', $GroupCategoryIDs);
        }
        return $GroupCategoryIDs;
    }
}
