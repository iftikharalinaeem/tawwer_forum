<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class RankModel
 */
class RankModel extends Gdn_Model {

    /** @var null|array  */
    protected static $_Ranks = null;

    /**
     * RankModel constructor.
     */
    public function __construct() {
        parent::__construct('Rank');
    }

    /**
     * Check a user & apply their appropriate rank.
     *
     * @param $User
     * @return array Key 'CurrentRank' with value of set RankID.
     */
    public function applyRank($User) {
        if (is_numeric($User)) {
            $User = Gdn::userModel()->getID($User, DATASET_TYPE_ARRAY);
        }
        $User = (array)$User;

        $CurrentRankID = val('RankID', $User);
        $Result = ['CurrentRank' => $CurrentRankID ? self::ranks($CurrentRankID) : null];

        $Ranks = self::ranks();

        // Check the ranks backwards so we know which rank to apply.
        $Ranks = array_reverse($Ranks);
        foreach ($Ranks as $Rank) {
            if (self::testRank($User, $Rank)) {
                $RankID = $Rank['RankID'];
                $Result['NewRank'] = $Rank;
                break;
            }
        }

        if (!isset($RankID)) {
            return $Result;
        }
        if (isset($RankID) && $RankID == $CurrentRankID) {
            return $Result;
        }

        // Apply the rank.
        $UserID = val('UserID', $User);
        Gdn::userModel()->setField($UserID, 'RankID', $RankID);

        // Notify the user?
        $Notify = $Rank['Level'] > 1;
        if (!isset($Result['NewRank']) || ($Result['CurrentRank'] && $Result['NewRank']['Level'] < $Result['CurrentRank']['Level'])) {
            $Notify = false;
        }
        if ($Notify) {
            $this->notify($User, $Rank);
        }

        return $Result;
    }

    /**
     * Tell a user about their new rank.
     *
     * @param $User
     * @param $Rank
     * @throws Exception
     */
    public function notify($User, $Rank) {
        $UserID = val('UserID', $User);
        $RankID = $Rank['RankID'];

        // Notify people of the rank.
        $Activity = [
                'ActivityType' => 'Rank',
                'ActivityUserID' => $UserID,
                'NotifyUserID' => $UserID,
                'HeadlineFormat' => t('Ranks.NotificationFormat', 'Congratulations! You\'ve been promoted to {Data.Name,plaintext}.'),
                'Story' => val('Body', $Rank),
                'RecordType' => 'Rank',
                'RecordID' => $RankID,
                'Route' => "/profile",
                'Emailed' => ActivityModel::SENT_PENDING,
                'Notified' => ActivityModel::SENT_PENDING,
                'Photo' => 'https://c3409409.ssl.cf0.rackcdn.com/images/ranks_100.png',
                'Data' => ['Name' => $Rank['Name'], 'Label' => $Rank['Label']]
        ];

        $ActivityModel = new ActivityModel();
        $ActivityModel->queue($Activity, false, ['Force' => true]);

        // Notify everyone else of your badge.
        $Activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
        $Activity['HeadlineFormat'] = t('Ranks.ActivityFormat', '{ActivityUserID,user} {ActivityUserID,plural,was,were} promoted to {Data.Name,plaintext}.');
        $Activity['Emailed'] = ActivityModel::SENT_OK;
        $Activity['Popup'] = ActivityModel::SENT_OK;
        unset($Activity['Route']);
        $ActivityModel->queue($Activity, false, ['GroupBy' => ['ActivityTypeID', 'RecordID', 'RecordType']]);

        $ActivityModel->saveQueue();
    }

