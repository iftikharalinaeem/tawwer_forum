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
    * @param PluginController $sender
    */
   public function PluginController_ModList_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');
      $this->Dispatch($sender);
   }

   /**
    *
    * @param PluginController $sender
    */
   public function Controller_Search($sender) {
      $sender->DeliveryType(DELIVERY_TYPE_DATA);
      $sender->DeliveryMethod(DELIVERY_METHOD_JSON);

      // Category (required)
      $categoryID = GetIncomingValue('CategoryID');
      $category = CategoryModel::Categories($categoryID);
      if (!$category)
         return $sender->Render();

      // Search for moderators
      $query = GetIncomingValue('q');
      $data = [];
      $database = Gdn::Database();
      if ($query) {
         $test = Gdn::SQL()->Limit(1)->Get('User')->FirstRow(DATASET_TYPE_ARRAY);
         $userData = Gdn::SQL()->Select('UserID, Name')->From('User')->Like('Name', $query, 'right')->Limit(20)->Get();
         foreach ($userData as $user) {
            $data[] = ['id' => $user->UserID, 'name' => $user->Name];
         }
      }

      $sender->SetData('Tokens', $data);

      $sender->Render();
   }

   /**
    * Get a list of moderators
    *
    * Also keeps a local cache of moderators, and sets/gets memcache for
    * efficiency.
    *
    * @param integer $categoryID
    */
   public function Moderators($categoryID, $cascade = TRUE) {
      $localKey = $cascade ? "{$categoryID}-cascade" : $categoryID;

      // Check local cache
      if (array_key_exists($categoryID, self::$CategoryModerators))
         return self::$CategoryModerators[$localKey];

      // Check memcache
      $moderatorCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $localKey);
      $moderatorCache = Gdn::Cache()->Get($moderatorCacheKey);
      if ($moderatorCache !== Gdn_Cache::CACHEOP_FAILURE) {
         self::$CategoryModerators[$localKey] = $moderatorCache;
         return $moderatorCache;
      }

      // Check database

      $categoryIDs = [$categoryID];

      // Walk category ancestors
      if ($cascade) {
         $ancestors = CategoryModel::GetAncestors($categoryID, true, true);
         $categoryIDs = array_keys($ancestors);
//         if (!in_array(-1, $CategoryIDs))
//            $CategoryIDs[] = -1;
      }

      $moderators = Gdn::SQL()->Select('*')
         ->From('CategoryModerator')
         ->WhereIn('CategoryID', $categoryIDs)
         ->Get()->ResultArray();

      Gdn::UserModel()->JoinUsers($moderators, ['UserID']);

      // Cache it
      self::$CategoryModerators[$localKey] = $moderators;
      Gdn::Cache()->Store($moderatorCacheKey, $moderators, [
         Gdn_Cache::FEATURE_EXPIRY  => $this->CacheDelay
      ]);

      return $moderators;
   }

   /**
    * Moderator List
    * @param SettingsController $sender
    */
   public function vanillaSettingsController_AfterCategorySettings_Handler($sender) {

      $categoryID = $sender->Data('CategoryID');
      $existingModerators = $this->Moderators($categoryID, FALSE);
      $prePopulate = [];
      foreach ($existingModerators as $existingModerator)
         $prePopulate[] = ['id' => $existingModerator['UserID'], 'name' => $existingModerator['Name']];
      $prePopulate = json_encode($prePopulate);

      $sender->AddCssFile('token-input.css', 'plugins/ModList');
      $sender->AddJsFile('jquery.tokeninput.vanilla.js', 'plugins/ModList');
      $sender->Head->AddString('<script type="text/javascript">
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
         prePopulate: '.$prePopulate.',
         dataFields: ["#Form_CategoryID"],
         onFocus: function() { $(".Help").hide(); $(".HelpTags").show(); }
     });
   });
</script>');

      $sender->ModList = $this;
      echo $sender->FetchView('categorysettings', '', 'plugins/ModList');
   }

   /**
    * Save changes to moderator list
    *
    * @param type $sender
    * @return type
    */
   public function SettingsController_AddEditCategory_Handler($sender) {
      if (!$sender->Form->AuthenticatedPostBack()) return;

      $categoryID = $sender->Form->GetValue('CategoryID');
      $moderatorListEnabled = $sender->Form->GetValue('CategoryModerators');

      // Clear db
      Gdn::SQL()->Delete('CategoryModerator', [
         'CategoryID'   => $categoryID
      ]);

      // Wipe all mods for category
      if ($moderatorListEnabled) {

         $moderators = $sender->Form->GetValue('Moderators');
         $moderators = explode(',', $moderators);
         $insertModerators = [];
         foreach ($moderators as $moderatorID) {
            $insertModerators[] = [
               'UserID'       => $moderatorID,
               'CategoryID'   => $categoryID
            ];
         }
         Gdn::SQL()->Insert('CategoryModerator', $insertModerators);

      }

      // Clear cache
      $cascadeKey = "{$categoryID}-cascade";
      $directKey = $categoryID;

      $cascadeCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $cascadeKey);
      $directCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $directKey);

      Gdn::Cache()->Remove($cascadeCacheKey);
      Gdn::Cache()->Remove($directCacheKey);
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
