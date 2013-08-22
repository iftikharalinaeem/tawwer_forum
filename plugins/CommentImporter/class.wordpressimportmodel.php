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
      $St = Gdn::Structure();
      $Sql = Gdn::SQL();

      $St->Table('User')
         ->Column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $St->Table('Discussion')
         ->Column('ForeignID', 'int', TRUE)
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $St->Table('Comment')
         ->Column('ForeignID', 'int', TRUE)
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $St->Table('Category')
         ->Column('ForeignID', 'varchar(32)', TRUE, 'index')
         ->Column('Source', 'varchar(20)', TRUE)
         ->Set();

      $St->Table('zWordpressUser')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('Name', 'varchar(50)', FALSE, 'key')
         ->Column('Email', 'varchar(200)', FALSE)
         ->Column('UserID', 'int', TRUE, 'key')
         ->Set(TRUE);
      $Sql->Truncate('zWordpressUser');
      
      $St->Table('zWordpressCategory')
         ->Column('ForeignID', 'int', FALSE, 'primary')
         ->Column('UrlCode', 'varchar(255)', FALSE, 'index')
         ->Column('Name', 'varchar(255)', FALSE)
         ->Column('ParentUrlCode', 'varchar(255)', TRUE)
         ->Column('CategoryID', 'int', TRUE)
         ->Column('ParentCategoryID', 'int', TRUE)
         ->Set();
      $Sql->Truncate('zWordpressCategory');
      
      $St->Table('zWordpressDiscussion')
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
      $Sql->Truncate('zWordpressDiscussion');
      
      $St->Table('zWordpressComment')
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
      $Sql->Truncate('zWordpressComment');
   }
   
   public function InsertUsers() {
      $Sql = "update GDN_zWordpressUser set UserID = null";
      $this->Query($Sql);
      
      // First try and link up as many users as possible.
      $Sql = "update GDN_zWordpressUser zu
         join GDN_User u
            on zu.ForeignID = u.ForeignID
               and u.Source = '_source_'
         set zu.UserID = u.UserID
         where zu.UserID is null";
      $this->Query($Sql);

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
      
      $this->Query($Sql);
   }
   
   public function InsertCatgories() {
      $Sql = "update GDN_zWordpressCategory set CategoryID = null";
      $this->Query($Sql);
      
      
      $Sql = "update GDN_zWordpressCategory zc
         join GDN_Category c
            on zc.ForeignID = c.ForeignID and c.Source = '_source_'
         set zc.CategoryID = c.CategoryID
         where zc.CategoryID is null";
      $this->Query($Sql);

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
      
      $this->Query($Sql);
      
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
      
      $CategoryModel = new CategoryModel();
      $CategoryModel->RebuildTree();
   }
   
   public function InsertComments() {
      $Sql = "update GDN_zWordpressComment set InsertUserID = null";
      $this->Query($Sql);
      
      
      // Insert any missing users.
      $this->_InsertUsers('zWordpressComment', array('Email', 'Name'), 'zWordpressUser');
      
      $Sql = "
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
      
      $this->Query($Sql);
   }
   
   public function InsertDiscussions() {
      $Sql = "update GDN_zWordpressDiscussion set InsertUserID = null, DiscussionID = null";
      $this->Query($Sql);
      
      $SystemUserID = Gdn::UserModel()->GetSystemUserID();
      
      // First assign all of the authors.
      $Sql = "update GDN_zWordpressDiscussion zd
         left join GDN_zWordpressUser zu
            on zd.UserName = zu.Name
         set zd.InsertUserID = coalesce(zu.UserID, $SystemUserID)";
      $this->Query($Sql);
      
      // Assign all of the discussion foreign IDs that already exist.
      $Sql = "update GDN_zWordpressDiscussion zd
         join GDN_Discussion d
            on zd.ForeignID = d.ForeignID and d.Source = '_source_'
         set zd.DiscussionID = d.DiscussionID";
      $this->Query($Sql);
      
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
      $this->Query($Sql);
   }
   
   public function InsertTables() {
      $this->InsertUsers();
      $this->InsertCatgories();
      $this->InsertDiscussions();
      $this->InsertComments();
   }
   
   public function Parse() {
      require_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
      
      
//      $xml = simplexml_load_file($this->Path);
//      decho($xml);
//      die();
      
      
      $Xml = new XmlReader();
//      decho($this->Path);
//      die();
      $Xml->open($this->Path);
      
      $Counts = array('Categories' => 0, 'Discussions' => 0, 'Comments' => 0);
      
      $Names = array();
      
      while ($Xml->read()) {
         if ($Xml->nodeType != XMLReader::ELEMENT)
            continue;
         
         $Name = $Xml->name;
         
         if (!isset($Names[$Name])) {
//            decho($Name);
            $Names[$Name] = TRUE;
         }
         
         
         switch ($Name) {
            case 'wp:author':
               $Str = $Xml->readOuterXml();
               $this->ParseUser($Str);
               $Xml->next();
               break;
            case 'wp:category':
               $Str = $Xml->readOuterXml();
               $this->ParseCategory($Str);
               $Xml->next();
               break;
            case 'item':
//               $Dom = $Xml->expand();
//               $Str = $Xml->readString();
               
               $Str = $Xml->readOuterXml();
//               if ($Str) {
                  $this->ParseDiscussion($Str);
                  $Xml->next();
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
      
      decho($Names, 'Names');
   }
   
   public function ParseCategory($Str) {
      $Xml = new SimpleXMLElement($Str);
      $Wp = $Xml->children('wp', TRUE);
      
      $Row = array(
          'ForeignID' => (int)$Wp->term_id,
          'UrlCode' => (string)$Wp->category_nicename,
          'Name' => (string)$Wp->cat_name,
          'ParentUrlCode' => (string)$Wp->category_parent);
      
      $this->Insert('zWordpressCategory', $Row);
   }
   
   /**
    *
    * @param SimpleXml $Xml
    * @param ParentXml $ParentXml
    */
   public function ParseComment($Xml, $ParentXml) {
//      $Xml = new SimpleXMLElement($Str);
      $Row = array(
          'ForeignID' => (int)$Xml->comment_id,
          'DiscussionForeignID' => (int)$ParentXml->post_id,
          'Body' => $Xml->comment_content,
          'Format' => 'Html',
          'DateInserted' => $Xml->comment_date_gmt,
          'InsertIPAddress' => $Xml->comment_author_IP,
          'UserName' => $Xml->comment_author,
          'UserEmail' => $Xml->comment_author_email
//          'Approved' => (int)$Xml->comment_approved
      );
      
      $this->Insert('zWordpressComment', $Row);
   }
   
   
   public function ParseDiscussion($Str) {
      // Set up some SimpleXml objects to parse the content.
      try {
         $Xml = new SimpleXMLElement($Str);
      } catch(Exception $Ex) {
         decho($Str);
         die();
      }
      $Wp = $Xml->children('wp', TRUE);
      
      if (strcasecmp($Wp->post_type, 'post') != 0)
         return;
      
      // Figure out the body.
      $Excerpt = (string)$Xml->children('excerpt', TRUE)->encoded;
      $Content = (string)$Xml->children('content', TRUE)->encoded;
      $ImgSrc = FALSE;
      $Url = (string)$Xml->link;
      
      // See if we can't find an image in the body.
      $Dom = str_get_html($Content);
      if ($Dom) {
         foreach ($Dom->find('img') as $element) {
            $ImgSrc = AbsoluteSource($element->src, $Url);
            break;
         }
      }
      unset($Dom);
            
      if (!$Excerpt)
         $Excerpt = SliceParagraph(Gdn_Format::PlainText($Content, 'Html'));
      $Image = '';
      if ($ImgSrc) {
         $Image = Img($ImgSrc, array('class' => 'LeftAlign'));
      }
      
      $Body = FormatString(T('EmbeddedDiscussionFormat'), array(
          'Title' => (string)$Xml->title,
          'Excerpt' => $Excerpt,
          'Image' => $Image,
          'Url' => $Url
      ));
      
      // There are lots of category records. Find one with 'category' domain.
      foreach ($Xml->category as $XmlCategory) {
         if ($XmlCategory['domain'] == 'category') {
            $CategoryUrlCode = $XmlCategory['nicename'];
            break;
         }
      }

      // Set up the discussion row.
      $Row = array(
          'ForeignID' => (int)$Wp->post_id,
          'Name' => (string)$Xml->title,
//          'Body' => SliceParagraph(Gdn_Format::PlainText($Xml->children('content')->encoded). 200),
          'Body' => $Body,
          'Format' => 'Html',
          'DateInserted' => (string)$Wp->post_date_gmt,
          'Closed' => strcasecmp($Wp->comment_status, 'closed') == 0,
          'Announce' => (int)$Wp->is_sticky,
          'CategoryUrlCode' => (string)$CategoryUrlCode,
          'UserName' => (string)$Xml->children('dc', TRUE)->creator,
          'Attributes' => array(
              'ForeignUrl' => (string)$Xml->link
          ));
      
      $HasComments = FALSE;
      foreach ($Wp->comment as $CommentXml) {
         $HasComments = TRUE;
         $this->ParseComment($CommentXml, $Wp);
      }
      
      if (TRUE || $HasComments)
         $this->Insert('zWordpressDiscussion', $Row);
      
//      $Row = array(
//          'ForeignID' => (int)$Xml->a
//          );
   }
   
   public function ParseUser($Str) {
      $Xml = new SimpleXMLElement($Str);
      $Wp = $Xml->children('wp', TRUE);
      
      $Row = array(
          'ForeignID' => (int)$Wp->author_id,
          'Name' => (string)$Wp->author_display_name,
          'Email' => (string)$Wp->author_email);
      
      $this->Insert('zWordpressUser', $Row);
   }
}