    /**
     * Return an HTML summary of a rank's abilities.
     *
     * @param $Rank
     * @return mixed|string
     */
    public static function abilitiesString($Rank) {
        $Abilities = val('Abilities', $Rank);
        $Result = [];

        self::abilityString($Abilities, 'DiscussionsAdd', 'Add Discussions', $Result);
        self::abilityString($Abilities, 'CommentsAdd', 'Add Comments', $Result);
        self::abilityString($Abilities, 'ConversationsAdd', 'Start Private Conversations', $Result);
        self::abilityString($Abilities, 'Verified', 'Verified', $Result);

        $V = val('Format', $Abilities);
        if ($V) {
            $V = strtolower($V);
            if ($V == 'textex') {
                $V = 'text, links, youtube';
            }
            $Result[] = '<b>Post Format</b>: '.$V;
        }

        self::abilityString($Abilities, 'ActivityLinks', 'Activity Links', $Result);
        self::abilityString($Abilities, 'CommentLinks', 'Discussion & Comment Links', $Result);
        self::abilityString($Abilities, 'ConversationLinks', 'Conversation Links', $Result);

        self::abilityString($Abilities, 'Titles', 'Titles', $Result);
        self::abilityString($Abilities, 'Locations', 'Locations', $Result);
        self::abilityString($Abilities, 'Avatars', 'Avatars', $Result);
        self::abilityString($Abilities, 'Signatures', 'Signatures', $Result);

        if ($V = val('SignatureMaxNumberImages', $Abilities)) {
            $Result[] = '<b>Max number of images in signature</b>: '.$V;
        }

        if ($V = val('SignatureMaxLength', $Abilities)) {
            $Result[] = '<b>Max number of characters in signature</b>: '.$V;
        }

        self::abilityString($Abilities, 'Polls', 'Polls', $Result);
        self::abilityString($Abilities, 'MeAction', 'Me Actions', $Result);
        self::abilityString($Abilities, 'Curation', 'Content Curation', $Result);

        $V = val('EditContentTimeout', $Abilities, '');
        if ($V !== '') {
            $Options = self::contentEditingOptions();
            $Result[] = '<b>'.t('Editing').'</b>: '.val($V, $Options);
        }

        if ($v = val('PermissionRole', $Abilities)) {
             $role = RoleModel::roles($v);
             if ($role) {
                  $Result[] = '<b>Role Permissions</b>: '.htmlspecialchars($role['Name']);
             }
        }

        if (count($Result) == 0) {
            return '';
        } elseif (count($Result) == 1) {
            return array_pop($Result);
        } else {
            return '<ul BulletList><li>'.implode('</li><li>', $Result).'</li></ul>';
        }
    }

    /**
     * Add the status of non-default abilities to $Result.
     *
     * @param array $Abilities Key is ability name, value is yes, no, or empty (default).
     * @param string $Value Name of the ability.
     * @param string $String What we're call in the ability in the UI.
     * @param array $Result Store the HTML output for this line.
     */
    public static function abilityString($Abilities, $Value, $String, &$Result) {
        $V = val($Value, $Abilities);

        if ($V === 'yes') {
            $Result[] = '<b>Add</b>: '.$String;
        } elseif ($V === 'no') {
            $Result[] = '<b>Remove</b>: '.$String;
        }
    }

    /**
     * Set permissions, configs, or properties depending on each rank abilities.
     *
     * The default (empty string) option will fail each initial if(GetValue) check and skip it entirely.
     * All config changes are set in memory only.
     *
     * @throws Exception
     */
    public static function applyAbilities() {
        $Session = Gdn::session();
        if (!$Session->User) {
            return;
        }

        $RanksPlugin = Gdn::pluginManager()->getPluginInstance('RanksPlugin');

        $Rank = self::ranks(GetValue('RankID', $Session->User, false));
        if (!$Rank) {
            return;
        }

        $Abilities = val('Abilities', $Rank, []);

        // Post discussions.
        if ($V = val('DiscussionsAdd', $Abilities)) {
            if ($V == 'no') {
                $Session->setPermission('Vanilla.Discussions.Add', []);
            }
        }

        // Add comments.
        if ($V = val('CommentsAdd', $Abilities)) {
            if ($V == 'no') {
                $Session->setPermission('Vanilla.Comments.Add', []);
            }
        }

        // Add conversations.
        if ($V = val('ConversationsAdd', $Abilities)) {
            $Session->setPermission('Conversations.Conversations.Add', $V == 'yes' ? true : false);
        }

        // Verified.
        if ($V = val('Verified', $Abilities)) {
            $Verified = ['yes' => 1, 'no'  => 0];
            $Verified = val($V, $Verified, null);
            if (is_integer($Verified)) {
                $Session->User->Verified = $Verified;
            }
        }

        // Post Format.
        if ($V = val('Format', $Abilities)) {
            saveToConfig([
                'Garden.InputFormatter' => $V,
                'Garden.InputFormatterBak' => c('Garden.InputFormatter'),
                'Garden.ForceInputFormatter' => true
            ], null, false);
        }

        // Titles.
        if ($V = val('Titles', $Abilities)) {
            saveToConfig('Garden.Profile.Titles', $V == 'yes' ? true : false, false);
        }

        // Locations.
        if ($V = val('Locations', $Abilities)) {
            saveToConfig('Garden.Profile.Locations', $V == 'yes' ? true : false, false);
        }

        // Avatars.
        if ($V = val('Avatars', $Abilities)) {
            saveToConfig('Garden.Profile.EditPhotos', $V == 'yes' ? true : false, false);
        }

        // Signatures.
        if ($V = val('Signatures', $Abilities)) {
            $Session->setPermission('Plugins.Signatures.Edit', $V == 'yes' ? true : false);
        }

        if ($V = val('SignatureMaxNumberImages', $Abilities)) {
            saveToConfig('Plugins.Signatures.MaxNumberImages', $V, false);
        }

        if ($V = val('SignatureMaxLength', $Abilities)) {
            saveToConfig('Plugins.Signatures.MaxLength', $V, false);
        }

        // Polls.
        if ($V = val('Polls', $Abilities)) {
            $Session->setPermission('Plugins.Polls.Add', $V == 'yes' ? true : false);
        }

        // MeActions.
        if ($V = val('MeAction', $Abilities)) {
            $Session->setPermission('Vanilla.Comments.Me', $V == 'yes' ? true : false);
        }

        /// Content curation.
        if ($V = val('Curation', $Abilities)) {
            $Session->setPermission('Garden.Curation.Manage', $V == 'yes' ? true : false);
        }

        // Links.
        $RanksPlugin->ActivityLinks = val('ActivityLinks', $Abilities);
        $RanksPlugin->CommentLinks = val('CommentLinks', $Abilities);
        $RanksPlugin->ConversationLinks = val('ConversationLinks', $Abilities);


        // Edit content timeout.
        if (($V = val('EditContentTimeout', $Abilities, false)) !== false) {
            saveToConfig('Garden.EditContentTimeout', $V, false);
        }

         $permissionRole = val('PermissionRole', $Abilities);
         if ($permissionRole) {
              $rankPermissions = Gdn::permissionModel()->getPermissionsByRole($permissionRole);
              Gdn::session()->addPermissions($rankPermissions);
         }
    }

