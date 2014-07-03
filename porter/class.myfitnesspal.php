<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

$Supported['myfitnesspal'] = array('name' => 'My Fitness Pal', 'prefix' => '');


class MyFitnessPal extends ExportController {
    /**
     * @param ExportModel $Ex
     */
    protected function ForumExport($Ex) {

        $lang = $this->Param('lang', 'en');

//        $this->Ex->TestMode = TRUE;

        $Ex->BeginExport('', 'myfitnesspal');

        // Forums => Categories:
        // Categories

        $Category_Map = array(
            'id' => 'CategoryID',
            'name' => 'Name',
            'description' => 'Body',
            'position' => 'TreeLeft',
            'language' => array('Column'=>'Language', 'Type' => 'varchar(10)')
        );
        $Ex->ExportTable(
            'Category',
            "select f.id, f.position, f.name, f.description, f.category_id, language, 'Default' as DisplayAs, 0 as AllowGroups
                from forums f where language= 'en' and group_id is null
                union all
                select 1003, 0, 'Customer Support', '', -1, 'en', 'Default', 0
                union all
                select 1004, 0, 'Social Groups', '', -1, 'en', 'Discussions', 1
            "
            , $Category_Map);

        // Topics => Discussions
        // Discussions
        $Discussion_Map = array(
            'id' => 'DiscussionID',
            'subject' => 'Name',
            'body' => 'Body',
            'user_id' => 'InsertUserID',
            'created_at' => 'DateInserted',
            'update_at' =>  'DataUpdated',
            'group_id' =>  'GroupID',
            'Format' => 'Format'
        );

        //1004 = social groups category id.
        $Ex->ExportTable(
            'Discussion',
            "select
                t.*, p.body, locked as closed, f.group_id,
                'BBCode' as Format,
                if (sticky>0, 1, 0) as Announce,

                case
                 when group_id is not null
                 then 1004
                 when group_id is null
                 then p.forum_id
                 when category_id = 2
                 then 1003
                 else null
 				end as CategoryID

                from topics t
                left join posts p on (t.id = p.topic_id and t.user_id = p.user_id )
                left join forums f on (t.forum_id = f.id)
				where language = 'en'
				and t.deleted = 0
                group by t.id order by t.id
                ",
            $Discussion_Map
        );

        // Posts => Comments
        // Comments

        $Comment_Map = array(
            'id' => 'CommentID',
            'topic_id' => 'DiscussionID',
            'body' => 'Body',
            'user_id' => 'InsertUserID',
            'created_at' => 'DateInserted',
            'update_at' => 'DateUpdated',
            'Format' => 'Format'
        );

        $Ex->ExportTable(
            'Comment',
            "select p.*, 'BBCode' as Format from posts p
              left join forums f on (forum_id = f.id) where
              language = '$lang' and deleted = 0 and subject is null
            ",
            $Comment_Map
        );

        // Users

        $User_Map = array(
            'id' => 'UserID',
            'email' => 'Email',
            'username' => 'Name',
            'created_at' => 'DateInserted',
            'update_at' => 'DateUpdated'
        );
        $Ex->ExportTable(
            'User',
            "select * from users",
            $User_Map
        );

        // Groups

        $Group_Map = array(
            'id' => 'GroupID',
            'name' => 'Name',
            'short_description' => 'Description',
            'created_at' => 'DateInserted',
            'update_at' => 'DateUpdated',
            'owner_id' => 'InsertUserID',
        );
        $Ex->ExportTable(
            'Group',
            "select g.*,
              1004 as CategoryID,
              'BBCode' as Format,
              if (private=1, 'Private', 'Public') as Privacy,
              if (private=1, 'Approval', 'Public') as Registration,
              if (private=1, 'Members', 'Public') as Visibility

              from groups g
            ",
            $Group_Map
        );

        // Group Members
        $Group_Member_Map = array(
            'user_id' => 'UserID',
            'group_id' => 'GroupID'
        );
        $Ex->ExportTable(
            'UserGroup',
            "select
                gm.*,
                if (gm.user_id = g.owner_id, 'Leader', 'Member') as Role
                from group_memberships gm join groups g on (g.id = gm.group_id)
            ",
            $Group_Member_Map
        );


        // Conversations
        $this->ExportConversations();

        // UserMeta // Signatures
        $User_Meta_Map = array(
            'user_id' => 'UserID',
            'group_id' => 'GroupID',
        );
        $Ex->ExportTable(
            'UserMeta',
            "select
                fs.user_id,
                'Plugin.Signatures.Format' as Name,
                'BBCode' as Value
                from forum_signatures fs

            union all

            select
                fs.user_id,
                'Plugin.Signatures.Sig' as Name,
                body as Value
                from forum_signatures fs
            ",
            $User_Meta_Map
        );

    }

