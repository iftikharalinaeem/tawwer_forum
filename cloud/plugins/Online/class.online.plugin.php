<?php

use Garden\Schema\Schema;

/**
 * Online Plugin
 *
 * This plugin tracks which users are online, and provides a panel module for
 * display the list of currently online people.
 *
 * Changes:
 *  1.0a    Development release
 *  1.0     Official release
 *  1.1     Add WhosOnline config import
 *  1.2     Fixed breakage if no memcache support
 *  1.3     Exposes getUser() for external querying
 *  1.4     Fix wasteful OnlineModule rendering, store Name in Online table
 *  1.5     Add caching to the OnlineModule rending process
 *  1.5.1   Fix inconsistent timezone handling
 *  1.6     Add SimpleAPI hooks
 *  1.6.1   Add online/count API hook
 *  1.6.3   Natsort OnlineUsers before rendering
 *  1.6.4   Some tweaks to the css.
 *  1.7
 *  1.7.1   Add configuration to show active users for the entire website
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */
class OnlinePlugin extends Gdn_Plugin {

    /**
     * Minimum amount of seconds to defer writes to the Online table.
     * @var integer Seconds
     */
    protected $writeDelay;

    /**
     * Length of time that a record must go without an update before it is eligible for pruning.
     * @var integer Seconds
     */
    protected $pruneDelay;

    /**
     * Minimum amount of seconds to defer cleanups to the Online table.
     * @var integer Seconds
     */
    protected $cleanDelay;

    /**
     * Length of time to cache counts
     * @var integer Seconds
     */
    public $cacheCountDelay;

    /**
     * Length of time to cache pre-rendered user lists
     * @var integer Seconds
     */
    public $cacheRenderDelay;

    /**
     * Current UTC timestamp
     * @var integer Seconds
     */
    protected static $now;

    /** @var bool */
    private static $cachingRequired = true;

    /** @var UserModel */
    private $userModel;

    const PRIVATE_MODE_ATTRIBUTE = 'Online/PrivateMode';

    /**
     * Track when we last wrote online status back to the database.
     * @const string
     */
    const CACHE_LAST_WRITE_KEY = 'plugin.online.%d.lastwrite';

    /**
     * Track additional online information such as DiscussionID, CategoryID.
     * @const string
     */
    const CACHE_ONLINE_SUPPLEMENT_KEY = 'plugin.online.%d.supplement';

    /**
     * Cache counts for selector queries for a few seconds to reduce load.
     * @const string
     */
    const CACHE_SELECTOR_COUNT_KEY = 'plugin.online.%s.%s.count';

    /**
     * Track when we last cleaned up the online table.
     * @const string
     */
    const CACHE_CLEANUP_DELAY_KEY = 'plugin.online.cleanup';

    /**
     * Cache rendered html for selector queries for a few seconds to reduce load.
     * @const string
     */
    const CACHE_ONLINE_MODULE_KEY = 'plugin.online.%s.%s.%s.module';

    /**
     * Cache list of online users temporarily to alleviate database
     * @const string
     */
    const CACHE_ONLINE_LIST_KEY = 'plugin.online.users.list';

    /**
     * Names of cookies and cache keys for tracking guests.
     * @const string
     */
    const COOKIE_GUEST_PRIMARY = '__vnOz0';
    const COOKIE_GUEST_SECONDARY = '__vnOz1';

    /**
     * Configuration Defaults
     */
    const DEFAULT_PRUNE_DELAY = 15;
    const DEFAULT_WRITE_DELAY = 60;
    const DEFAULT_CLEAN_DELAY = 60;
    const DEFAULT_STYLE = 'pictures';
    const DEFAULT_LOCATION = 'every';
    const DEFAULT_HIDE = 'true';
    const DEFAULT_CHECK_ENTIRE_SITE = false;

    public function __construct(UserModel $userModel = null) {
        parent::__construct();

        if ($userModel === null) {
            $this->userModel = Gdn::getContainer()->get(UserModel::class);
        } else {
            $this->userModel = $userModel;
        }

        $this->writeDelay = c('Plugins.Online.WriteDelay', self::DEFAULT_WRITE_DELAY);
        $this->pruneDelay = c('Plugins.Online.PruneDelay', self::DEFAULT_PRUNE_DELAY) * 60;
        $this->cleanDelay = c('Plugins.Online.CleanDelay', self::DEFAULT_CLEAN_DELAY);
        $this->cacheCountDelay = c('Plugins.Online.CacheCountDelay', 20);
        $this->cacheRenderDelay = c('Plugins.Online.CacheRenderDelay', 30);

        $utc = new DateTimeZone('UTC');
        $now = new DateTime('now', $utc);
        self::$now = $now->getTimestamp();
    }

