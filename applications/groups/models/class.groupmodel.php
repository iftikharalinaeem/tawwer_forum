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

    /** @var int Limit of query results per page */
    const LIMIT = 24;

    /** @var array The permissions associated with a group. */
    private static $permissions = [];

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
     * Add an applicant to a group.
     *
     * @throws Gdn_UserException
     * @param int $groupID
     * @param int $userID
     * @param string $reason
     * @return bool
     */
    public function apply($groupID, $userID, $reason) {
        $group = $this->getID($groupID);

        if ($group['Privacy'] !== 'Private') {
            throw new Gdn_UserException('Cannot apply to a group that is not private.');
        }

        $permissions = $this->checkPermission(false, $groupID, $userID);

        if (!$permissions['Join']) {
            throw new GDN_UserException($permissions['Join.Reason']);
        }

        $model = new Gdn_Model('GroupApplicant');
        foreach ($model->Validation->results() as $key => $value) {
            $this->Validation->addValidationResult($key, $value);
        }

        return (bool)$model->insert([
            'GroupID' => $groupID,
            'UserID' => $userID,
            'Type' => 'Application',
            'Reason' => $reason,
        ]);
    }

    /**
     * Add a user to a group.
     *
     * @param int $groupID
     * @param int $userID
     * @param string $role Enum['Member', 'Leader']
     * @return bool
     */
    public function addUser($groupID, $userID, $role) {
        if (!in_array($role, ['Member', 'Leader'])) {
            $this->Validation->addValidationResult('Role', 'Invalid role.');
            return false;
        }

        $data = [
            'GroupID' => $groupID,
            'UserID' => $userID,
            'Role' => $role,
        ];
        $this->addInsertFields($data);

        $success = (bool)$this->SQL->insert('UserGroup', $data);

        $this->updateCount($groupID, 'CountMembers');

        return $success;
    }

    /**
     * Calculate the rows in a groups result.
     *
     * @param Gdn_DataSet $result
     */
    public function calc(&$result) {
        foreach ($result as &$row) {
            $row['Url'] = groupUrl($row, null, '//');
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
     *  - Apply: User can apply to the group.
     *  - Join: User can join the group.
     *  - Leave: User can leave the group.
     *  - Edit: The user may edit the group.
     *  - Delete: User can delete the group.
     *  - View: The user may view the group's contents.
     *  - Moderate: The user may moderate the group.
     * @param int|array $groupID The groupID or group record.
     * @param int|null $userID
     * @param bool $useCache Use the user-group permission cache? If false, don't read or write to it.
     * @return boolean
     */
    public function checkPermission($permission, $groupID, $userID = null, $useCache = true) {

        if ($userID === null) {
            $userID = Gdn::session()->UserID;
        }

        if (is_array($groupID)) {
            $group = $groupID;
            $groupID = $group['GroupID'];
        }

        $key = "$userID-$groupID";

        if (!$useCache || !isset(self::$permissions[$key])) {
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
                'Access' => true,
                'Member' => false,
                'Leader' => false,
                'Apply' => false,
                'Join' => Gdn::session()->isValid(),
                'Leave' => false,
                'Edit' => false,
                'Delete' => false,
                'Moderate' => false,
                'View' => true,
            ];

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
                    $perms['Apply'] = true;
                    $perms['View'] = false;
                    $perms['View.Reason'] = t('Join this group to view its content.');

                    // Secret groups basically have the same permissions as non-public groups with some minor tweaks.
                    if ($group['Privacy'] === 'Secret') {
                        $perms['Access'] = false;
                        $perms['Apply'] = false;
                    }
                }
            }

            if ($groupApplicant) {
                $perms['Apply'] = false; // Already applied or banned.
                $perms['Join'] = false; // Already applied or banned.
                switch (strtolower($groupApplicant['Type'])) {
                    case 'application':
                        $perms['Join.Reason'] = t("You've applied to join this group.");
                        $perms['Apply.Reason'] = t("You've applied to join this group.");
                        break;
                    case 'denied':
                        $perms['Join.Reason'] = t("You're application for this group was denied.", 'Your application for this group was denied.');
                        $perms['Apply.Reason'] = t("You're application for this group was denied.", 'Your application for this group was denied.');
                        break;
                    case 'ban':
                        $perms['Join.Reason'] = t("You're banned from joining this group.");
                        $perms['Apply.Reason'] = t("You're banned from joining this group.");
                        break;
                    case 'invitation':
                        $perms['Access'] = true;
                        $perms['Apply.Reason'] = t('You have a pending invitation to join this group.');
                        $perms['Join'] = true;
                        unset($perms['Join.Reason']);
                        break;
                }
            }

            // Moderators can view and edit all groups.
            $canManage = $this->isModerator();

            if ($userID == Gdn::session()->UserID && $canManage) {
                $managerOverrides = [
                    'Access' => true,
                    'Delete' => true,
                    'Edit' => true,
                    'Leader' => true,
                    'Moderate' => true,
                    'View' => true,
                ];

                unset($perms['View.Reason']);
                $perms = array_merge($perms, $managerOverrides);
            }

            if ($useCache) {
                self::$permissions[$key] = $perms;
            }
        } else {
            $perms = self::$permissions[$key];
        }

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
     * Reset the cached grouped permissions.
     */
    public function resetCachedPermissions() {
        self::$permissions = [];
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
     * Delete an invitation.
     *
     * @param int $groupID
     * @param int $userID
     */
    public function deleteInvites($groupID, $userID) {
        $this->SQL->delete('GroupApplicant', ['GroupID' => $groupID, 'UserID' => $userID, 'Type' => 'Invitation']);
    }

    /**
     * Determine if the current user is a Groups global moderator.
     *
     * @return bool
     */
    public function isModerator(): bool {
        $result = Gdn::session()->checkPermission([
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Groups.Moderation.Manage'
        ], false);
        return $result;
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
     * Get group applicants/invites.
     *
     * @param int $groupID
     * @param array $where
     * @param int|bool $limit
     * @param int|bool $offset
     * @param bool $joinUserData
     * @return array
     * @throws Exception
     */
    public function getApplicants($groupID, $where = [], $limit = false, $offset = false, $joinUserData = true) {
        $applications = $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted', 'desc')
            ->limit($limit, $offset)
            ->get()->resultArray();

        if ($joinUserData) {
            Gdn::userModel()->joinUsers($applications, ['UserID']);
        }

        return $applications;
    }

    /**
     * Get group applicants/invites count.
     *
     * @param int $groupID
     * @param array $where
     * @return int
     */
    public function getApplicantsCount($groupID, $where = []) {
        return $this->SQL
            ->from('GroupApplicant')
            ->where('GroupID', $groupID)
            ->where($where)
            ->getCount();
    }

    /**
     * Get the userIDs of the applicants to a group.
     *
     * @param int $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @return array
     */
    public function getApplicantIds($groupID, $where = [], $limit = false, $offset = false) {
        // First grab the members.
        $users = $this->SQL
            ->select('UserID')
            ->from('GroupApplicant')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
            ->get()->resultArray();

        return array_column($users, 'UserID');
    }

    /**
     * Get a group by its ID.
     *
     * @param int $id The ID of the group.
     * @param string $dataSetType The type of return.
     * @param array $options Base class compatibility.
     * @return array|mixed|object
     */
    public function getID($id, $dataSetType = DATASET_TYPE_ARRAY, $options = []) {
        if (!ctype_digit((string)$id)) {
            deprecated('getID($slug)', 'getID(GroupModel::idFromSlug($slug))');
            $id = self::idFromSlug($id);
        }

        return parent::getID($id, $dataSetType, $options);
    }

    /**
     * Get member information from a group.
     *
     * @param int $groupID
     * @param int $userID
     * @return array|false The member data on success or false otherwise.
     */
    public function getMember($groupID, $userID) {
        $user = false;

        $users = $this->getMembers($groupID, ['UserID' => $userID], false, false, false);
        if (count($users)) {
            $user = array_pop($users);
        }

        return $user;
    }

    /**
     * Get the userIDs of the members of a group.
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
            ->select('UserID')
            ->from('UserGroup')
            ->where('GroupID', $groupID)
            ->where($where)
            ->orderBy('DateInserted')
            ->limit($limit, $offset)
            ->get()->resultArray();

        return array_column($users, 'UserID');
    }

    /**
     * Get members information from a group.
     *
     * @param int $groupID
     * @param array $where
     * @param bool $limit
     * @param bool $offset
     * @param bool $joinUserData
     * @return array
     * @throws Exception
     */
    public function getMembers($groupID, $where = [], $limit = false, $offset = false, $joinUserData = true) {
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

        if ($joinUserData) {
            Gdn::userModel()->joinUsers($users, ['UserID']);
        }
        return $users;
    }

    /**
     * Get the groupIDs that a user is a member of.
     *
     * @param int $userID
     * @return array An array of groupID
     */
    public function getUserGroupIDs($userID) {
        $userGroups = $this->SQL->getWhere('UserGroup', ['UserID' => $userID])->resultArray();
        return array_column($userGroups, 'GroupID');
    }

    /**
     * Get the groupID from a group slug.
     *
     * @param string $slug
     * @return int|false
     */
    public static function idFromSlug($slug) {
        $id = false;
        if (preg_match('/(\d+)-.*/', $slug, $matches)) {
            $id = (int)$matches[1];
        }

        return $id;
    }

    /**
     * Check if a User is a member of a Group.
     *
     * @param integer $userID
     * @param integer $groupID
     * @return bool
     */
    public function isMember($userID, $groupID) {
        $memberCount = $this->SQL->getCount('UserGroup', [
            'UserID' => $userID,
            'GroupID' => $groupID
        ]);
        return $memberCount > 0;
    }

    /**
     * Tells whether a user is being invited to a group or not.
     *
     * @param int $groupID
     * @param int $userID
     * @return int|false The invitationID or false.
     */
    public function isUserInvited($groupID, $userID) {
        $result = $this->SQL
            ->select('GroupApplicantID')
            ->from('GroupApplicant')
            ->where([
                'GroupID' => $groupID,
                'UserID' => $userID,
                'Type' => 'Invitation',
            ])
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        return $result ? (int)$result['GroupApplicantID'] : false;
    }

    /**
     * Invite users to join a group.
     * Application from a user, if there was one, will be overridden by the invitation.
     *
     * @param int $groupID
     * @param array $userIDs
     * @throws Gdn_UserException
     * @return bool
     */
    public function inviteUsers($groupID, array $userIDs) {
        $validUserIDs = [];

        foreach ($userIDs as $userID) {
            // Make sure the user hasn't already been invited.
            $application = $this->SQL->getWhere('GroupApplicant', [
                'GroupID' => $groupID,
                'UserID' => $userID
            ])->firstRow(DATASET_TYPE_ARRAY);

            if ($application) {
                if ($application['Type'] == 'Invitation') {
                    continue;
                } else {
                    $this->SQL->put('GroupApplicant',
                        ['Type' => 'Invitation'],
                        [
                            'GroupID' => $groupID,
                            'UserID' => $userID
                        ]
                    );
                }
            } else {
                $model = new Gdn_Model('GroupApplicant');
                $model->options('Ignore', true)->insert([
                    'GroupID' => $groupID,
                    'UserID' => $userID,
                    'Type' => 'Invitation',
                ]);
                foreach ($model->Validation->results() as $key => $value) {
                    $this->Validation->addValidationResult($key, $value);
                }
            }
            $validUserIDs[] = $userID;
        }

        if (count($validUserIDs) > 0) {
            $group = $this->getID($groupID);

            // Send a message for the invite.
            if (class_exists('ConversationModel')) {
                $model = new ConversationModel();

                $groupPrivacy = $group['Privacy'] ?? null;
                $groupURL = ($groupPrivacy == 'Secret') ? '/groups' : groupUrl($group);

                $args = [
                    'Name' => htmlspecialchars($group['Name']),
                    'Url' => $groupURL,

                ];

                $row = [
                    'Subject' => formatString(t('Please join my group.'), $args),
                    'Body' => formatString(t("You've been invited to join {Name}."), $args),
                    'Format' => 'Html',
                    'RecipientUserID' => $validUserIDs,
                    'Type' => 'invite',
                    'RegardingID' => $group['GroupID'],
                ];

                $options = [
                    'Url' => $groupURL,
                    'ActionText' => 'Join',
                ];

                if (!$model->save($row, [], $options)) {
                    throw new Gdn_UserException($model->Validation->resultsText());
                }
            } else {
                // If Conversations are disabled; Improve notification with a link to group.
                if (!class_exists('ConversationModel')) {
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
                            'Notified' => ActivityModel::SENT_PENDING,
                            'Data' => ['Name' => $group['Name']]
                        ];
                        $activityModel = new ActivityModel();
                        $activityModel->save($activity);
                    }
                }
            }
        }

        return count($this->validationResults()) == 0;
    }

    /**
     * Make user join a public group or a private group if he has been invited.
     *
     * @throws Exception
     * @param int $groupID The group ID you want to join.
     * @param int $userID The user that will join the group. Temporarily defaults to null until we remove deprecatedJoin();
     * @return bool
     */
    public function join($groupID, $userID = null) {
        if (is_array($groupID)) {
            deprecated('join($data)', 'join($groupID, $userID)');
            return $this->deprecatedJoin($groupID);
        } else if (!filter_var($userID, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException('$userID must be set.');
        }

        $group = $this->getID($groupID);

        if ($group['Privacy'] !== 'Public' || $group['Registration'] !== 'Public') {
            if ($invitationID = $this->isUserInvited($groupID, $userID)) {
                $this->deleteInvites($groupID, $userID);
            } else {
                throw new Gdn_UserException('A group must be public or you have to be invited to join it.');
            }
        }

        return $this->addUser($groupID, $userID, 'Member');
    }

    /**
     * @deprecated
     *
     * @param string $slug
     * @return mixed
     */
    public static function parseID($slug) {
        deprecated(__FUNCTION__, 'idFromSlug');
        $id = self::idFromSlug($slug);
        if (!$id) {
            $id = explode('-', $slug, 2)[0];
        }
        return $id;
    }

    /**
     * Approve or deny an application.
     * When state === 'Approved' the user will be added to the group the application will be deleted.
     * When state === 'Denied' the application type will be changed to denied.
     *
     * @throws Exception
     * @param int $groupID
     * @param int $userID
     * @param bool $isApproved
     * @return bool
     */
    public function processApplicant($groupID, $userID, $isApproved) {
        $success = false;

        $row = $this->SQL->getWhere('GroupApplicant', ['GroupID' => $groupID, 'UserID' => $userID])->firstRow(DATASET_TYPE_ARRAY);
        if (!$row) {
            throw notFoundException('Applicant');
        }

        if ($isApproved) {
            // Add the user to the group.
            $model = new Gdn_Model('UserGroup');
            $inserted = $model->insert([
                'GroupID' => $groupID,
                'UserID' => $userID,
                'Role' => 'Member',
            ]);
            foreach ($model->Validation->results() as $key => $value) {
                $this->Validation->addValidationResult($key, $value);
            }

            if ($inserted) {
                $this->updateCount($groupID, 'CountMembers');
                $this->SQL->delete('GroupApplicant', ['GroupID' => $groupID, 'UserID' => $userID]);
                $success = true;
            }
        } else {
            $model = new Gdn_Model('GroupApplicant');
            $success = (bool)$model->update(['Type' => 'Denied'], ['GroupID' => $groupID, 'UserID' => $userID]);
        }

        return $success;
    }

    /**
     * Remove a member from a group.
     * Optionally the user can be "Banned" or "Denied" which will prevent him from becoming an applicant to the group.
     *
     * @param $groupID
     * @param $userID
     * @param string|null $persistState Enum['Banned', 'Denied']
     */
    public function removeMember($groupID, $userID, $persistState = null) {
        // Remove the member.
        $this->SQL->delete('UserGroup', ['GroupID' => $groupID, 'UserID' => $userID]);

        // If the user was banned then let's add the ban.
        if (in_array($persistState, ['Banned', 'Denied'])) {
            $model = new Gdn_Model('GroupApplicant');
            $model->delete(['GroupID' => $groupID, 'UserID' => $userID]);
            $model->insert([
                'GroupID' => $groupID,
                'UserID' => $userID,
                'Type' => $persistState
            ]);
        }

        $this->updateCount($groupID, 'CountMembers');
    }

    /**
     * Set a user role.
     *
     * @param int $groupID
     * @param int $userID
     * @param string $role Enum['Member', 'Leader']
     */
    public function setMemberRole($groupID, $userID, $role) {
        $this->SQL->put('UserGroup', ['Role' => $role], ['UserID' => $userID, 'GroupID' => $groupID]);
    }

    ####################################
    ## Non refactored section.
    ####################################

    /**
     * @deprecated Use join($groupID) or apply($groupID, $reason)
     *
     * @param $data
     * @throws Gdn_UserException
     * @return bool
     */
    private function deprecatedJoin($data) {
        $valid = $this->validateJoin($data);
        if (!$valid) {
            return false;
        }

        $group = $this->getID(val('GroupID', $data));
        trace($group, 'Group');

        switch (strtolower($group['Registration'])) {
            case 'public':
                // This is a public group, go ahead and add the user.
                $data['Role'] = 'Member';
                $model = new Gdn_Model('UserGroup');
                $model->insert($data);
                foreach ($model->Validation->results() as $key => $value) {
                    $this->Validation->addValidationResult($key, $value);
                }
                $this->updateCount($group['GroupID'], 'CountMembers');
                return count($this->validationResults()) == 0;

            case 'approval':
                // The user must apply to this group.
                $data['Type'] = 'Application';
                $model = new Gdn_Model('GroupApplicant');
                $model->insert($data);
                foreach ($model->Validation->results() as $key => $value) {
                    $this->Validation->addValidationResult($key, $value);
                }
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
     * Get a list of the groups a user is invited to.
     *
     * @param $userID
     * @param string $orderFields
     * @param string $orderDirection
     * @param int $limit
     * @param bool $offset
     * @return Gdn_DataSet
     * @throws Exception
     */
    public function getInvites($userID, $orderFields = '', $orderDirection = 'desc', $limit = 9, $offset = false) {
        $ids = $this->SQL
            ->select('GroupID')
            ->from('GroupApplicant')
            ->where([
                'UserID' => $userID,
                'Type' => 'Invitation',
            ])
            ->orderBy('DateInserted')
            ->limit(100) // protect against weird data
            ->get()->resultArray();
        $ids = array_column($ids, 'GroupID');

        $result = $this->getWhere(['GroupID' => $ids], $orderFields, $orderDirection, $limit, $offset);
        $result->datasetType(DATASET_TYPE_ARRAY);
        $this->calc($result->resultArray());
        return $result->resultArray();
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
     * Invite users.
     *
     * @deprecated
     *
     * @param $data
     * @return bool
     * @throws Gdn_UserException
     */
    public function invite($data) {
        deprecated('invite', 'inviteUsers');
        $valid = $this->validateJoin($data);
        if (!$valid) {
            return false;
        }

        $groupID = val('GroupID', $data);
        $userIDs = (array)val('UserID', $data);

        return $this->inviteUsers($groupID, $userIDs);
    }

    /**
     * Make a user leave a group.
     *
     * @deprecated
     *
     * @param array $data
     */
    public function leave($data) {
        deprecated('leave($data)', 'removeMember($groupID, $userID)');
        $groupID = val('GroupID', $data);
        $userID = val('UserID', $data);

        $this->removeMember($groupID, $userID);
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
            throw notFoundException('Invitation');
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
     * @deprecated
     *
     * @param array $data
     * @return bool
     */
    public function joinApprove($data) {
        deprecated('joinApprove', 'processApplicant');

        // Grab the applicant row.
        $iD = $data['GroupApplicantID'];
        $row = $this->SQL->getWhere('GroupApplicant', ['GroupApplicantID' => $iD])->firstRow(DATASET_TYPE_ARRAY);
        if (!$row) {
            throw notFoundException('Applicant');
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
                'Role' => 'Member',
            ]);
            foreach ($model->Validation->results() as $key => $value) {
                $this->Validation->addValidationResult($key, $value);
            }

            if ($inserted) {
                $this->updateCount($row['GroupID'], 'CountMembers');
                $this->SQL->delete('GroupApplicant', ['GroupApplicantID' => $iD]);
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
                    'Type' => $value,
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
    public function joinRecentPosts(&$data, $joinUsers = true) {
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

        $discussions = [];
        // Grab the discussions.
        if (count($discussionIDs) > 0) {
            $discussions = $sql->whereIn('DiscussionID', $discussionIDs)->get('Discussion')->resultArray();
            $discussions = Gdn_DataSet::index($discussions, ['DiscussionID']);
        }

        $comments = [];

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
        } else {
            // Wipe the user permissions.
            Gdn::session()->setPermission('Vanilla.Discussions.View', false);
            // Wipe the per category permissions.
            Gdn::session()->setPermission('Vanilla.Discussions.View', []);
        }

        if ($this->checkPermission('Member', $group) || $this->checkPermission('Moderate', $group)) {
            Gdn::session()->setPermission('Vanilla.Discussions.Add', [$categoryID]);
            Gdn::session()->setPermission('Vanilla.Comments.Add', [$categoryID]);
        } else {
            // Wipe the user permissions.
            Gdn::session()->setPermission('Vanilla.Discussions.Add', false);
            Gdn::session()->setPermission('Vanilla.Comments.Add', false);
            // Wipe the per category permissions.
            Gdn::session()->setPermission('Vanilla.Discussions.Add', []);
            Gdn::session()->setPermission('Vanilla.Comments.Add', []);
        }
    }

    /**
     *
     *
     * @param array $data
     * @param bool $settings
     * @return int|false
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

        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $primaryKeyVal = val($this->PrimaryKey, $data, false);
        $insert = $primaryKeyVal == false ? true : false;

        // Set the visibility and registration based on the privacy. If no option is chosen and this is a new group,
        // use the public settings.
        $privacy = strtolower(val('Privacy', $data));
        switch (true) {
            case ($privacy === 'private'):
                $data['Visibility'] = 'Members';
                $data['Registration'] = 'Approval';
                break;
            case ($privacy === 'secret'):
                $data['Visibility'] = 'Members';
                $data['Registration'] = 'Invite';
                break;
            case ($privacy === 'public'):
            case $insert:
                $data['Visibility'] = 'Public';
                $data['Registration'] = 'Public';
                break;
        }

        if ($insert) {
            if (!isset($data['CategoryID'])) {
                $categories = self::getGroupCategoryIDs();
                if (count($categories)) {
                    $data['CategoryID'] = $categories[0];
                }
            }

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
                foreach ($model->Validation->results() as $key => $value) {
                    $this->Validation->addValidationResult($key, $value);
                }
            }
            $this->updateCount($groupID, 'CountMembers');
            $this->getCount(null); // clear cache.
        }
        return $groupID;
    }

    /**
     * Set a user role.
     *
     * @param int $groupID
     * @param int $userID
     * @param string $role Enum['Member', 'Leader']
     */
    public function setRole($groupID, $userID, $role) {
        deprecated('setRole', 'setMemberRole');
        $this->setMemberRole($groupID, $userID, $role);
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
        if (trim(val('Name', $formPostValues))) {
            $rows = $this->SQL->getWhere('Group', ['Name' => $formPostValues['Name']])->resultArray();

            $groupID = val('GroupID', $formPostValues);
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
     * Retrieves group category IDs.
     *
     * @return array An array of category IDs.
     */
    public static function getGroupCategoryIDs() {
        $groupCategoryIDs = Gdn::cache()->get('GroupCategoryIDs');
        if ($groupCategoryIDs === Gdn_Cache::CACHEOP_FAILURE) {
            $categoryModel = new CategoryModel();
            $groupCategories = $categoryModel->getWhere(['AllowGroups' => 1])->resultArray();
            $groupCategoryIDs = [];
            foreach ($groupCategories as $groupCategory) {
                $groupCategoryIDs[] = $groupCategory['CategoryID'];
            }

            Gdn::cache()->store('GroupCategoryIDs', $groupCategoryIDs);
        }
        return $groupCategoryIDs;
    }

    /**
     * Search groups by name.
     *
     * @param string $name
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws Exception
     */
    public function searchByName(string $name, string $orderField = null, string $orderDirection = null, int $limit = self::LIMIT, int $offset = 0): array {
        $result = [];
        $memberID = Gdn::session()->UserID ?: null;
        $isModerator = $this->isModerator() ?: null;

        if ($name) {
            $orderField = $orderField ?: 'Name';
            $orderDirection = $orderDirection ?: 'asc';

            $groupIDs = $this->getUserGroupIDs($memberID);

            $fullMatch = $this->SQL->conditionExpr('g.Name', $name, false);

            // User is a moderator, display all groups.
            if ($isModerator) {
                //Get all the groups
                $result = $this->SQL
                    ->select('g.*')
                    ->select($fullMatch, '', 'FullMatch')
                    ->from('Group g')
                    ->like('Name', $name)
                    ->orderBy('FullMatch', 'desc')
                    ->orderBy($orderField, $orderDirection)
                    ->limit($limit, $offset)
                    ->get()
                    ->resultArray();
            } else {
                // User is not a moderator, display all groups, except secret group member is not part of.
                if (!empty($groupIDs)) {
                    $result = $this->SQL
                        ->select('g.*')
                        ->select($fullMatch, '', 'FullMatch')
                        ->from('Group g')
                        ->like('Name', $name)
                        ->beginWhereGroup()
                        ->whereIn('Privacy', ['Public', 'Private'])
                        ->orWhereIn('GroupId', $groupIDs)
                        ->endWhereGroup()
                        ->orderBy('FullMatch', 'desc')
                        ->orderBy($orderField, $orderDirection)
                        ->limit($limit, $offset)
                        ->get()
                        ->resultArray();
                } else {
                    $result = $this->SQL
                        ->select('g.*')
                        ->select($fullMatch, '', 'FullMatch')
                        ->from('Group g')
                        ->like('Name', $name)
                        ->whereIn('Privacy', ['Public', 'Private'])
                        ->orderBy('FullMatch', 'desc')
                        ->orderBy($orderField, $orderDirection)
                        ->limit($limit, $offset)
                        ->get()
                        ->resultArray();
                }
            }
        }
        return $result;
    }

    /**
     * Get total number of groups that match the search..
     *
     * @param string $name
     * @return int
     * @throws Exception
     */
    public function searchTotal(string $name): int {
        $total = 0;

        if ($name) {
            $total = $this->SQL
                ->from('Group')
                ->like('Name', $name, 'right')
                ->getCount();
        }

        return $total;
    }

}
