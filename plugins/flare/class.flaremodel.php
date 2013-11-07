<?php if (!defined('APPLICATION')) exit();

class FlareModel extends Gdn_Pluggable {
   /// Properties ///
   
   protected $currentFlare = array();
   
   /// Methods ///
   
   /**
    * Grab badge information for a given user id, direct from the database.
    * @param type $user_id
    */
   public function addBadgeFlare($user_id) {
      $UserBadgeModel = new UserBadgeModel();
      $user_badges = $UserBadgeModel->GetBadges($user_id)->ResultArray();
      
      foreach ($user_badges as $badge) {
         // If no badge level
         if (!$badge['Level']) {
            $badge['Level'] = 1;
         }

         $this->addFlare($badge['Slug'], array(
            'title' => $badge['Name'], 
            'url' => Gdn_Upload::Url($badge['Photo']),
            'class' => strtolower($badge['Class']), 
            'slug' => $badge['Slug'],
            'sort' => $badge['Level']
         ));
      }
   }

   public function addFlare($key, $flare_row) {
      $this->currentFlare[$key] = $flare_row;
   }
   
   /**
    * 
    * @param type $data
    * @param type $column
    * @return type
    */
   public function fetchUsers($data, $column) {
      $user_ids = array();
      
      foreach ($data as $row) {
         $user_id = GetValue($column, $row);
         
         if ($user_id) {
            $user_ids[] = $user_id;
         }
      }
      
      $result = $this->getIds($user_ids);
      
      return $result;
   }
   
   /**
    * 
    * @param type $user_id
    * @return type
    */
   public function getId($user_id) {
      $cache_key = self::cacheKey($user_id);
      $cache_expire = 10;
      
      // Check the cache for the flare.
      $flare = Gdn::Cache()->Get($cache_key);
      $this->EventArguments['src'] = 'cache';
      
      if ($flare === Gdn_Cache::CACHEOP_FAILURE) {
         // Make sure to clear on every call, as singleton instance will carry 
         // old values.
         $this->currentFlare = array();
         
         // Fire event
         $this->EventArguments['user_id'] = $user_id;
         $this->EventArguments['src'] = 'db';
         $this->FireEvent('get');

         // Add flare from DB
         $this->addBadgeFlare($user_id);
         
         // Append any flare that was added by plugin, then sort and filter below.
         $flare = $this->currentFlare;
         $this->currentFlare = array();
         
         // Sort and filter up only highest achievements for each badge class
         $flare = $this->filterRedundantBadgeClasses($flare);

         Gdn::Cache()->Store($cache_key, $flare, array(Gdn_Cache::FEATURE_EXPIRY => $cache_expire));
      }
      
      return $flare;
   }
   
   /**
    * Take array of all badges that a user has, group them by their classes, 
    * and if there are multiple in each class, only return the highest 
    * badge achieved in that class. 
    * 
    * @param type $flare_array
    * @return type
    */
   public function filterRedundantBadgeClasses($flare_array) {
      $badge_groups = array();
      foreach ($flare_array as $badge_slug => $badge_info) {
         // Not all badges have classes, so just use slug, as that is unique
         if (!isset($badge_info['class'])) {
            $badge_info['class'] = $badge_slug;
         }
         
         $badge_groups[$badge_info['class']][] = $badge_info;
      }
      
      // Sort all the badges in each class, and return the highest from each, 
      // add to new array with only the highest badge from each class.
      $badge_groups_classes_filtered = array();
      foreach($badge_groups as $badge_group) {
         $this->sortBadgesHighestToLowest($badge_group);

         // Return only first badge from each group, which is the highest 
         // achieved, due to above sort.
         $badge_groups_classes_filtered[] = $badge_group[0];
      }
      
      // Now sort the combined array of highest achieved badges
      $this->sortBadgesHighestToLowest($badge_groups_classes_filtered);
      
      return $badge_groups_classes_filtered;
   }
   
   public function sortBadgesHighestToLowest(&$badges_array) {
      uasort($badges_array, function($a, $b) {
         return $a['sort'] < $b['sort'];
      });
   }
   
   /**
    * Like getIds, except provide an array of user ids, and retrieve their 
    * individual badges.
    */
   public function getIds($user_ids = array()) {
      $keys = array_map('FlareModel::cacheKey', $user_ids);
      $cache_flare = Gdn::Cache()->Get($keys);
      $result = array();
      
      $db_keys = array_diff(array_keys($cache_flare), $keys);
      foreach ($db_keys as $key) {
         // Strip cache key.
         $user_id = self::stripCacheKey($key);
         $result[$user_id] = $this->getId($user_id);
      }
      
      foreach ($cache_flare as $key => $flare) {
         $result[self::stripCacheKey($key)] = $flare;
      }
      
      return $result;
   }
   
   public static function cacheKey($user_id) {
      return "flare.$user_id";
   }
   
   public static function stripCacheKey($key) {  
      return rtrim(strrchr($key, '.'), '.');
   }
   
   public function clearCache($user_id) {
      if ($user_id) {
         $cache_key = self::cacheKey($user_id);
         Gdn::Cache()->Remove($cache_key);
      }
   }
   
   protected static $instance;
   /**
    * Return the singleton instance of this class.
    * 
    * @return FlareModel
    */
   public static function instance() {
      if (!isset(self::$instance)) {
         self::$instance = new FlareModel();
      }
      
      return self::$instance;
   }
}