    /**
     * Add mapper methods
     *
     * @param SimpleApiPlugin $sender
     */
    public function simpleApiPlugin_mapper_handler($sender) {
        switch ($sender->Mapper->Version) {
            case '1.0':
                $sender->Mapper->addMap([
                    'online/privacy' => 'profile/online/privacy',
                    'online/count' => 'profile/online/count'
                ], null, [
                    'online/privacy' => ['Success', 'Private'],
                    'online/count' => ['Online']
                ]);
                break;
        }
    }

    /*
     * TRIGGER HOOKS
     * Events used for online tracking
     *
     */

    /**
     * Hook into the Tick event for every real page load
     *
     * Here we'll track and update the online status of each user, including
     * guests.
     *
     * @param Gdn_Statistics $sender
     */
    public function gdn_statistics_analyticsTick_handler($sender) {
        switch (Gdn::session()->isValid()) {

            // Guests
            case false:
                $this->trackGuest();
                break;

            // Logged-in users
            case true:
                // We're tracking from AnalyticsTick, so we pass true to update the supplement
                $this->trackActiveUser(true);
                break;
        }

        // Cleanup some entries maybe
        $this->cleanup();
    }

    /**
     * Is caching required?
     *
     * @return bool
     */
    public static function isCachingRequired(): bool {
        return self::$cachingRequired;
    }

    /**
     * Hook into Informs for every minute updates
     *
     * Here we'll track and update the online status of each user while they're
     * sitting on the page.
     *
     * @param Gdn_Controller $sender
     */
    public function notificationsController_beforeInformNotifications_handler($sender) {
        if (Gdn::session()->isValid()) {
            $this->trackActiveUser(false);
        }
    }

    /**
     * Hook into signout and remove the user from online status
     *
     * @param EntryController $sender
     * @return void
     */
    public function entryController_signOut_handler($sender) {
        $user = $sender->EventArguments['SignoutUser'];
        $userID = val('UserID', $user, false);
        if ($userID === false) {
            return;
        }

        Gdn::sql()->delete('Online', [
            'UserID' => val('UserID', $user)
        ]);
    }

    /*
     * GUESTS
     * Logic for tracking guests
     */

    /**
     * Track guests
     *
     * Uses a shifting double cookie method to track the online state of guests.
     */
    public function trackGuest() {
        if (self::isCachingRequired() && !Gdn::cache()->activeEnabled()) {
            return;
        }

        // We are going to be checking one of two cookies and flipping them once every 10 minutes.
        $namePrimary = self::COOKIE_GUEST_PRIMARY;
        $nameSecondary = self::COOKIE_GUEST_SECONDARY;

        list($expirePrimary, $expireSecondary) = self::expiries(self::$now);

        $counts = [];
        if (!Gdn::session()->isValid()) {
            // Check to see if this guest has been counted.
            if (!isset($_COOKIE[$namePrimary]) && !isset($_COOKIE[$nameSecondary])) {

                safeCookie($namePrimary, self::$now, $expirePrimary + 30, '/'); // Cookies expire a little after the cache so they'll definitely be counted in the next one
                $counts[$namePrimary] = self::incrementCache($namePrimary, $expirePrimary);
                Gdn::controller()->setData('Online.Primary', $counts[$namePrimary]);

                safeCookie($nameSecondary, self::$now, $expireSecondary + 30, '/'); // We want both cookies expiring at different times.
                $counts[$nameSecondary] = self::incrementCache($nameSecondary, $expireSecondary);
                Gdn::controller()->setData('Online.Secondary', $counts[$nameSecondary]);
            } elseif (!isset($_COOKIE[$namePrimary])) {

                safeCookie($namePrimary, self::$now, $expirePrimary + 30, '/');
                $counts[$namePrimary] = self::incrementCache($namePrimary, $expirePrimary);
                Gdn::controller()->setData('Online.Primary', $counts[$namePrimary]);
            } elseif (!isset($_COOKIE[$nameSecondary])) {

                safeCookie($nameSecondary, self::$now, $expireSecondary + 30, '/');
                $counts[$nameSecondary] = self::incrementCache($nameSecondary, $expireSecondary);
                Gdn::controller()->setData('Online.Secondary', $counts[$nameSecondary]);
            }
        }
    }

    /**
     * Wrapper to increment guest cache keys
     *
     * @param string $name
     * @param integer $expiry
     * @return int
     */
    protected static function incrementCache($name, $expiry) {

        $value = Gdn::cache()->increment($name, 1);

        if (!$value) {
            $value = 1;
            $r = Gdn::cache()->store($name, $value, [Gdn_Cache::FEATURE_EXPIRY => $expiry]);
        }

        return $value;
    }

