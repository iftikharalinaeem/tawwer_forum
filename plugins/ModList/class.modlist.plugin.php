<?php if (!defined('APPLICATION')) exit();

/**
 * Per-Category Moderator List
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package Misc
 */

class ModListPlugin extends Gdn_Plugin {

   /**
    * Cached list of moderators per category
    * @var array
    */
   protected static $CategoryModerators = [];

   /**
    * Length of time to cache per-category moderator lists
    * @var integer Seconds
    */
   protected $CacheDelay;

   /**
    * Cache rendered html for selector queries for a few seconds to reduce load.
    * @const string
    */
   const CACHE_CATEGORY_MODERATORS = 'plugin.modlist.%s.moderators';

   /**
    * Configuration Defaults
    */
   const DEFAULT_STYLE = 'pictures';
   const DEFAULT_CACHE_DELAY = 60;     // seconds

   public function __construct() {
      parent::__construct();

      $this->CacheDelay = C('Plugins.ModList.CacheDelay', self::DEFAULT_CACHE_DELAY);
   }

   /**
    *
    * @param PluginController $Sender
    */
   public function PluginController_ModList_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $this->Dispatch($Sender);
   }

   /**
    *
    * @param PluginController $Sender
    */
   public function Controller_Search($Sender) {
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);

      // Category (required)
      $CategoryID = GetIncomingValue('CategoryID');
      $Category = CategoryModel::Categories($CategoryID);
      if (!$Category)
         return $Sender->Render();

      // Search for moderators
      $Query = GetIncomingValue('q');
      $Data = [];
      $Database = Gdn::Database();
      if ($Query) {
         $Test = Gdn::SQL()->Limit(1)->Get('User')->FirstRow(DATASET_TYPE_ARRAY);
         $UserData = Gdn::SQL()->Select('UserID, Name')->From('User')->Like('Name', $Query, 'right')->Limit(20)->Get();
         foreach ($UserData as $User) {
            $Data[] = ['id' => $User->UserID, 'name' => $User->Name];
         }
      }

      $Sender->SetData('Tokens', $Data);

      $Sender->Render();
   }

   /**
    * Get a list of moderators
    *
    * Also keeps a local cache of moderators, and sets/gets memcache for
    * efficiency.
    *
    * @param integer $CategoryID
    */
   public function Moderators($CategoryID, $Cascade = TRUE) {
      $LocalKey = $Cascade ? "{$CategoryID}-cascade" : $CategoryID;

      // Check local cache
      if (array_key_exists($CategoryID, self::$CategoryModerators))
         return self::$CategoryModerators[$LocalKey];

      // Check memcache
      $ModeratorCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $LocalKey);
      $ModeratorCache = Gdn::Cache()->Get($ModeratorCacheKey);
      if ($ModeratorCache !== Gdn_Cache::CACHEOP_FAILURE) {
         self::$CategoryModerators[$LocalKey] = $ModeratorCache;
         return $ModeratorCache;
      }

      // Check database

      $CategoryIDs = [$CategoryID];

      // Walk category ancestors
      if ($Cascade) {
         $Ancestors = CategoryModel::GetAncestors($CategoryID, true, true);
         $CategoryIDs = array_keys($Ancestors);
//         if (!in_array(-1, $CategoryIDs))
//            $CategoryIDs[] = -1;
      }

      $Moderators = Gdn::SQL()->Select('*')
         ->From('CategoryModerator')
         ->WhereIn('CategoryID', $CategoryIDs)
         ->Get()->ResultArray();

      Gdn::UserModel()->JoinUsers($Moderators, ['UserID']);

      // Cache it
      self::$CategoryModerators[$LocalKey] = $Moderators;
      Gdn::Cache()->Store($ModeratorCacheKey, $Moderators, [
         Gdn_Cache::FEATURE_EXPIRY  => $this->CacheDelay
      ]);

      return $Moderators;
   }

   /**
    * Moderator List
    * @param SettingsController $Sender
    */
   public function vanillaSettingsController_AfterCategorySettings_Handler($Sender) {

      $CategoryID = $Sender->Data('CategoryID');
      $ExistingModerators = $this->Moderators($CategoryID, FALSE);
      $PrePopulate = [];
      foreach ($ExistingModerators as $ExistingModerator)
         $PrePopulate[] = ['id' => $ExistingModerator['UserID'], 'name' => $ExistingModerator['Name']];
      $PrePopulate = json_encode($PrePopulate);

      $Sender->AddCssFile('token-input.css', 'plugins/ModList');
      $Sender->AddJsFile('jquery.tokeninput.vanilla.js', 'plugins/ModList');
      $Sender->Head->AddString('<script type="text/javascript">
   jQuery(document).ready(function($) {
      var tags = $("#Form_Moderators").val();
      if (tags && tags.length)
         tags = tags.split(",");
      $("#Form_Moderators").tokenInput("'.Gdn::Request()->Url('plugin/modlist/search').'", {
         hintText: "Start to type...",
         searchingText: "Searching...",
         searchDelay: 300,
         minChars: 1,
         maxLength: 40,
         preventDuplicates: true,
         prePopulate: '.$PrePopulate.',
         dataFields: ["#Form_CategoryID"],
         onFocus: function() { $(".Help").hide(); $(".HelpTags").show(); }
     });
   });
</script>');

      $Sender->ModList = $this;
      echo $Sender->FetchView('categorysettings', '', 'plugins/ModList');
   }

   /**
    * Save changes to moderator list
    *
    * @param type $Sender
    * @return type
    */
   public function SettingsController_AddEditCategory_Handler($Sender) {
      if (!$Sender->Form->AuthenticatedPostBack()) return;

      $CategoryID = $Sender->Form->GetValue('CategoryID');
      $ModeratorListEnabled = $Sender->Form->GetValue('CategoryModerators');

      // Clear db
      Gdn::SQL()->Delete('CategoryModerator', [
         'CategoryID'   => $CategoryID
      ]);

      // Wipe all mods for category
      if ($ModeratorListEnabled) {

         $Moderators = $Sender->Form->GetValue('Moderators');
         $Moderators = explode(',', $Moderators);
         $InsertModerators = [];
         foreach ($Moderators as $ModeratorID) {
            $InsertModerators[] = [
               'UserID'       => $ModeratorID,
               'CategoryID'   => $CategoryID
            ];
         }
         Gdn::SQL()->Insert('CategoryModerator', $InsertModerators);

      }

      // Clear cache
      $CascadeKey = "{$CategoryID}-cascade";
      $DirectKey = $CategoryID;

      $CascadeCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $CascadeKey);
      $DirectCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $DirectKey);

      Gdn::Cache()->Remove($CascadeCacheKey);
      Gdn::Cache()->Remove($DirectCacheKey);
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('CategoryModerator')
         ->Column('CategoryID', 'int', FALSE, 'primary')
         ->Column('UserID', 'int', FALSE, 'primary')
         ->Set();
   }

}
