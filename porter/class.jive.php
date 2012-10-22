<?php
/**
 * phpBB exporter tool
 *
 * @copyright Vanilla Forums Inc. 2010
 * @license Proprietary
 * @package VanillaPorter
 */

/* How to use this exporter.
 * 1. Create a folder called jive in the same folder as this file.
 * 2. Make sure the folder has 777 permissions.
 * 3. Create a mysql database with the same name as the postgres database.
 * 4. Symlink this file into the same folder as the porter.
 * 5. Browse to the porter with a ?type=jive&step=convert query string.
 * 6. Run the porter.
 * 7. Browse to the porter with a ?type=jive query string.
 * 8. Run the porter.
 */

class Jive extends ExportController {
   static $Extensions = array(
      "text/html" => '.html',
      "application/zip" => '.zip',
      "application/pdf" => '.pdf',
      "image/jpeg" => '.jpg',
      "application/postscript" => '.ps',
      "audio/mpeg" => '.mpg',
      "image/gif" => '.gif',
      "image/png" => '.png',
      "application/msword" => '.doc');
   
   static $Increments = array();
   
   const TYPE_BLOGPOST = 38;
   const TYPE_COMMUNITY = 14;
   const TYPE_SYSTEM = -2;
   const TYPE_SOCIALGROUP = 700;
   const TYPE_DOCUMENT = 102;
   const TYPE_BLOG = 37;
   const TYPE_COMMENT = 105;
   const TYPE_MESSAGE = 2;
   