    /**
     * Convenience function to retrieve guest cookie expiries based on current time
     *
     * @param integer $time
     * @return array Pair of expiry times, and the index of the currently active cookie
     */
    public static function expiries($time) {
        $timespan = (c('Plugins.Online.PruneDelay', self::DEFAULT_PRUNE_DELAY) * 60) * 2; // Double the real amount

        $expiry0 = $time - ($time % $timespan) + $timespan;

        $expiry1 = $expiry0 - ($timespan / 2);
        if ($expiry1 <= $time) {
            $expiry1 = $expiry0 + ($timespan / 2);
        }

        $active = $expiry0 < $expiry1 ? 0 : 1;

        return [$expiry0, $expiry1, $active];
    }

    /**
     * Get the current total number of guests on the site
     *
     * @return int Number of guests
     */
    public static function guests() {
        if (self::isCachingRequired() && !Gdn::cache()->activeEnabled()) {
            return 0;
        }

        try {
            $cookieNames = [self::COOKIE_GUEST_PRIMARY, self::COOKIE_GUEST_SECONDARY];

            $time = OnlinePlugin::$now;
            list($expirePrimary, $expireSecondary, $active) = self::expiries($time);

            // Get both keys from the cache.
            $cache = Gdn::cache()->get($cookieNames);

            $debug = [
                'Cache' => $cache,
                'Active' => $active
            ];
            $controller = Gdn::controller();
            if ($controller instanceof Gdn_Controller) {
                Gdn::controller()->setData('GuestCountCache', $debug);
            }

            if (isset($cache[$cookieNames[$active]])) {
                return $cache[$cookieNames[$active]];
            } elseif (is_array($cache) && count($cache) > 0) {
                // Maybe the key expired, but the other key is still there.
                return array_pop($cache);
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * Get the current total number of guests on the site
     *
     * @return int
     */
    public function guestCount(): int {
        $result = self::guests();
        return $result ?? 0;
    }

    /*
     * LOGGED-IN USERS
     * Logic for tracking logged-in users
     */

    /**
     * Track a logged-in user
     *
     * Optionally update the user's location, provided the proper environment
     * exists, such as the one created by AnalyticsTick. false to simply adjust
     * online status
     *
     * @param boolean $withSupplement Optional.
     * @return type
     */
    public function trackActiveUser($withSupplement = false) {
        if (self::isCachingRequired() && !Gdn::cache()->activeEnabled()) {
            return;
        }

        if (!Gdn::session()->isValid()) {
            return;
        }

        $userID = Gdn::session()->UserID;
        if (!$userID) {
            return;
        }
        $userName = Gdn::session()->User->Name;

        if ($withSupplement) {
            // Figure out where the user is
            $location = OnlinePlugin::whereAmI();

            // Get the extra data we pushed into the tick with our events
            $tickExtra = json_decode(Gdn::request()->getValue('TickExtra'), true);
            if (!is_array($tickExtra)) {
                $tickExtra = [];
            }

            // Get the user's cache supplement
            $userOnlineSupplementKey = sprintf(self::CACHE_ONLINE_SUPPLEMENT_KEY, $userID);
            $userOnlineSupplement = Gdn::cache()->get($userOnlineSupplementKey);
            if (!is_array($userOnlineSupplement)) {
                $userOnlineSupplement = [];
            }

            // Build an online supplement from the current state
            $onlineSupplement = [
                'Location' => $location,
                'Visible' => !$this->privateMode(Gdn::session()->User)
            ];
            switch ($location) {
                // User is viewing a category
                case 'category':
                    $categoryID = val('CategoryID', $tickExtra, false);
                    $onlineSupplement['CategoryID'] = $categoryID;
                    break;

                // User is in a discussion
                case 'discussion':
                case 'comment':
                    $categoryID = val('CategoryID', $tickExtra, false);
                    $discussionID = val('DiscussionID', $tickExtra, false);
                    $onlineSupplement['CategoryID'] = $categoryID;
                    $onlineSupplement['DiscussionID'] = $discussionID;
                    break;

                // User is soooooomewhere, ooooouuttt there
                case 'limbo':

                    break;
            }

            // Check if there are differences between this supplement and the user's existing one
            // If there are, write the new one to the cache
            $userSupplementHash = md5(serialize($userOnlineSupplement));
            $supplementHash = md5(serialize($onlineSupplement));
            if ($userSupplementHash != $supplementHash) {
                Gdn::cache()->store($userOnlineSupplementKey, $onlineSupplement, [
                    Gdn_Cache::FEATURE_EXPIRY => ($this->pruneDelay * 2)
                ]);
            }
        }

        // Now check if we need to update the user's status in the Online table
        $userLastWriteKey = sprintf(self::CACHE_LAST_WRITE_KEY, $userID);
        $userLastWrite = Gdn::cache()->get($userLastWriteKey);

        // If we last wrote more than $writeDelay seconds ago, write again
        $lastWroteSecondsAgo = self::$now - $userLastWrite;
        if ($lastWroteSecondsAgo > $this->writeDelay) {

            // Write to Online table
            $utc = new DateTimeZone('UTC');
            $currentDate = new DateTime('now', $utc);

            $timestamp = $currentDate->format('Y-m-d H:i:s');
            $px = Gdn::database()->DatabasePrefix;
            $sql = "INSERT INTO {$px}Online (UserID, Name, Timestamp) VALUES (:UserID, :Name, :Timestamp) ON DUPLICATE KEY UPDATE Timestamp = :Timestamp1";
            Gdn::database()->query($sql, [':UserID' => $userID, ':Name' => $userName, ':Timestamp' => $timestamp, ':Timestamp1' => $timestamp]);

            // Remember that we've written to the DB
            Gdn::cache()->store($userLastWriteKey, self::$now);
        }
    }

    /*
     * CONVENIENCE
     * Helper methods to facilitate tracking
     */

    /**
     * Determine where the current user is on the site
     *
     * @param type $resolvedPath
     * @param type $resolvedArgs
     * @return string
     */
    public static function whereAmI($resolvedPath = null, $resolvedArgs = null) {
        $location = 'limbo';
        if (Gdn::config('Plugins.Online.EntireSite', self::DEFAULT_CHECK_ENTIRE_SITE)) {
            return $location;
        }
        $wildLocations = [
            'vanilla/categories/index' => 'category',
            'vanilla/discussion/index' => 'discussion',
            'vanilla/discussion/comment' => 'comment'
        ];

        if (is_null($resolvedPath)) {
            $resolvedPath = Gdn::request()->getValue('ResolvedPath');
        }

        if (is_null($resolvedArgs)) {
            $resolvedArgs = Gdn::request()->getValue('ResolvedArgs');
            if (is_string($resolvedArgs)) {
                $resolvedArgs = json_decode($resolvedArgs, true);
            }
        }

        if (!$resolvedPath) {
            return $location;
        }

        if (isset($wildLocations[$resolvedPath])) {
            $location = $wildLocations[$resolvedPath];
        }

        // Check if we're on the categories list, or inside one, and adjust location
        if ($location == 'category') {
            $loweredResolvedArgs = array_change_key_case($resolvedArgs);

            if (!is_array($loweredResolvedArgs) || empty($loweredResolvedArgs['categoryidentifier'])) {
                $location = 'limbo';
            }
        }

        return $location;
    }

    /**
     * Check if this user is private
     *
     * @param array $user
     * @return boolean
     */
    public function privateMode($user) {
        $onlinePrivacy = valr('Attributes.'.self::PRIVATE_MODE_ATTRIBUTE, $user, false);
        return $onlinePrivacy;
    }

    /**
     * Clean out expired Online entries
     *
     * Optionally only delete $Limit number of rows.
     *
     * @param integer $limit Optional.
     */
    public function cleanup($limit = 0) {
        $lastCleanup = Gdn::cache()->get(self::CACHE_CLEANUP_DELAY_KEY);
        $lastCleanupDelay = self::$now - $lastCleanup;
        if ($lastCleanup && $lastCleanupDelay < $this->cleanDelay) {
            return;
        }

        trace('OnlinePlugin->Cleanup');
        // How old does an entry have to be to get pruned?
        $pruneTimestamp = self::$now - $this->pruneDelay;

        Gdn::sql()->delete('Online', [
            'Timestamp<' => Gdn_Format::toDateTime($pruneTimestamp)
        ]);

        // Remember that we've cleaned up the DB
        Gdn::cache()->store(self::CACHE_CLEANUP_DELAY_KEY, self::$now);
    }

    /**
     * Get (and cache for this page load) the full list of online users
     *
     * This method will keep a copy of all the online users after they've been
     * supplemented.
     *
     * @staticvar array $AllOnlineUsers
     * @return array
     */
    public function getAllOnlineUsers($forceRefresh = false) {
        static $allOnlineUsers = null;
        if (is_null($allOnlineUsers) || $forceRefresh) {
            $allOnlineUsers = [];
            try {
                $allOnlineUsersResult = Gdn::sql()
                        ->cache(self::CACHE_ONLINE_LIST_KEY, 'get', [
                            Gdn_Cache::FEATURE_EXPIRY => 30
                        ])
                        ->select('UserID, Timestamp')
                        ->from('Online')
                        ->get();
            } catch (Exception $ex) {
                Gdn::sql()->reset();
                return $allOnlineUsers;
            }

            while ($onlineUser = $allOnlineUsersResult->nextRow(DATASET_TYPE_ARRAY)) {
                $allOnlineUsers[$onlineUser['UserID']] = $onlineUser;
            }

            unset($allOnlineUsersResult);

            $this->joinSupplements($allOnlineUsers);
        }

        return $allOnlineUsers;
    }

    /**
     * Get a list of online users
     *
     * This method allows the selection of the current set of online users, optionally
     * filtered into a subset based on their location in the forum.
     *
     * @param string $selector Optional.
     * @param integer $selectorID Optional.
     * @param string $selectorField Optional.
     * @return array
     */
    public function onlineUsers($selector = null, $selectorID = null, $selectorField = null) {
        $allOnlineUsers = $this->getAllOnlineUsers();

        if (is_null($selector)) {
            $selector = 'all';
        }
        switch ($selector) {
            case 'category':
            case 'discussion':
                // Allow selection of a subset of users based on the DiscussionID or CategoryID
                if (is_null($selectorField)) {
                    $selectorField = ucfirst($selector) . 'ID';
                }

            case 'limbo':

                $selectorSubset = [];
                foreach ($allOnlineUsers as $userID => $onlineData) {

                    // Searching by SelectorField+SelectorID
                    if (!is_null($selectorID) && !is_null($selectorField) && (!array_key_exists($selectorField, $onlineData) || $onlineData[$selectorField] != $selectorID)) {
                        continue;
                    }

                    // Searching by Location/Selector only
                    if ((is_null($selectorID) || is_null($selectorField)) && $onlineData['Location'] != $selector) {
                        continue;
                    }

                    $selectorSubset[$userID] = $onlineData;
                }
                return $selectorSubset;
                break;

            case 'all':
            default:
                return $allOnlineUsers;
                break;
        }
    }

    /**
     * Get a count of online users
     *
     * This method allows the calculation of the number of online users, optionally
     * filtered into a subset based on their location in the forum.
     *
     * @param string $selector Optional.
     * @param integer $selectorID Optional.
     * @param string $selectorField Optional.
     * @return integer
     */
    public function onlineCount($selector = null, $selectorID = null, $selectorField = null) {
        if (is_null($selector) || $selector == 'all') {
            $allOnlineUsers = $this->getAllOnlineUsers(!self::$cachingRequired);
            return count($allOnlineUsers);
        }

        // Now first build cache keys
        switch ($selector) {
            case 'category':
            case 'discussion':
                if (is_null($selectorField)) {
                    $selectorField = ucfirst($selector) . 'ID';
                }

                if (is_null($selectorID)) {
                    $selectorID = 'all';
                }

                $selectorStub = "{$selectorID}-{$selectorField}";
                break;

            case 'limbo':
                $selectorStub = 'all';
                break;

            case 'all':
            default:
                $selectorStub = 'all';
                break;
        }

        // Check cache for matching pre-built data
        $cacheKey = sprintf(self::CACHE_SELECTOR_COUNT_KEY, $selector, $selectorStub);
        $count = Gdn::cache()->get($cacheKey);
        if ($count !== Gdn_Cache::CACHEOP_FAILURE) {
            return $count;
        }

        // Otherwise do the expensive query
        $count = sizeof($this->onlineUsers($selector, $selectorID, $selectorField));

        // And cache it for a little
        Gdn::cache()->store($cacheKey, $count, [
            Gdn_Cache::FEATURE_EXPIRY => $this->cacheCountDelay
        ]);

        return $count;
    }

    /**
     * Get online information for a user
     *
     * @param string $userID
     */
    public function getUser($userID) {
        if (!array_key_exists($userID, $this->getAllOnlineUsers())) {
            return false;
        }
        return val($userID, $this->getAllOnlineUsers());
    }

    /**
     * Join in the supplement cache entries
     *
     * @param array $users Users list, by reference
     */
    public function joinSupplements(&$users) {
        $userIDs = array_keys($users);
        $cacheKeys = [];
        $numUserIDs = sizeof($userIDs);
        for ($i = 0; $i < $numUserIDs; $i++) {
            $cacheKeys[sprintf(self::CACHE_ONLINE_SUPPLEMENT_KEY, $userIDs[$i])] = $userIDs[$i];
        }

        $userSupplements = Gdn::cache()->get(array_keys($cacheKeys));
        if (!$userSupplements) {
            return;
        }

        foreach ($userSupplements as $onlineSupplementKey => $onlineSupplement) {
            $userID = $cacheKeys[$onlineSupplementKey];
            if (array_key_exists($userID, $users)) {
                if (!is_array($onlineSupplement)) {
                    $onlineSupplement = ['Location' => 'limbo', 'Visible' => true];
                }
                $users[$userID] = array_merge($users[$userID], $onlineSupplement);
            }
        }
    }

    /**
     * Getter for prune delay
     *
     * @return integer
     */
    public function getPruneDelay() {
        return $this->pruneDelay;
    }

    /*
     * DATA HOOKS
     *
     * We hook into the CategoriesController and DiscussionController in order to
     * provide Tick with some extra data.
     *
     * We hook into the UserModel to add 'Online', 'LastOnlineDate' and 'Private'
     * to user objects.
     */

    public function categoriesController_beforeCategoriesRender_handler($sender) {
        Gdn::statistics()->addExtra('CategoryID', $sender->data('Category.CategoryID'));
    }

    public function discussionController_beforeDiscussionRender_handler($sender) {
        Gdn::statistics()->addExtra('CategoryID', $sender->data('Discussion.CategoryID'));
        Gdn::statistics()->addExtra('DiscussionID', $sender->data('Discussion.DiscussionID'));
    }

    /**
     * Attach users' online state to user objects
     *
     * @param UserModel $sender
     */
    public function userModel_setCalculatedFields_handler($sender) {
        $user = &$sender->EventArguments['User'];
        $this->adjustUser($user);
    }

    public function adjustUser(&$user) {
        $userID = val('UserID', $user, null);

        if (!$userID) {
            return false;
        }

        // Need to apply 'online' state
        $online = val('Online', $user, null);
        if (is_null($online)) {
            $utc = new DateTimeZone('UTC');
            $currentDate = new DateTime('now', $utc);

            $userInfo = $this->getUser($userID);
            $userIsOnline = false;

            $userLastOnline = val('Timestamp', $userInfo, null);
            if (Gdn::session()->isValid() && $userID == Gdn::session()->UserID) {
                $userLastOnline = $currentDate->format('Y-m-d H:i:s');
            }

            if (!is_null($userLastOnline)) {

                $earliestOnlineDate = new DateTime('now', $utc);
                $earliestOnlineDate->sub(new DateInterval("PT{$this->pruneDelay}S"));
                $earliestOnlineTime = $earliestOnlineDate->getTimestamp();
                $earliestOnline = $earliestOnlineDate->format('Y-m-d H:i:s');

                $userLastOnlineDate = DateTime::createFromFormat('Y-m-d H:i:s', $userLastOnline, $utc);
                $userLastOnlineTime = $userLastOnlineDate->getTimestamp();
                $userLastOnline = $userLastOnlineDate->format('Y-m-d H:i:s');

                $userIsOnline = $userLastOnlineTime >= $earliestOnlineTime;
            }

            setValue('Online', $user, $userIsOnline);
            setValue('LastOnlineDate', $user, $userLastOnline);

            $userIsPrivate = $this->privateMode($user);
            setValue('Private', $user, $userIsPrivate);
        }

        // Apply CSS classes if needed
        $userClasses = trim(val('_CssClass', $user));
        if (!preg_match('`(On|Off)line`i', $userClasses)) {
            $userIsOnline = val('Online', $user, false);
            $userIsPrivate = val('Private', $user, false);

            if ($userIsOnline && !$userIsPrivate) {
                $userClasses .= " Online";
            } else {
                $userClasses .= " Offline";
            }

            setValue('_CssClass', $user, $userClasses);
        }
    }

    /*
     * UI HOOKS
     * Used to render the module
     */

    /**
     * Add module to specified pages
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        $pluginRenderLocation = c('Plugins.Online.Location', 'all');
        $controller = strtolower($sender->ControllerName);

        $sender->addCssFile('online.css', 'plugins/Online');

        // Don't add the module of the plugin is hidden for guests
        $hideForGuests = c('Plugins.Online.HideForGuests', true);
        if ($hideForGuests && !Gdn::session()->isValid()) {
            return;
        }

        // Is this a page for including the module?
        $showOnController = [];
        switch ($pluginRenderLocation) {
            case 'custom':
                return;

            case 'every':
                $showOnController = [
                    'discussioncontroller',
                    'categoriescontroller',
                    'discussionscontroller',
                    'profilecontroller',
                    'activitycontroller'
                ];
                break;

            case 'discussions':
                $showOnController = [
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;

            case 'discussion':
            default:
                $showOnController = [
                    'discussioncontroller',
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;
        }

        // Include the module
        if (in_array($controller, $showOnController)) {
            $sender->addModule('OnlineModule');
        }
    }

    /*
     * PLUGIN SETUP
     * Configuration and upkeep
     */

    /**
     * Profile settings
     *
     * @param ProfileController $sender
     */
    public function profileController_online_create($sender) {
        $sender->permission('Garden.SignIn.Allow');
        $sender->title(t("Online Preferences"));

        $this->dispatch($sender);
    }

    /**
     * User-facing configuration.
     *
     * Allows configuration of 'Invisible' status.
     *
     * @param Gdn_Controller $sender
     */
    public function controller_index($sender) {

        $args = $sender->RequestArgs;
        if (sizeof($args) < 2) {
            $args = array_merge($args, [0, 0]);
        } elseif (sizeof($args) > 2) {
            $args = array_slice($args, 0, 2);
        }

        list($userReference, $username) = $args;

        $sender->getUserInfo($userReference, $username);
        $sender->_setBreadcrumbs(t('Online Preferences'), '/profile/online');

        $userPrefs = dbdecode($sender->User->Preferences);
        if (!is_array($userPrefs)) {
            $userPrefs = [];
        }

        $userID = $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID != $viewingUserID) {
            $sender->permission('Garden.Users.Edit');
            $userID = $sender->User->UserID;
        }

        $sender->setData('ForceEditing', ($userID == Gdn::session()->UserID) ? false : $sender->User->Name);
        $privateMode = valr('Attributes.'.self::PRIVATE_MODE_ATTRIBUTE, Gdn::session()->User, false);
        $sender->Form->setValue('PrivateMode', $privateMode);

        // Form submission handling.
        if ($sender->Form->authenticatedPostBack()) {
            $newPrivateMode = $sender->Form->getValue('PrivateMode', false);
            if ($newPrivateMode != $privateMode) {
                Gdn::userModel()->saveAttribute($userID, self::PRIVATE_MODE_ATTRIBUTE, $newPrivateMode);
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $sender->render('online', '', 'plugins/Online');
    }

    public function controller_privacy($sender) {
        $sender->permission('Garden.Users.Edit');
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);

        if (!$sender->Form->authenticatedPostBack()) {
            throw new Exception('Post required.', 405);
        }

        $userID = Gdn::request()->get('UserID');
        $user = Gdn::userModel()->getID($userID);
        if (!$user) {
            throw new Exception("No such user '{$userID}'", 404);
        }

        $privateMode = strtolower(Gdn::request()->get('PrivateMode', 'no'));
        $privateMode = in_array($privateMode, ['yes', 'true', 'on', true]) ? true : false;

        Gdn::userModel()->saveAttribute($userID, self::PRIVATE_MODE_ATTRIBUTE, $privateMode);
        $sender->setData('Success', sprintf("Set Online Privacy to %s.", $privateMode ? "ON" : "OFF"));
        $sender->setData('Private', $privateMode);

        $sender->render();
    }

    public function controller_count($sender) {
        $sender->permission('Garden.Settings.View');
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);

        $onlineCount = new OnlineCountModule();

        $categoryID = Gdn::request()->get('CategoryID', null);
        if ($categoryID) {
            $onlineCount->CategoryID = $categoryID;
        }

        $discussionID = Gdn::request()->get('DiscussionID', null);
        if ($discussionID) {
            $onlineCount->DiscussionID = $discussionID;
        }

        list($count, $guestCount) = $onlineCount->getData();

        $countOutput = [
            'Users' => $count,
            'Guests' => $guestCount,
            'Total' => $count + $guestCount
        ];
        $sender->setData('Online', $countOutput);

        $sender->render();
    }

    public function profileController_afterAddSideMenu_handler($sender) {
        if (!Gdn::session()->checkPermission('Garden.SignIn.Allow')) {
            return;
        }

        $sideMenu = $sender->EventArguments['SideMenu'];
        $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID == $viewingUserID) {
            $sideMenu->addLink('Options', sprite('SpWhosOnline') . ' ' . t("Who's Online"), '/profile/online', false, ['class' => 'Popup']);
        } else {
            $sideMenu->addLink('Options', sprite('SpWhosOnline') . ' ' . t("Who's Online"), userUrl($sender->User, '', 'online'), 'Garden.Users.Edit', ['class' => 'Popup']);
        }
    }

    /**
     * Admin-facing configuration
     *
     * Allows configuration of timing intervals, layout, etc.
     *
     * @param Gdn_Controller $sender
     */
    public function pluginController_online_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('plugin/online');
        $sender->title('Online Settings');
        $sender->Form = new Gdn_Form();

        $fields = [
            'Plugins.Online.Location' => self::DEFAULT_LOCATION,
            'Plugins.Online.Style' => self::DEFAULT_STYLE,
            'Plugins.Online.HideForGuests' => self::DEFAULT_HIDE,
            'Plugins.Online.PruneDelay' => self::DEFAULT_PRUNE_DELAY,
            'Plugins.Online.EntireSite' => self::DEFAULT_CHECK_ENTIRE_SITE
        ];

        $saved = false;
        foreach ($fields as $field => $defaultValue) {
            $currentValue = c($field, $defaultValue);
            $sender->Form->setValue($field, $currentValue);

            if ($sender->Form->authenticatedPostBack()) {
                $newValue = $sender->Form->getValue($field);
                if ($newValue != $currentValue) {
                    saveToConfig($field, $newValue);
                    $saved = true;
                }
            }
        }

        if ($saved) {
            $sender->informMessage('Your changed have been saved');
        } elseif ($sender->Form->isMyPostBack() && !$saved) {
            $sender->informMessage("No changes");
        }

        $sender->render('settings', '', 'plugins/Online');
    }

