<?php if (!defined('APPLICATION')) exit();
/**
 * Badge Model.
 *
 * @package Reputation
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappmodel.php');
 
/**
 * Badge handling.
 *
 * @package Reputation
 */
class BadgeModel extends BadgesAppModel {
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @access public
    */
   public function __construct() {
      parent::__construct('Badge');
   }
   
   /**
    * Set default select conditions.
    */
   protected function _BeforeGet() {
      
   }
   
   /**
    * Prep data for single badge or array of badges.
    *
    * @param array $Badge
    */
   public function Calculate(&$Badge) {
      if (is_array($Badge) && isset($Badge[0])) {
         // Multiple badges
         foreach ($Badge as &$B) {
            $this->_Calculate($B);
         }
      } elseif ($Badge) {
         // One valid result
         $this->_Calculate($Badge);
      }
   }
   
   /**
    * Prep badge data.
    */
   protected function _Calculate(&$Badge) {
      if (isset($Badge['Attributes']) && !empty($Badge['Attributes']))
         $Badge['Attributes'] = @unserialize($Badge['Attributes']);
      else
         $Badge['Attributes'] = array();
      
      $Badge['Photo'] = Gdn_Upload::Url($Badge['Photo']);
   }
   
   /**
    * Create a new badge. Do not modify an existing badge.
    */
   public function Define($Data) {
      $Slug = GetValue('Slug', $Data);
      $ExistingBadge = $this->GetID($Slug);
      
      return ($ExistingBadge) ? GetValue('BadgeID', $ExistingBadge) : $this->Save($Data);
   }
   
   /**
    * Return list of badges with only latest in each class.
    * 
    * @since 1.0.0
    * @access public
    * 
    * @param array $Badges
    * @return array Filtered badge list.
    */
   public static function FilterByClass($Badges) {
      $FilteredBadges = array();

      foreach ($Badges as $Badge) {
         $Class = GetValue('Class', $Badge);
         
         // Keep highest level badge of each class and all classless badges
         if ($Class) {
            if (isset($FilteredBadges[$Class])) {
               if (GetValue('Level', $Badge) > GetValue('Level', $FilteredBadges[$Class]))
                  $FilteredBadges[$Class] = $Badge;
            }
            else
               $FilteredBadges[$Class] = $Badge;
         }
         else
            $FilteredBadges[] = $Badge;         
      }
      
      return $FilteredBadges;
   }
   
   /**
    * Get badges of a single type.
    *
    * @param string $Type Valid: Custom, Manual, UserCount, Timeout, DiscussionContent.
    * @return Dataset
    */
   public function GetByType($Type) {
      $Result = $this->GetWhere(array('Type' => $Type), 'Threshold', 'desc')->ResultArray();
      $this->Calculate($Result);
      return $Result;
   }
   
   /**
    * Get a single badge by ID, slug, or data array.
    *
    * @param mixed $Badge Int, string, or array.
    * @return mixed Array if badge exists or false.
    */
   public function GetID($Badge) {
      if (is_numeric($Badge)) {
         $Result = parent::GetID($Badge, DATASET_TYPE_ARRAY);
      } elseif (is_string($Badge)) {
         $Result = $this->GetWhere(array('Slug' => $Badge))->FirstRow(DATASET_TYPE_ARRAY);
      } elseif (is_array($Badge)) {
         $Result = $Badge;
      } else {
         return FALSE;
      }
      
      if ($Result) {
         $this->Calculate($Result);
         return $Result;
      }
      
      return FALSE;
   }
   
   /**
    * Get badges list for viewing.
    */
   public function GetList() {
      if (!CheckPermission('Reputation.Badges.Give') && !CheckPermission('Garden.Settings.Manage'))
         $this->SQL->Where('Visible', 1);
      
      $this->SQL->OrderBy('Class, Threshold, Name', 'asc');
      
      return $this->Get();
   }
   
   /**
    * Get badges list for public viewing.
    */
   public function GetFilteredList($UserID, $Exclusive = FALSE) {
      $ListQuery = $this->SQL
         ->Select('b.*')
         ->Select('ub.DateInserted', '', 'DateGiven')
         ->Select('ub.InsertUserID', '', 'GivenByUserID')
         ->Select('ub.Reason')
         ->From('Badge b');
      
      // Only badges this user has earned
      if ($Exclusive)
         $ListQuery->Join('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($UserID).' AND ub.DateCompleted is not null');
      // All badges, highlighting user's earned badges
      else
         $ListQuery->LeftJoin('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($UserID).' AND ub.DateCompleted is not null');
         
      $Badges = $ListQuery->Where('Visible', 1)
         ->Where('Active', 1)
         ->OrderBy('Name', 'asc')
         ->Get()->ResultArray();
      
      $this->Calculate($Badges);
      return $Badges;
   }
   
   /**
    * Get badges for dropdown.
    */
   public function GetMenu() {
      $this->SQL
         ->Where('Active', 1)
         ->OrderBy('Name', 'asc');
      return $this->Get();
   }
   
   /**
    * Insert or update badge data.
    *
    * @param array $Data The badge we're creating or updating.
    */
   public function Save($Data) {
      // See if there is an existing badge.
      if (GetValue('Slug', $Data) && !GetValue('BadgeID', $Data)) {
         $ExistingBadge = $this->GetID($Data['Slug']);
         if ($ExistingBadge) {
            $Different = FALSE;
            foreach ($Data as $Key => $Value) {
               if (array_key_exists($Key, $ExistingBadge) && $ExistingBadge[$Key] != $Value) {
//                  decho($ExistingBadge[$Key], "Existing $Key");
//                  decho($Value, "New $Key");
                  $Different = TRUE;
                  break;
               }
            }
            if (!$Different)
               return $ExistingBadge['BadgeID'];
            $Data['BadgeID'] = $ExistingBadge['BadgeID'];
            
         }
      }
      if (isset($Data['Attributes']) && is_array($Data['Attributes'])) {
         $Data['Attributes'] = serialize($Data['Attributes']);
      }
      if (!isset($Data['BadgeID']))
         TouchValue('Threshold', $Data, 0);
      
      return parent::Save($Data);
   }
}