    /**
     *
     *
     * @param $Data
     */
    public function calculate(&$Data) {
        if (is_array($Data) && isset($Data[0])) {
            // Multiple badges
            foreach ($Data as &$B) {
                $this->_calculate($B);
            }
        } elseif ($Data) {
            // One valid result
            $this->_calculate($Data);
        }
    }

    /**
     * Get HTML describing rank criteria.
     *
     * @param $Rank
     * @return mixed|string
     */
    public static function criteriaString($Rank) {
        $Criteria = val('Criteria', $Rank);
        $Result = array();

        if ($V = val('Points', $Criteria)) {
            $Result[] = plural($V, '%s point', '%s points');
        }

        if ($V = val('Time', $Criteria)) {
            $Result[] = sprintf(t('member for %s'), $V);
        }

        if ($V = val('CountPosts', $Criteria)) {
            $Result[] = plural($V, '%s post', '%s posts');
        }

        if ($V = val('Role', $Criteria)) {
            $Result[] = sprintf(t('Must have role of %s'), $V);
        }

        if (isset($Criteria['Permission'])) {
            $Permissions = (array)$Criteria['Permission'];
            foreach ($Permissions as $Permission) {
                switch ($Permission) {
                    case 'Garden.Moderation.Manage':
                        $Result[] = 'Must be a moderator';
                        break;
                    case 'Garden.Settings.Manage':
                        $Result[] = 'Must be an administrator';
                        break;
                }
            }
        }

        if ($V = val('Manual', $Criteria)) {
            $Result[] = t('Applied Manually');
        }

        if (sizeof($Result)) {
            if (count($Result) == 1) {
                return array_pop($Result);
            } else {
                return '<ul BulletList><li>'.implode('</li><li>', $Result).'</li></ul>';
            }
        } else {
            return '';
        }
    }

    /**
     *
     *
     * @param $Data
     */
    protected function _calculate(&$Data) {
        if (isset($Data['Attributes']) && !empty($Data['Attributes'])) {
            $Attributes = dbdecode($Data['Attributes']);
        } else {
            $Attributes = [];
        }

        unset($Data['Attributes']);
        $Data = array_merge($Data, $Attributes);
    }

    /**
     * Get options for post editing timeouts.
     *
     * @return array|null
     */
    public static function contentEditingOptions() {
        static $Options = null;

        if (!isset($Options)) {
            $Options = [
                '' => t('default'),
                '0' => t('Authors may never edit'),
                '350' => sprintf(t('Authors may edit for %s'), t('5 minutes')),
                '900' => sprintf(t('Authors may edit for %s'), t('15 minutes')),
                '3600' => sprintf(t('Authors may edit for %s'), t('1 hour')),
                '14400' => sprintf(t('Authors may edit for %s'), t('4 hours')),
                '86400' => sprintf(t('Authors may edit for %s'), t('1 day')),
                '604800' => sprintf(t('Authors may edit for %s'), t('1 week')),
                '2592000' => sprintf(t('Authors may edit for %s'), t('1 month')),
                '-1' => t('Authors may always edit')
            ];
        }

        return $Options;
    }

    /**
     * Get a selection of ranks.
     *
     * @param bool $Where
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return Gdn_DataSet
     */
    public function getWhere($Where = false, $OrderFields = 'Level', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $Result = parent::getWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
        $this->calculate($Result->resultArray());

        return $Result;
    }