    public function setup() {

        // Run Database adjustments

        $this->structure();

        // Disable WhosOnline

        if (Gdn::addonManager()->isEnabled('WhosOnline', \Vanilla\Addon::TYPE_ADDON)) {
            Gdn::pluginManager()->disablePlugin('WhosOnline');
        }

        // Import WhosOnline settings if they exist

        $displayStyle = c('WhosOnline.DisplayStyle', null);
        if (!is_null($displayStyle)) {
            switch ($displayStyle) {
                case 'pictures':
                    saveToConfig('Plugins.Online.Style', 'pictures');
                    break;
                case 'list':
                    saveToConfig('Plugins.Online.Style', 'links');
                    break;
            }

            removeFromConfig('WhosOnline.DisplayStyle');
        }

        $displayLocation = c('WhosOnline.Location.Show', null);
        if (!is_null($displayLocation)) {
            switch ($displayLocation) {
                case 'every':
                case 'custom':
                    saveToConfig('Plugins.Online.Location', $displayLocation);
                    break;
                case 'discussion':
                    saveToConfig('Plugins.Online.Location', 'discussions');
                    break;
                case 'discussionsonly':
                    saveToConfig('Plugins.Online.Location', 'discussionlists');
                    break;
            }

            removeFromConfig('WhosOnline.Location.Show');
        }

        $hideForGuests = c('WhosOnline.Hide', null);
        if (!is_null($hideForGuests)) {
            if ($hideForGuests) {
                saveToConfig('Plugins.Online.HideForGuests', true);
            } else {
                saveToConfig('Plugins.Online.HideForGuests', false);
            }

            removeFromConfig('WhosOnline.Hide');
        }
    }

