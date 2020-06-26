<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class DisqusImporter extends Gdn_Plugin {
   /// Properties ///

   public $BufferSize = 250;

   public $Path = '';

   public $Source = 'Disqus';

   /// Methods ///

   protected $_Categories = NULL;

   public function insert($table, $row = NULL) {
      static $lastTable = NULL;
      static $rows = [];

      if (isset($row['Attributes']) && is_array($row['Attributes']))
         $row['Attributes'] = dbencode($row['Attributes']);


      if ($table === NULL) {
         $this->insertMulti($lastTable, $rows);
         $lastTable = NULL;
         $rows = [];

         return;
      }

      if ($lastTable && $lastTable != $table || count($rows) >= $this->BufferSize) {
         $this->insertMulti($lastTable, $rows);
         $rows = [];
      }

      $lastTable = $table;
      $rows[] = $row;
   }

   public function insertMulti($table, $rows) {
      if (empty($rows))
         return;

      $px = Gdn::database()->DatabasePrefix;
      $pDO = Gdn::database()->connection();

      $sql = '';
      foreach ($rows as $row) {
         if ($sql)
            $sql .= ",\n";

         $values = array_map([$pDO, 'quote'], $row);
         $sql .= '('.implode(',', $values).')';
      }

      $sql = "insert ignore {$px}$table\n".
         '('.implode(',', array_keys($rows[0])).") values\n".
         $sql;

//      echo htmlspecialchars($Sql);
      Gdn::database()->query($sql);
//      die();
   }

   public function parseCategory($str) {
      $xml = new SimpleXMLElement($str);
      $name = html_entity_decode($xml->title, ENT_COMPAT, 'UTF-8');
      $row = [
          'DisqusID' => (int)$xml->attributes('dsq', TRUE)->id,
          'Name' => $name,
          'UrlCode' => Gdn_Format::url($name)
      ];
      $this->insert('zDisqusCategory', $row);

//      <category xmlns="http://disqus.com" xmlns:dsq="http://disqus.com/disqus-internals" dsq:id="684542">
//      <forum>cultofmac</forum>
//      <title>General</title>
//      <isDefault>true</isDefault>
//      </category>
   }

   public function parseComment($str) {
      $xml = new SimpleXMLElement($str);
      $name = html_entity_decode($xml->title, ENT_COMPAT, 'UTF-8');
      $row = [
          'ForeignID' => (int)$xml->attributes('dsq', TRUE)->id,
          'DisqusDiscussionID' => (int)$xml->thread->attributes('dsq', TRUE)->id,
          'ParentForeignID' => NULL,
          'Body' => $xml->message,
          'Format' => 'Html',
          'DateInserted' => $xml->createdAt,
          'InsertIPAddress' => $xml->ipAddress,
          'UserName' => $xml->author->username,
          'UserEmail' => trim($xml->author->email),
          'UserFullName' => $xml->author->name,
          'UserAnonymous' => $xml->author->anonymous,
          'Deleted' => (int)$xml->isDeleted,
          'Approved' => (int)$xml->isApproved,
          'Flagged' => (int)$xml->isFlagged,
          'Spam' => (int)$xml->isSpam,
          'Highlighted' => (int)$xml->isHighlighted,
          'UniqueType' => NULL,
          'UniqueID' => NULL
      ];

      if (!$row['UserName'])
         $row['UserName'] = $row['UserFullName'];

      if (preg_match('`^([a-zA-Z]+)-(.+)$`', $row['UserName'], $matches)) {
         if (in_array($matches[1], ['google', 'facebook', 'yahoo', 'twitter', 'openid'])) {
            $row['UserName'] = $row['UserFullName'];
            $row['UniqueType'] = $matches[1];
            $row['UniqueID'] = $matches[2];

            if (!$row['UserEmail'])
               $row['UserEmail'] = $row['UniqueID'].'@noreply.com';
         }
      }

      if ($xml->parent)
          $row['ParentForeignID'] = $xml->parent->attributes('dsq', TRUE)->id;

      $this->insert('zDisqusComment', $row);

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

   public function parseDiscussion($str) {
      $xml = new SimpleXMLElement($str);
      $name = html_entity_decode($xml->title, ENT_COMPAT, 'UTF-8');
      $row = [
          'DisqusID' => (int)$xml->attributes('dsq', TRUE)->id,
          'ForeignID' => substr($xml->id, 0, 200),
          'DisqusCategoryID' => $xml->category->attributes('dsq', TRUE)->id,
          'Name' => $name,
          'Body' => $xml->link,
          'Format' => 'Html',
          'DateInserted' => $xml->createdAt,
          'InsertIPAddress' => $xml->ipAddress,
          'Closed' => $xml->isClosed,
          'Deleted' => $xml->isDeleted,
          'UserName' => $xml->author->username,
          'UserEmail' => trim($xml->author->email),
          'UserFullName' => $xml->author->name,
          'UserAnonymous' => $xml->author->anonymous,
          'UniqueType' => NULL,
          'UniqueID' => NULL
      ];

      if (!$row['UserName'])
         $row['UserName'] = $row['UserFullName'];

      if (!$row['UserEmail'] && preg_match('`([a-zA-Z]+)-(.+)$`', $row['UserName'], $matches)) {
         $row['UserName'] = $row['UserFullName'];
         $row['UniqueType'] = $matches[1];
         $row['UniqueID'] = $matches[2];

         $row['UserEmail'] = $row['UniqueID'].'@noreply.com';
      }

      $attributes = [
          'DisqusID' => $row['DisqusID'],
          'ForeignUrl' => (string)$xml->link,
          'RegenerateBody' => TRUE
          ];
      $row['Attributes'] = dbencode($attributes);

      $this->insert('zDisqusDiscussion', $row);

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

   public function defineTables() {
      // Define the temp tables.
      Gdn::structure()->table('zDisqusCategory')
         ->column('DisqusID', 'varchar(32)', FALSE, 'primary')
         ->column('CategoryID', 'int', TRUE)
         ->column('Name', 'varchar(255)')
         ->column('UrlCode', 'varchar(255)', TRUE)
         ->set(TRUE);
      Gdn::sql()->truncate('zDisqusCategory');

      Gdn::structure()->table('zDisqusDiscussion')
         ->column('DisqusID', 'int', FALSE, 'primary')
         ->column('DiscussionID', 'int', TRUE)
         ->column('ForeignID', 'varchar(191)', FALSE, 'index')
         ->column('DisqusCategoryID', 'int', FALSE)
         ->column('CategoryID', 'int', TRUE)
         ->column('Name', 'varchar(100)')
         ->column('Body', 'text')
         ->column('Format', 'varchar(20)')
         ->column('InsertUserID', 'int', TRUE)
         ->column('DateInserted', 'datetime', TRUE)
         ->column('InsertIPAddress', 'varchar(15)', TRUE)
         ->column('Attributes', 'text', TRUE)
         ->column('Closed', 'tinyint', 0)
         ->column('Deleted', 'tinyint', 0)
         ->column('UserName', 'varchar(50)', TRUE)
         ->column('UserEmail', 'varchar(200)', TRUE)
         ->column('UserFullName', 'varchar(50)', TRUE)
         ->column('UserAnonymous', 'tinyint', 0)
         ->column('UniqueType', 'varchar(32)', TRUE)
         ->column('UniqueID', 'varchar(32)', TRUE, 'index')
         ->set(TRUE);
      Gdn::sql()->truncate('zDisqusDiscussion');

      Gdn::structure()->table('zDisqusComment')
         ->column('ForeignID', 'int', FALSE, 'primary')
         ->column('CommentID', 'int', TRUE, 'index')
         ->column('DisqusDiscussionID', 'int', TRUE, 'index')
         ->column('DiscussionID', 'int', TRUE)
         ->column('ParentForeignID', 'int', TRUE)
         ->column('Body', 'text')
         ->column('Format', 'varchar(20)')
         ->column('InsertUserID', 'int', TRUE)
         ->column('DateInserted', 'datetime', TRUE)
         ->column('InsertIPAddress', 'varchar(15)', TRUE)
         ->column('UserName', 'varchar(50)', TRUE)
         ->column('UserEmail', 'varchar(200)', TRUE)
         ->column('UserFullName', 'varchar(50)', TRUE)
         ->column('UserAnonymous', 'tinyint', 0)
         ->column('UniqueType', 'varchar(32)', TRUE)
         ->column('UniqueID', 'varchar(32)', TRUE)
         ->column('Deleted', 'tinyint', 0)
         ->column('Approved', 'tinyint', 0)
         ->column('Flagged', 'tinyint', 0)
         ->column('Spam', 'tinyint', 0)
         ->column('Highlighted', 'tinyint', 0)
         ->set(TRUE);
      Gdn::sql()->truncate('zDisqusComment');
   }



   protected $_Xml = NULL;
   /**
    * @return SimpleXmlElement
    */
   public function xml() {
      if ($this->_Xml === NULL) {
         $this->_Xml = simplexml_load_file($this->Path);
      }
      return $this->_Xml;
   }



   public function insertCategories() {
      Gdn::structure()
         ->table('Category')
         ->column('ForeignID', 'varchar(32)')
         ->column('Source', 'varchar(20)')
         ->set();

      $sql = "insert GDN_Category (
            UrlCode,
            ParentCategoryID,
            Name,
            DateUpdated,
            ForeignID
         )
         select
            i.UrlCode,
            -1 as ParentID,
            i.Name,
            curdate() as DateUpdated,
            DisqusID
         from GDN_zDisqusCategory i
         left join GDN_Category t
            on i.UrlCode = t.UrlCode
         where t.CategoryID is null";
      $this->query($sql);

      $sql = "update GDN_zDisqusCategory i
         join GDN_Category t
            on t.UrlCode = i.UrlCode
         set i.CategoryID = t.CategoryID";
      $this->query($sql);
      $categoryModel = new CategoryModel();
      $categoryModel->rebuildTree();

      $sql = "update GDN_zDisqusDiscussion d
         join GDN_zDisqusCategory c
            on d.DisqusCategoryID = c.DisqusID
         set d.CategoryID = c.CategoryID";
      $this->query($sql);
   }

   public function insertComments() {
      Gdn::structure()
         ->table('Comment')
         ->column('ForeignID', 'varchar(32)')
//         ->column('Source', 'varchar(20)')
         ->set();

      $this->_InsertUsers('zDisqusComment');


      // Add the discussion IDs to the comments.
      // This is much faster than a left join on the insert.
      $sql = "update GDN_zDisqusComment c
         join GDN_zDisqusDiscussion d
            on c.DisqusDiscussionID = d.DisqusID
         set c.DiscussionID = d.DiscussionID";
      $this->query($sql);


      // Mark all of the comments that have already been inserted.
      $sql = "update GDN_zDisqusComment i
         join GDN_Comment t
            on i.ForeignID = t.ForeignID
         set i.CommentID = t.CommentID";
//                     and t.Source = 'Disqus'
      $this->query($sql);

      // Insert the comments.
      $sql = "insert GDN_Comment (
         ForeignID,
         DiscussionID,
         Body,
         Format,
         InsertUserID,
         DateInserted,
         InsertIPAddress
      )
      select
         i.ForeignID,
         i.DiscussionID,
         i.Body,
         i.Format,
         i.InsertUserID,
         i.DateInserted,
         i.InsertIPAddress
      from GDN_zDisqusComment i
      where i.CommentID is null";
      $this->query($sql);
   }

   public function insertDiscussions() {
      $this->_InsertUsers('zDisqusDiscussion');

      $sql = "insert GDN_Discussion (
            ForeignID,
            Type,
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
      $this->query($sql);

      $sql = "update GDN_zDisqusDiscussion i
         join GDN_Discussion d
            on i.ForeignID = d.ForeignID
         set i.DiscussionID = d.DiscussionID";
      $this->query($sql);
   }

   /**
    * Inserts the users from a disqus table.
    *
    * @param string $table
    */
   protected function _InsertUsers($table) {
      Gdn::structure()
         ->table('User')
         ->column('ForeignID', 'varchar(32)', TRUE)
         ->column('Source', 'varchar(20)', TRUE)
         ->set();

      // Insert the missing users.
      $sql = "insert GDN_User (
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
         from GDN_{$table} i
         left join GDN_User u
            on i.UserEmail = u.Email
         where u.UserID is null
         group by
            i.UserName,
            i.UserEmail";
      $this->query($sql);

      // Make sure the users have a role.
      $defaultRoleIDs = c('Garden.Registration.DefaultRoles');
      if (is_array($defaultRoleIDs)) {
         $roleID = array_pop($defaultRoleIDs);
         $sql = "insert GDN_UserRole (
               UserID,
               RoleID
            )
            select
               u.UserID,
               $roleID as RoleID
            from GDN_User u
            left join GDN_UserRole ur
               on u.UserID = ur.UserID
            where ur.RoleID is NULL
               and u.Source = 'Disqus'";
      }

      $sql = "update GDN_{$table} i
         join GDN_User u
            on u.Email = i.UserEmail
         set i.InsertUserID = u.UserID";
      $this->query($sql);

      // Insert authentication.
      $sql = "insert ignore GDN_UserAuthentication (
            ProviderKey,
            ForeignUserKey,
            UserID
         )
         select
            UniqueType,
            UniqueID,
            InsertUserID
         from GDN_{$table}
         where UniqueID <> '' and InsertUserID is not null
         group by UniqueType, UniqueID";
      $this->query($sql);
   }

   public function insertTables() {
      $this->insertCategories();
      $this->insertDiscussions();
      $this->insertComments();
   }

   public function parse() {
      $xml = new XmlReader();
      $xml->open($this->Path);

      $counts = ['Categories' => 0, 'Discussions' => 0, 'Comments' => 0];

      while ($xml->read()) {
         if ($xml->nodeType != XMLReader::ELEMENT)
            continue;

         switch ($xml->name) {
            case 'category':
               $str = $xml->readOuterXml();
               $this->parseCategory($str);
               $xml->next();
               $counts['Categories']++;
               break;
            case 'thread':
               $str = $xml->readOuterXml();
               $this->parseDiscussion($str);
               $xml->next();
               $counts['Discussions']++;
               break;
            case 'post':
               $str = $xml->readOuterXml();
               $this->parseComment($str);
               $xml->next();
               $counts['Comments']++;
               break;
         }

//         if ($i++ > 100)
//            break;
      }
      $this->insert(NULL);
      return $counts;
   }

   public function updateCounts() {
      $sqls = [];
      $im = new ImportModel();

      $sqls[] = $im->getCountSQL('min', 'Discussion', 'Comment', 'FirstCommentID');
      $sqls[] = $im->getCountSQL('max', 'Discussion', 'Comment', 'LastCommentID');
      $sqls[] = $im->getCountSQL('count', 'Discussion', 'Comment');
//      $Sqls[] = $Im->getCountSQL('min', 'Discussion', 'Comment', 'DateFirstComment', 'DateInserted');
      $sqls[] = $im->getCountSQL('max', 'Discussion', 'Comment', 'DateLastComment', 'DateInserted');
      $sqls[] = "update GDN_Discussion d
         join GDN_Comment c
            on d.LastCommentID = c.CommentID
         set d.LastCommentUserID = c.InsertUserID";

      $sqls[] = $im->getCountSQL('count', 'Category', 'Discussion', 'CountDiscussions');
      $sqls[] = $im->getCountSQL('sum', 'Category', 'Discussion', 'CountComments', 'CountComments');

      foreach ($sqls as $sql) {
         $this->query($sql);
      }
   }

   public function query($sql, $parameters = NULL) {
      $px = Gdn::database()->DatabasePrefix;
      if ($px != 'GDN_')
         $sql = str_replace(' GDN_', ' '.$px, $sql);
      $sql = str_replace(':_', $px, $sql);
      $sql = str_replace('_source_', $this->Source, $sql);

      $sql = trim($sql, ';');

      echo '<pre>'.htmlspecialchars($sql).";\n\n</pre>";

      return Gdn::database()->query($sql, $parameters);
   }

   /// Event Handlers ///
   public function importController_disqus_create($sender, $step = 'load') {
      $start = time();

      // Preserve plugin RequiredApplications version.
      $increaseMaxExecutionTime =
          function_exists('increaseMaxExecutionTime') // Exists in Vanilla 2.3
              ? 'increaseMaxExecutionTime'
              : function ($maxExecutionTime) {

             $iniMaxExecutionTime = ini_get('max_execution_time');

             // max_execution_time == 0 means no limit.
             if ($iniMaxExecutionTime === '0') {
                return true;
             }

             if (((string)$maxExecutionTime) === '0') {
                return set_time_limit(0);
             }

             if (!ctype_digit($iniMaxExecutionTime) || $iniMaxExecutionTime < $maxExecutionTime) {
                return set_time_limit($maxExecutionTime);
             }

             return true;
          };
      $increaseMaxExecutionTime(60 * 30);

      echo '<pre>';

      switch ($step) {
         case 'load':
            $this->Path = PATH_UPLOADS.'/disqusimport.xml';

            $this->defineTables();
      //      $this->loadDiscussions();
            $counts = $this->parse();
            decho($counts, 'Counts');
            break;
         case 'insert':
            $this->insertTables();
            break;
         case 'counts':
            $this->updateCounts();
            break;
      }

      $finish = time();
      echo '-- '.($finish - $start).'s';
      echo '</pre>';
   }

}