    /**
     * Get all ranks data.
     *
     * This is what to (nearly always) use when you need a list of ranks.
     *
     * @param null|int $RankID
     * @return array|mixed|null
     */
    public static function ranks($RankID = null) {
        if (self::$_Ranks === null) {
            $M = new RankModel();
            $Ranks = $M->getWhere()->resultArray();
            $Ranks = Gdn_DataSet::index($Ranks, ['RankID']);
            self::$_Ranks = $Ranks;
        }

        if (!is_null($RankID) && !is_bool($RankID)) {
            return val($RankID, self::$_Ranks, null);
        } else {
            return self::$_Ranks;
        }
    }

    /**
     * Save a rank.
     *
     * @param array $Data Form post data.
     * @param bool|false $Settings Unused
     * @return bool
     */
    public function save($Data, $Settings = false) {
        // Put the data into a format that's savible.
        $this->defineSchema();
        $SchemaFields = $this->Schema->fields();

        $SaveData = [];
        $Attributes = [];

        foreach ($Data as $Name => $Value) {
            if ($Name == 'Attributes') {
                continue;
            }
            if (isset($SchemaFields[$Name])) {
                $SaveData[$Name] = $Value;
            } else {
                $Attributes[$Name] = $Value;
            }
        }
        if (sizeof($Attributes)) {
            $SaveData['Attributes'] = $Attributes;
        }

        // Grab the current rank.
        if (isset($SaveData['RankID'])) {
            $PrimaryKeyVal = $SaveData['RankID'];
            $CurrentRank = $this->SQL->getWhere('Rank', ['RankID' => $PrimaryKeyVal])->firstRow(DATASET_TYPE_ARRAY);
            if ($CurrentRank) {
                $Insert = false;
            } else {
                $Insert = true;
            }
        } else {
            $PrimaryKeyVal = false;
            $Insert = true;
        }

        // Validate the form posted values.
        if ($this->validate($SaveData, $Insert) === true) {
            $Fields = $this->Validation->validationFields();

            if ($Insert === false) {
                unset($Fields[$this->PrimaryKey]); // Don't try to update the primary key
                $this->update($Fields, [$this->PrimaryKey => $PrimaryKeyVal]);
            } else {
                $PrimaryKeyVal = $this->insert($Fields);
            }
        } else {
            $PrimaryKeyVal = false;
        }

        return $PrimaryKeyVal;
    }

    /**
     * Test whether or not a user is eligible for a rank.
     *
     * @param array|object $User
     * @param array $Rank
     * @return bool
     */
    public static function testRank($User, $Rank) {
        if (!isset($Rank['Criteria']) || !is_array($Rank['Criteria'])) {
            return true;
        }

        $Criteria = $Rank['Criteria'];
        // All criteria must apply so return false if any of the criteria doesn't match.
        $UserPoints = val('Points', $User);

        if (isset($Criteria['Points'])) {
            $PointsCriteria = $Criteria['Points'];

            if ($PointsCriteria >= 0 && $UserPoints < $PointsCriteria) {
                return false;
            } elseif ($PointsCriteria < 0 && $UserPoints > $PointsCriteria) {
                return false;
            }
        }

        if (isset($Criteria['Time'])) {
            $TimeFirstVisit = Gdn_Format::toTimestamp(val('DateFirstVisit', $User));
            $TimeCriteria = strtotime($Criteria['Time'], 0);

            if ($TimeCriteria && ($TimeFirstVisit + $TimeCriteria > time())) {
                return false;
            }
        }

        if (isset($Criteria['CountPosts'])) {
            $CountPosts = val('CountDiscussions', $User, 0) + val('CountComments', $User, 0);
            if ($CountPosts < $Criteria['CountPosts']) {
                return false;
            }
        }

        if ($Role = val('Role', $Criteria)) {
            $Roles = RoleModel::getByName($Role);
            $RankRoleID = key($Roles);

            $UserModel = Gdn::userModel();
            // TODO: Refactor: Make method GetRoleIDs in UserModel.
            $RoleData = $UserModel->getRoles(val('UserID', $User));
            $UserRoles = $RoleData->result(DATASET_TYPE_ARRAY);
            $UserRoles = array_column($UserRoles, 'RoleID');

            if (!in_array($RankRoleID, $UserRoles)) {
                return false;
            }
        }

        if (isset($Criteria['Permission'])) {
            $Permissions = (array)$Criteria['Permission'];
            foreach ($Permissions as $Perm) {
                if (!Gdn::userModel()->checkPermission($User, $Perm)) {
                    return false;
                }
            }
        }

        if ($V = val('Manual', $Criteria)) {
            if (val('RankID', $User) != $Rank['RankID']) {
                return false;
            }
        }

        return true;
    }
}
