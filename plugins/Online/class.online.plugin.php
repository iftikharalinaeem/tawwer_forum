<?php if (!defined('APPLICATION')) exit();

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
 *  1.3     Exposes GetUser() for external querying
 *  1.4     Fix wasteful OnlineModule rendering, store Name in Online table
 *  1.5     Add caching to the OnlineModule rending process
 *  1.5.1   Fix inconsistent timezone handling
 *  1.6     Add SimpleAPI hooks
 *  1.6.1   Add online/count API hook
 *  1.6.3   Natsort OnlineUsers before rendering
 *  1.6.4   Some tweaks to the css.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

$PluginInfo['Online'] = array(
   'Name' => 'Online',
   'Description' => 'Tracks who is online, and provides a panel module for displaying a list of online people.',
   'Version' => '1.6.4',
   'MobileFriendly' => FALSE,
   'RequiredApplications' => array('Vanilla' => '2.1a20'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/plugin/online',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/tim'
);

class OnlinePlugin extends Gdn_Plugin {
   
   /**
    * Minimum amount of seconds to defer writes to the Online table.
    * @var integer Seconds
    */
   protected $WriteDelay;
   
   /**
    * Length of time that a record must go without an update before it is eligible for pruning.
    * @var integer Seconds
    */
   protected $PruneDelay;
   
   /**
    * Minimum amount of seconds to defer cleanups to the Online table.
    * @var integer Seconds
    */
   protected $CleanDelay;
   
   /**
    * Length of time to cache counts
    * @var integer Seconds
    */
   public $CacheCountDelay;
   
   /**
    * Length of time to cache pre-rendered user lists
    * @var integer Seconds
    */
   public $CacheRenderDelay;
   
   /**
    * Current UTC timestamp
    * @var integer Seconds
    */
   protected static $Now;
   
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
   const CACHE_ONLINE_MODULE_KEY = 'plugin.online.%s.%s.module';
   
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
   
   public function __construct() {
      parent::__construct();
      
      $this->WriteDelay = C('Plugins.Online.WriteDelay', self::DEFAULT_WRITE_DELAY);
      $this->PruneDelay = C('Plugins.Online.PruneDelay', self::DEFAULT_PRUNE_DELAY) * 60;
      $this->CleanDelay = C('Plugins.Online.CleanDelay', self::DEFAULT_CLEAN_DELAY);
      $this->CacheCountDelay = C('Plugins.Online.CacheCountDelay', 20);
      $this->CacheRenderDelay = C('Plugins.Online.CacheRenderDelay', 60);
      
      $UTC = new DateTimeZone('UTC');
      $CurrentDate = new DateTime('now', $UTC);
      self::$Now = $CurrentDate->getTimestamp();
   }
   
   /**
    * Add mapper methods
    * 
    * @param SimpleApiPlugin $Sender
    */
   public function SimpleApiPlugin_Mapper_Handler($Sender) {
      switch ($Sender->Mapper->Version) {
         case '1.0':
            $Sender->Mapper->AddMap(array(
               'online/privacy'        => 'dashboard/profile/online/privacy',
               'online/count'          => 'dashboard/profile/online/count'
            ), NULL, array(
               'online/privacy'        => array('Success', 'Private'),
               'online/count'          => array('Online')
            ));
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
    * @param Gdn_Statistics $Sender
    */
   public function Gdn_Statistics_AnalyticsTick_Handler($Sender) {
      switch (Gdn::Session()->IsValid()) {
         
         // Guests
         case FALSE:
            $this->TrackGuest();
            break;
         
         // Logged-in users
         case TRUE:
            // We're tracking from AnalyticsTick, so we pass TRUE to update the supplement
            $this->TrackActiveUser(TRUE);
            break;
      }
   }
   
   /**
    * Hook into Informs for every minute updates
    * 
    * Here we'll track and update the online status of each user while they're 
    * sitting on the page.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function NotificationsController_BeforeInformNotifications_Handler($Sender) {
      if (Gdn::Session()->IsValid())
         $this->TrackActiveUser(FALSE);
   }
   
   /**
    * Hook into signout and remove the user from online status
    * 
    * @param EntryController $Sender
    * @return void
    */
   public function EntryController_SignOut_Handler($Sender) {
      $User = $Sender->EventArguments['SignoutUser'];
      $UserID = GetValue('UserID', $User, FALSE);
      if ($UserID === FALSE) return;
      
      Gdn::SQL()->Delete('Online', array(
         'UserID' => GetValue('UserID', $User)
      ));
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
   public function TrackGuest() {
      if (!Gdn::Cache()->ActiveEnabled())
         return;
      
      // If this is the first time this person is showing up, try to set a cookie and then return
      // This prevents tracking bounces, as well as weeds out clients that don't support cookies.
      $BounceCookieName = C('Garden.Cookie.Name').'-Vv';
      $BounceCookie = GetValue($BounceCookieName, $_COOKIE);
      if (!$BounceCookie) {
         setcookie($BounceCookieName, self::$Now, self::$Now + 1200, C('Garden.Cookie.Path', '/'));
         return;
      }
      
      // We are going to be checking one of two cookies and flipping them once every 10 minutes.
      // When we read from one cookie
      $NamePrimary = self::COOKIE_GUEST_PRIMARY;
      $NameSecondary = self::COOKIE_GUEST_SECONDARY;
      
      list($ExpirePrimary, $ExpireSecondary) = self::Expiries(self::$Now);
      
      if (!Gdn::Session()->IsValid()) {
         // Check to see if this guest has been counted.
         if (!isset($_COOKIE[$NamePrimary]) && !isset($_COOKIE[$NameSecondary])) {
            setcookie($NamePrimary, self::$Now, $ExpirePrimary + 30, '/'); // cookies expire a little after the cache so they'll definitely be counted in the next one
            $Counts[$NamePrimary] = self::IncrementCache($NamePrimary, $ExpirePrimary);

            setcookie($NameSecondary, self::$Now, $ExpireSecondary + 30, '/'); // We want both cookies expiring at different times.
            $Counts[$NameSecondary] = self::IncrementCache($NameSecondary, $ExpireSecondary);
         } elseif (!isset($_COOKIE[$NamePrimary])) {
            setcookie($NamePrimary, self::$Now, $ExpirePrimary + 30, '/');
            $Counts[$NamePrimary] = self::IncrementCache($NamePrimary, $ExpirePrimary);
         } elseif (!isset($_COOKIE[$NameSecondary])) {
            setcookie($NameSecondary, self::$Now, $ExpireSecondary + 30, '/');
            $Counts[$NameSecondary] = self::IncrementCache($NameSecondary, $ExpireSecondary);
         }
      }
   }
   
   /**
    * Wrapper to increment guest cache keys
    * 
    * @param string $Name
    * @param integer $Expiry
    * @return int 
    */
   protected static function IncrementCache($Name, $Expiry) {
      $Value = Gdn::Cache()->Increment($Name, 1, array(Gdn_Cache::FEATURE_EXPIRY => $Expiry));
      
      if (!$Value) {
         $Value = 1;
         $R = Gdn::Cache()->Store($Name, $Value, array(Gdn_Cache::FEATURE_EXPIRY => $Expiry));
      }
      
      return $Value;
   }
   
   /**
    * Convenience function to retrieve guest cookie expiries based on current time
    * 
    * @param integer $Time
    * @return array Pair of expiry times, and the index of the currently active cookie
    */
   public static function Expiries($Time) {
      $Timespan = (C('Plugins.Online.PruneDelay', self::DEFAULT_PRUNE_DELAY) * 60) * 2; // Double the real amount
      
      $Expiry0 = $Time - $Time % $Timespan + $Timespan;

      $Expiry1 = $Expiry0 - $Timespan / 2;
      if ($Expiry1 <= $Time)
         $Expiry1 = $Expiry0 + $Timespan / 2;

      $Active = $Expiry0 < $Expiry1 ? 0 : 1;

      return array($Expiry0, $Expiry1, $Active);
   }
   
   /**
    * Get the current total number of guests on the site
    * 
    * @return int Number of guests
    */
   public static function Guests() {
      if (!Gdn::Cache()->ActiveEnabled())
         return 0;
      
      try {
         $Names = array(self::COOKIE_GUEST_PRIMARY, self::COOKIE_GUEST_SECONDARY);
         
         $Time = OnlinePlugin::$Now;
         list($ExpirePrimary, $ExpireSecondary, $Active) = self::Expiries($Time);

         // Get bot keys from the cache.
         $Cache = Gdn::Cache()->Get($Names);

         $Debug = array(
             'Cache' => $Cache, 
             'Active' => $Active);
         Gdn::Controller()->SetData('GuestCountCache', $Debug);

         if (isset($Cache[$Names[$Active]]))
            return $Cache[$Names[$Active]];
         elseif (is_array($Cache) && count($Cache) > 0) {
            // Maybe the key expired, but the other key is still there.
            return array_pop($Cache);
         }
      } catch (Exception $Ex) {
         echo $Ex->getMessage();
      }
   }
   
   /* 
    * LOGGED-IN USERS
    * Logic for tracking logged-in users
    */
   
   /**
    * Track a logged-in user
    * 
    * Optionally update the user's location, provided the proper environment 
    * exists, such as the one created by AnalyticsTick. FALSE to simply adjust
    * online status
    * 
    * @param boolean $WithSupplement Optional.
    * @return type 
    */
   public function TrackActiveUser($WithSupplement = FALSE) {
      if (!Gdn::Cache()->ActiveEnabled())
         return;
      
      if (!Gdn::Session()->IsValid())
         return;
      
      $UserID = Gdn::Session()->UserID;
      $UserName = Gdn::Session()->User->Name;
      
      if ($WithSupplement) {
         // Figure out where the user is
         $Location = OnlinePlugin::WhereAmI();
         
         // Get the extra data we pushed into the tick with our events
         $TickExtra = @json_decode(Gdn::Request()->GetValue('TickExtra'), TRUE);
         if (!is_array($TickExtra)) $TickExtra = array();

         // Get the user's cache supplement
         $UserOnlineSupplementKey = sprintf(self::CACHE_ONLINE_SUPPLEMENT_KEY, $UserID);
         $UserOnlineSupplement = Gdn::Cache()->Get($UserOnlineSupplementKey);
         if (!is_array($UserOnlineSupplement)) $UserOnlineSupplement = array();

         // Build an online supplement from the current state
         $OnlineSupplement = array(
            'Location'     => $Location,
            'Visible'      => !$this->PrivateMode(Gdn::Session()->User)
         );
         switch ($Location) {
            // User is viewing a category
            case 'category':
               $CategoryID = GetValue('CategoryID', $TickExtra, FALSE);
               $OnlineSupplement['CategoryID'] = $CategoryID;
               break;

            // User is in a discussion
            case 'discussion':
            case 'comment':
               $CategoryID = GetValue('CategoryID', $TickExtra, FALSE);
               $DiscussionID = GetValue('DiscussionID', $TickExtra, FALSE);
               $OnlineSupplement['CategoryID'] = $CategoryID;
               $OnlineSupplement['DiscussionID'] = $DiscussionID;
               break;

            // User is soooooomewhere, ooooouuttt there
            case 'limbo':

               break;
         }

         // Check if there are differences between this supplement and the user's existing one
         // If there are, write the new one to the cache
         $UserSupplementHash = md5(serialize($UserOnlineSupplement));
         $SupplementHash = md5(serialize($OnlineSupplement));
         if ($UserSupplementHash != $SupplementHash)
            Gdn::Cache()->Store($UserOnlineSupplementKey, $OnlineSupplement, array(
               Gdn_Cache::FEATURE_EXPIRY  => ($this->PruneDelay * 2)
            ));
      }
      
      // Now check if we need to update the user's status in the Online table
      $UserLastWriteKey = sprintf(self::CACHE_LAST_WRITE_KEY, $UserID);
      $UserLastWrite = Gdn::Cache()->Get($UserLastWriteKey);
      
      $LastWriteDelay = time() - $UserLastWrite;
      if ($LastWriteDelay < $this->WriteDelay)
         return;
      
      // Write to Online table
      $UTC = new DateTimeZone('UTC');
      $CurrentDate = new DateTime('now', $UTC);
      
      $Timestamp = $CurrentDate->format('Y-m-d H:i:s');
      $Px = Gdn::SQL()->Database->DatabasePrefix;
      $Sql = "INSERT INTO {$Px}Online (UserID, Name, Timestamp) VALUES (:UserID, :Name, :Timestamp) ON DUPLICATE KEY UPDATE Timestamp = :Timestamp1";
      Gdn::SQL()->Database->Query($Sql, array(':UserID' => $UserID, ':Name' => $UserName, ':Timestamp' => $Timestamp, ':Timestamp1' => $Timestamp));
      
      // Cleanup some entries
      $this->Cleanup();
   }
   
   /* 
    * CONVENIENCE
    * Helper methods to facilitate tracking
    */
   
   /**
    * Determine where the current user is on the site
    * 
    * @param type $ResolvedPath
    * @param type $ResolvedArgs
    * @return string 
    */
   public static function WhereAmI($ResolvedPath = NULL, $ResolvedArgs = NULL) {
      $Location = 'limbo';
      $WildLocations = array(
         'vanilla/categories/index'   => 'category',
         'vanilla/discussion/index'   => 'discussion',
         'vanilla/discussion/comment' => 'comment'
      );
      
      if (is_null($ResolvedPath))
         $ResolvedPath = Gdn::Request()->GetValue('ResolvedPath');
      
      if (is_null($ResolvedArgs))
         $ResolvedArgs = json_decode(Gdn::Request()->GetValue('ResolvedArgs'), TRUE);
      
      if (!$ResolvedPath) return $Location;
      $Location = GetValue($ResolvedPath, $WildLocations, 'limbo');

      // Check if we're on the categories list, or inside one, and adjust location
      if ($Location == 'category') {
         $CategoryIdentifier = GetValue('CategoryIdentifier', $ResolvedArgs);
         if (empty($CategoryIdentifier))
            $Location = 'limbo';
      }
      return $Location;
   }
   
   /**
    * Check if this user is private
    * 
    * @param array $User
    * @return boolean 
    */
   public function PrivateMode($User) {
      $OnlinePrivacy = GetValueR('Attributes.Online/PrivateMode', $User, FALSE);
      return $OnlinePrivacy;
   }
   
   /**
    * Clean out expired Online entries 
    * 
    * Optionally only delete $Limit number of rows.
    * 
    * @param integer $Limit Optional.
    */
   public function Cleanup($Limit = 0) {
      $LastCleanup = Gdn::Cache()->Get(self::CACHE_CLEANUP_DELAY_KEY);
      $LastCleanupDelay = time() - $LastCleanup;
      if ($LastCleanupDelay < $this->CleanDelay)
         return;
      
      Trace('OnlinePlugin->Cleanup');
      // How old does an entry have to be to get pruned?
      $PruneTimestamp = self::$Now - $this->PruneDelay;
      
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "DELETE FROM {$Px}Online WHERE Timestamp < :Timestamp";
      if ($Limit > 0)
         $Sql .= " LIMIT {$Limit}";
         
      Gdn::SQL()->Database->Query($Sql, array(':Timestamp' => Gdn_Format::ToDateTime($PruneTimestamp)));
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
   public function GetAllOnlineUsers() {
      static $AllOnlineUsers = NULL;
      if (is_null($AllOnlineUsers)) {
         $AllOnlineUsers = array();
         try {
            $AllOnlineUsersResult = Gdn::SQL()
               ->Select('UserID, Timestamp')
               ->From('Online')
               ->Get();
         } catch (Exception $Ex) {
            Gdn::SQL()->Reset();
            return $AllOnlineUsers;
         }

         while ($OnlineUser = $AllOnlineUsersResult->NextRow(DATASET_TYPE_ARRAY))
            $AllOnlineUsers[$OnlineUser['UserID']] = $OnlineUser;

         unset($AllOnlineUsersResult);
         
         $this->JoinSupplements($AllOnlineUsers);
      }
      
      return $AllOnlineUsers;
   }
   
   /**
    * Get a list of online users
    * 
    * This method allows the selection of the current set of online users, optionally
    * filtered into a subset based on their location in the forum.
    * 
    * @param string $Selector Optional.
    * @param integer $SelectorID Optional. 
    * @param string $SelectorField Optional. 
    * @return array
    */
   public function OnlineUsers($Selector = NULL, $SelectorID = NULL, $SelectorField = NULL) {
      $AllOnlineUsers = $this->GetAllOnlineUsers();
      
      if (is_null($Selector)) $Selector = 'all';
      switch ($Selector) {
         case 'category':
         case 'discussion':
            // Allow selection of a subset of users based on the DiscussionID or CategoryID
            if (is_null($SelectorField))
               $SelectorField = ucfirst($Selector).'ID';
            
         case 'limbo':
            
            $SelectorSubset = array();
            foreach ($AllOnlineUsers as $UserID => $OnlineData) {
               
               // Searching by SelectorField+SelectorID
               if (!is_null($SelectorID) && !is_null($SelectorField) && (!array_key_exists($SelectorField, $OnlineData) || $OnlineData[$SelectorField] != $SelectorID)) continue;
               
               // Searching by Location/Selector only
               if ((is_null($SelectorID) || is_null($SelectorField)) && $OnlineData['Location'] != $Selector) continue;
               
               $SelectorSubset[$UserID] = $OnlineData;
            }
            return $SelectorSubset;
            break;
         
         case 'all':
         default:
            return $AllOnlineUsers;
            break;
      }
   }
   
   /**
    * Get a count of online users
    * 
    * This method allows the calculation of the number of online users, optionally
    * filtered into a subset based on their location in the forum.
    * 
    * @param string $Selector Optional.
    * @param integer $SelectorID Optional. 
    * @param string $SelectorField Optional. 
    * @return integer
    */
   public function OnlineCount($Selector = NULL, $SelectorID = NULL, $SelectorField = NULL) {
      if (is_null($Selector) || $Selector == 'all') {
         $AllOnlineUsers = $this->GetAllOnlineUsers();
         return sizeof($AllOnlineUsers);
      }
      
      // Now first build cache keys
      switch ($Selector) {
         case 'category':
         case 'discussion':
            if (is_null($SelectorField))
               $SelectorField = ucfirst($Selector).'ID';
            
            if (is_null($SelectorID))
               $SelectorID = 'all';

            $SelectorStub = "{$SelectorID}-{$SelectorField}";
            break;
            
         case 'limbo':
            $SelectorStub = 'all';
            break;
         
         case 'all':
         default:
            $SelectorStub = 'all';
            break;
      }
      
      // Check cache for matching pre-built data
      $CacheKey = sprintf(self::CACHE_SELECTOR_COUNT_KEY, $Selector, $SelectorStub);
      $Count = Gdn::Cache()->Get($CacheKey);
      if ($Count !== Gdn_Cache::CACHEOP_FAILURE)
         return $Count;
      
      // Otherwise do the expensive query
      $Count = sizeof($this->OnlineUsers($Selector, $SelectorID, $SelectorField));
      
      // And cache it for a little
      Gdn::Cache()->Store($CacheKey, $Count, array(
         Gdn_Cache::FEATURE_EXPIRY  => $this->CacheCountDelay
      ));
      
      return $Count;
   }
   
   /**
    * Get online information for a user
    * 
    * @param string $UserID
    */
   public function GetUser($UserID) {
      if (!array_key_exists($UserID, $this->GetAllOnlineUsers())) return FALSE;
      return GetValue($UserID, $this->GetAllOnlineUsers());
   }
   
   /**
    * Join in the supplement cache entries
    * 
    * @param array $Users Users list, by reference
    */
   public function JoinSupplements(&$Users) {
      $UserIDs = array_keys($Users);
      $CacheKeys = array();
      $NumUserIDs = sizeof($UserIDs);
      for ($i = 0; $i < $NumUserIDs; $i++)
         $CacheKeys[sprintf(self::CACHE_ONLINE_SUPPLEMENT_KEY, $UserIDs[$i])] = $UserIDs[$i];
      
      $UserSupplements = Gdn::Cache()->Get(array_keys($CacheKeys));
      if (!$UserSupplements)
         return;
      
      foreach ($UserSupplements as $OnlineSupplementKey => $OnlineSupplement) {
         $UserID = $CacheKeys[$OnlineSupplementKey];
         if (array_key_exists($UserID, $Users)) {
            if (!is_array($OnlineSupplement))
               $OnlineSupplement = array('Location' => 'limbo', 'Visible' => TRUE);
            $Users[$UserID] = array_merge($Users[$UserID], $OnlineSupplement);
         }
      }
   }
   
   /**
    * Getter for prune delay
    * 
    * @return integer
    */
   public function GetPruneDelay() {
      return $this->PruneDelay;
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
   
   public function CategoriesController_BeforeCategoriesRender_Handler($Sender) {
      Gdn::Statistics()->AddExtra('CategoryID', $Sender->Data('Category.CategoryID'));
   }
   
   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
      Gdn::Statistics()->AddExtra('CategoryID', $Sender->Data('Discussion.CategoryID'));
      Gdn::Statistics()->AddExtra('DiscussionID', $Sender->Data('Discussion.DiscussionID'));
   }
   
   /**
    * Attach users' online state to user objects
    * 
    * @param UserModel $Sender
    */
   public function UserModel_SetCalculatedFields_Handler($Sender) {
      $User = &$Sender->EventArguments['User'];
      $this->AdjustUser($User);
   }
   
   public function AdjustUser(&$User) {
      $UserID = GetValue('UserID', $User, NULL);
      
      if (!$UserID) return false;
      
      // Need to apply 'online' state
      $Online = GetValue('Online', $User, null);
      if (is_null($Online)) {
         $UTC = new DateTimeZone('UTC');
         $CurrentDate = new DateTime('now', $UTC);

         $UserInfo = $this->GetUser($UserID);
         $UserIsOnline = false;

         $UserLastOnline = GetValue('Timestamp', $UserInfo, null);
         if (Gdn::Session()->IsValid() && $UserID == Gdn::Session()->UserID)
            $UserLastOnline = $CurrentDate->format('Y-m-d H:i:s');

         if (!is_null($UserLastOnline)) {

            $EarliestOnlineDate = new DateTime('now', $UTC);
            $EarliestOnlineDate->sub(new DateInterval("PT{$this->PruneDelay}S"));
            $EarliestOnlineTime = $EarliestOnlineDate->getTimestamp();
            $EarliestOnline = $EarliestOnlineDate->format('Y-m-d H:i:s');

            $UserLastOnlineDate = DateTime::createFromFormat('Y-m-d H:i:s', $UserLastOnline, $UTC);
            $UserLastOnlineTime = $UserLastOnlineDate->getTimestamp();
            $UserLastOnline = $UserLastOnlineDate->format('Y-m-d H:i:s');

            $UserIsOnline = $UserLastOnlineTime >= $EarliestOnlineTime;
         }

         SetValue('Online', $User, $UserIsOnline);
         SetValue('LastOnlineDate', $User, $UserLastOnline);

         $UserIsPrivate = $this->PrivateMode($User);
         SetValue('Private', $User, $UserIsPrivate);
      }
      
      // Apply CSS classes if needed
      $UserClasses = trim(GetValue('_CssClass', $User));
      if (!preg_match('`(On|Off)line`i', $UserClasses)) {
         $UserIsOnline = GetValue('Online', $User, false);
         $UserIsPrivate = GetValue('Private', $User, false);
      
         if ($UserIsOnline && !$UserIsPrivate)
            $UserClasses .= " Online";
         else
            $UserClasses .= " Offline";
      
         SetValue('_CssClass', $User, $UserClasses);
      }
   }
   
   /*
    * UI HOOKS
    * Used to render the module
    */
   
   /**
    * Add module to specified pages
    * 
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      $PluginRenderLocation = C('Plugins.Online.Location', 'all');
      $Controller = strtolower($Sender->ControllerName);
      
      $Sender->AddCssFile('online.css', 'plugins/Online');

      // Don't add the module of the plugin is hidden for guests
      if (C('Plugins.Online.HideForGuests', TRUE) && !Gdn::Session()->IsValid())
         return;
      
      // Is this a page for including the module?
      $ShowOnController = array();      
      switch($PluginRenderLocation) {
         case 'custom':
            return;
            
         case 'every':
            $ShowOnController = array(
               'discussioncontroller',
               'categoriescontroller',
               'discussionscontroller',
               'profilecontroller',
               'activitycontroller'
            );
            break;
         
         case 'discussions':
            $ShowOnController = array(
               'discussionscontroller',
               'categoriescontroller'
            );   
            break;
         
         case 'discussion':
         default:
            $ShowOnController = array(
               'discussioncontroller',
               'discussionscontroller',
               'categoriescontroller'
            );   
            break;                  
      }
      
      // Include the module
      if (in_array($Controller, $ShowOnController))
         $Sender->AddModule('OnlineModule');
   }
   
   /*
    * PLUGIN SETUP
    * Configuration and upkeep
    */
   
   /**
    * Profile settings
    * 
    * @param ProfileController $Sender 
    */
   public function ProfileController_Online_Create($Sender) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->Title("Online Preferences");
      
      $this->Dispatch($Sender);
   }
   
   /**
    * User-facing configuration.
    * 
    * Allows configuration of 'Invisible' status.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Controller_Index($Sender) {
      
      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 2)
         $Args = array_merge($Args, array(0,0));
      elseif (sizeof($Args) > 2)
         $Args = array_slice($Args, 0, 2);
      
      list($UserReference, $Username) = $Args;
      
      $Sender->GetUserInfo($UserReference, $Username);
      $Sender->_SetBreadcrumbs(T('Online Preferences'), '/profile/online');
      
      $UserPrefs = Gdn_Format::Unserialize($Sender->User->Preferences);
      if (!is_array($UserPrefs))
         $UserPrefs = array();
      
      $UserID = $ViewingUserID = Gdn::Session()->UserID;
      
      if ($Sender->User->UserID != $ViewingUserID) {
         $Sender->Permission('Garden.Users.Edit');
         $UserID = $Sender->User->UserID;
      }
      
      $Sender->SetData('ForceEditing', ($UserID == Gdn::Session()->UserID) ? FALSE : $Sender->User->Name);
      $PrivateMode = GetValueR('Attributes.Online/PrivateMode', Gdn::Session()->User, FALSE);
      $Sender->Form->SetValue('PrivateMode', $PrivateMode);
      
      // If seeing the form for the first time...
      if ($Sender->Form->IsPostBack()) {
         $NewPrivateMode = $Sender->Form->GetValue('PrivateMode', FALSE);
         if ($NewPrivateMode != $PrivateMode) {
            Gdn::UserModel()->SaveAttribute($UserID, 'Online/PrivateMode', $NewPrivateMode);
            $Sender->InformMessage(T("Your changes have been saved."));
         }
      }

      $Sender->Render('online','','plugins/Online');
   }
   
   public function Controller_Privacy($Sender) {
      $Sender->Permission('Garden.Users.Edit');
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      if (!$Sender->Form->IsPostBack())
         throw new Exception('Post required.', 405);
      
      $UserID = Gdn::Request()->Get('UserID');
      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw new Exception("No such user '{$UserID}'", 404);
         
      $PrivateMode = strtolower(Gdn::Request()->Get('PrivateMode', 'no'));
      $PrivateMode = in_array($PrivateMode, array('yes', 'true', 'on', TRUE)) ? TRUE : FALSE;
      
      Gdn::UserModel()->SaveAttribute($UserID, 'Online/PrivateMode', $PrivateMode);
      $Sender->SetData('Success', sprintf("Set Online Privacy to %s.", $PrivateMode ? "ON" : "OFF"));
      $Sender->SetData('Private', $PrivateMode);
      
      $Sender->Render();
   }
   
   public function Controller_Count($Sender) {
      $Sender->Permission('Garden.Settings.View');
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $OnlineCount = new OnlineCountModule();
      
      $CategoryID = Gdn::Request()->Get('CategoryID', NULL);
      if ($CategoryID) $OnlineCount->CategoryID = $CategoryID;
      
      $DiscussionID = Gdn::Request()->Get('DiscussionID', NULL);
      if ($DiscussionID) $OnlineCount->DiscussionID = $DiscussionID;
      
      list($Count, $GuestCount) = $OnlineCount->GetData();
      
      $CountOutput = array(
         'Users'  => $Count,
         'Guests' => $GuestCount,
         'Total'  => $Count + $GuestCount
      );
      $Sender->SetData('Online', $CountOutput);
      
      $Sender->Render();
   }
   
   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.SignIn.Allow'))
         return;
   
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $ViewingUserID = Gdn::Session()->UserID;
      
      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', Sprite('SpWhosOnline').' '.T("Who's Online"), '/profile/online', FALSE, array('class' => 'Popup'));
      } else {
         $SideMenu->AddLink('Options', Sprite('SpWhosOnline').' '.T("Who's Online"), UserUrl($Sender->User, '', 'online'), 'Garden.Users.Edit', array('class' => 'Popup'));
      }
   }
   
   
   /**
    * Admin-facing configuration
    * 
    * Allows configuration of timing intervals, layout, etc.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function PluginController_Online_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('plugin/online');
      $Sender->Title('Online Settings');
      $Sender->Form = new Gdn_Form();
      
      $Fields = array(
         'Plugins.Online.Location'        => self::DEFAULT_LOCATION,
         'Plugins.Online.Style'           => self::DEFAULT_STYLE,
         'Plugins.Online.HideForGuests'   => self::DEFAULT_HIDE,
         'Plugins.Online.PruneDelay'      => self::DEFAULT_PRUNE_DELAY
      );
      
      $Saved = FALSE;
      foreach ($Fields as $Field => $DefaultValue) {
         $CurrentValue = C($Field, $DefaultValue);
         $Sender->Form->SetValue($Field, $CurrentValue);
         
         if ($Sender->Form->IsPostBack()) {
            $NewValue = $Sender->Form->GetValue($Field);
            if ($NewValue != $CurrentValue) {
               SaveToConfig ($Field, $NewValue);
               $Saved = TRUE;
            }
         }
      }
      
      if ($Saved)
         $Sender->InformMessage('Your changed have been saved');
      elseif ($Sender->Form->IsMyPostBack() && !$Saved)
         $Sender->InformMessage("No changes");
      
      $Sender->Render('settings','','plugins/Online');
   }
   
   public function Setup() {
      
      // Run Database adjustments
      
      $this->Structure();
      
      // Disable WhosOnline
      
      if (Gdn::PluginManager()->CheckPlugin('WhosOnline')) {
         Gdn::PluginManager()->DisablePlugin('WhosOnline');
      }
      
      // Import WhosOnline settings if they exist
      
      $DisplayStyle = C('WhosOnline.DisplayStyle', NULL);
      if (!is_null($DisplayStyle)) {
         switch ($DisplayStyle) {
            case 'pictures':
               SaveToConfig('Plugins.Online.Style', 'pictures');
               break;
            case 'list':
               SaveToConfig('Plugins.Online.Style', 'links');
               break;
         }
         
         RemoveFromConfig('WhosOnline.DisplayStyle');
      }
      
      $DisplayLocation = C('WhosOnline.Location.Show', NULL);
      if (!is_null($DisplayLocation)) {
         switch ($DisplayLocation) {
            case 'every':
            case 'custom':
               SaveToConfig('Plugins.Online.Location', $DisplayLocation);
               break;
            case 'discussion':
               SaveToConfig('Plugins.Online.Location', 'discussions');
               break;
            case 'discussionsonly':
               SaveToConfig('Plugins.Online.Location', 'discussionlists');
               break;
         }
         
         RemoveFromConfig('WhosOnline.Location.Show');
      }
      
      $HideForGuests = C('WhosOnline.Hide', NULL);
      if (!is_null($HideForGuests)) {
         if ($HideForGuests) {
            SaveToConfig('Plugins.Online.HideForGuests', 'true');
         } else {
            SaveToConfig('Plugins.Online.HideForGuests', 'false');
         }
         
         RemoveFromConfig('WhosOnline.Hide');
      }
      
   }
   
   public function Structure() {
      Gdn::Structure()->Reset();
      Gdn::Structure()->Table('Online')
         ->Column('UserID', 'int(11)', FALSE, 'primary')
         ->Column('Name', 'varchar(64)', NULL)
         ->Column('Timestamp', 'datetime')
         ->Set(FALSE, FALSE); 
   }
}
