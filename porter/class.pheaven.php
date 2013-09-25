<?php
/**
 * ProgrammersHeaven exporter tool
 *
 * @copyright Vanilla Forums Inc. 2013
 * @license Proprietary
 * @package VanillaPorter
 */

$Supported['pheaven'] = array('name'=> 'Programmers Heaven', 'prefix'=>'');

class PHeaven extends ExportController {
   /**
    *
    * @param ExportModel $Ex 
    */
   public function ForumExport($Ex) {
      // Get the characterset for the comments.
      $CharacterSet = $Ex->GetCharacterSet('Messages');
      if ($CharacterSet)
         $Ex->CharacterSet = $CharacterSet;
      
      $Ex->BeginExport('', 'Programmers Heaven');
      $Ex->SourcePrefix = '';

      
      // User.
      $User_Map = array(
          'User_ID' => 'UserID',
          'UserName' => array('Column' => 'Name', 'Filter' => array($Ex, 'HTMLDecoder')),
          'EntryDate' => array('Column' => 'DateInserted'),
          'LastVisited' => array('Column' => 'DateLastActive')
          );
      $Ex->ExportTable('User', "
         select u.*,
            'Reset' as HashMethod,
            uu.Email,
            uu.EntryDate,
            uu.LastVisited
         from UserDetails u
         left join Users uu on uu.UserID = u.User_ID
         ", $User_Map);

      
      // UserMeta
      $Ex->ExportTable('UserMeta', "
         select
            User_ID as UserID,
            'Plugin.Signatures.Sig' as `Name`,
            Signature as `Value`
         from UserDetails
         where Signature <> ''");
      
      
      // Category.
      $Category_Map = array(
         'Parent_ID' => 'ParentCategoryID',
         'SortOrder' => 'Sort',
         'URLName' => 'UrlCode'
      );
      $Ex->ExportTable('Category', "
         select
            f.ForumID as CategoryID,
            if (f.ParentForum_ID > 0, f.ParentForum_ID, f.ForumCategory_ID + 1200) as Parent_ID,
            f.Name,
            null,
            d.Description,
            f.URLName
         from Forums f
         left join ForumDetails d on f.ForumID = d.Forum_ID

         union all

         select
            c.ForumCategoryID + 1200 as CategoryID,
            null,
            c.Name,
            c.SortOrder,
            null,
            null
         from ForumCategories c
         ", $Category_Map);
      
      // Discussion.
      $Discussion_Map = array(
          'Thread_ID' => 'DiscussionID',
          'Forum_ID' => 'CategoryID',
          'User_ID' => 'InsertUserID',
          'Subject' => array('Column' => 'Name', 'Filter' => array($Ex, 'HTMLDecoder')),
          'DecompressedBody' => 'Body',
          'EntryDate' => array('Column' => 'DateInserted'),          
          'LockedThread' => 'Closed'
          );
      $Ex->ExportTable('Discussion', "
         select t.*,
            m.LockedThread,
            m.User_ID,
            m.Subject,
            mb.DecompressedBody,
            m.EntryDate,
            'Html' as Format
         from Threads t
         join ThreadStructure ts
            on t.Thread_ID = ts.Thread_ID
         left join Messages m
            on ts.Message_ID = m.MessageID
         left join MessageBody mb   
            on m.MessageID = mb.Message_ID
         where ts.ReplyTo_ID = 0", $Discussion_Map);
      
      // Comment.
      $Comment_Map = array(
          'MessageID' => 'CommentID',
          'Thread_ID' => 'DiscussionID',
          'User_ID' => 'InsertUserID',
          'DecompressedBody' => 'Body',
          'Format' => 'Format',
          'EntryDate' => array('Column' => 'DateInserted')
      );
      $Ex->ExportTable('Comment', "
      select m.*,
         'Html' as Format,
         mb.DecompressedBody,
         ts.Thread_ID
      from Messages m
      left join MessageBody mb
         on m.MessageID = mb.Message_ID
      left join ThreadStructure ts 
         on ts.Message_ID = m.MessageID
      where ts.ReplyTo_ID > 0
      ", $Comment_Map);
            
      $Ex->EndExport();
   }   
   
   /**
    * @param string $Hex Hex-encoded data.
    */
   public function HexDecoder($Hex) {
      return iconv("Windows-1252", "UTF-8//TRANSLIT", pack('H*', $Hex));
   }
}
