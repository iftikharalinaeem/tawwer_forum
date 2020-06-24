<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class WordpressImportModel extends CommentImportModel {
   /// Properties ///

   public $CurrentItemID;

   /// Methods ///

   public function __construct() {
      $this->Source = 'Wordpress';
   }

   public function defineTables() {
      $st = Gdn::structure();
      $sql = Gdn::sql();

      $st->table('User')
         ->column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->column('Source', 'varchar(20)', TRUE)
         ->set();

      $st->table('Discussion')
         ->column('ForeignID', 'int', TRUE)
         ->column('Source', 'varchar(20)', TRUE)
         ->set();

      $st->table('Comment')
         ->column('ForeignID', 'int', TRUE)
         ->column('Source', 'varchar(20)', TRUE)
         ->set();

      $st->table('Category')
         ->column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->column('Source', 'varchar(20)', TRUE)
         ->set();

      $st->table('zWordpressUser')
         ->column('ForeignID', 'int', FALSE, 'primary')
         ->column('Name', 'varchar(50)', FALSE, 'key')
         ->column('Email', 'varchar(200)', FALSE)
         ->column('UserID', 'int', TRUE, 'key')
         ->set(TRUE);
      $sql->truncate('zWordpressUser');

      $st->table('zWordpressCategory')
         ->column('ForeignID', 'int', FALSE, 'primary')
         ->column('UrlCode', 'varchar(191)', FALSE, 'index')
         ->column('Name', 'varchar(255)', FALSE)
         ->column('ParentUrlCode', 'varchar(255)', TRUE)
         ->column('CategoryID', 'int', TRUE)
         ->column('ParentCategoryID', 'int', TRUE)
         ->set();
      $sql->truncate('zWordpressCategory');

      $st->table('zWordpressDiscussion')
         ->column('ForeignID', 'int', FALSE, 'primary')
         ->column('CategoryUrlCode', 'varchar(255)', TRUE)
         ->column('Name', 'varchar(100)', FALSE)
         ->column('Body', 'text', FALSE)
         ->column('Format', 'varchar(20)', TRUE)
         ->column('DateInserted', 'datetime', TRUE)
         ->column('InsertIPAddress', 'varchar(15)', TRUE)
         ->column('Closed', 'tinyint')
         ->column('Announce', 'tinyint')
         ->column('Attributes', 'text', TRUE)
         ->column('DiscussionID', 'int', TRUE, 'index')
         ->column('InsertUserID', 'int', TRUE, 'index')
         ->column('UserName', 'varchar(50)', TRUE)
         ->set(TRUE);
      $sql->truncate('zWordpressDiscussion');

      $st->table('zWordpressComment')
         ->column('ForeignID', 'int', FALSE, 'primary')
         ->column('DiscussionForeignID', 'int', FALSE, 'key')
         ->column('Body', 'text')
         ->column('Format', 'varchar(20)')
         ->column('DateInserted', 'datetime', TRUE)
         ->column('InsertIPAddress', 'varchar(15)', TRUE)
//         ->column('Approved', 'tinyint', 0)
         ->column('UserName', 'varchar(50)', TRUE)
         ->column('UserEmail', 'varchar(200)', TRUE)
         ->column('CommentID', 'int', TRUE, 'index')
         ->column('DiscussionID', 'int', TRUE)
         ->column('InsertUserID', 'int', TRUE)
         ->set(TRUE);
      $sql->truncate('zWordpressComment');
   }

   public function insertUsers() {
      $sql = "update GDN_zWordpressUser set UserID = null";
      $this->query($sql);

      // First try and link up as many users as possible.
      $sql = "update GDN_zWordpressUser zu
         join GDN_User u
            on zu.ForeignID = u.ForeignID
               and u.Source = '_source_'
         set zu.UserID = u.UserID
         where zu.UserID is null";
      $this->query($sql);

      $this->query("update GDN_zWordpressUser zu
         join GDN_User u
            on zu.Email = u.Email
         set zu.UserID = u.UserID
         where zu.UserID is null");

      $this->query("update GDN_zWordpressUser zu
         join GDN_User u
            on zu.Name = u.Name
         set zu.UserID = u.UserID
         where zu.UserID is null");

      $this->query("
         insert GDN_User (
            Name,
            Email,
            Password,
            HashMethod,
            DateInserted,
            Source,
            ForeignID
         )
         select
            zu.Name,
            zu.Email,
            'xxx' as Password,
             'Random' as HashMethod,
             curdate() as DateInserted,
             '_source_' as Source,
             zu.ForeignID as ForeignID
         from GDN_zWordpressUser zu
         where zu.UserID is null");

      $this->query($sql);
   }

   public function insertCatgories() {
      $sql = "update GDN_zWordpressCategory set CategoryID = null";
      $this->query($sql);


      $sql = "update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.ForeignID = c.ForeignID and c.Source = '_source_'
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null";
      $this->query($sql);

      $this->query("update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.UrlCode = c.UrlCode
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null");

      $this->query("update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.Name = c.Name
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null");

      $this->query("
         insert GDN_Category (
            Name,
            UrlCode,
            ParentCategoryID,
            ForeignID,
            Source
         )
         select
            Name,
            UrlCode,
            -1,
            ForeignID,
            '_source_'
         from GDN_zWordpressCategory zc
         where zc.CategoryID is null");

      $this->query($sql);

      // Update the parents now.
      $this->query("update GDN_zWordpressCategory zc
         join GDN_zWordpressCategory zc2
            on zc.ParentUrlCode = zc2.UrlCode
         set zc.ParentCategoryID = zc2.CategoryID");

      $this->query("update GDN_Category c
         join GDN_zWordpressCategory zc
            on c.CategoryID = zc.CategoryID
         set c.ParentCategoryID = zc.ParentCategoryID
         where zc.ParentCategoryID is not null");

      $categoryModel = new CategoryModel();
      $categoryModel->rebuildTree();
   }

   public function insertComments() {
      $sql = "update GDN_zWordpressComment set InsertUserID = null";
      $this->query($sql);


      // Insert any missing users.
      $this->_InsertUsers('zWordpressComment', ['Email', 'Name'], 'zWordpressUser');

      $sql = "
         insert GDN_Comment (
            DiscussionID,
            Body,
            Format,
            DateInserted,
            InsertIPAddress,
            InsertUserID,
            ForeignID,
            Source
         )
         select
            zd.DiscussionID,
            zc.Body,
            zc.Format,
            zc.DateInserted,
            zc.InsertIPAddress,
            zc.InsertUserID,
            zc.ForeignID,
            '_source_'
         from GDN_zWordpressComment zc
         join GDN_zWordpressDiscussion zd
            on zc.DiscussionForeignID = zd.ForeignID
         left join GDN_Comment c
            on c.ForeignID = zc.ForeignID and c.Source = '_source_'
         where c.CommentID is null";

      $this->query($sql);
   }

   public function insertDiscussions() {
      $sql = "update GDN_zWordpressDiscussion set InsertUserID = null, DiscussionID = null";
      $this->query($sql);

      $systemUserID = Gdn::userModel()->getSystemUserID();

      // First assign all of the authors.
      $sql = "update GDN_zWordpressDiscussion zd
         left join GDN_zWordpressUser zu
            on zd.UserName = zu.Name
         set zd.InsertUserID = coalesce(zu.UserID, $systemUserID)";
      $this->query($sql);

      // Assign all of the discussion foreign IDs that already exist.
      $sql = "update GDN_zWordpressDiscussion zd
         join GDN_Discussion d
            on zd.ForeignID = d.ForeignID and d.Source = '_source_'
         set zd.DiscussionID = d.DiscussionID";
      $this->query($sql);

      // Insert the new discussions.
      $this->query("
      insert GDN_Discussion (
         Name,
         CategoryID,
         Body,
         Format,
         DateInserted,
         InsertIPAddress,
         Closed,
         Announce,
         Attributes,
         InsertUserID,
         ForeignID,
         Source

      )
      select
         zd.Name,
         coalesce(zc.CategoryID, -1),
         zd.Body,
         zd.Format,
         zd.DateInserted,
         zd.InsertIPAddress,
         zd.Closed,
         zd.Announce,
         zd.Attributes,
         zd.InsertUserID,
         zd.ForeignID,
         '_source_'
      from GDN_zWordpressDiscussion zd
      left join GDN_zWordpressCategory zc
         on zd.CategoryUrlCode = zc.UrlCode
      where zd.DiscussionID is null");

      // Assign all of the discussion IDs for comment inserts.
      $this->query($sql);
   }

   public function insertTables() {
      $this->insertUsers();
      $this->insertCatgories();
      $this->insertDiscussions();
      $this->insertComments();
   }

   public function parse() {
//      $xml = simplexml_load_file($this->Path);
//      decho($xml);
//      die();


      $xml = new XmlReader();
//      decho($this->Path);
//      die();
      $xml->open($this->Path);

      $counts = ['Categories' => 0, 'Discussions' => 0, 'Comments' => 0];

      $names = [];

      while ($xml->read()) {
         if ($xml->nodeType != XMLReader::ELEMENT)
            continue;

         $name = $xml->name;

         if (!isset($names[$name])) {
//            decho($Name);
            $names[$name] = TRUE;
         }


         switch ($name) {
            case 'wp:author':
               $str = $xml->readOuterXml();
               $this->parseUser($str);
               $xml->next();
               break;
            case 'wp:category':
               $str = $xml->readOuterXml();
               $this->parseCategory($str);
               $xml->next();
               break;
            case 'item':
//               $Dom = $Xml->expand();
//               $Str = $Xml->readString();

               $str = $xml->readOuterXml();
//               if ($Str) {
                  $this->parseDiscussion($str);
                  $xml->next();
//               }
               break;
//            case 'wp:comment':
//               $Str = $Xml->readOuterXml();
//               $this->parseComment($Str);
//               $Xml->next();
//
//               $Counts['Comments']++;
//
//
//               break;
         }
      }
      $this->insert(NULL);

      decho($names, 'Names');
   }

   public function parseCategory($str) {
      $xml = new SimpleXMLElement($str);
      $wp = $xml->children('wp', TRUE);

      $row = [
          'ForeignID' => (int)$wp->term_id,
          'UrlCode' => (string)$wp->category_nicename,
          'Name' => (string)$wp->cat_name,
          'ParentUrlCode' => (string)$wp->category_parent];

      $this->insert('zWordpressCategory', $row);
   }

   /**
    *
    * @param SimpleXml $xml
    * @param ParentXml $parentXml
    */
   public function parseComment($xml, $parentXml) {
      if ($xml->comment_type == 'pingback')
         return;

//      $Xml = new simpleXMLElement($Str);
      $row = [
          'ForeignID' => (int)$xml->comment_id,
          'DiscussionForeignID' => (int)$parentXml->post_id,
          'Body' => $xml->comment_content,
          'Format' => 'Html',
          'DateInserted' => $xml->comment_date_gmt,
          'InsertIPAddress' => $xml->comment_author_IP,
          'UserName' => $xml->comment_author,
          'UserEmail' => $xml->comment_author_email
//          'Approved' => (int)$Xml->comment_approved
      ];

      $this->insert('zWordpressComment', $row);
   }


   public function parseDiscussion($str) {
      // Set up some SimpleXml objects to parse the content.
      try {
         $xml = new SimpleXMLElement($str);
      } catch(Exception $ex) {
         decho($str);
         die();
      }
      $wp = $xml->children('wp', TRUE);

      if (strcasecmp($wp->post_type, 'post') != 0)
         return;

      // Figure out the body.
      $excerpt = (string)$xml->children('excerpt', TRUE)->encoded;
      $content = (string)$xml->children('content', TRUE)->encoded;
      $imgSrc = FALSE;
      $url = (string)$xml->link;

      // See if we can't find an image in the body.
      $dom = pQuery::parseStr($content);
      if ($dom) {
         foreach ($dom->query('img') as $element) {
            $imgSrc = absoluteSource($element->attr('src'), $url);
            break;
         }
      }
      unset($dom);

      if (!$excerpt)
         $excerpt = sliceParagraph(Gdn_Format::plainText($content, 'Html'));
      $image = '';
      if ($imgSrc) {
         $image = img($imgSrc, ['class' => 'LeftAlign']);
      }

      $body = formatString(t('EmbeddedDiscussionFormat'), [
          'Title' => (string)$xml->title,
          'Excerpt' => $excerpt,
          'Image' => $image,
          'Url' => $url
      ]);

      // There are lots of category records. Find one with 'category' domain.
      foreach ($xml->category as $xmlCategory) {
         if ($xmlCategory['domain'] == 'category') {
            $categoryUrlCode = $xmlCategory['nicename'];
            break;
         }
      }

      // Set up the discussion row.
      $row = [
          'ForeignID' => (int)$wp->post_id,
          'Name' => (string)$xml->title,
//          'Body' => sliceParagraph(Gdn_Format::plainText($Xml->children('content')->encoded). 200),
          'Body' => $body,
          'Format' => 'Html',
          'DateInserted' => (string)$wp->post_date_gmt,
          'Closed' => strcasecmp($wp->comment_status, 'closed') == 0,
          'Announce' => (int)$wp->is_sticky,
          'CategoryUrlCode' => (string)$categoryUrlCode,
          'UserName' => (string)$xml->children('dc', TRUE)->creator,
          'Attributes' => [
              'ForeignUrl' => (string)$xml->link
          ]];

      $hasComments = FALSE;
      foreach ($wp->comment as $commentXml) {
         $hasComments = TRUE;
         $this->parseComment($commentXml, $wp);
      }

      if (TRUE || $hasComments)
         $this->insert('zWordpressDiscussion', $row);

//      $Row = array(
//          'ForeignID' => (int)$Xml->a
//          );
   }

   public function parseUser($str) {
      $xml = new SimpleXMLElement($str);
      $wp = $xml->children('wp', TRUE);

      $row = [
          'ForeignID' => (int)$wp->author_id,
          'Name' => (string)$wp->author_display_name,
          'Email' => (string)$wp->author_email];

      $this->insert('zWordpressUser', $row);
   }
}
