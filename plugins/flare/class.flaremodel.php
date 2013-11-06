<?php if (!defined('APPLICATION')) exit();

class FlareModel extends Gdn_Pluggable {
   /// Properties ///
   
   protected $currentFlare = array();
   
   /// Methods ///

   public function addFlare($key, $flare_row) {
      $this->currentFlare[$key] = $flare_row;
   }
   
   public function fetchUsers($data, $columns) {
      
   }
   
   public function getId($user_id) {
      $cache_key = self::cacheKey($user_id);
      $cache_expire = 10;
      
      // Make sure to clear on every call, as singleton instance will carry 
      // old values.
      $this->currentFlare = array();
      
      // Check the cache for the flare.
      $flare = Gdn::Cache()->Get($cache_key);
      $this->EventArguments['src'] = 'cache';

      if (!$flare) {
         $flare = array();
         $UserBadgeModel = new UserBadgeModel();
         $user_badges = $UserBadgeModel->GetBadges($user_id)->ResultArray();
         
         foreach ($user_badges as $badge) {
            // If no badge level
            if (!$badge['Level']) {
               $badge['Level'] = 1;
            }
            
            $flare[$badge['Slug']] = array(
                'slug' => $badge['Slug'],
                'title' => $badge['Name'], 
                'url' => $badge['Photo'],
                'class' => strtolower($badge['Class']), 
                'sort' => $badge['Level']
            );
         }

         $this->EventArguments['src'] = 'db';
         Gdn::Cache()->Store($cache_key, $flare, array(Gdn_Cache::FEATURE_EXPIRY => $cache_expire));
      }
      
      // Fire event
      $this->EventArguments['user_id'] = $user_id;
      //$this->EventArguments['flare'] = $flare;
      $this->FireEvent('get');

      // Append any flare that was added by plugin, then sort and filter below.
      $flare += $this->currentFlare;

      // Sort and filter badges
      // Placed after event firing so that newly-added otf badges can be 
      // sorted as well. Downside is that eventargument of flare cannot be 
      // passed.
      $this->currentFlare = $this->filterRedundantBadgeClasses($flare);
      
      return $this->currentFlare;
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
      /*
      $badge_classes = array(
          'Commenter', 
          'Anniversary', 
          'Insightful', 
          'Agree', 
          'Like', 
          'Up', 
          'Awesome', 
          'LOL', 
          'Answerer'
      );
       */
      
      $badge_groups = array();
      foreach ($flare_array as $badge_slug => $badge_info) {
         // Not all badges have classes, so just use slug, as that is unique
         if (!$badge_info['class']) {
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
   
   public function getIds($user_ids = array()) {
      
   }
   
   public static function cacheKey($user_id) {
      return "flare.$user_id";
   }
   
   public function clearCache($user_id) {
      $cache_key = self::cacheKey($user_id);
      Gdn::Cache()->Remove($cache_key);
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