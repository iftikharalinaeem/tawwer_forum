<?php if (!defined('APPLICATION')) exit(); 

/**
 * API Mapper v1
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 */

class ApiMapper implements IApiMapper {
   
   protected $URIMap;
   
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
         'users'                 => 'profile',
         'users/edit'            => 'profile/edit',
         'users/multi'           => 'profile/multi',
          
         // Roles
         'roles'                 => 'role',
         
         // Ranks
         'ranks'                 => 'settings/ranks'
         
      );
   }

   public function Map($APIRequest) {
      
      $TrimmedRequest = trim($APIRequest, ' /');
      foreach ($this->URIMap as $MatchURI => $ResolvedURI) {
         if (preg_match("`{$MatchURI}(\.(:?json|xml))?`i", $TrimmedRequest)) {
            return preg_replace("`{$MatchURI}(\.(:?json|xml))?`i", "{$ResolvedURI}\$1", $TrimmedRequest);
         }
      }
      
      return $APIRequest;
   }
   
}