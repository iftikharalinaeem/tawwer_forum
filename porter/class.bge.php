<?php
/**
 * Vanilla 2 exporter tool
 *
 * @copyright Vanilla Forums Inc. 2010
 * @license Proprietary
 * @package VanillaPorter
 */

class BGE extends ExportController {
   /** @var array Required tables => columns */
   protected $_SourceTables = array();
   
   /**
    * @param ExportModel $Ex
    */
   protected function ForumExport($Ex) {
      $this->Ex = $Ex;

      $Ex->BeginExport('', 'Big Green Egg', array('HashMethod' => 'Django'));
      
      // Insert users for the recipes.
      $Ex->Query('alter table :_users add column tmp tinyint(1) default 0');

      $Ex->Query("
         insert ignore mos_users (
           username,
           email,
           registerdate,
           password,
           tmp
         )
         select
           imgauthor,
           imgemail,
           min(imgdate),
           'xxx',
           1
         from :_recipes
         where imguserid is null
           and coalesce(imgauthor, '') <> ''
         group by imgauthor, imgemail");
      
      // Users
      $User_Map = array(
         'id'=>'UserID',
         'username'=>'Name',
         'email'=>'Email',
         'registerDate'=>'DateInserted',
         'lastvisitDate'=>'DateLastActive',
         'showemail'=>'ShowEmail',
         'DELETED'=>'Deleted',
         'Admin'=>array('Column'=>'Admin','Type'=>'tinyint(1)')
      );
      $Ex->ExportTable('User', "
         SELECT 
            u.*, 
            concat('md5$$', password) as `Password`,
            concat('avatars/', sbu.avatar) as `Photo`,
            case u.usertype when 'superadministrator' then 1 else 0 end as Admin
         FROM :_users u 
         left join :_sb_users sbu 
            on sbu.userid = u.id", $User_Map);

  
      // Roles
      $Role_Map = array(
          'id'=>'RoleID',
          'name'=>'Name'
      );
      $Ex->ExportTable('Role', "
      select 2 as id, 'Guest' as name
      union select 3, 'Confirm Email'
      union select 4, 'Applicant'
      union select 8, 'Member'
      union select 16, 'Administrator'
      union select 32, 'Moderator'", $Role_Map);
      
      // UserRoles
      $Ex->ExportTable('UserRole', "
         select id as UserID, 16 as RoleID
         from :_users where usertype = 'superadministrator'
         union select userid, 32
         from :_sb_users where moderator = 1
         union select id, 8
         from :_users where coalesce(usertype, '') <> 'superadministrator'");
      
      // Permissions.
      $Ex->ExportTable('Permission',
      "select 2 as RoleID, 'View' as _Permissions
      union
      select 3 as RoleID, 'View' as _Permissions
      union
      select 16 as RoleID, 'All' as _Permissions", array('_Permissions' => array('Column' => '_Permissions', 'Type' => 'varchar(20)')));

      
      // Categories
      $Category_Map = array(
          'id'=>'CategoryID',
          'name'=>'Name',
          'parent'=>'ParentCategoryID',
          'locked'=>'Archived',
          'ordering'=>'Sort',
          'description'=>'Description'
      );
      $Ex->ExportTable('Category', "
         select id, name, parent, locked, ordering, description
         from :_sb_categories
         union
         select 200, 'Cookbook', null, 0, 100, ''
         union
         select id, name, 200, 0, 100, description
         from :_categories
         where section = 'com_recipes'", $Category_Map);
      
      // Assign users to the recipes.
      $Ex->Query("update :_recipes r
      join :_users u
        on r.imgauthor = u.username
      set r.imguserid = u.id
      where r.imguserid is null");
      
      // We have to figure out the max discussion id so recipes won't clash.
      $q = $Ex->Query('select max(id) as id from :_sb_messages m where m.id = m.thread');
      $Row = mysql_fetch_assoc($q);
      $ID = $Row['id'];
      mysql_free_result($q);
      
      // Discussions.
      $Discussion_Map = array(
          'id' => 'DiscussionID',
          'subject' => 'Name',
          'message' => 'Body',
          'catid' => 'CategoryID',
          'userid' => 'InsertUserID',
          'ip' => 'InsertIPAddress',
          'locked' => 'Closed',
          'hits' => 'CountViews'
      );
      $Ex->ExportTable('Discussion', "
         select
            m.id, m.subject, m.catid, m.userid, m.ip, m.locked, m.hits,
            from_unixtime(m.time) as DateInserted,
            mt.message,
            'BBCode' as Format
         from :_sb_messages m
         left join :_sb_messages_text mt
            on m.id = mt.mesid
         where m.id = m.thread
         
         union all
         
         select
            r.id + $ID, r.imgtitle, r.catid, r.imguserid, null, 0, imgcounter,
           from_unixtime(imgdate),
           concat('<h4>Ingredients</h4>', replace(r.ingredients, '&#39;', '\"'), '<h4>Instructions</h4>', r.instructions, '<h4>Notes</h4>', r.notes,
           '<p><b>Number of Servings:</b> ', coalesce(r.numberofservings, ''), '</p><p><b>Time to Prepare:</b> ', coalesce(r.timetoprepare, ''), '</p>'),
           'Raw' as Format
         from mos_recipes r", $Discussion_Map);
      
      // Comments.
      $Discussion_Map = array(
          'id' => 'CommentID',
          'message' => 'Body',
          'userid' => 'InsertUserID',
          'ip' => 'InsertIPAddress',
          'thread' => 'DiscussionID'
      );
      $Ex->ExportTable('Comment', "
         select
            m.*,
            from_unixtime(m.time) as DateInserted,
            mt.*,
            'BBCode' as Format
         from :_sb_messages m
         left join :_sb_messages_text mt
            on m.id = mt.mesid
         where m.id <> m.thread", $Discussion_Map);
      
      // Bookmarks.
      $UserDiscussion_Map = array(
          'thread' => 'DiscussionID',
          'userid' => 'UserID'
      );
      $Ex->ExportTable('UserDiscussion', "select s.*, 1 as Bookmarked from :_sb_subscriptions s", $UserDiscussion_Map);
      
      // Media.
      $Media_Map = array(
          'mesid' => 'ForeignID',
          'userid' => 'InsertUserID'
      );
      $Ex->ExportTable('Media', "
      select
        a.*,
        case when m.thread = m.id then 'discussion' else 'comment' end as 'ForeignTable',
        case lcase(right(a.filelocation, 3)) when 'jpg' then 'image/jpg' when 'gif' then 'image/gif' when 'png' then 'image/png' else 'binary/octet-stream' end as Type,
        0 as Size,
        'local' as StorageMethod,
        m.userid,
        from_unixtime(m.time) as DateInserted,
        concat('/FileUpload', substr(a.filelocation, instr(a.filelocation, 'com_simpleboard/uploaded') + length('com_simpleboard/uploaded'))) as Path,
        right(a.filelocation, instr(reverse(a.filelocation), '/') - 1) as Name
      from :_sb_attachments a
      join :_sb_messages m
        on m.id = a.mesid", $Media_Map);
      
      $Ex->EndExport();
  }
}