    /**
     * Modify the output of a GET record request to the users endpoint.
     *
     * @param array $result
     * @param UsersApiController $sender
     * @param Schema $in
     * @param array $query
     * @param array $row
     * @return array
     */
    public function usersApiController_getOutput(array $result, UsersApiController $sender, Schema $in, array $query, array $row) {
        $attributes = $row['attributes'] ?? [];
        $privateMode = $attributes['online/PrivateMode'] ?? false;
        $result['hidden'] = (bool)$privateMode;
        return $result;
    }

    /**
     * Add hidden flag to the user row schema.
     *
     * @param Schema $schema
     */
    public function userSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'hidden:b?' => 'Is this user hiding their online status?',
        ]));
    }

    /**
     * Adjust a user’s Online privacy setting.
     *
     * @param UsersApiController $sender
     * @param int $id The user ID.
     * @param array $body The request body.
     * @return array
     * @throws \Garden\Schema\ValidationException if input or output fails schema validation.
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException if the permission check fails.
     */
    public function usersApiController_put_hidden(UsersApiController $sender, int $id, array $body) {
        $sender->permission('Garden.Users.Edit');

        $sender->idParamSchema('in');
        $in = $sender->schema([
            'hidden:b' => 'Whether not the user should be hidden from Online status.'
        ], 'in')->setDescription('Adjust a user’s Online privacy.');
        $out = $sender->schema([
            'hidden:b' => 'Whether not the user is hidden from Online status.'
        ], 'out');

        $body = $in->validate($body);

        $this->userByID($id);
        $this->userModel->saveAttribute(
            $id,
            self::PRIVATE_MODE_ATTRIBUTE,
            $body['hidden']
        );
        $user = $this->userByID($id);
        $attributes = $user['Attributes'] ?? null;
        if (!is_array($attributes) || !array_key_exists(self::PRIVATE_MODE_ATTRIBUTE, $attributes)) {
            throw new Garden\Web\Exception\ServerException('Unable to set Private Mode for user.');
        }
        $result = [
            'hidden' => $attributes[self::PRIVATE_MODE_ATTRIBUTE]
        ];

        $result = $out->validate($result);
        return $result;
    }

    /**
     * Configure whether or not caching is required.
     *
     * @param $cachingRequired
     * @return bool
     */
    public static function setCachingRequired(bool $cachingRequired) {
        self::$cachingRequired = $cachingRequired;
        return $cachingRequired;
    }

    public function structure() {
        Gdn::structure()->reset();
        Gdn::structure()->table('Online')
                ->column('UserID', 'int(11)', false, 'primary')
                ->column('Name', 'varchar(64)', null)
                ->column('Timestamp', 'datetime', false, 'index')
                ->set(false, false);
    }

    /**
     * Get a user by its numeric ID.
     *
     * @param int $id The user ID.
     * @throws \Garden\Web\Exception\NotFoundException if the user could not be found.
     * @return array
     */
    private function userByID($id) {
        $row = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row || $row['Deleted'] > 0) {
            throw new \Garden\Web\Exception\NotFoundException('User');
        }
        return $row;
    }
}