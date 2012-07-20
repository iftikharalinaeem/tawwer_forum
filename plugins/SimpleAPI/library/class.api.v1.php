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
         'categories/add'        => 'settings/addcategory',
         'categories/edit'       => 'settings/editcategory',
         'categories/delete'     => 'settings/deletecategory',
         'categories/list'       => 'categories/all',
         
         // Discussions
         'discussions/add'       => 'post/discussion',
         'discussions/edit'      => 'post/editdiscussion',
         'discussions/category'  => 'categories',
         'discussions/list'      => 'discussions',
          
         // Comments
         'comments/add'          => 'post/comment',
         'comments/edit'         => 'post/editcomment'
      );
   }

   public function Map($APIRequest) {
      
      $TrimmedRequest = trim($APIRequest, ' /');
      if (array_key_exists($TrimmedRequest, $this->URIMap))
         return $this->URIMap[$TrimmedRequest];
      
      return $APIRequest;
   }
   
}