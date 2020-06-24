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

      $this->CacheDelay = c('Plugins.ModList.CacheDelay', self::DEFAULT_CACHE_DELAY);
   }

   /**
    *
    * @param PluginController $sender
    */
   public function pluginController_modList_create($sender) {
      $sender->permission('Garden.Settings.Manage');
      $this->dispatch($sender);
   }

   /**
    *
    * @param PluginController $sender
    */
   public function controller_Search($sender) {
      $sender->deliveryType(DELIVERY_TYPE_DATA);
      $sender->deliveryMethod(DELIVERY_METHOD_JSON);

      // Category (required)
      $categoryID = getIncomingValue('CategoryID');
      $category = CategoryModel::categories($categoryID);
      if (!$category)
         return $sender->render();

      // Search for moderators
      $query = getIncomingValue('q');
      $data = [];
      $database = Gdn::database();
      if ($query) {
         $test = Gdn::sql()->limit(1)->get('User')->firstRow(DATASET_TYPE_ARRAY);
         $userData = Gdn::sql()->select('UserID, Name')->from('User')->like('Name', $query, 'right')->limit(20)->get();
         foreach ($userData as $user) {
            $data[] = ['id' => $user->UserID, 'name' => $user->Name];
         }
      }

      $sender->setData('Tokens', $data);

      $sender->render();
   }

   /**
    * Get a list of moderators
    *
    * Also keeps a local cache of moderators, and sets/gets memcache for
    * efficiency.
    *
    * @param integer $categoryID
    */
   public function moderators($categoryID, $cascade = TRUE) {
      $localKey = $cascade ? "{$categoryID}-cascade" : $categoryID;

      // Check local cache
      if (array_key_exists($categoryID, self::$CategoryModerators))
         return self::$CategoryModerators[$localKey];

      // Check memcache
      $moderatorCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $localKey);
      $moderatorCache = Gdn::cache()->get($moderatorCacheKey);
      if ($moderatorCache !== Gdn_Cache::CACHEOP_FAILURE) {
         self::$CategoryModerators[$localKey] = $moderatorCache;
         return $moderatorCache;
      }

      // Check database

      $categoryIDs = [$categoryID];

      // Walk category ancestors
      if ($cascade) {
         $ancestors = CategoryModel::getAncestors($categoryID, true, true);
         $categoryIDs = array_keys($ancestors);
//         if (!in_array(-1, $CategoryIDs))
//            $CategoryIDs[] = -1;
      }

      $moderators = Gdn::sql()->select('*')
         ->from('CategoryModerator')
         ->whereIn('CategoryID', $categoryIDs)
         ->get()->resultArray();

      Gdn::userModel()->joinUsers($moderators, ['UserID']);

      // Cache it
      self::$CategoryModerators[$localKey] = $moderators;
      Gdn::cache()->store($moderatorCacheKey, $moderators, [
         Gdn_Cache::FEATURE_EXPIRY  => $this->CacheDelay
      ]);

      return $moderators;
   }

   /**
    * Moderator List
    * @param SettingsController $sender
    */
   public function vanillaSettingsController_afterCategorySettings_handler($sender) {

      $categoryID = $sender->data('CategoryID');
      $existingModerators = $this->moderators($categoryID, FALSE);
      $prePopulate = [];
      foreach ($existingModerators as $existingModerator)
         $prePopulate[] = ['id' => $existingModerator['UserID'], 'name' => $existingModerator['Name']];
      $prePopulate = json_encode($prePopulate);

      $sender->addCssFile('token-input.css', 'plugins/ModList');
      $sender->addJsFile('jquery.tokeninput.vanilla.js', 'plugins/ModList');
      $sender->Head->addString('<script type="text/javascript">
   jQuery(document).ready(function($) {
      var tags = $("#Form_Moderators").val();
      if (tags && tags.length)
         tags = tags.split(",");
      $("#Form_Moderators").tokenInput("'.Gdn::request()->url('plugin/modlist/search').'", {
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
      echo $sender->fetchView('categorysettings', '', 'plugins/ModList');
   }

   /**
    * Save changes to moderator list
    *
    * @param type $sender
    * @return type
    */
   public function settingsController_addEditCategory_handler($sender) {
      if (!$sender->Form->authenticatedPostBack()) return;

      $categoryID = $sender->Form->getValue('CategoryID');
      $moderatorListEnabled = $sender->Form->getValue('CategoryModerators');

      // Clear db
      Gdn::sql()->delete('CategoryModerator', [
         'CategoryID'   => $categoryID
      ]);

      // Wipe all mods for category
      if ($moderatorListEnabled) {

         $moderators = $sender->Form->getValue('Moderators');
         $moderators = explode(',', $moderators);
         $insertModerators = [];
         foreach ($moderators as $moderatorID) {
            $insertModerators[] = [
               'UserID'       => $moderatorID,
               'CategoryID'   => $categoryID
            ];
         }
         Gdn::sql()->insert('CategoryModerator', $insertModerators);

      }

      // Clear cache
      $cascadeKey = "{$categoryID}-cascade";
      $directKey = $categoryID;

      $cascadeCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $cascadeKey);
      $directCacheKey = sprintf(ModListPlugin::CACHE_CATEGORY_MODERATORS, $directKey);

      Gdn::cache()->remove($cascadeCacheKey);
      Gdn::cache()->remove($directCacheKey);
   }

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::structure()
         ->table('CategoryModerator')
         ->column('CategoryID', 'int', FALSE, 'primary')
         ->column('UserID', 'int', FALSE, 'primary')
         ->set();
   }

}
