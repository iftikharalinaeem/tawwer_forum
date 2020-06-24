<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Vanilla\Formatting\Formats\TextFormat;
/**
 * Class RankModel
 */
class RankModel extends Gdn_Model {

    /** @var null|array  */
    protected static $_Ranks = null;

    /**
     * @var array
     */
    private $allowedLinkHosts = [
        '*.v-cdn.net',
    ];

    /**
     * RankModel constructor.
     */
    public function __construct() {
        parent::__construct('Rank');
    }

    /**
     * Check a user & apply their appropriate rank.
     *
     * @param array|int $user array of user data or int user ID.
     * @return array Key 'CurrentRank' with value of set RankID.
     */
    public function applyRank($user) {
        if (is_numeric($user)) {
            $user = Gdn::userModel()->getID($user, DATASET_TYPE_ARRAY);
        }
        $user = (array)$user;

        $currentRankID = val('RankID', $user);
        $result = ['CurrentRank' => $currentRankID ? self::ranks($currentRankID) : null];

        $rankID = $this->determineUserRank($user);

        if ($rankID === null) {
            return $result;
        }

        $rank = self::ranks($rankID);
        $result['NewRank'] = $rank;

        if ($rankID == $currentRankID) {
            return $result;
        }

        // Apply the rank.
        $userID = val('UserID', $user);
        Gdn::userModel()->setField($userID, 'RankID', $rankID);

        // Notify the user?
        $notify = $rank['Level'] > 1;
        if (!isset($result['NewRank']) || ($result['CurrentRank'] && $result['NewRank']['Level'] < $result['CurrentRank']['Level'])) {
            $notify = false;
        }
        if ($notify) {
            $this->notify($user, $rank);
        }

        return $result;
    }

    /**
     * Given a user row, determine the proper rank for that user.
     *
     * @param array $user
     * @return int|null
     */
    public function determineUserRank(array $user) {
        $result = null;

        // Reverse sort ranks to ensure the highest match is first.
        $ranks = self::ranks();
        $ranks = array_reverse($ranks);

        foreach ($ranks as $rank) {
            if (self::testRank($user, $rank)) {
                $result = $rank['RankID'];
                break;
            }
        }

        return $result;
    }

