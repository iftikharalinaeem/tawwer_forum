<?php
/**
 * Invision Powerboard exporter tool
 *
 * @copyright Vanilla Forums Inc. 2010
 * @license Proprietary
 * @package VanillaPorter
 */

$Supported['ipb'] = array('name'=> 'Invision Powerboard (IPB) 3.*', 'prefix'=>'ibf_');

class IPB extends ExportController {
   
   /**
    * @param ExportModel $Ex 
    */
   protected function ForumExport($Ex) {
      $Ex->TestMode = TRUE;
      $Ex->TestLimit = FALSE;
      $Ex->Destination = 'database';
      $Ex->DestDb = 'grill';
//      $Ex->CaptureOnly = TRUE;
      $Ex->ScriptCreateTable = FALSE;
      $Ex->DestPrefix = 'GDN_';
      
      $Ex->SourcePrefix = 'ibf_';
      $Cdn = 'http://cdn.vanillaforums.com/grilldome.vanillaforums.com/';
      
      // Get the characterset for the comments.
      $CharacterSet = $Ex->GetCharacterSet('posts');
      if ($CharacterSet)
         $Ex->CharacterSet = $CharacterSet;
      
      // Decode all of the necessary fields.
//      $Ex->HTMLDecoderDb('members', 'members_display_name', 'member_id');
//      $Ex->HTMLDecoderDb('members', 'name', 'member_id');
//      $Ex->HTMLDecoderDb('members', 'title', 'member_id');
//      $Ex->HtmlDecoderDb('groups', 'g_title', 'g_id');
//      $Ex->HtmlDecoderDb('topics', 'title', 'tid');
//      $Ex->HtmlDecoderDb('topics', 'description', 'tid');
      
      // Begin
      $Ex->BeginExport('', 'IPB 3.*', array('HashMethod' => 'ipb'));
      
      // Users.
      $User_Map = array(
         'member_id' => 'UserID',
         'members_display_name' => 'Name',
         'email' => 'Email',
         'joined' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
         'firstvisit' => array('Column' => 'DateFirstVisit', 'SourceColumn' => 'joined', 'Filter' => array($Ex, 'TimestampToDate')),
         'ip_address' => 'InsertIPAddress',
         'title' => 'Title',
         'time_offset' => 'HourOffset',
         'last_activity' => array('Column' => 'DateLastActive', 'Filter' => array($Ex, 'TimestampToDate')),
         'member_banned' => 'Banned',
         'Photo' => 'Photo',
         'title' => 'Title',
         'location' => 'Location'
      );
      if ($Ex->Exists('member_extra')) {
         $Sql = "select
                  m.*,
                  m.joined as firstvisit,
                  'ipb' as HashMethod,
                  !hide_email as ShowEmail,
                  concat(m.members_pass_hash, '$', m.members_pass_salt) as Password,
                  x.avatar_location as Photo,
                  x.location
                 from ibf_members m
                 left join ibf_member_extra x
                  on m.member_id = x.id";
      } else {
         $Sql = "select
                  m.*,
                  joined as firstvisit,
                  'ipb' as HashMethod,
                  !hide_email as ShowEmail,
                  concat(m.members_pass_hash, '$', m.members_pass_salt) as Password,
                  case when length(p.avatar_location) <= 3 or p.avatar_location is null then null
                  	when p.avatar_type = 'local' then concat('$Cdn', p.avatar_location)
                  	when p.avatar_type = 'upload' then concat('$Cdn', p.avatar_location)
                  	else p.avatar_location end as Photo
                 from ibf_members m
                 left join ibf_profile_portal p
                 	on m.member_id = p.pp_member_id";
      }
      $this->ClearFilters('members', $User_Map, $Sql, 'm');
      $Ex->ExportTable('User', $Sql, $User_Map);  // ":_" will be replaced by database prefix
      
      // Roles.
      $Role_Map = array(
          'g_id' => 'RoleID',
          'g_title' => 'Name'
      );
      $Ex->ExportTable('Role', "select * from ibf_groups", $Role_Map);
      
      // Permissions.
      $Permission_Map = array(
          'g_id' => 'RoleID',
          'g_view_board' => 'Garden.SignIn.Allow',
          'g_view_board2' => 'Garden.Profiles.View',
          'g_view_board3' => 'Garden.Activity.View',
          'g_view_board4' => 'Vanilla.Discussions.View',
          'g_edit_profile' => 'Garden.Profiles.Edit',
          'g_post_new_topics' => 'Vanilla.Discussions.Add',
          'g_reply_other_topics' => 'Vanilla.Comments.Add',
//          'g_edit_posts' => 'Vanilla.Comments.Edit', // alias
          'g_open_close_posts' => 'Vanilla.Discussions.Close',
          'g_is_supmod' => 'Garden.Moderation.Manage',
          'g_access_cp' => 'Garden.Settings.View',
//          'g_edit_topic' => 'Vanilla.Discussions.Edit'
      );
      $Permission_Map = $Ex->FixPermissionColumns($Permission_Map);
      $Ex->ExportTable('Permission', "
         select r.*,
            r.g_view_board as g_view_board2,
            r.g_view_board as g_view_board3,
            r.g_view_board as g_view_board4
         from ibf_groups r", $Permission_Map);
      
      // User Role.
      $UserRole_Map = array(
          'member_id' => 'UserID',
          'member_group_id' => 'RoleID'
      );
      $Ex->ExportTable('UserRole', "select * from ibf_members", $UserRole_Map);
      
      // Category.
      $Category_Map = array(
          'id' => 'CategoryID',
          'name' => 'Name',
          'name_seo' => 'UrlCode',
          'description' => 'Description',
          'parent_id' => 'ParentCategoryID',
          'position' => 'Sort'
          );
      $Ex->ExportTable('Category', "select * from ibf_forums", $Category_Map);
      
      // Discussion.
      $Discussion_Map = array(
          'tid' => 'DiscussionID',
          'title' => 'Name',
          'forum_id' => 'CategoryID',
          'starter_id' => 'InsertUserID',
          'start_date' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
          'ip_address' => 'InsertIPAddress',
          'edit_time' => array('Column' => 'DateUpdated', 'Filter' => array($Ex, 'TimestampToDate')),
          'views' => 'CountViews',
          'post' => 'Body'
          );
      $Sql = "
      select 
         t.*,
         case 
         	when t.description <> '' and p.post is not null then concat('<div class=\"IPBDescription\">', t.description, '</div>', p.post)
         	when t.description <> '' then t.description
         	else p.post
         end as post,
         'IPB' as Format,
         p.ip_address,
         p.edit_time
      from ibf_topics t
      left join ibf_posts p
         on t.topic_firstpost = p.pid
      where t.tid between {from} and {to}";
      $this->ClearFilters('topics', $Discussion_Map, $Sql, 't');
      $Ex->ExportTable('Discussion', $Sql, $Discussion_Map);
      
      // Comments.
      $Comment_Map = array(
          'pid' => 'CommentID',
          'topic_id' => 'DiscussionID',
          'author_id' => 'InsertUserID',
          'ip_address' => 'InsertIPAddress',
          'post_date' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
          'edit_time' => array('Column' => 'DateUpdated', 'Filter' => array($Ex, 'TimestampToDate')),
          'post' => 'Body'
          );
      $Sql = "
      select
         p.*,
         'IPB' as Format
      from ibf_posts p
      join ibf_topics t
         on p.topic_id = t.tid
      where p.pid between {from} and {to}
         and p.pid <> t.topic_firstpost";
      $this->ClearFilters('Comment', $Comment_Map, $Sql, 'p');
      $Ex->ExportTable('Comment', $Sql, $Comment_Map);
      
      // Media.
      $Media_Map = array(
          'attach_id' => 'MediaID',
          'atype_mimetype' => 'Type',
          'attach_file' => 'Name',
          'attach_location' => 'Path',
          'attach_date' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
          'attach_member_id' => 'InsertUserID',
          'attach_filesize' => 'Size',
          'ForeignID' => 'ForeignID',
          'ForeignTable' => 'ForeignTable',
          'StorageMethod' => 'StorageMethod',
          'img_width' => 'ImageWidth',
          'img_height' => 'ImageHeight'
      );
      $Sql = "select 
	a.*, 
	ty.atype_mimetype,
	case when p.pid = t.topic_firstpost then 'discussion' else 'comment' end as ForeignTable,
	case when p.pid = t.topic_firstpost then t.tid else p.pid end as ForeignID,
	case a.attach_img_width when 0 then a.attach_thumb_width else a.attach_img_width end as img_width,
	case a.attach_img_height when 0 then a.attach_thumb_height else a.attach_img_height end as img_height,
	'local' as StorageMethod
from ibf_attachments a
join ibf_posts p
	on a.attach_rel_id = p.pid and a.attach_rel_module = 'post'
join ibf_topics t
	on t.tid = p.topic_id
left join ibf_attachments_type ty
	on a.attach_ext = ty.atype_extension";
      $this->ClearFilters('Media', $Media_Map, $Sql);
      $Ex->ExportTable('Media', $Sql, $Media_Map);
      
      
      // Converations.
      $Conversation_Map = array(
          'mt_id' => 'ConversationID',
          'mt_date' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
          'mt_title' => 'Subject',
          'mt_starter_id' => 'InsertUserID'
          );
      $Sql = "select * from ibf_message_topics where mt_is_deleted = 0";
      $this->ClearFilters('Conversation', $Conversation_Map, $Sql);
      $Ex->ExportTable('Conversation', $Sql, $Conversation_Map);
      
      // Converation Message.
      $ConversationMessage_Map = array(
          'msg_id' => 'MessageID',
          'msg_topic_id' => 'ConversationID',
          'msg_date' => array('Column' => 'DateInserted', 'Filter' => array($Ex, 'TimestampToDate')),
          'msg_post' => 'Body',
          'Format' => 'Format',
          'msg_author_id' => 'InsertUserID',
          'msg_ip_address' => 'InsertIPAddress'
          );
      $Sql = "select 
            m.*,
            'IPB' as Format
         from ibf_message_posts m";
      $this->ClearFilters('ConversationMessage', $ConversationMessage_Map, $Sql);
      $Ex->ExportTable('ConversationMessage', $Sql, $ConversationMessage_Map);
      
      // User Conversation.
      $UserConversation_Map = array(
          'map_user_id' => 'UserID',
          'map_topic_id' => 'ConversationID',
          'Deleted' => 'Deleted'
          );
      $Sql = "select
         t.*,
         !map_user_active as Deleted
      from ibf_message_topic_user_map t";
      $Ex->ExportTable('UserConversation', $Sql, $UserConversation_Map);
      
      $Ex->EndExport();
   }
   
   public function ClearFilters($Table, &$Map, &$Sql) {
      $PK = FALSE;
      $Selects = array();
      
      foreach ($Map as $Column => $Info) {
         if (!$PK)
            $PK = $Column;
         
         if (!is_array($Info) || !isset($Info['Filter']))
            continue;
         
         
         $Filter = $Info['Filter'];
         if (isset($Info['SourceColumn']))
            $Source = $Info['SourceColumn'];
         else
            $Source = $Column;
         
         switch ($Filter[1]) {
            case 'HTMLDecoder':
               $this->Ex->HTMLDecoderDb($Table, $Column, $PK);
               unset($Map[$Column]['Filter']);
               break;
            case 'TimestampToDate':
               $Selects[] = "from_unixtime($Source) as {$Column}_Date";
               
               unset($Map[$Column]);
               $Map[$Column.'_Date'] = $Info['Column'];
               break;
         }
      }
      
      if (count($Selects) > 0) {
         $Statement = implode(', ', $Selects);
         $Sql = str_replace('from ', ", $Statement\nfrom ", $Sql);
      }
   }
}