    protected function ExportConversations() {
        $Ex = $this->Ex;

        $Sql = <<<EOT
create table tmp_to (
	id int,
	userid int,
	primary key (id, userid)
);

truncate table tmp_to;

insert ignore tmp_to (
	id,
	userid
)
select
	id,
	from_user_id
from messages;

insert ignore tmp_to (
	id,
	userid
)
select
	id,
	to_user_id
from messages;

create table tmp_to2 (
	id int primary key,
	userids varchar(255)
);
truncate table tmp_to2;

insert tmp_to2 (
	id,
	userids
)
select
	id,
	group_concat(userid order by userid)
from tmp_to
group by id;

create table tmp_conversation (
	id int primary key,
	title varchar(255),
	title2 varchar(255),
	userids varchar(255),
	groupid int
);

replace tmp_conversation (
	id,
	title,
	title2,
	userids
)
select
	t.id,
	subject,
	subject,
	t2.userids
from messages t
join tmp_to2 t2
	on t.id = t2.id;

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 3))
where title2 like 'Re:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 5))
where title2 like 'Sent:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 3))
where title2 like 'Re:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 5))
where title2 like 'Sent:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 3))
where title2 like 'Re:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 5))
where title2 like 'Sent:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 3))
where title2 like 'Re:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 5))
where title2 like 'Sent:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 3))
where title2 like 'Re:%';

update tmp_conversation
set title2 = trim(right(title2, length(title2) - 5))
where title2 like 'Sent:%';

create table tmp_group (
	title2 varchar(255),
	userids varchar(255),
	groupid int,
	primary key (title2, userids)
);

replace tmp_group (
	title2,
	userids,
	groupid
)
select
	title2,
	userids,
	min(id)
from tmp_conversation
group by title2, userids;

create index tidx_group on tmp_group(title2, userids);
create index tidx_conversation on tmp_conversation(title2, userids);

update tmp_conversation c
join tmp_group g
	on c.title2 = g.title2 and c.userids = g.userids
set c.groupid = g.groupid;
EOT;

        $Ex->QueryN($Sql);

        // Converations.
        $Conversation_Map = array(
            'groupid' => 'ConversationID',
            'title2' => 'Subject',
            'created_at' => 'DateInserted',
            'from_user_id' => 'InsertUserID'
        );
        $Sql = "select
	mt.*,
   tc.title2,
	tc.groupid
from messages mt
join tmp_conversation tc
	on mt.id = tc.id";
//        $this->ClearFilters('Conversation', $Conversation_Map, $Sql);
        $Ex->ExportTable('Conversation', $Sql, $Conversation_Map);

        // Converation Message.
        $ConversationMessage_Map = array(
            'id' => 'MessageID',
            'groupid' => 'ConversationID',
            'created_at' => 'DateInserted',
            'body' => 'Body',
            'from_user_id' => 'InsertUserID',
        );
        $Sql = "select
	m.*,
	tc.title2,
	tc.groupid
from messages m
join tmp_conversation tc
	on m.id = tc.id";
     //   $this->ClearFilters('ConversationMessage', $ConversationMessage_Map, $Sql);
        $Ex->ExportTable('ConversationMessage', $Sql, $ConversationMessage_Map);

        // User Conversation.
        $UserConversation_Map = array(
            'userid' => 'UserID',
            'groupid' => 'ConversationID'
        );
        $Sql = "select distinct
	g.groupid,
	t.userid
from tmp_to t
join tmp_group g
	on g.groupid = t.id";
        $Ex->ExportTable('UserConversation', $Sql, $UserConversation_Map);

        $Ex->QueryN("
      drop table tmp_conversation;
drop table tmp_to;
drop table tmp_to2;
drop table tmp_group;");
    }




}