    /**
     * Tell a user about their new rank.
     *
     * @param $user
     * @param $rank
     * @throws Exception
     */
    public function notify($user, $rank) {
        $userID = val('UserID', $user);
        $rankID = $rank['RankID'];

        // Notify people of the rank.
        $activity = [
                'ActivityType' => 'Rank',
                'ActivityUserID' => $userID,
                'NotifyUserID' => $userID,
                'HeadlineFormat' => t('Ranks.NotificationFormat', 'Congratulations! You\'ve been promoted to {Data.Name,plaintext}.'),
                'Story' => val('Body', $rank),
                'Format' => TextFormat::FORMAT_KEY,
                'RecordType' => 'Rank',
                'RecordID' => $rankID,
                'Route' => "/profile",
                'Photo' => 'https://images.v-cdn.net/ranks_100.png',
                'Data' => ['Name' => $rank['Name'], 'Label' => $rank['Label']]
        ];

        $activityModel = new ActivityModel();
        $activityModel->queue($activity, 'Rank', ['Force' => true]);

        // Notify everyone else of your badge.
        $activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
        $activity['HeadlineFormat'] = t('Ranks.ActivityFormat', '{ActivityUserID,user} {ActivityUserID,plural,was,were} promoted to {Data.Name,plaintext}.');
        $activity['Emailed'] = ActivityModel::SENT_OK;
        $activity['Popup'] = ActivityModel::SENT_OK;
        unset($activity['Route']);
        $activityModel->queue($activity, false, ['GroupBy' => ['ActivityTypeID', 'RecordID', 'RecordType']]);

        $activityModel->saveQueue();
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
     * Add the status of non-default abilities to $result.
     *
     * @param array $abilities Key is ability name, value is yes, no, or empty (default).
     * @param string $value Name of the ability.
     * @param string $string What we're call in the ability in the UI.
     * @param array $result Store the HTML output for this line.
     */
    public static function abilityString($abilities, $value, $string, &$result) {
        $v = val($value, $abilities);

        if ($v === 'yes') {
            $result[] = '<b>Add</b>: '.$string;
        } elseif ($v === 'no') {
            $result[] = '<b>Remove</b>: '.$string;
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
        $session = Gdn::session();
        if (!$session->User) {
            return;
        }

        $ranksPlugin = Gdn::pluginManager()->getPluginInstance('RanksPlugin');

        $rank = self::ranks(val('RankID', $session->User, false));
        if (!$rank) {
            return;
        }

        $abilities = val('Abilities', $rank, []);

        // Post discussions.
        if ($allowed = val('DiscussionsAdd', $abilities)) {
            if ($allowed === 'no') {
                $session->setPermission('Vanilla.Discussions.Add', []);
            }
        }

        // Add comments.
        if ($allowed = val('CommentsAdd', $abilities)) {
            if ($allowed === 'no') {
                $session->setPermission('Vanilla.Comments.Add', []);
            }
        }

        // Add conversations.
        if ($allowed = val('ConversationsAdd', $abilities)) {
            $session->setPermission('Conversations.Conversations.Add', ($allowed === 'yes'));
        }

        // Verified.
        if ($allowed = val('Verified', $abilities)) {
            $verified = ['yes' => 1, 'no'  => 0];
            $verified = val($allowed, $verified, null);
            if (is_integer($verified)) {
                $session->User->Verified = $verified;
            }
        }

        // Post Format.
        if ($allowed = val('Format', $abilities)) {
            saveToConfig([
                'Garden.InputFormatter' => $allowed,
                'Garden.InputFormatterBak' => c('Garden.InputFormatter'),
                'Garden.ForceInputFormatter' => true
            ], null, false);
        }

        // Titles.
        if ($allowed = val('Titles', $abilities)) {
            saveToConfig('Garden.Profile.Titles', ($allowed === 'yes'), false);
        }

        // Locations.
        if ($allowed = val('Locations', $abilities)) {
            saveToConfig('Garden.Profile.Locations', ($allowed === 'yes'), false);
        }

        // Avatars.
        if ($allowed = val('Avatars', $abilities)) {
            saveToConfig('Garden.Profile.EditPhotos', ($allowed === 'yes'), false);
        }

        // Signatures.
        if ($allowed = val('Signatures', $abilities)) {
            $session->setPermission('Plugins.Signatures.Edit', ($allowed === 'yes'));
        }

        if ($allowed = val('SignatureMaxNumberImages', $abilities)) {
            saveToConfig('Plugins.Signatures.MaxNumberImages', $allowed, false);
        }

        if ($allowed = val('SignatureMaxLength', $abilities)) {
            saveToConfig('Plugins.Signatures.MaxLength', $allowed, false);
        }

        // Polls.
        if ($allowed = val('Polls', $abilities)) {
            $session->setPermission('Plugins.Polls.Add', ($allowed === 'yes'));
        }

        // MeActions.
        if ($allowed = val('MeAction', $abilities)) {
            $session->setPermission('Vanilla.Comments.Me', ($allowed === 'yes'));
        }

        /// Content curation.
        if ($allowed = val('Curation', $abilities)) {
            $session->setPermission('Garden.Curation.Manage', ($allowed === 'yes'));
        }

        // Links.
        $ranksPlugin->ActivityLinks = val('ActivityLinks', $abilities);
        $ranksPlugin->CommentLinks = val('CommentLinks', $abilities);
        $ranksPlugin->ConversationLinks = val('ConversationLinks', $abilities);


        // Edit content timeout.
        if (($allowed = val('EditContentTimeout', $abilities, false)) !== false) {
            saveToConfig('Garden.EditContentTimeout', $allowed, false);
        }

         $permissionRole = val('PermissionRole', $abilities);
         if ($permissionRole) {
              $rankPermissions = Gdn::permissionModel()->getPermissionsByRole($permissionRole);
              Gdn::session()->addPermissions($rankPermissions);
         }
    }

    /**
     *
     *
     * @param $data
     */
    public function calculate(&$data) {
        if (is_array($data) && isset($data[0])) {
            // Multiple badges
            foreach ($data as &$b) {
                $this->_calculate($b);
            }
        } elseif ($data) {
            // One valid result
            $this->_calculate($data);
        }
    }

    /**
     * Get HTML describing rank criteria.
     *
     * @param $rank
     * @return mixed|string
     */
    public static function criteriaString($rank) {
        $criteria = val('Criteria', $rank);
        $result = [];

        if ($v = val('Points', $criteria)) {
            $result[] = plural($v, '%s point', '%s points');
        }

        if ($v = val('Time', $criteria)) {
            $result[] = sprintf(t('member for %s'), $v);
        }

        if ($v = val('CountPosts', $criteria)) {
            $result[] = plural($v, '%s post', '%s posts');
        }

        if ($v = val('Role', $criteria)) {
            $result[] = sprintf(t('Must have role of %s'), $v);
        }

        if (isset($criteria['Permission'])) {
            $permissions = (array)$criteria['Permission'];
            foreach ($permissions as $permission) {
                switch ($permission) {
                    case 'Garden.Moderation.Manage':
                        $result[] = 'Must be a moderator';
                        break;
                    case 'Garden.Settings.Manage':
                        $result[] = 'Must be an administrator';
                        break;
                }
            }
        }

        if ($v = val('Manual', $criteria)) {
            $result[] = t('Applied Manually');
        }

        if (sizeof($result)) {
            if (count($result) == 1) {
                return array_pop($result);
            } else {
                return '<ul BulletList><li>'.implode('</li><li>', $result).'</li></ul>';
            }
        } else {
            return '';
        }
    }

    /**
     *
     *
     * @param $data
     */
    protected function _calculate(&$data) {
        if (isset($data['Attributes']) && !empty($data['Attributes'])) {
            $attributes = dbdecode($data['Attributes']);
        } else {
            $attributes = [];
        }

        unset($data['Attributes']);
        $data = array_merge($data, $attributes);
    }

    /**
     * Get options for post editing timeouts.
     *
     * @return array|null
     */
    public static function contentEditingOptions() {
        static $options = null;

        if (!isset($options)) {
            $options = [
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

        return $options;
    }

    /**
     * Get a selection of ranks.
     *
     * @param bool $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool|int $limit
     * @param bool|int $offset
     * @return Gdn_DataSet
     */
    public function getWhere($where = false, $orderFields = 'Level', $orderDirection = 'asc', $limit = false, $offset = false) {
        $result = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        $this->calculate($result->resultArray());

        return $result;
    }

    /**
     * Get all ranks data.
     *
     * This is what to (nearly always) use when you need a list of ranks.
     *
     * @param null|int $rankID
     * @return array|mixed|null
     */
    public static function ranks($rankID = null) {
        if (self::$_Ranks === null) {
            static::refreshCache();
        }

        if (!is_null($rankID) && !is_bool($rankID)) {
            return val($rankID, self::$_Ranks, null);
        } else {
            return self::$_Ranks;
        }
    }

    /**
     * Refresh cache of ranks.
     *
     * @return array
     */
    public static function refreshCache() {
        $m = new RankModel();
        $ranks = $m->getWhere()->resultArray();
        $ranks = Gdn_DataSet::index($ranks, ['RankID']);
        self::$_Ranks = $ranks;
        return self::$_Ranks;
    }

    /**
     * Save a rank.
     *
     * @param array $data Form post data.
     * @param bool|false $settings Unused
     * @return bool
     */
    public function save($data, $settings = false) {
        // Put the data into a format that's savible.
        $this->defineSchema();
        $schemaFields = $this->Schema->fields();

        $saveData = [];
        $attributes = [];

        foreach ($data as $name => $value) {
            if ($name == 'Attributes') {
                continue;
            }
            if (isset($schemaFields[$name])) {
                $saveData[$name] = $value;
            } else {
                $attributes[$name] = $value;
            }
        }
        if (sizeof($attributes)) {
            $saveData['Attributes'] = $attributes;
        }

        // Grab the current rank.
        if (isset($saveData['RankID'])) {
            $primaryKeyVal = $saveData['RankID'];
            $currentRank = $this->SQL->getWhere('Rank', ['RankID' => $primaryKeyVal])->firstRow(DATASET_TYPE_ARRAY);
            if ($currentRank) {
                $insert = false;
            } else {
                $insert = true;
            }
        } else {
            $primaryKeyVal = false;
            $insert = true;
        }

        // Validate the form posted values.
        if ($this->validate($saveData, $insert) === true) {
            $fields = $this->Validation->validationFields();

            if ($insert === false) {
                unset($fields[$this->PrimaryKey]); // Don't try to update the primary key
                $this->update($fields, [$this->PrimaryKey => $primaryKeyVal]);
            } else {
                $primaryKeyVal = $this->insert($fields);
            }
        } else {
            $primaryKeyVal = false;
        }

        return $primaryKeyVal;
    }

    /**
     * Test whether or not a user is eligible for a rank.
     *
     * @param array|object $user
     * @param array $rank
     * @return bool
     */
    public static function testRank($user, $rank) {
        if (!isset($rank['Criteria']) || !is_array($rank['Criteria'])) {
            return true;
        }

        $criteria = $rank['Criteria'];
        // All criteria must apply so return false if any of the criteria doesn't match.
        $userPoints = val('Points', $user);

        if (isset($criteria['Points'])) {
            $pointsCriteria = $criteria['Points'];

            if ($pointsCriteria >= 0 && $userPoints < $pointsCriteria) {
                return false;
            } elseif ($pointsCriteria < 0 && $userPoints > $pointsCriteria) {
                return false;
            }
        }

        if (isset($criteria['Time'])) {
            $timeFirstVisit = Gdn_Format::toTimestamp(val('DateFirstVisit', $user));
            $timeCriteria = strtotime($criteria['Time'], 0);

            if ($timeCriteria && ($timeFirstVisit + $timeCriteria > time())) {
                return false;
            }
        }

        if (isset($criteria['CountPosts'])) {
            $countPosts = val('CountDiscussions', $user, 0) + val('CountComments', $user, 0);
            if ($countPosts < $criteria['CountPosts']) {
                return false;
            }
        }

        if ($role = val('Role', $criteria)) {
            $roles = RoleModel::getByName($role);
            $rankRoleID = key($roles);

            $userModel = Gdn::userModel();
            // TODO: Refactor: Make method GetRoleIDs in UserModel.
            $roleData = $userModel->getRoles(val('UserID', $user));
            $userRoles = $roleData->result(DATASET_TYPE_ARRAY);
            $userRoles = array_column($userRoles, 'RoleID');

            if (!in_array($rankRoleID, $userRoles)) {
                return false;
            }
        }

        if (isset($criteria['Permission'])) {
            $permissions = (array)$criteria['Permission'];
            foreach ($permissions as $perm) {
                if (!Gdn::userModel()->checkPermission($user, $perm)) {
                    return false;
                }
            }
        }

        if ($v = val('Manual', $criteria)) {
            if (val('RankID', $user) != $rank['RankID']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether or not some HTML has external links.
     *
     * @param string $html
     * @return bool
     */
    public function hasExternalLinks(string $html): bool {
        // Do not allow any anchors. This could include links to attachments in some formats, like rich.
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $anchors = $dom->getElementsByTagName("a");
        $currentDomain = parse_url(Gdn::request()->domain(), PHP_URL_HOST);

        // Allow links to the current domain or uploads.
        if ($anchors instanceof Traversable) {
            /** @var DOMElement $anchor */
            foreach ($anchors as $anchor) {
                $linkUrl = $anchor->getAttribute("href");
                if ($this->isExternalHost($linkUrl, $currentDomain)) {
                    return true;
                }
                if ($this->isLeavingURL($linkUrl)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all of the allowed link hosts.
     *
     * @return string[]
     */
    public function getAllowedLinkHosts(): array {
        return $this->allowedLinkHosts;
    }

    /**
     * Set the allowed link hosts.
     *
     * @param string[] $allowedLinkHosts
     */
    public function setAllowedLinkHosts(array $allowedLinkHosts): void {
        $this->allowedLinkHosts = $allowedLinkHosts;
    }

    /**
     * Add an allowed link host.
     *
     * @param string $host
     */
    public function addAllowedLinkHost(string $host): void {
        $this->allowedLinkHosts[] = $host;
    }

    /**
     * Determine whether or not URL is from an external host.
     *
     * @param string $url The URL to check.
     * @param string $currentHost The current host.
     *
     * @return bool
     */
    public function isExternalHost(string $url, string $currentHost): bool {
        $linkHost = parse_url($url, PHP_URL_HOST);

        if (empty($linkHost) || $linkHost === $currentHost) {
            goto THE_ONLY_GOTO_IN_PROD;
        }
        foreach ($this->allowedLinkHosts as $host) {
            if (fnmatch($host, $linkHost, FNM_CASEFOLD)) {
                goto THE_ONLY_GOTO_IN_PROD;
            }
        }
        return true;

        THE_ONLY_GOTO_IN_PROD:
            return false;
    }

    /**
     * Determine whether or not a URL is the leaving URL.
     *
     * @param string $linkUrl
     * @return bool
     */
    private function isLeavingURL(string $linkUrl): bool {
        return strpos(parse_url($linkUrl, PHP_URL_PATH), '/home/leaving') !== false;
    }
}
