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
     * @param Gdn_DataSet $result
     */
    public function calc(&$result) {
        foreach ($result as &$row) {
            $row['Url'] = GroupUrl($row, null, '//');
            $row['DescriptionHtml'] = Gdn_Format::to($row['Description'], $row['Format']);

            if ($row['Icon']) {
                $row['IconUrl'] = Gdn_Upload::url($row['Icon']);
            }
            if ($row['Banner']) {
                $row['BannerUrl'] = Gdn_Upload::url($row['Banner']);
            }
        }
    }

    /**
     * Check permission on a group.
     *
     * @param string $permission The permission to check. Valid values are:
     *  - Member: User is a member of the group.
     *  - Leader: User is a leader of the group.
     *  - Join: User can join the group.
     *  - Leave: User can leave the group.
     *  - Edit: The user may edit the group.
     *  - Delete: User can delete the group.
     *  - View: The user may view the group's contents.
     *  - Moderate: The user may moderate the group.
     * @param int $groupID
     * @return boolean
     */
    public function checkPermission($permission, $groupID) {
        static $permissions = [];

        $userID = Gdn::session()->UserID;

        if (is_array($groupID)) {
            $group = $groupID;
            $groupID = $group['GroupID'];
        }

        $key = "$userID-$groupID";

        if (!isset($permissions[$key])) {
            // Get the data for the group.
            if (!isset($group)) {
                $group = $this->getID($groupID);
            }

            if ($userID) {
                $userGroup = Gdn::sql()->getWhere('UserGroup', ['GroupID' => $groupID, 'UserID' => Gdn::session()->UserID])->firstRow(DATASET_TYPE_ARRAY);
                $groupApplicant = Gdn::sql()->getWhere('GroupApplicant', ['GroupID' => $groupID, 'UserID' => Gdn::session()->UserID])->firstRow(DATASET_TYPE_ARRAY);
            } else {
                $userGroup = false;
                $groupApplicant = false;
            }

            // Set the default permissions.
            $perms = [
                'Member' => false,
                'Leader' => false,
                'Join' => Gdn::session()->isValid(),
                'Leave' => false,
                'Edit' => false,
                'Delete' => false,
                'Moderate' => false,
                'View' => true];

            // The group creator is always a member and leader.
            if ($userID == $group['InsertUserID']) {
                $perms['Delete'] = true;

                if (!$userGroup) {
                    $userGroup = ['Role' => 'Leader'];
                }
            }

            if ($userGroup) {
                $perms['Join'] = false;
                $perms['Join.Reason'] = t('You are already a member of this group.');

                $perms['Member'] = true;
                $perms['Leader'] = ($userGroup['Role'] == 'Leader');
                $perms['Edit'] = $perms['Leader'];
                $perms['Moderate'] = $perms['Leader'];

                if ($userID != $group['InsertUserID']) {
                    $perms['Leave'] = true;
                } else {
                    $perms['Leave.Reason'] = t("You can't leave the group you started.");
                }
            } else {
                if ($group['Privacy'] != 'Public') {
                    $perms['View'] = false;
                    $perms['View.Reason'] = t('Join this group to view its content.');
                }
            }

            if ($groupApplicant) {
                $perms['Join'] = false; // Already applied or banned.
                switch (strtolower($groupApplicant['Type'])) {
                    case 'application':
                        $perms['Join.Reason'] = t("You've applied to join this group.");
                        break;
                    case 'denied':
                        $perms['Join.Reason'] = t("You're application for this group was denied.");
                        break;
                    case 'ban':
                        $perms['Join.Reason'] = t("You're banned from joining this group.");
                        break;
                    case 'invitation':
                        $perms['Join'] = true;
                        unset($perms['Join.Reason']);
                        break;
                }
            }

            // Moderators can view and edit all groups.
            $canManage = Gdn::session()->checkPermission([
                'Garden.Settings.Manage',
                'Garden.Community.Manage',
                'Groups.Moderation.Manage'
            ], false);

            if ($userID == Gdn::session()->UserID && $canManage) {
                $managerOverrides = [
                    'Delete' => true,
                    'Edit' => true,
                    'Leader' => true,
                    'Moderate' => true,
                    'View' => true,
                ];

                unset($perms['View.Reason']);
                $perms = array_merge($perms, $managerOverrides);
            }

            $permissions[$key] = $perms;
        }

        $perms = $permissions[$key];

        if (!$permission) {
            return $perms;
        }

        if (!isset($perms[$permission])) {
            if (strpos($permission, '.Reason') === false) {
                trigger_error("Invalid group permission $permission.");
                return false;
            } else {
                $permission = stringEndsWith($permission, '.Reason', true, true);
                if ($perms[$permission]) {
                    return '';
                }

                if (in_array($permission, ['Member', 'Leader'])) {
                    $message = t(sprintf("You aren't a %s of this group.", strtolower($permission)));
                } else {
                    $message = sprintf(t("You aren't allowed to %s this group."), t(strtolower($permission)));
                }

                return $message;
            }
        } else {
            return $perms[$permission];
        }
    }

    /**
     *
     *
     * @param $column
     * @param bool $from
     * @param bool $to
     * @param bool $max
     * @return array
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function counts($column, $from = false, $to = false, $max = false) {
        $result = ['Complete' => true];
        switch ($column) {
            case 'CountDiscussions':
                $this->Database->query(DBAModel::getCountSQL('count', 'Group', 'Discussion', $column, 'GroupID'));
                break;
            case 'CountMembers':
                $this->Database->query(DBAModel::getCountSQL('count', 'Group', 'UserGroup', $column, 'UserGroupID'));
                break;
            case 'DateLastComment':
                $this->Database->query(DBAModel::getCountSQL('max', 'Group', 'Discussion', $column, 'DateLastComment'));
                break;
            case 'LastDiscussionID':
                $this->SQL->update('Group g')
                    ->join('Discussion d', 'd.DateLastComment = g.DateLastComment and g.GroupID = d.GroupID')
                    ->set('g.LastDiscussionID', 'd.DiscussionID', false, false)
                    ->set('g.LastCommentID', 'd.LastCommentID', false, false)
                    ->put();
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        return $result;
    }

    /**
     *
     *
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $pageNumber
     * @return Gdn_Dataset
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        $result = parent::get($orderFields, $orderDirection, $limit, $pageNumber);
        $result->datasetType(DATASET_TYPE_ARRAY);
        $this->calc($result->result());
        return $result;
    }

    /**
     * @param $userID
     * @param string $orderFields
     * @param string $orderDirection
     * @param int $limit
     * @param bool $offset
     * @return array
     */
    public function getByUser($userID, $orderFields = '', $orderDirection = 'desc', $limit = 9, $offset = false) {
        $userGroups = $this->SQL->getWhere('UserGroup', ['UserID' => $userID])->resultArray();
        $iDs = array_column($userGroups, 'GroupID');

        $result = $this->getWhere(['GroupID' => $iDs], $orderFields, $orderDirection, $limit, $offset)->resultArray();
        $this->calc($result);
        return $result;
    }

    /**
     *
     *
     * @param string $wheres
     * @return Gdn_Dataset|mixed|null
     */
    public function getCount($wheres = '') {
        if ($wheres) {
            return parent::getCount($wheres);
        }

        $key = 'Group.Count';

        if ($wheres === null) {
            Gdn::cache()->remove($key);
            return null;
        }

        $count = Gdn::cache()->get($key);
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count = parent::getCount();
            Gdn::cache()->store($key, $count);
        }

        return $count;
    }

    /**
     *
     *
     * @param int|string $iD The ID or slug of the group.
     * @param bool|string $datasetType The type of return.
     * @param array $options Base class compatibility.
     * @return array|mixed|object
     */
    public function getID($iD, $datasetType = DATASET_TYPE_ARRAY, $options = []) {
        static $cache = [];

        $iD = self::parseID($iD);
        if (isset($cache[$iD])) {
            return $cache[$iD];
        }

        $row = parent::getID($iD, $datasetType);
        $cache[$iD] = $row;

        return $row;
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
     * @param $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @return array
     * @throws Exception
     */
    public function getApplicants($groupID, $where = [], $limit = false, $offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
            ->get()->resultArray();

        Gdn::userModel()->joinUsers($users, ['UserID']);
        return $users;
    }

    /**
     *
     *
     * @param $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @return array
     * @throws Exception
     */
    public function getApplicantIds($groupID, $where = [], $limit = false, $offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
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
     * @param $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @return array
     * @throws Exception
     */
    public function getMembers($groupID, $where = [], $limit = false, $offset = false) {
        // Little fix since UserID is now ambiguous
        $duplicatedColumns = ['UserID', 'DateInserted'];
        foreach($duplicatedColumns as $columnName) {
            if (isset($where[$columnName])) {
                $where['ug.'.$columnName] = $where[$columnName];
                unset($where[$columnName]);
            }
        }

        // First grab the members.
        $users = $this->SQL
            ->select('ug.*')
            ->from('UserGroup ug')
                ->join('User u', 'ug.UserID = u.UserID')
            ->where('ug.GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
            ->get()->resultArray();

        Gdn::userModel()->joinUsers($users, ['UserID']);
        return $users;
    }

    /**
     *
     *
     * @param $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @return array
     * @throws Exception
     */
    public function getMemberIds($groupID, $where = [], $limit = false, $offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->from('UserGroup')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
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
     * @param $userID
     * @return mixed
     */
    public function getUserCount($userID) {
        $count = $this->SQL
            ->select('InsertUserID', 'count', 'CountGroups')
            ->from('Group')
            ->where('InsertUserID', $userID)
            ->get()->value('CountGroups');
        return $count;
    }

    /**
     *
     *
     * @param $iD
     * @return mixed
     */
    public static function parseID($iD) {
        $parts = explode('-', $iD, 2);
        return $parts[0];
    }

    /**
     *
     *
     * @param $groupID
     * @param $inc
     * @param int $discussionID
     * @param string $dateLastComment
     * @throws Exception
     */
    public function incrementDiscussionCount($groupID, $inc, $discussionID = 0, $dateLastComment = '') {
        $group = $this->getID($groupID);
        $set = [];

        if ($discussionID) {
            $set['LastDiscussionID'] = $discussionID;
            $set['LastCommentID'] = null;
        }
        if ($dateLastComment) {
            $set['DateLastComment'] = $dateLastComment;
        }

        if (val('CountDiscussions', $group) < 100) {
            $countDiscussions = $this->SQL
                ->select('DiscussionID', 'count', 'CountDiscussions')
                ->from('Discussion')
                ->where('GroupID', $groupID)
                ->get()->value('CountDiscussions', 0);

            $set['CountDiscussions'] = $countDiscussions;
            $this->setField($groupID, $set);
            return;
        }
        $sQLInc = sprintf('%+d', $inc);
        $this->SQL
            ->update('Group')
            ->set('CountDiscussions', "CountDiscussions " . $sQLInc, false, false)
            ->where('GroupID', $groupID);

        if (!empty($set)) {
            $this->SQL->set($set);
        }
        $this->SQL->put();
    }

    /**
     * Check if a User is a member of a Group.
     *
     * @param integer $userID
     * @param integer $groupID
     * @return bool
     */
    public function isMember($userID, $groupID) {
        $isMember = $this->SQL->getCount('UserGroup', [
            'UserID' => $userID,
            'GroupID' => $groupID
        ]);
        return $isMember > 0;
    }

    /**
     *
     *
     * @param $data
     * @return bool
     * @throws Gdn_UserException
     */
    public function invite($data) {
        $valid = $this->validateJoin($data);
        if (!$valid) {
            return false;
        }

        $group = $this->getID(val('GroupID', $data));
        trace($group, 'Group');

        $userIDs = (array)$data['UserID'];
        $validUserIDs = [];

        foreach ($userIDs as $userID) {
            // Make sure the user hasn't already been invited.
            $application = $this->SQL->getWhere('GroupApplicant', [
                'GroupID' => $group['GroupID'],
                'UserID' => $userID
            ])->firstRow(DATASET_TYPE_ARRAY);

            if ($application) {
                if ($application['Type'] == 'Invitation') {
                    continue;
                } else {
                    $this->SQL->put('GroupApplicant',
                        ['Type' => 'Invitation'],
                        [
                            'GroupID' => $group['GroupID'],
                            'UserID' => $userID
                        ]);
                }
            } else {
                $data['Type'] = 'Invitation';
                $data['UserID'] = $userID;
                $model = new Gdn_Model('GroupApplicant');
                $model->options('Ignore', true)->insert($data);
                $this->Validation = $model->Validation;
            }
            $validUserIDs[] = $userID;
        }

        // If Conversations are disabled; Improve notification with a link to group.
        if (!class_exists('ConversationModel') && count($validUserIDs) > 0) {
            foreach ($validUserIDs as $userID) {
                $activity = [
                    'ActivityType' => 'Group',
                    'ActivityUserID' => Gdn::session()->UserID,
                    'HeadlineFormat' => t('HeadlineFormat.GroupInvite', 'Please join my <a href="{Url,html}">group</a>.'),
                    'RecordType' => 'Group',
                    'RecordID' => $group['GroupID'],
                    'Route' => groupUrl($group, false, '/'),
                    'Story' => formatString(t("You've been invited to join {Name}."), ['Name' => htmlspecialchars($group['Name'])]),
                    'NotifyUserID' => $userID,
                    'Data' => ['Name' => $group['Name']]
                ];
                $activityModel = new ActivityModel();
                $activityModel->save($activity, 'Groups');
            }
        }

        // Send a message for the invite.
        if (class_exists('ConversationModel') && count($validUserIDs) > 0) {
            $model = new ConversationModel();
            $messageModel = new ConversationMessageModel();

            $args = [
                'Name' => htmlspecialchars($group['Name']),
                'Url' => groupUrl($group, '/')
            ];
            $row = [
                'Subject' => formatString(t("Please join my group."), $args),
                'Body' => formatString(t("You've been invited to join {Name}."), $args),
                'Format' => 'Html',
                'RecipientUserID' => $validUserIDs,
                'Type' => 'ginvite',
                'RegardingID' => $group['GroupID'],
            ];

            if (!$model->save($row, $messageModel)) {
                throw new Gdn_UserException($model->Validation->resultsText());
            }
        }

        return count($this->validationResults()) == 0;
    }

    /**
     *
     *
     * @param $data
     * @return bool
     * @throws Gdn_UserException
     */
    public function join($data) {
        $valid = $this->validateJoin($data);
        if (!$valid) {
            return false;
        }

        $group = $this->getID(GetValue('GroupID', $data));
        trace($group, 'Group');

        switch (strtolower($group['Registration'])) {
            case 'public':
                // This is a public group, go ahead and add the user.
                touchValue('Role', $data, 'Member');
                $model = new Gdn_Model('UserGroup');
                $model->insert($data);
                $this->Validation = $model->Validation;
                $this->updateCount($group['GroupID'], 'CountMembers');
                return count($this->validationResults()) == 0;

            case 'approval':
                // The user must apply to this group.
                $data['Type'] = 'Application';
                $model = new Gdn_Model('GroupApplicant');
                $model->insert($data);
                $this->Validation = $model->Validation;
                return count($this->validationResults()) == 0;

            case 'invite':
            default:
                throw new Gdn_UserException("Registration type {$group['Registration']} not supported.");
                // TODO: The user must be invited.
                return false;
        }
    }

    /**
     *
     *
     * @param $groupID
     * @param $userID
     * @param bool $accept
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function joinInvite($groupID, $userID, $accept = true) {
        // Grab the application.
        $row = $this->SQL
            ->getWhere('GroupApplicant',['GroupID' => $groupID, 'UserID' => $userID])
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$row || $row['Type'] != 'Invitation') {
            throw NotFoundException('Invitation');
        }

        $data = [
            'GroupApplicantID' => $row['GroupApplicantID'],
            'Type' => $accept ? 'Approved' : 'Denied'
        ];

        return $this->joinApprove($data);
    }

    /**
     * Approve a membership application.
     *
     * @param array $data
     * @return bool
     */
    public function joinApprove($data) {
        // Grab the applicant row.
        $iD = $data['GroupApplicantID'];
        $row = $this->SQL->getWhere('GroupApplicant', ['GroupApplicantID' => $iD])->firstRow(DATASET_TYPE_ARRAY);
        if (!$row) {
            throw NotFoundException('Applicant');
        }

        $value = val('Type', $data);
        if (!in_array($value, ['Approved', 'Denied'])) {
            throw new Gdn_UserException(t('Type must be either approved or denied.'));
        }

        if ($value == 'Approved') {
            // Add the user to the group.
            $model = new Gdn_Model('UserGroup');
            $inserted = $model->insert([
                'UserID' => $row['UserID'],
                'GroupID' => $row['GroupID'],
                'Role' => val('Role', $data, 'Member')
            ]);
            $this->Validation = $model->Validation;

            if ($inserted) {
                $this->updateCount($row['GroupID'], 'CountMembers');
                $this->SQL->delete('GroupApplicant', ['GroupApplicantID' => $iD]);

                // TODO: Notify the user.
            }

            return $inserted;
        } else {
            $model = new Gdn_Model('GroupApplicant');

            if ($row['Type'] == 'Invitation') {
                $model->delete(['GroupApplicantID' => $iD]);
                $saved = true;
            } else {
                $saved = $model->save([
                    'GroupApplicantID' => $iD,
                    'Type' => $value
                ]);
            }

            return $saved;
        }
    }

    /**
     * Join the recent discussions/comments to a given set of groups.
     *
     * @param array $data The groups to join to.
     * @param bool $joinUsers
     */
    public function JoinRecentPosts(&$data, $joinUsers = true) {
        $discussionIDs = [];
        $commentIDs = [];

        foreach ($data as &$row) {
            if (isset($row['LastTitle']) && $row['LastTitle']) {
                continue;
            }

            if ($row['LastDiscussionID']) {
                $discussionIDs[] = $row['LastDiscussionID'];
            }

            if ($row['LastCommentID']) {
                $commentIDs[] = $row['LastCommentID'];
            }
        }

        // Create a fresh copy of the Sql object so as not to pollute.
        $sql = clone Gdn::sql();
        $sql->reset();

        // Grab the discussions.
        if (count($discussionIDs) > 0) {
            $discussions = $sql->whereIn('DiscussionID', $discussionIDs)->get('Discussion')->resultArray();
            $discussions = Gdn_DataSet::index($discussions, ['DiscussionID']);
        }

        if (count($commentIDs) > 0) {
            $comments = $sql->whereIn('CommentID', $commentIDs)->get('Comment')->resultArray();
            $comments = Gdn_DataSet::index($comments, ['CommentID']);
        }

        foreach ($data as &$row) {
            $discussion = val($row['LastDiscussionID'], $discussions);
            if ($discussion) {
                $row['LastTitle'] = Gdn_Format::text($discussion['Name']);
                $row['LastDiscussionUserID'] = $discussion['InsertUserID'];
                $row['LastDateInserted'] = $discussion['DateInserted'];
                $row['LastUrl'] = discussionUrl($discussion, false, '/').'#latest';
            }
            $comment = val($row['LastCommentID'], $comments);
            if ($comment) {
                $row['LastCommentUserID'] = $comment['InsertUserID'];
                $row['LastDateInserted'] = $comment['DateInserted'];
            } else {
                $row['NoComment'] = true;
            }

            touchValue('LastTitle', $row, '');
            touchValue('LastDiscussionUserID', $row, null);
            touchValue('LastCommentUserID', $row, null);
            touchValue('LastDateInserted', $row, null);
            touchValue('LastUrl', $row, null);
        }

        // Now join the users.
        if ($joinUsers) {
            Gdn::userModel()->joinUsers($data, ['LastCommentUserID', 'LastDiscussionUserID']);
        }
    }

    /**
     *
     *
     * @param $data
     * @throws Gdn_UserException
     */
    public function leave($data) {
        $this->SQL->delete('UserGroup', [
            'UserID' => val('UserID', $data),
            'GroupID' => val('GroupID', $data)]);

        $this->updateCount($data['GroupID'], 'CountMembers');
    }

    /**
     *
     *
     * @param $group
     */
    public function overridePermissions($group) {
        $categoryID = val('CategoryID', $group);
        if (!$categoryID) {
            return;
        }
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            return;
        }
        $categoryID = val('PermissionCategoryID', $category);

        if ($this->checkPermission('Moderate', $group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.Announce', [$categoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Close', [$categoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Edit', [$categoryID]);
            Gdn::session()->setPermission('Vanilla.Discussions.Delete', [$categoryID]);
            if (c('Groups.Leaders.CanModerate')) {
                Gdn::session()->setPermission('Vanilla.Comments.Announce', [$categoryID]);
                Gdn::session()->setPermission('Vanilla.Comments.Close', [$categoryID]);
                Gdn::session()->setPermission('Vanilla.Comments.Edit', [$categoryID]);
                Gdn::session()->setPermission('Vanilla.Comments.Delete', [$categoryID]);
            }
        }

        if ($this->checkPermission('View', $group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.View', [$categoryID]);
            CategoryModel::setLocalField($categoryID, 'PermsDiscussionsView', true);
        }

        if ($this->checkPermission('Member', $group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.Add', [$categoryID]);
            Gdn::session()->setPermission('Vanilla.Comments.Add', [$categoryID]);
        }
    }

    /**
     *
     *
     * @param array $data
     * @param bool $settings
     * @return unknown
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function save($data, $settings = false) {
        $this->EventArguments['Fields'] = &$data;
        $this->fireEvent('BeforeSave');

        if ($this->MaxUserGroups && !val('GroupID', $data)) {
            $countUserGroups = $this->getUserCount(Gdn::session()->UserID);
            if ($countUserGroups >= $this->MaxUserGroups) {
                $this->Validation->addValidationResult('Count', "You've already created the maximum number of groups.");
                return false;
            }
        }

        // Set the visibility and registration based on the privacy.
        switch (strtolower(GetValue('Privacy', $data))) {
            case 'private':
                $data['Visibility'] = 'Members';
                $data['Registration'] = 'Approval';
                break;
            case 'public':
                $data['Visibility'] = 'Public';
                $data['Registration'] = 'Public';
                break;
        }

        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $primaryKeyVal = val($this->PrimaryKey, $data, false);
        $insert = $primaryKeyVal == false ? true : false;
        if ($insert) {
            $this->addInsertFields($data);
        } else {
            $this->addUpdateFields($data);
        }

        // Validate the form posted values
        $isValid = $this->validate($data, $insert) === true;
        $this->EventArguments['IsValid'] = &$isValid;
        $this->fireEvent('AfterValidateGroup');

        if (!$isValid) {
            return false;
        }

        $groupID = parent::save($data, $settings);

        if ($groupID) {
            // Make sure the group owner is a member.
            $group = $this->getID($groupID);
            $insertUserID = $group['InsertUserID'];
            $row = $this->SQL
                ->getWhere('UserGroup', ['GroupID' => $groupID, 'UserID' => $insertUserID])
                ->firstRow(DATASET_TYPE_ARRAY);
            if (!$row) {
                $row = [
                    'GroupID' => $groupID,
                    'UserID' => $insertUserID,
                    'Role' => 'Leader'
                ];
                $model = new Gdn_Model('UserGroup');
                $model->insert($row);
                $this->Validation = $model->Validation;
            }
            $this->updateCount($groupID, 'CountMembers');
            $this->getCount(null); // clear cache.
        }
        return $groupID;
    }

    /**
     *
     *
     * @param $groupID
     * @param $userID
     * @param $role
     */
    public function setRole($groupID, $userID, $role) {
        $this->SQL->put('UserGroup', ['Role' => $role], ['UserID' => $userID, 'GroupID' => $groupID]);
    }

    /**
     *
     *
     * @param $groupID
     * @param $userID
     * @param bool $type
     */
    public function removeMember($groupID, $userID, $type = false) {
        // Remove the member.
        $this->SQL->delete('UserGroup', ['GroupID' => $groupID, 'UserID' => $userID]);

        // If the user was banned then let's add the ban.
        if (in_array($type, ['Banned', 'Denied'])) {
            $model = new Gdn_Model('GroupApplicant');
            $model->delete(['GroupID' => $groupID, 'UserID' => $userID]);
            $model->insert([
                'GroupID' => $groupID,
                'UserID' => $userID,
                'Type' => $type
            ]);
        }
    }

    /**
     *
     *
     * @param $groupID
     * @param $column
     * @throws Gdn_UserException
     */
    public function updateCount($groupID, $column) {
        switch ($column) {
            case 'CountDiscussions':
                $sql = DBAModel::getCountSQL('count', 'Group', 'Discussion', $column, 'GroupID');
                break;
            case 'CountMembers':
                $sql = DBAModel::getCountSQL('count', 'Group', 'UserGroup', $column, 'UserGroupID');
                break;
            case 'DateLastComment':
                $sql = DBAModel::getCountSQL('max', 'Group', 'Discussion', $column, 'DateLastComment');
                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }
        $sql .= " where p.GroupID = ".$this->Database->connection()->quote($groupID);
        $this->Database->query($sql);
    }

    /**
     *
     *
     * @param array $formPostValues
     * @param bool $insert
     * @return bool
     */
    public function validate($formPostValues, $insert = false) {
        $valid = parent::validate($formPostValues, $insert);

        // Check to see if there is another group with the same name.
        if (trim(GetValue('Name', $formPostValues))) {
            $rows = $this->SQL->getWhere('Group', ['Name' => $formPostValues['Name']])->resultArray();

            $groupID = GetValue('GroupID', $formPostValues);
            foreach ($rows as $row) {
                if (!$groupID || $groupID != $row['GroupID']) {
                    $valid = false;
                    $this->Validation->addValidationResult(
                        'Name',
                        '@'.sprintf(t("There's already a %s with the name %s."), t('group'), htmlspecialchars($formPostValues['Name']))
                    );
                }
            }
        }
        return $valid;
    }

    /**
     *
     *
     * @param $fieldName
     * @param $data
     * @param $rule
     * @param bool $customError
     */
    protected function validateRule($fieldName, $data, $rule, $customError = false) {
        $value = val($fieldName, $data);
        $valid = $this->Validation->validateRule($value, $fieldName, $rule, $customError);
        if ($valid !== true) {
            $this->Validation->addValidationResult($fieldName, $valid.$value);
        }
    }

    /**
     *
     *
     * @param $data
     * @return bool
     */
    public function validateJoin($data) {
        $this->validateRule('UserID', $data, 'ValidateRequired');
        $this->validateRule('GroupID', $data, 'ValidateRequired');

        $groupID = val('GroupID', $data);
        if ($groupID) {
            $group = $this->getID($groupID);

            switch (strtolower($group['Privacy'])) {
                case 'private':
                    if (!$this->checkPermission('Leader', $group)) {
                        $this->validateRule('Reason', $data, 'ValidateRequired', 'Why do you want to join?');
                    }
                    break;
            }
        }

        // First validate the basic field requirements.
        $valid = $this->Validation->validate($data);
        return $valid;
    }

    /**
     * Delete a group.
     *
     * @param array|string $where
     * @param integer|bool $limit
     * @param boolean $resetData Unused.
     * @return Gdn_DataSet
     */
    public function delete($where = '', $limit = false, $resetData = false) {
        // Get list of matching groups
        $matchGroups = $this->getWhere($where,'','',$limit);
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
        $deleted = parent::delete($where, $limit ? ['limit' => $limit] : []);

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
        $groupCategoryIDs = Gdn::Cache()->Get('GroupCategoryIDs');
        if ($groupCategoryIDs === Gdn_Cache::CACHEOP_FAILURE) {
            $categoryModel = new CategoryModel();
            $groupCategories = $categoryModel->GetWhere(['AllowGroups' => 1])->ResultArray();
            $groupCategoryIDs = [];
            foreach ($groupCategories as $groupCategory) {
                $groupCategoryIDs[] = $groupCategory['CategoryID'];
            }

            Gdn::Cache()->Store('GroupCategoryIDs', $groupCategoryIDs);
        }
        return $groupCategoryIDs;
    }
}
