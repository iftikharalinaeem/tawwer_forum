<?php if (!defined('APPLICATION')) exit(); 

/**
 * API Mapper v1
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 */

class ApiMapper extends SimpleApiMapper {
   
   public $Version = '1.0';
   
   public function __construct() {
      
      $this->URIMap = array(
         // Categories
         'categories/add'        => 'vanilla/settings/addcategory',
         'categories/edit'       => 'vanilla/settings/editcategory',
         'categories/delete'     => 'vanilla/settings/deletecategory',
         'categories/list'       => 'vanilla/categories/all',
         
         // Discussions
         'discussions/add'       => 'vanilla/post/discussion',
         'discussions/edit'      => 'vanilla/post/editdiscussion',
         'discussions/category'  => 'vanilla/categories',
         'discussions/list'      => 'vanilla/discussions',
          
         // Comments
         'comments/add'          => 'vanilla/post/comment',
         'comments/edit'         => 'vanilla/post/editcomment',
          
         // Badges
         'badges/give'           => 'reputation/badge/giveuser',
         'badges/user'           => 'reputation/badges/user',
         'badges/list'           => 'reputation/badges/all',
         
         // Users
         'users/edit'            => 'dashboard/profile/edit',
         'users/multi'           => 'dashboard/profile/multi',
         'users/notifications'   => 'dashboard/profile/preferences',
         'users/get'             => 'dashboard/profile',
          
         // Roles
         'roles/list'            => 'dashboard/role',
         'roles/get'             => 'dashboard/role',
          
      );
      
      $this->Filter = array(
         'users/notifications'   => array('Profile', 'Preferences')
      );
      
   }
   
   public function Map($APIRequest) {
      $TrimmedRequest = trim($APIRequest, ' /');
      foreach ($this->URIMap as $MatchURI => $MapURI) {
         if (preg_match("`{$MatchURI}(\.(:?json|xml))`i", $TrimmedRequest)) {
            $this->Mapping = $MatchURI;
            return preg_replace("`{$MatchURI}(\.(:?json|xml))`i", "{$MapURI}\$1", $TrimmedRequest);
         }
      }
      
      return $APIRequest;
   }
   
   public function Filter(&$Data) {
      $Filter = GetValue($this->Mapping, $this->Filter, NULL);
      if (empty($Filter)) return;
      
      $Data = ArrayTranslate($Data, $Filter);
   }
   
}