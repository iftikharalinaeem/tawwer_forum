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

   public function DefineTables() {
      $st = Gdn::Structure();
      $sql = Gdn::SQL();

      $st->Table('User')
         ->Column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $st->Table('Discussion')
         ->Column('ForeignID', 'int', TRUE)
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $st->Table('Comment')
         ->Column('ForeignID', 'int', TRUE)
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $st->Table('Category')
         ->Column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $st->Table('zWordpressUser')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('Name', 'varchar(50)', FALSE, 'key')
         ->Column('Email', 'varchar(200)', FALSE)
         ->Column('UserID', 'int', TRUE, 'key')
         ->Set(TRUE);
      $sql->Truncate('zWordpressUser');

      $st->Table('zWordpressCategory')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('UrlCode', 'varchar(191)', FALSE, 'index')
         ->Column('Name', 'varchar(255)', FALSE)
         ->Column('ParentUrlCode', 'varchar(255)', TRUE)
         ->Column('CategoryID', 'int', TRUE)
         ->Column('ParentCategoryID', 'int', TRUE)
         ->Set();
      $sql->Truncate('zWordpressCategory');

      $st->Table('zWordpressDiscussion')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('CategoryUrlCode', 'varchar(255)', TRUE)
         ->Column('Name', 'varchar(100)', FALSE)
         ->Column('Body', 'text', FALSE)
         ->Column('Format', 'varchar(20)', TRUE)
         ->Column('DateInserted', 'datetime', TRUE)
         ->Column('InsertIPAddress', 'varchar(15)', TRUE)
         ->Column('Closed', 'tinyint')
         ->Column('Announce', 'tinyint')
         ->Column('Attributes', 'text', TRUE)
         ->Column('DiscussionID', 'int', TRUE, 'index')
         ->Column('InsertUserID', 'int', TRUE, 'index')
         ->Column('UserName', 'varchar(50)', TRUE)
         ->Set(TRUE);
      $sql->Truncate('zWordpressDiscussion');

      $st->Table('zWordpressComment')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('DiscussionForeignID', 'int', FALSE, 'key')
         ->Column('Body', 'text')
         ->Column('Format', 'varchar(20)')
         ->Column('DateInserted', 'datetime', TRUE)
         ->Column('InsertIPAddress', 'varchar(15)', TRUE)
//         ->Column('Approved', 'tinyint', 0)
         ->Column('UserName', 'varchar(50)', TRUE)
         ->Column('UserEmail', 'varchar(200)', TRUE)
         ->Column('CommentID', 'int', TRUE, 'index')
         ->Column('DiscussionID', 'int', TRUE)
         ->Column('InsertUserID', 'int', TRUE)
         ->Set(TRUE);
      $sql->Truncate('zWordpressComment');
   }

   public function InsertUsers() {
      $sql = "update GDN_zWordpressUser set UserID = null";
      $this->Query($sql);

      // First try and link up as many users as possible.
      $sql = "update GDN_zWordpressUser zu
         join GDN_User u
            on zu.ForeignID = u.ForeignID
               and u.Source = '_source_'
         set zu.UserID = u.UserID
         where zu.UserID is null";
      $this->Query($sql);

      $this->Query("update GDN_zWordpressUser zu
         join GDN_User u
            on zu.Email = u.Email
         set zu.UserID = u.UserID
         where zu.UserID is null");

      $this->Query("update GDN_zWordpressUser zu
         join GDN_User u
            on zu.Name = u.Name
         set zu.UserID = u.UserID
         where zu.UserID is null");

      $this->Query("
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

      $this->Query($sql);
   }

   public function InsertCatgories() {
      $sql = "update GDN_zWordpressCategory set CategoryID = null";
      $this->Query($sql);


      $sql = "update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.ForeignID = c.ForeignID and c.Source = '_source_'
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null";
      $this->Query($sql);

      $this->Query("update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.UrlCode = c.UrlCode
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null");

      $this->Query("update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.Name = c.Name
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null");

      $this->Query("
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

      $this->Query($sql);

      // Update the parents now.
      $this->Query("update GDN_zWordpressCategory zc
         join GDN_zWordpressCategory zc2
            on zc.ParentUrlCode = zc2.UrlCode
         set zc.ParentCategoryID = zc2.CategoryID");

      $this->Query("update GDN_Category c
         join GDN_zWordpressCategory zc
            on c.CategoryID = zc.CategoryID
         set c.ParentCategoryID = zc.ParentCategoryID
         where zc.ParentCategoryID is not null");

      $categoryModel = new CategoryModel();
      $categoryModel->RebuildTree();
   }

   public function InsertComments() {
      $sql = "update GDN_zWordpressComment set InsertUserID = null";
      $this->Query($sql);


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

      $this->Query($sql);
   }

   public function InsertDiscussions() {
      $sql = "update GDN_zWordpressDiscussion set InsertUserID = null, DiscussionID = null";
      $this->Query($sql);

      $systemUserID = Gdn::UserModel()->GetSystemUserID();

      // First assign all of the authors.
      $sql = "update GDN_zWordpressDiscussion zd
         left join GDN_zWordpressUser zu
            on zd.UserName = zu.Name
         set zd.InsertUserID = coalesce(zu.UserID, $systemUserID)";
      $this->Query($sql);

      // Assign all of the discussion foreign IDs that already exist.
      $sql = "update GDN_zWordpressDiscussion zd
         join GDN_Discussion d
            on zd.ForeignID = d.ForeignID and d.Source = '_source_'
         set zd.DiscussionID = d.DiscussionID";
      $this->Query($sql);

      // Insert the new discussions.
      $this->Query("
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
      $this->Query($sql);
   }

   public function InsertTables() {
      $this->InsertUsers();
      $this->InsertCatgories();
      $this->InsertDiscussions();
      $this->InsertComments();
   }

   public function Parse() {
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
               $this->ParseUser($str);
               $xml->next();
               break;
            case 'wp:category':
               $str = $xml->readOuterXml();
               $this->ParseCategory($str);
               $xml->next();
               break;
            case 'item':
//               $Dom = $Xml->expand();
//               $Str = $Xml->readString();

               $str = $xml->readOuterXml();
//               if ($Str) {
                  $this->ParseDiscussion($str);
                  $xml->next();
//               }
               break;
//            case 'wp:comment':
//               $Str = $Xml->readOuterXml();
//               $this->ParseComment($Str);
//               $Xml->next();
//
//               $Counts['Comments']++;
//
//
//               break;
         }
      }
      $this->Insert(NULL);

      decho($names, 'Names');
   }

   public function ParseCategory($str) {
      $xml = new SimpleXMLElement($str);
      $wp = $xml->children('wp', TRUE);

      $row = [
          'ForeignID' => (int)$wp->term_id,
          'UrlCode' => (string)$wp->category_nicename,
          'Name' => (string)$wp->cat_name,
          'ParentUrlCode' => (string)$wp->category_parent];

      $this->Insert('zWordpressCategory', $row);
   }

   /**
    *
    * @param SimpleXml $xml
    * @param ParentXml $parentXml
    */
   public function ParseComment($xml, $parentXml) {
      if ($xml->comment_type == 'pingback')
         return;

//      $Xml = new SimpleXMLElement($Str);
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

      $this->Insert('zWordpressComment', $row);
   }


   public function ParseDiscussion($str) {
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
            $imgSrc = AbsoluteSource($element->attr('src'), $url);
            break;
         }
      }
      unset($dom);

      if (!$excerpt)
         $excerpt = SliceParagraph(Gdn_Format::PlainText($content, 'Html'));
      $image = '';
      if ($imgSrc) {
         $image = Img($imgSrc, ['class' => 'LeftAlign']);
      }

      $body = FormatString(T('EmbeddedDiscussionFormat'), [
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
//          'Body' => SliceParagraph(Gdn_Format::PlainText($Xml->children('content')->encoded). 200),
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
         $this->ParseComment($commentXml, $wp);
      }

      if (TRUE || $hasComments)
         $this->Insert('zWordpressDiscussion', $row);

//      $Row = array(
//          'ForeignID' => (int)$Xml->a
//          );
   }

   public function ParseUser($str) {
      $xml = new SimpleXMLElement($str);
      $wp = $xml->children('wp', TRUE);

      $row = [
          'ForeignID' => (int)$wp->author_id,
          'Name' => (string)$wp->author_display_name,
          'Email' => (string)$wp->author_email];

      $this->Insert('zWordpressUser', $row);
   }
}
