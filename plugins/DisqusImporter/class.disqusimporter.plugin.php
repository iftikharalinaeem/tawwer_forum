<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['DisqusImporter'] = array(
   'Name' => 'Disqus Importer',
   'Description' => 'Imports commments from Disqus into Vanilla.',
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => FALSE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class DisqusImporter extends Gdn_Plugin {
   /// Properties ///
   
   public $BufferSize = 250;
   
   public $Path = '';
   
   /// Methods ///
   
   protected $_Categories = NULL;
   
   public function Insert($Table, $Row = NULL) {
      static $LastTable = NULL;
      static $Rows = array();
      
      if (isset($Row['Attributes']) && is_array($Row['Attributes']))
         $Row['Attributes'] = serialize($Row['Attributes']);
      
      
      if ($Table === NULL) {
         $this->InsertMulti($LastTable, $Rows);
         $LastTable = NULL;
         $Rows = array();
         
         return;
      }
      
      if ($LastTable && $LastTable != $Table || count($Rows) >= $this->BufferSize) {
         $this->InsertMulti($LastTable, $Rows);
         $Rows = array();
      }
      
      $LastTable = $Table;
      $Rows[] = $Row;
   }
   
   public function InsertMulti($Table, $Rows) {
      if (empty($Rows))
         return;
      
      $Px = Gdn::Database()->DatabasePrefix;
      $PDO = Gdn::Database()->Connection();
      
      $Sql = '';
      foreach ($Rows as $Row) {
         if ($Sql)
            $Sql .= ",\n";
         
         $Values = array_map(array($PDO, 'quote'), $Row);
         $Sql .= '('.implode(',', $Values).')';
      }
      
      $Sql = "insert ignore {$Px}$Table\n".
         '('.implode(',', array_keys($Rows[0])).") values\n".
         $Sql;
      
//      echo htmlspecialchars($Sql);
      Gdn::Database()->Query($Sql);
//      die();
   }
   
   public function ParseCategory($Str) {
      $Xml = new SimpleXMLElement($Str);
      $Name = html_entity_decode($Xml->title, ENT_COMPAT, 'UTF-8');
      $Row = array(
          'DisqusID' => (int)$Xml->attributes('dsq', TRUE)->id,
          'Name' => $Name,
          'UrlCode' => Gdn_Format::Url($Name)
      );
      $this->Insert('zDisqusCategory', $Row);
      
//      <category xmlns="http://disqus.com" xmlns:dsq="http://disqus.com/disqus-internals" dsq:id="684542">
//      <forum>cultofmac</forum>
//      <title>General</title>
//      <isDefault>true</isDefault>
//      </category>
   }
   
   public function ParseComment($Str) {
      $Xml = new SimpleXMLElement($Str);
      $Name = html_entity_decode($Xml->title, ENT_COMPAT, 'UTF-8');
      $Row = array(
          'ForeignID' => (int)$Xml->attributes('dsq', TRUE)->id,
          'DisqusDiscussionID' => (int)$Xml->thread->attributes('dsq', TRUE)->id,
          'ParentForeignID' => NULL,
          'Body' => $Xml->message,
          'Format' => 'Html',
          'DateInserted' => $Xml->createdAt,
          'InsertIPAddress' => $Xml->ipAddress,
          'UserName' => $Xml->author->username,
          'UserEmail' => trim($Xml->author->email),
          'UserFullName' => $Xml->author->name,
          'UserAnonymous' => $Xml->author->anonymous,
          'Deleted' => (int)$Xml->isDeleted,
          'Approved' => (int)$Xml->isApproved,
          'Flagged' => (int)$Xml->isFlagged,
          'Spam' => (int)$Xml->isSpam,
          'Highlighted' => (int)$Xml->isHighlighted,
          'UniqueType' => NULL,
          'UniqueID' => NULL
      );
      
      if (!$Row['UserName'])
         $Row['UserName'] = $Row['UserFullName'];
      
      if (preg_match('`^([a-zA-Z]+)-(.+)$`', $Row['UserName'], $Matches)) {
         if (in_array($Matches[1], array('google', 'facebook', 'yahoo', 'twitter', 'openid'))) {
            $Row['UserName'] = $Row['UserFullName'];
            $Row['UniqueType'] = $Matches[1];
            $Row['UniqueID'] = $Matches[2];

            if (!$Row['UserEmail'])
               $Row['UserEmail'] = $Row['UniqueID'].'@noreply.com';
         }
      }
      
      if ($Xml->parent)
          $Row['ParentForeignID'] = $Xml->parent->attributes('dsq', TRUE)->id;
      
      $this->Insert('zDisqusComment', $Row);
      
//      <post dsq:id="154441910">
//         <id>wp_id=2</id>
//         <message><![CDATA[<p>Great idea. Love giva aways, love the blogg. The audience must be complimentary.<br><br><br><br>Mvh<br><br>Kai simon</p>]]></message>
//         <createdAt>2005-11-17T01:23:13Z</createdAt>
//         <author>
//            <email>kai.simon@gmail.com</email>
//            <name>Kai S Fredriksen</name>
//            <isAnonymous />
//         </author>
//         <ipAddress>84.48.142.213</ipAddress>
//         <thread dsq:id="237937629" />
//      </post>
   }
   
   public function ParseDiscussion($Str) {
      $Xml = new SimpleXMLElement($Str);
      $Name = html_entity_decode($Xml->title, ENT_COMPAT, 'UTF-8');
      $Row = array(
          'DisqusID' => (int)$Xml->attributes('dsq', TRUE)->id,
          'ForeignID' => substr($Xml->id, 0, 200),
          'DisqusCategoryID' => $Xml->category->attributes('dsq', TRUE)->id,
          'Name' => $Name,
          'Body' => $Xml->link,
          'Format' => 'Html',
          'DateInserted' => $Xml->createdAt,
          'InsertIPAddress' => $Xml->ipAddress,
          'Closed' => $Xml->isClosed,
          'Deleted' => $Xml->isDeleted,
          'UserName' => $Xml->author->username,
          'UserEmail' => trim($Xml->author->email),
          'UserFullName' => $Xml->author->name,
          'UserAnonymous' => $Xml->author->anonymous,
          'UniqueType' => NULL,
          'UniqueID' => NULL
      );
      
      if (!$Row['UserName'])
         $Row['UserName'] = $Row['UserFullName'];
      
      if (!$Row['UserEmail'] && preg_match('`([a-zA-Z]+)-(.+)$`', $Row['UserName'], $Matches)) {
         $Row['UserName'] = $Row['UserFullName'];
         $Row['UniqueType'] = $Matches[1];
         $Row['UniqueID'] = $Matches[2];
         
         $Row['UserEmail'] = $Row['UniqueID'].'@noreply.com';
      }
      
      $Attributes = array(
          'DisqusID' => $Row['DisqusID'],
          'ForeignUrl' => (string)$Xml->link,
          'RegenerateBody' => TRUE
          );
      $Row['Attributes'] = serialize($Attributes);
         
      $this->Insert('zDisqusDiscussion', $Row);
      
//      <thread xmlns="http://disqus.com" xmlns:dsq="http://disqus.com/disqus-internals" dsq:id="237937629">
//      <id>3</id>
//      <forum>cultofmac</forum>
//      <category dsq:id="684542"/>
//      <link>http://www.cultofmac.com/3/lcitegcult_of_ipodl_citeg_giveaway_2/</link>
//      <title>Cult of iPod Giveaway</title>
//      <message/>
//      <createdAt>2011-02-23T05:29:13Z</createdAt>
//      <author>
   //      <email>leander@cultofmac.com</email>
   //      <name>Leander Kahney</name>
   //      <isAnonymous/>
   //      <username>lkahney</username>
//      </author>
//      <ipAddress>127.0.0.1</ipAddress>
//      <isClosed/>
//      <isDeleted/>
//      </thread>
   }
   
   public function DefineTables() {
      // Define the temp tables.
      Gdn::Structure()->Table('zDisqusCategory')
         ->Column('DisqusID', 'varchar(32)', FALSE, 'primary')
         ->Column('CategoryID', 'int', TRUE)
         ->Column('Name', 'varchar(255)')
         ->Column('UrlCode', 'varchar(255)', TRUE)
         ->Set(TRUE);
      Gdn::SQL()->Truncate('zDisqusCategory');
      
      Gdn::Structure()->Table('zDisqusDiscussion')
         ->Column('DisqusID', 'int', FALSE, 'primary')
         ->Column('DiscussionID', 'int', TRUE)
         ->Column('ForeignID', 'varchar(200)', FALSE, 'index')
         ->Column('DisqusCategoryID', 'int', FALSE)
         ->Column('CategoryID', 'int', TRUE)
         ->Column('Name', 'varchar(100)')
         ->Column('Body', 'text')
         ->Column('Format', 'varchar(20)')
         ->Column('InsertUserID', 'int', TRUE)
         ->Column('DateInserted', 'datetime', TRUE)
         ->Column('InsertIPAddress', 'varchar(15)', TRUE)
         ->Column('Attributes', 'text', TRUE)
         ->Column('Closed', 'tinyint', 0)
         ->Column('Deleted', 'tinyint', 0)
         ->Column('UserName', 'varchar(50)', TRUE)
         ->Column('UserEmail', 'varchar(200)', TRUE)
         ->Column('UserFullName', 'varchar(50)', TRUE)
         ->Column('UserAnonymous', 'tinyint', 0)
         ->Column('UniqueType', 'varchar(32)', TRUE)
         ->Column('UniqueID', 'varchar(32)', TRUE, 'index')
         ->Set(TRUE);
      Gdn::SQL()->Truncate('zDisqusDiscussion');
      
      Gdn::Structure()->Table('zDisqusComment')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('CommentID', 'int', TRUE, 'index')
         ->Column('DisqusDiscussionID', 'int', TRUE, 'index')
         ->Column('DiscussionID', 'int', TRUE)
         ->Column('ParentForeignID', 'int', TRUE)
         ->Column('Body', 'text')
         ->Column('Format', 'varchar(20)')
         ->Column('InsertUserID', 'int', TRUE)
         ->Column('DateInserted', 'datetime', TRUE)
         ->Column('InsertIPAddress', 'varchar(15)', TRUE)
         ->Column('UserName', 'varchar(50)', TRUE)
         ->Column('UserEmail', 'varchar(200)', TRUE)
         ->Column('UserFullName', 'varchar(50)', TRUE)
         ->Column('UserAnonymous', 'tinyint', 0)
         ->Column('UniqueType', 'varchar(32)', TRUE)
         ->Column('UniqueID', 'varchar(32)', TRUE)
         ->Column('Deleted', 'tinyint', 0)
         ->Column('Approved', 'tinyint', 0)
         ->Column('Flagged', 'tinyint', 0)
         ->Column('Spam', 'tinyint', 0)
         ->Column('Highlighted', 'tinyint', 0)
         ->Set(TRUE);
      Gdn::SQL()->Truncate('zDisqusComment');
   }
   

   
   protected $_Xml = NULL;
   /**
    * @return SimpleXmlElement
    */
   public function Xml() {
      if ($this->_Xml === NULL) {
         $this->_Xml = simplexml_load_file($this->Path);
      }
      return $this->_Xml;
   }
   

   
   public function InsertCategories() {
      Gdn::Structure()
         ->Table('Category')
         ->Column('ForeignID', 'varchar(32)')
         ->Column('Source', 'varchar(20)')
         ->Set();
      
      $Sql = "insert GDN_Category (
            UrlCode,
            ParentCategoryID,
            Name,
            DateUpdated,
            ForeignID,
            Source
         )
         select
            i.UrlCode,
            -1 as ParentID,
            i.Name,
            curdate() as DateUpdated,
            DisqusID,
            'Disqus'
         from GDN_zDisqusCategory i
         left join GDN_Category t
            on i.UrlCode = t.UrlCode
         where t.CategoryID is null";
      $this->Query($Sql);
      
      $Sql = "update GDN_zDisqusCategory i
         join GDN_Category t
            on t.UrlCode = i.UrlCode
         set i.CategoryID = t.CategoryID";
      $this->Query($Sql);
      $CategoryModel = new CategoryModel();
      $CategoryModel->RebuildTree();
      
      $Sql = "update GDN_zDisqusDiscussion d
         join GDN_zDisqusCategory c
            on d.DisqusCategoryID = c.DisqusID
         set d.CategoryID = c.CategoryID";
      $this->Query($Sql);
   }
   
   public function InsertComments() {
      $this->_InsertUsers('zDisqusComment');
      
      
      // Add the discussion IDs to the comments.
      // This is much faster than a left join on the insert.
      $Sql = "update GDN_zDisqusComment c
         join GDN_zDisqusDiscussion d
            on c.DisqusDiscussionID = d.DisqusID
         set c.DiscussionID = d.DiscussionID";
      $this->Query($Sql);
      
      
      // Mark all of the comments that have already been inserted.
      $Sql = "update GDN_zDisqusComment i
         join GDN_Comment t
            on i.ForeignID = t.ForeignID
               and t.Source = 'Disqus'
         set i.CommentID = t.CommentID";
      $this->Query($Sql);
      
      // Insert the comments.
      $Sql = "insert GDN_Comment (
         ForeignID,
         Source,
         DiscussionID,
         Body,
         Format,
         InsertUserID,
         DateInserted,
         InsertIPAddress
      )
      select
         i.ForeignID,
         'Disqus',
         i.DiscussionID,
         i.Body,
         i.Format,
         i.InsertUserID,
         i.DateInserted,
         i.InsertIPAddress
      from GDN_zDisqusComment i
      where i.CommentID is null";
      $this->Query($Sql);  
   }
   
   public function InsertDiscussions() {
      $this->_InsertUsers('zDisqusDiscussion');
      
      $Sql = "insert GDN_Discussion (
            ForeignID,
            Type,
            Source,
            CategoryID,
            Name,
            Body,
            Format,
            DateInserted,
            InsertUserID,
            InsertIPAddress,
            Attributes,
            Closed
         )
         select
            i.ForeignID,
            'page',
            'Disqus',
            i.CategoryID,
            i.Name,
            i.Body,
            i.Format,
            i.DateInserted,
            i.InsertUserID,
            i.InsertIPAddress,
            i.Attributes,
            i.Closed
         from GDN_zDisqusDiscussion i
         left join GDN_Discussion d
            on i.ForeignID = d.ForeignID
         where d.ForeignID is null";
      $this->Query($Sql);
      
      $Sql = "update GDN_zDisqusDiscussion i
         join GDN_Discussion d
            on i.ForeignID = d.ForeignID
         set i.DiscussionID = d.DiscussionID";
      $this->Query($Sql);
   }
   
   /**
    * Inserts the users from a disqus table.
    * 
    * @param string $Table 
    */
   protected function _InsertUsers($Table) {
      Gdn::Structure()
         ->Table('User')
         ->Column('ForeignID', 'varchar(32)', TRUE)
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();
      
      // Insert the missing users.
      $Sql = "insert GDN_User (
            Name,
            Email,
            Password,
            HashMethod,
            DateInserted,
            InsertIPAddress,
            Source
         )
         select
            i.UserName,
            i.UserEmail,
            'xxx' as Password,
            'Random' as HashMethod,
            curdate() as DateInserted,
            min(i.InsertIPAddress),
            'Disqus' as Source
         from GDN_{$Table} i
         left join GDN_User u
            on i.UserEmail = u.Email
         where u.UserID is null
         group by
            i.UserName,
            i.UserEmail";
      $this->Query($Sql);
      
      // Make sure the users have a role.
      $DefaultRoleIDs = C('Garden.Registration.DefaultRoles');
      if (is_array($DefaultRoleIDs)) {
         $RoleID = array_pop($DefaultRoleIDs);
         $Sql = "insert GDN_UserRole (
               UserID,
               RoleID
            )	
            select
               u.UserID,
               $RoleID as RoleID
            from GDN_User u
            left join GDN_UserRole ur
               on u.UserID = ur.UserID
            where ur.RoleID is NULL
               and u.Source = 'Disqus'";
      }
      
      $Sql = "update GDN_{$Table} i
         join GDN_User u
            on u.Email = i.UserEmail
         set i.InsertUserID = u.UserID";
      $this->Query($Sql);
      
      // Insert authentication.
      $Sql = "insert ignore GDN_UserAuthentication (
            ProviderKey,
            ForeignUserKey,
            UserID
         )
         select
            UniqueType,
            UniqueID,
            InsertUserID
         from GDN_{$Table}
         where UniqueID <> '' and InsertUserID is not null
         group by UniqueType, UniqueID";
      $this->Query($Sql);
   }
   
   public function InsertTables() {
//      $this->InsertCategories();
//      $this->InsertDiscussions();
//      $this->InsertComments();
   }
   
   public function Parse() {
      $Xml = new XmlReader();
      $Xml->open($this->Path);
      
      $Counts = array('Categories' => 0, 'Discussions' => 0, 'Comments' => 0);
      
      while ($Xml->read()) {
         if ($Xml->nodeType != XMLReader::ELEMENT)
            continue;
         
         switch ($Xml->name) {
            case 'category':
               $Str = $Xml->readOuterXml();
               $this->ParseCategory($Str);
               $Xml->next();
               $Counts['Categories']++;
               break;
            case 'thread':
               $Str = $Xml->readOuterXml();
               $this->ParseDiscussion($Str);
               $Xml->next();
               $Counts['Discussions']++;
               break;
            case 'post':
               $Str = $Xml->readOuterXml();
               $this->ParseComment($Str);
               $Xml->next();
               $Counts['Comments']++;
               break;
         }
         
//         if ($i++ > 100)
//            break;
      }
      $this->Insert(NULL);
      return $Counts;
   }
   
   public function UpdateCounts() {
      $Sqls = array();
      $Im = new ImportModel();
      
      $Sqls[] = $Im->GetCountSQL('min', 'Discussion', 'Comment', 'FirstCommentID');
      $Sqls[] = $Im->GetCountSQL('max', 'Discussion', 'Comment', 'LastCommentID');
      $Sqls[] = $Im->GetCountSQL('count', 'Discussion', 'Comment');
//      $Sqls[] = $Im->GetCountSQL('min', 'Discussion', 'Comment', 'DateFirstComment', 'DateInserted');
      $Sqls[] = $Im->GetCountSQL('max', 'Discussion', 'Comment', 'DateLastComment', 'DateInserted');
      $Sqls[] = "update GDN_Discussion d
         join GDN_Comment c
            on d.LastCommentID = c.CommentID
         set d.LastCommentUserID = c.InsertUserID";
      
      $Sqls[] = $Im->GetCountSQL('count', 'Category', 'Discussion', 'CountDiscussions');
      $Sqls[] = $Im->GetCountSQL('sum', 'Category', 'Discussion', 'CountComments', 'CountComments');
      
      foreach ($Sqls as $Sql) {
         $this->Query($Sql);
      }
   }
   
   /// Event Handlers ///
   public function ImportController_Disqus_Create($Sender, $Step = 'load') {
      $Start = time();
      set_time_limit(60 * 30);
      echo '<pre>';
      
      switch ($Step) {
         case 'load':
            $this->Path = PATH_UPLOADS.'/boinx-68c878a517e887cc0934e1e44d056881.xml';

            $this->DefineTables();
      //      $this->LoadDiscussions();
            $Counts = $this->Parse();
            decho($Counts, 'Counts');
            break;
         case 'insert':
            $this->InsertTables();
            break;
         case 'counts':
            $this->UpdateCounts();
            break;
      }
      
      $Finish = time();
      echo ($Finish - $Start).'s';
      echo '</pre>';
   }
   
}