   /**
    * @param ExportModel $Ex 
    */
   protected function ForumExport($Ex) {
      $this->Ex->TestMode = FALSE;
      
      $Step = $_GET['step'];
      
      if ($Step == 'convert') {
         // Convert all of the tables to csv and load them into mysql.
         echo '<pre>';
         $Tables = $this->ExportPostgresCSV();
         $this->ImportCSVs($Tables);
         echo '</pre>';
         die();
      }
      
      $Ex->Query("create index ix_user_userid on jiveuser (userid)");
      $Ex->Query("create index ix_attachment_objectid on jiveattachment (objectid)");
      $Ex->Query("create index ix_thread_threadid on jivethread (threadid)");
      $Ex->Query("create index ix_message_messageid on jivemessage (messageid)");
      $Ex->Query("create index ix_userperm_userid on jiveuserperm (userid)");
      $Ex->Query("alter table jiveuser add column permission int null");
      
      // Begin.
      $Ex->BeginExport('', 'Jive', array('HashMethod' => 'reset'));
      
      $User_Map = array(
          'userid' => 'UserID',
          'email' => 'Email',
          'username' => 'Name',
          'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
          'modificationdate' => array('Column' => 'DateUpdated', 'Filter' => array($this, 'JiveDate')),
          'lastloggedin' => array('Column' => 'DateLastActive', 'Filter' => array($this, 'JiveDate')),
          'passwordhash' => 'Password',
          'firstname' => array('Column' => 'FirstName', 'Type' => 'varchar(30)'),
          'lastname' => array('Column' => 'LastName', 'Type' => 'varchar(30)'),
          'attachmentid' => array('Column' => 'Photo', 'Filter' => array($this, 'JivePhoto')),
          'emailvisible' => 'ShowEmail');
      $Ex->ExportTable('User', 
         "select u.*, !u.userenabled as Banned, a.attachmentid, a.filename, a.contenttype
         from :_user u
         left join :_attachment a
            on a.objecttype = 501 and filename like '%_72' and a.objectid = u.userid;"
         , $User_Map);
      
      // Roles.
      $Ex->ExportTable('Role', "
         select 2 as RoleID, 'Guest' as Name
         union all select 3, 'Confirm Email'
         union all select 4, 'Applicant'
         union all select 8, 'Member'
         union all select 16, 'Administrator'
         union all select 32, 'Moderator'");
      
      // Permissions
      $Ex->ExportTable('Permission', "
         select 2 as RoleID, 'View' as _Permissions
         union all select 3, 'View,Garden.SignIn.Allow'
         union all select 4, 'View,Garden.SignIn.Allow'
         union all select 8, 'View,Garden.SignIn.Allow,Vanilla.Discussions.Add,Vanilla.Comments.Add'
         union all select 16, 'All'
         union all select 32, 'View,Garden.SignIn.Allow,Vanilla.Discussions.Add,Vanilla.Comments.Add,Garden.Moderation.Manage'
      ");
      
      // User roles.
      $Ex->Query("update jiveuser u
         join jiveuserperm up
            on up.userid = u.userid
         set u.permission = 16
         where up.permissiontype = 1
            and up.permission = 59");
	
      $Ex->Query("update jiveuser u
         join jiveuserperm up
            on up.userid = u.userid
         set u.permission = 32
         where up.permissiontype = 1
            and up.permission = 7
            and u.permission is null");
      
      $UserRole_Map = array(
          'userid' => 'UserID',
          'roleid' => 'RoleID');
      $Ex->ExportTable('UserRole', "select userid, coalesce(permission, 8) as roleid from :_user", $UserRole_Map);
      
      
      $Now = time() * 1000;
      
      $Category_Map = array(
          'id' => 'CategoryID',
          'name' => 'Name',
          'description' => 'Description',
          'displayname' => 'UrlCode',
          'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
          'modificationdate' => array('Column' => 'DateUpdated', 'Filter' => array($this, 'JiveDate')),
          'parentid' => 'ParentCategoryID');
      
      list($MinComID, $MaxComID) = $this->GetMax('community', 'communityid');
      list($MinBlogID, $MaxBlogID) = $this->GetMax('blog', 'blogid');
      
      $BInc = $MaxComID - $MinBlogID + 1;
      self::$Increments[self::TYPE_BLOG] = $BInc;
      $Inc = $BInc + $MaxBlogID;
      
      
      $Ex->ExportTable('Category', 
      "select
         c.communityid as id,
         c.name,
         c.description,
         c.displayname,
         c.creationdate,
         c.modificationdate,
         c2.communityid as parentid,
         1 as Sort
      from :_community c
      left join jivecommunity c2
         on c2.lft < c.lft and c2.rgt > c.rgt
      
      union all
      
      select blogid + $BInc, name, description, coalesce(nullif(displayname, ''), concat('b-', blogid)), creationdate, modificationdate, $Inc + 1 as parentid, 2
      from :_blog
      
      union all
      
      select $Inc + 1 as id, 'Blogs' as name, '' as description, 'blogs', $Now, $Now, null, 2 as Sort
      ", $Category_Map);
      
      // Discussions.
      $Discussion_Map = array(
          'threadid' => 'DiscussionID',
          'subject' => 'Name',
          'body' => array('Column' => 'Body', 'Filter' => array($this, 'StripBody')),
          'userid' => 'InsertUserID',
          'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
          'categoryid' => 'CategoryID',
          'type' => array('Column' => 'Type', 'Filter' => array($this, 'DiscussionFilter')),
          'foreignid' => array('Column' => 'ForeignID', 'Type' => 'varchar(100)', 'Filter' => array($this, 'DiscussionFilter')),
          'attributes' => array('Column' => 'Attributes', 'Type' => 'text', 'Filter' => array($this, 'DiscussionFilter')),
          'sourceid' => array('Column' => 'SourceID', 'Type' => 'varchar(100)'));
      
      list($MinThreadID, $MaxThreadID) = $this->GetMax('thread', 'threadid');
      list($MinBlogPostID, $MaxBlogPostID) = $this->GetMax('blogpost', 'blogpostid');
      
      $BlogPostInc = $MaxThreadID - $MinBlogPostID + 1;
      self::$Increments[self::TYPE_BLOGPOST] = $BlogPostInc;
      
      $Sql = "/* All discussions. */
select 
	t.threadid,
	m.subject,
	m.body,
   'Html' as Format,
	m.userid,
	t.creationdate,
	t.containerid as categoryid,
	null as type,
	null as foreignid,
	null as attributes,
   null
from jivethread t
join jivemessage m
	on t.rootmessageid = m.messageid
where m.containertype = 14

union all

/* All blog posts. */
select
	b.blogpostid + $BlogPostInc,
	b.subject,
	b.body,
   'Html' as Format,
	b.userid,
	b.publishdate,
	b.blogid + $BInc,
	'blog',
	b.blogpostid,
	null,
   b.permalink
from jiveblogpost b";
      
      $Ex->ExportTable('Discussion', $Sql, $Discussion_Map);
      
      // Comments.
      
      list($MinMessageID, $MaxMessageID) = $this->GetMax('message', 'messageid');
      list($MinCommentID, $MaxCommentID) = $this->GetMax('comment', 'commentid');
      
      $CommentInc = $MaxMessageID - $MinCommentID + 1;
      self::$Increments[self::TYPE_COMMENT] = $CommentInc;
      
      $Comment_Map = array(
          'messageid' => 'CommentID',
          'threadid' => 'DiscussionID',
          'userid' => 'InsertUserID',
          'ip' => 'InsertIPAddress',
          'body' => array('Column' => 'Body', 'Filter' => array($this, 'StripBody')),
          'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
          'sourceid' => array('Column' => 'SourceID', 'Type' => 'varchar(100)')
      );
      
      $Sql = "select
	m.messageid,
	m.threadid,
	m.userid,
	null as ip,
	m.body,
	'Html' as Format,
	m.creationdate,
	null as sourceid
from jivemessage m
left join jivethread t
	on m.threadid = t.threadid
where m.messageid <> t.rootmessageid

union all

select
	c.commentid + $CommentInc,
	c.objectid + $BlogPostInc,
	c.userid,
	c.ip,
	c.body,
	'Html' as Format,
	creationdate,
	c.commentid as sourceid
from jivecomment c
where c.objecttype in (38)";
      
      $Ex->ExportTable('Comment', $Sql, $Comment_Map);
      
      // Tags.
      $Tag_Map = array(
          'tagid' => 'TagID',
          'tagname' => 'Name',
          'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')));
      $Ex->ExportTable('Tag', 'select * from :_tag', $Tag_Map);
      
      // Tag discussion.
      $TagDiscussion_Map = array(
          'tagid' => 'TagID',
          'containerid' => array('Column' => 'DiscussionID', 'Filter' => array($this, 'Inc'))
      );
      $Ex->ExportTable('TagDiscussion', 'select * from :_tagcloud', $TagDiscussion_Map);
      
      // UserMeta.
      $UserMeta_Map = array(
          'userid' => 'UserID',
          'name' => 'Name',
          'propvalue' => 'Value');
      $Ex->ExportTable('UserMeta', 'select * from :_userprop', $UserMeta_Map);
      
      // Media.
      $Media_Map = array(
          'attachmentid' => 'MediaID',
          'filname' => 'Name',
          'contenttype' => 'Type',
          'filesize' => 'Size',
          'foreignid' => array('Column' => 'ForeignID', 'Filter' => array($this, 'Inc')),
          'creationdate' => array('DateInserted', 'Filter' => array($this, 'JiveDate'))
      );
      
      $Sql = 
"select
	t.threadid, 
	a.*,
	concat('/jivebinstore/attachment-', attachmentid, '-', filename) as Path,
	case when t.threadid is not null then 1 else a.objecttype end as containertype,
	case when t.threadid is not null then 'Discussion' when objecttype = 2 then 'Comment' else 'Discussion' end as ForeignTable,
	case when t.threadid is not null then threadid else a.objectid end as ForeignID
from jiveattachment a
left join jivethread t
	on a.objectid = t.rootmessageid and a.objecttype = 2
where objecttype in (38, 2, 102);";
      
      $Ex->ExportTable('Media', $Sql, $Media_Map);
      
      // Export the conversations.
      $this->_ExportConversations();
      
      // End.
      $Ex->EndExport();
   }
   
   protected function _ExportConversations() {
      $Ex = $this->Ex;
      
      $Ex->Query('drop table if exists z_pmto;');

      $Ex->Query("create table z_pmto (
         id bigint,
         userid bigint,
         primary key(id, userid));");

      $Ex->Query('insert ignore z_pmto (id, userid)
         select pmessageid, senderid
         from jivepmessage;');

      $Ex->Query("insert ignore z_pmto (id, userid)
         select pmessageid, recipientid
         from jivepmessage;");

      $Ex->Query("drop table if exists z_pmto2;");

      $Ex->Query("create table z_pmto2 (
           id int unsigned,
           userids varchar(250),
           primary key (id)
         );");

      $Ex->Query("insert ignore z_pmto2 (id, userids)
         select
           id,
           group_concat(userid order by userid)
         from z_pmto
         group by id;

         drop table if exists z_pm;

         create table z_pm (
           id int unsigned,
           subject varchar(255),
           subject2 varchar(255),
           userids varchar(250),
           groupid int unsigned
         );");

      $Ex->Query("insert z_pm (
           id,
           subject,
           subject2,
           userids
         )
         select
           pm.pmessageid,
           pm.subject,
           case when pm.subject like 'Re: %' then trim(substring(pm.subject, 4)) else pm.subject end as subject2,
           t.userids
         from jivepmessage pm
         join z_pmto2 t
           on t.id = pm.pmessageid;");

      $Ex->Query("create index z_idx_pm on z_pm (id);");

      $Ex->Query("drop table if exists z_pmgroup;");

      $Ex->Query("create table z_pmgroup (
           groupid int unsigned,
           subject varchar(255),
           userids varchar(250)
         );");

      $Ex->Query("insert z_pmgroup (
           groupid,
           subject,
           userids
         )
         select
           min(pm.id),
           pm.subject2,
           pm.userids
         from z_pm pm
         group by pm.subject2, pm.userids;");

      $Ex->Query("create index z_idx_pmgroup on z_pmgroup (subject, userids);");
      $Ex->Query("create index z_idx_pmgroup2 on z_pmgroup (groupid);");

      $Ex->Query("update z_pm pm
         join z_pmgroup g
           on pm.subject2 = g.subject and pm.userids = g.userids
         set pm.groupid = g.groupid;

         select *
         from z_pm;");
      
      // Conversation.
      $Conversation_Map = array(
         'pmessageid' => 'ConversationID',
         'senderid' => 'InsertUserID',
         'RealSubject' => array('Column' => 'Subject', 'Type' => 'varchar(250)'),
         'creationdate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate'))
      );
      $Sql = "select
           g.subject as RealSubject,
           pm.*
         from jivepmessage pm
         join z_pmgroup g
           on g.groupid = pm.pmessageid";
      $Ex->ExportTable('Conversation', $Sql, $Conversation_Map);
      
      // Comversation message.
      $ConversationMessage_Map = array(
          'pmessageid' => 'MessageID',
          'groupid' => 'ConversationID',
          'senderid' => 'InsertUserID',
          'body' => array('Column' => 'Body', 'Filter' => array($this, 'StripBody')),
          'sentdate' => 'DateInserted'
      );
      $Sql = "select
         pm.*,
         pm2.groupid,
         'Html' as Format
       from jivepmessage pm
       join z_pm pm2
         on pm.pmessageid = pm2.id;";
      $Ex->ExportTable('ConversationMessage', $Sql, $ConversationMessage_Map);
      
      // User Conversation.
      $UserConversation_Map = array(
         'userid' => 'UserID',
         'groupid' => 'ConversationID'
      );
      $Ex->ExportTable('UserConversation',
      "select
         g.groupid,
         t.userid
       from z_pmto t
       join z_pmgroup g
         on g.groupid = t.id;", $UserConversation_Map);

      $Ex->Query('drop table if exists z_pmto');
      $Ex->Query('drop table if exists z_pmto2;');
      $Ex->Query('drop table if exists z_pm;');
      $Ex->Query('drop table if exists z_pmgroup;');
   }
   
   
   public function Inc($Value, $ColumnName, $Row) {
      $ContainerType = $Row['containertype'];
      if (isset(self::$Increments[$ContainerType]))
         return $Value + self::$Increments[$ContainerType];
      return $Value;
   }
   
   public function DiscussionFilter($Value, $ColumnName, $Row) {
      if ($Value)
         return $Value;
      
      if (strpos($Row['body'], '<body><p>This thread ha') !== FALSE) {
         if (preg_match('`"(http://[^"]+)"`', $Row['body'], $Matches)) {
            $Url = $Matches[1];
            $ID = NULL;
            if (preg_match('`(\d+)`', $Url, $Matches))
               $ID = $Matches[1];
            
            switch ($ColumnName) {
               case 'Type':
                  return 'page';
               case 'ForeignID':
                  return $ID;
               case 'Attributes':
                  return serialize(array('ForeignUrl' => $Url));
               default:
                  return $ColumnName;
            }
         } else {
            return NULL;
         }
      } else {
         return NULL;
      }
   }
   
   public function GetMax($TableName, $Column) {
      $Sql = "select min($Column) as MinValue, max($Column) as MaxValue from :_$TableName";
      $Result = $this->Ex->Query($Sql, TRUE);
      $Row = mysql_fetch_assoc($Result);
      if ($Row)
         return array($Row['MinValue'], $Row['MaxValue']);
      return 0;
   }
   
   protected function _DefinePosgresTable($Path, $TableName, $ColumnInfo) {
      $TypeMaps = array("character varying" => 'varchar', 'timestamp without time zone' => 'datetime', 'bytea' => 'text');
      
      // Loop through the columns and buld up a tabledef.
      $Defs = array();
      foreach ($ColumnInfo as $ColumnName => $Info) {
         $ColumnName = trim($ColumnName);
         if (!$ColumnName) {
            continue;
         }
         
         $Type = $Info['data_type'];
         if (isset($TypeMaps[$Type]))
            $Type = $TypeMaps[$Type];

         if ($Info['character_maximum_length']) {
            $Defs[$ColumnName] = "$ColumnName $Type({$Info['character_maximum_length']})";
         } else
            $Defs[$ColumnName] = $ColumnName.' '.$Type;
      }

      // Drop the table.
      $this->Ex->Query("drop table if exists `$TableName`");

      // Create the table.
      $CreateDef = "create table `$TableName` (\n".implode(",\n", $Defs).')';
      $this->Ex->Query($CreateDef, TRUE);
   }
   
   public function PgConnect() {
      $ConnectionString = "host={$this->Ex->_Host} port=5432 dbname={$this->Ex->_DbName} user=postgres password=fr0d0";
      $pg = pg_connect($ConnectionString);
      return $pg;
   }
   
   public function ExportPostgresCSV() {
      $pg = $this->PgConnect();
      
      if (!$pg)
         throw new Exception('Could not connect to postgres.');
      
      $Sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';";
      
      $Result = pg_query($pg, $Sql);
      $Rows = pg_fetch_all($Result);
      pg_free_result($Result);
      
      $ImportTables = array();
      
      foreach ($Rows as $Row) {
         $TableName = $Row['table_name'];
         $Name = substr($TableName, strlen($this->Ex->Prefix));
      
         // Save the table to csv.
         $Path = dirname(__FILE__)."/{$this->Ex->Prefix}/$Name.csv";
         
         // Get all of the columns from the table.
         $Sql = "SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name ='$TableName' order by ordinal_position;";
         pg_send_query($pg, $Sql);
         $Result = pg_get_result($pg);
         $Error = pg_result_error($Result);
         if ($Error) {
            echo "$Sql\n$Error";
            continue;
         }
         $Data = pg_fetch_all($Result);
         
         $Columns = array();
         $ByteColumns = array();
         foreach ($Data as $Row) {
            if ($Row['data_type'] == 'bytea') {
               $ByteColumns[$Row['column_name']] = $Row;
            } else {
               $Columns[$Row['column_name']] = $Row;
            }
         }
         
         $ImportTables[$TableName] = array('Path' => $Path, 'Columns' => $Columns, 'ByteColumns' => $ByteColumns);
         
         // Copy the table to csv.
         $ColumnsString = implode(', ',array_keys($Columns));
         $Sql = "copy $TableName ($ColumnsString) to '$Path' with csv header;";
         
//         pg_send_query($pg, $Sql);
//         $Result = pg_get_result($pg);
//         $Error = pg_result_error($Result);
//         if ($Error) {
//            echo "$Sql\n$Error";
//            continue;
//         }
//         pg_free_result($Result);  
      }
      pg_close();
      return $ImportTables;
   }
   
   public function ImportCSVs($ImportTables) {
      foreach ($ImportTables as $TableName => $Info) {
         $Path = $Info['Path'];
         $Columns = $Info['Columns'];
         $ByteColumns = $Info['ByteColumns'];
         
//         $this->ImportCSV($Path, $TableName, $Columns);
         
         if (count($ByteColumns) > 0) {
            $this->ExportFiles($TableName, $Columns, $ByteColumns);
         }
      }
   }
   
   public function JivePhoto($Value, $ColumnName, $Row) {
      if (!$Row['filename'])
         return NULL;
      $Result = 'jivebinstore/'.$this->AddExtension("attachment-$Value-{$Row['filename']}", $Row['contenttype']);
      return $Result;
   }
   
   public function ExportFiles($TableName, $Columns, $ByteColumns) {
      
      if ($TableName != $this->Ex->Prefix.'binstore')
         return;
      
      echo $TableName;
      
      $Directory = dirname(__FILE__)."/{$this->Ex->Prefix}/uploads/{$TableName}";
      if (!file_exists($Directory))
         mkdir($Directory, 0777, TRUE);
      
      $pg = $this->PgConnect();
      $i = 0;
      $j = 10000;
      
      do  {
         $Sql = "SET bytea_output = 'escape'; select b.*, a.filename, a.filesize, a.contenttype, a.objecttype from $TableName b join {$this->Ex->Prefix}attachment a on 'attachment-' || a.attachmentid = b.binkey limit $j offset $i";
         $Result = pg_query($pg, $Sql);
         
         $Count = 0;
         while (($Row = pg_fetch_assoc($Result)) !== FALSE) {
            // Figure out the filename.
            $Filename = $Row['binkey'].'-'.$this->AddExtension($Row['filename'], $Row['contenttype']);
            $Path = $Directory.'/'.$Filename;
            
            // Change the filename for profile pictures.
            if ($Row['objecttype'] == 501) {
               if (substr_compare($Row['filename'], '_72', -3) == 0) {
                  $Basename = 'n'.$Row['binkey'].'-'.$this->AddExtension(substr($Row['filename'], 0, -3), $Row['contenttype']);
               } elseif (substr_compare($Row['filename'], '_350', -4) == 0) {
                  $Basename = 'p'.$Row['binkey'].'-'.$this->AddExtension(substr($Row['filename'], 0, -4), $Row['contenttype']);
               } else {
                  continue;
               }
               
               $Path = $Directory.'/'.$Basename;
            } else {
//               continue;
            }
            
//            echo $Path."\n";
            
            $Data = pg_unescape_bytea($Row['bindata']);
            $SizeDiff = $Row['datasize'] - $Row['filesize'];
            if ($SizeDiff > 0)
               $Data = substr($Data, $SizeDiff);
            
            $fp = fopen($Path, "wb");
            fwrite($fp, $Data);
            fclose($fp);
            
            $Count++;
         }
         if ($Count < $j - 1)
            break;
         
         $i += $j;
      } while ($i < $j * 100);
      
      switch ($TableName) {
         case 'jivebinstore':
      }
   }
   
   public function ImportCSV($Path, $TableName, $ColumnInfo = array()) {
      $this->_DefinePosgresTable($Path, $TableName, $ColumnInfo);

      $this->Ex->Query("truncate table `$TableName`;");

      $QPath = mysql_escape_string($Path);

      $Sql = "load data infile '$QPath' into table $TableName
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         lines terminated by '\\n'
         ignore 1 lines";
      $this->Ex->Query($Sql);
   }
   
   public function JiveDate($Value) {
      if (!$Value)
         return NULL;
      
      return date('Y-m-d H:i:s', $Value / 1000);
   }
   
   public function StripBody($Value) {
      if (substr_compare($Value, '<body>', 0, strlen('<body>')) == 0)
         $Value = substr($Value, strlen('<body>'));
      if (substr_compare($Value, '</body>', -strlen('</body>')) == 0)
         $Value = substr($Value, 0, -strlen('</body>'));
      return $Value;
   }
   
   public function AddExtension($Filename, $ContentType) {
      if (strpos($Filename, '.') === FALSE && isset(self::$Extensions[$ContentType])) {
         $Ext = self::$Extensions[$ContentType];
         $Filename .= $Ext;
      }
      return $Filename;
   }
}