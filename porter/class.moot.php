<?php
/**
 * Moot exporter tool.
 *
 * Use jsondump to create tables: channel, discussion, comment
 *
 * @copyright Vanilla Forums Inc. 2010
 * @license Proprietary
 * @package VanillaPorter
 */

$Supported['moot'] = array('name'=> 'Moot', 'prefix'=>'');

class Moot extends ExportController {
   /**
    *
    * @param ExportModel $Ex 
    */
   public function ForumExport($Ex) {
      $Ex->BeginExport('', 'Moot');
      $Ex->SourcePrefix = '';

      
      // User.
      // Create a temp user table to generate UserIDs & fake emails
      $Ex->Query("CREATE TABLE MootUsers (
         Name varchar(255) DEFAULT NULL,
         Email varchar(255) DEFAULT NULL,
         UserID int(10) unsigned NOT NULL AUTO_INCREMENT,
         PRIMARY KEY (UserID))");
      $Ex->Query("insert into MootUsers (Name) select distinct author
         from (select d.author from :_discussion d union select c.author from :_comment c) a
         where author <> ''");
      $Ex->Query("update MootUsers set Email = concat('user',UserID,'@deleted.email')");

      $User_Map = array();
      $Ex->ExportTable('User', "
         select u.*,
            NOW() as DateInserted,
            'Reset' as HashMethod
         from MootUsers u
         ", $User_Map);


      // Category.
      // Create a temp category table to generate CategoryIDs
      $Ex->Query("CREATE TABLE MootChannels (
         Name varchar(255) DEFAULT NULL,
         UrlCode varchar(255) DEFAULT NULL,
         CategoryID int(10) unsigned NOT NULL AUTO_INCREMENT,
         PRIMARY KEY (CategoryID))");
      $Ex->Query("insert into MootChannels (Name, UrlCode) select title, category from channel");

      $Category_Map = array();
      $Ex->ExportTable('Category', "
         select c.*,
            -1 as ParentCategoryID
         from MootChannels c
         ", $Category_Map);


      // Discussion.
      // Create a temp discussion table to generate DiscussionIDs
      $Ex->Query("CREATE TABLE MootDiscussions (
         Name varchar(255) DEFAULT NULL,
         MootKey varchar(255) DEFAULT NULL,
         Body text DEFAULT NULL,
         DateInserted datetime DEFAULT NULL,
         InsertUserID int(10) unsigned DEFAULT NULL,
         CategoryID int(10) unsigned DEFAULT NULL,
         DiscussionID int(10) unsigned NOT NULL AUTO_INCREMENT,
         PRIMARY KEY (DiscussionID))");
      $Ex->Query("insert into MootDiscussions (Name, MootKey, Body, DateInserted, InsertUserID, CategoryID)
         select d.title, d.key, d.body, FROM_UNIXTIME(d.date), u.UserID, c.CategoryID
         from :_discussion d
            left join MootChannels c on c.UrlCode = d.category
            left join MootUsers u on u.Name = d.author");

      $Discussion_Map = array();
      $Ex->ExportTable('Discussion', "
         select d.*,
            'Markdown' as Format
         from MootDiscussions d", $Discussion_Map);


      // Comment.
      $Comment_Map = array();
      $Ex->ExportTable('Comment', "
      select c.body as Body,
         d.DiscussionID,
         u.UserID as InsertUserID,
         FROM_UNIXTIME(c.date) as DateInserted,
         'Markdown' as Format
      from :_comment c
        left join MootDiscussions d on d.MootKey = c.key
        left join MootUsers u on u.Name = c.author", $Comment_Map);


      // Cleanup
      $Ex->Query("DROP TABLE IF EXISTS MootUsers");
      $Ex->Query("DROP TABLE IF EXISTS MootChannels");
      $Ex->Query("DROP TABLE IF EXISTS MootDiscussions");
      
      $Ex->EndExport();
   }
}
?>