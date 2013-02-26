<?php
/**
 * Pebble (.NET) exporter tool
 *
 * @copyright Vanilla Forums Inc. 2013
 * @license Proprietary
 * @package VanillaPorter
 */

$Supported['pebble'] = array('name'=> 'Pebble', 'prefix'=>'');

class Pebble extends ExportController {
   static $PasswordFormats = array(0 => 'md5', 1 => 'sha1', 2 => 'sha256', 3 => 'sha384', 4 => 'sha512');
   
   /**
    *
    * @param ExportModel $Ex 
    */
   public function ForumExport($Ex) {
      $CharacterSet = $Ex->GetCharacterSet('posts');
      if ($CharacterSet)
         $Ex->CharacterSet = $CharacterSet;
      
      $Ex->BeginExport('', 'Pebble Forum');
      $Ex->SourcePrefix = '';
      
      // User.
      $User_Map = array(
          'id' => 'UserID',
          'username' => 'Name',
          'topics_count' => 'CountDiscussions',
          'posts_count' => 'CountComments',
          'created_at' => 'DateInserted',
          'updated_at' => 'DateUpdated'
          );
      $Ex->ExportTable('User', "
         select u.*,
            r.email as Email,
            if(r.emailVerified = 1, r.bcryptPassword, r2.bcryptPassword) as Password
         from :_local_users u
         left join :_results r on r.username = u.username
         left join :_results r2 on r2.email = u.username", $User_Map);
      
      // Category.
      $Category_Map = array(
          'id' => 'CategoryID',
          'category_id' => 'ParentCategoryID',
          'title' => 'Name',
          'description' => 'Description', 
          'posts_count' => 'CountComments',
          'topics_count' => 'CountDiscussions');
      
      $Ex->ExportTable('Category', "
         select f.*
         from :_forums f;", $Category_Map);
      
      // Discussion.
      $Discussion_Map = array(
          'id' => 'DiscussionID',
          'forum_id' => 'CategoryID',
          'user_id' => 'InsertUserID',
          'created_at' => 'DateInserted',
          'updated_at' => 'DateUpdated',
          'title' => 'Name',
          'Message' => 'Body',
          'hits' => 'CountViews',
          'posts_count' => 'CountComments'
          );
      $Ex->ExportTable('Discussion', "
         select t.*,
            'Html' as Format
         from :_topics t", $Discussion_Map);
      
      // Comment.
      $Comment_Map = array(
          'id' => 'CommentID',
          'topic_id' => 'DiscussionID',
          'user_id' => 'InsertUserID',
          'created_at' => 'DateInserted',
          'updated_at' => 'DateUpdated',
          'body' => 'Body');
      $Ex->ExportTable('Comment', "
         select *,
            'Html' as Format
         from :_posts p", $Comment_Map);
      
      $Ex->EndExport();
   }
   
   public function CleanDate($Value) {
      if (!$Value)
         return NULL;
      if (substr($Value, 0, 4) == '0000')
         return NULL;
      return $Value;
   }

}