-- Copy data from forums into GDN_Category

mysql> show columns from forums;
+---------------+------------+------+-----+---------+-------+
| Field         | Type       | Null | Key | Default | Extra |
+---------------+------------+------+-----+---------+-------+
| forum_id      | int(11)    | NO   |     | NULL    |       | 
| title         | text       | YES  |     | NULL    |       | 
| description   | text       | YES  |     | NULL    |       | 
| display_order | int(11)    | YES  |     | NULL    |       | 
| is_visable    | tinyint(1) | YES  |     | 1       |       | 
+---------------+------------+------+-----+---------+-------+
5 rows in set (0.00 sec)

mysql> show columns from GDN_Category;        
+------------------+---------------+------+-----+---------+----------------+
| Field            | Type          | Null | Key | Default | Extra          |
+------------------+---------------+------+-----+---------+----------------+
| CategoryID       | int(11)       | NO   | PRI | NULL    | auto_increment | 
| ParentCategoryID | int(11)       | YES  |     | NULL    |                | 
| CountDiscussions | int(11)       | NO   |     | 0       |                | 
| AllowDiscussions | enum('1','0') | NO   |     | 1       |                | 
| Name             | varchar(30)   | NO   |     | NULL    |                | 
| Description      | varchar(250)  | YES  |     | NULL    |                | 
| Sort             | int(11)       | YES  |     | NULL    |                | 
| InsertUserID     | int(11)       | NO   | MUL | NULL    |                | 
| UpdateUserID     | int(11)       | YES  |     | NULL    |                | 
| DateInserted     | datetime      | NO   |     | NULL    |                | 
| DateUpdated      | datetime      | NO   |     | NULL    |                | 
+------------------+---------------+------+-----+---------+----------------+
11 rows in set (0.00 sec)

truncate table GDN_Category;
insert into GDN_Category
(CategoryID, Name, Description, InsertUserID)
select forum_id, title, description, 1 from forums;



-- Copy data from forums_threads into GDN_Discussion

mysql> show columns from forums_threads;
+------------------+------------+------+-----+---------------------+-------+
| Field            | Type       | Null | Key | Default             | Extra |
+------------------+------------+------+-----+---------------------+-------+
| forum_thread_id  | int(11)    | NO   |     | NULL                |       | 
| user_id          | int(11)    | YES  |     | NULL                |       | 
| user_id_latest   | int(11)    | YES  |     | NULL                |       | 
| title            | text       | YES  |     | NULL                |       | 
| creation_tsz     | timestamp  | NO   |     | CURRENT_TIMESTAMP   |       | 
| last_updated_tsz | timestamp  | NO   |     | 0000-00-00 00:00:00 |       | 
| post_count       | int(11)    | YES  |     | NULL                |       | 
| forum_id         | int(11)    | YES  |     | NULL                |       | 
| title_fti        | text       | YES  |     | NULL                |       | 
| is_locked        | tinyint(1) | YES  |     | 0                   |       | 
| title_post_fti   | text       | YES  |     | NULL                |       | 
| active           | tinyint(1) | NO   |     | 1                   |       | 
+------------------+------------+------+-----+---------------------+-------+
12 rows in set (0.01 sec)

mysql> show columns from GDN_Discussion;
+-----------------+---------------+------+-----+---------+----------------+
| Field           | Type          | Null | Key | Default | Extra          |
+-----------------+---------------+------+-----+---------+----------------+
| DiscussionID    | int(11)       | NO   | PRI | NULL    | auto_increment | 
| CategoryID      | int(11)       | NO   | MUL | NULL    |                | 
| InsertUserID    | int(11)       | NO   | MUL | NULL    |                | 
| UpdateUserID    | int(11)       | NO   |     | NULL    |                | 
| FirstCommentID  | int(11)       | YES  | MUL | NULL    |                | 
| LastCommentID   | int(11)       | YES  | MUL | NULL    |                | 
| Name            | varchar(100)  | NO   |     | NULL    |                | 
| CountComments   | int(11)       | NO   |     | 1       |                | 
| Closed          | enum('1','0') | NO   |     | 0       |                | 
| Announce        | enum('1','0') | NO   |     | 0       |                | 
| Sink            | enum('1','0') | NO   |     | 0       |                | 
| DateInserted    | datetime      | NO   |     | NULL    |                | 
| DateUpdated     | datetime      | NO   |     | NULL    |                | 
| DateLastComment | datetime      | NO   |     | NULL    |                | 
| Attributes      | text          | YES  |     | NULL    |                | 
+-----------------+---------------+------+-----+---------+----------------+
15 rows in set (0.00 sec)

truncate table GDN_Discussion;
insert into GDN_Discussion
(DiscussionID, CategoryID, InsertUserID, Name, CountComments, Closed, DateInserted, DateLastComment)
select forum_thread_id, forum_id, user_id, title, post_count, is_locked, DATE_FORMAT(creation_tsz, '%Y-%m-%d %H:%i:%s'), DATE_FORMAT(last_updated_tsz, '%Y-%m-%d %H:%i:%s')
from forums_threads;


-- Copy data from forums_threads_posts into GDN_Comment

mysql> show columns from forums_threads_posts;
+----------------------+-------------+------+-----+-------------------+-------+
| Field                | Type        | Null | Key | Default           | Extra |
+----------------------+-------------+------+-----+-------------------+-------+
| forum_thread_post_id | int(11)     | NO   |     | NULL              |       | 
| post                 | text        | YES  |     | NULL              |       | 
| user_id              | int(11)     | YES  |     | NULL              |       | 
| creation_tsz         | timestamp   | NO   |     | CURRENT_TIMESTAMP |       | 
| post_order           | int(11)     | YES  |     | 1                 |       | 
| ip                   | varchar(50) | YES  |     | NULL              |       | 
| forum_thread_id      | int(11)     | YES  |     | NULL              |       | 
| post_fti             | text        | YES  |     | NULL              |       | 
| active               | tinyint(1)  | NO   |     | 1                 |       | 
+----------------------+-------------+------+-----+-------------------+-------+
9 rows in set (0.00 sec)

mysql> show columns from GDN_Comment;
+--------------+-------------+------+-----+---------+----------------+
| Field        | Type        | Null | Key | Default | Extra          |
+--------------+-------------+------+-----+---------+----------------+
| CommentID    | int(11)     | NO   | PRI | NULL    | auto_increment | 
| DiscussionID | int(11)     | NO   | MUL | NULL    |                | 
| InsertUserID | int(11)     | YES  | MUL | NULL    |                | 
| UpdateUserID | int(11)     | YES  |     | NULL    |                | 
| DeleteUserID | int(11)     | YES  |     | NULL    |                | 
| Body         | text        | NO   |     | NULL    |                | 
| Format       | varchar(20) | YES  |     | NULL    |                | 
| DateInserted | datetime    | NO   |     | NULL    |                | 
| DateDeleted  | datetime    | YES  |     | NULL    |                | 
| DateUpdated  | datetime    | YES  |     | NULL    |                | 
| Flag         | tinyint(4)  | NO   |     | 0       |                | 
+--------------+-------------+------+-----+---------+----------------+
11 rows in set (0.00 sec)

truncate table GDN_Comment;
insert into GDN_Comment
(CommentID, DiscussionID, InsertUserID, Body, Format, DateInserted)
select SQL_BIG_RESULT DISTINCT forum_thread_post_id, forum_thread_id, user_id, post, 'Html', DATE_FORMAT(creation_tsz, '%Y-%m-%d %H:%i:%s')
from forums_threads_posts;

-- Don't have the usernames, so insert UserIDs instead

mysql> show columns from GDN_User;
+--------------------------+------------------+------+-----+---------+----------------+
| Field                    | Type             | Null | Key | Default | Extra          |
+--------------------------+------------------+------+-----+---------+----------------+
| UserID                   | int(10)          | NO   | PRI | NULL    | auto_increment | 
| PhotoID                  | int(8)           | YES  | MUL | NULL    |                | 
| Name                     | varchar(20)      | NO   | MUL | NULL    |                | 
| Password                 | varbinary(34)    | NO   |     | NULL    |                | 
| About                    | text             | YES  |     | NULL    |                | 
| Email                    | varchar(200)     | NO   |     | NULL    |                | 
| ShowEmail                | enum('1','0')    | NO   |     | 0       |                | 
| Gender                   | enum('m','f')    | NO   |     | m       |                | 
| CountVisits              | int(8)           | NO   |     | 0       |                | 
| CountInvitations         | int(2)           | NO   |     | 0       |                | 
| CountNotifications       | int(11)          | NO   |     | 0       |                | 
| InviteUserID             | int(10)          | YES  |     | NULL    |                | 
| DiscoveryText            | text             | YES  |     | NULL    |                | 
| Preferences              | text             | YES  |     | NULL    |                | 
| Permissions              | text             | YES  |     | NULL    |                | 
| Attributes               | text             | YES  |     | NULL    |                | 
| DateSetInvitations       | datetime         | YES  |     | NULL    |                | 
| DateOfBirth              | datetime         | YES  |     | NULL    |                | 
| DateFirstVisit           | datetime         | YES  |     | NULL    |                | 
| DateLastActive           | datetime         | YES  |     | NULL    |                | 
| DateInserted             | datetime         | NO   |     | NULL    |                | 
| DateUpdated              | datetime         | YES  |     | NULL    |                | 
| HourOffset               | int(2)           | NO   |     | 0       |                | 
| CacheRoleID              | int(11)          | YES  |     | NULL    |                | 
| Admin                    | enum('1','0')    | NO   |     | 0       |                | 
| CountUnreadConversations | int(11)          | NO   |     | 0       |                | 
| CountDiscussions         | int(11)          | NO   |     | 0       |                | 
| CountUnreadDiscussions   | int(11)          | NO   |     | 0       |                | 
| CountComments            | int(11)          | NO   |     | 0       |                | 
| CountDrafts              | int(11)          | NO   |     | 0       |                | 
| CountBookmarks           | int(11)          | NO   |     | 0       |                | 
| AccountID                | int(10) unsigned | YES  |     | NULL    |                | 
| DateContributorAgreement | datetime         | YES  |     | NULL    |                | 
+--------------------------+------------------+------+-----+---------+----------------+
33 rows in set (0.00 sec)

insert into GDN_User
(UserID, Name, Password, Email, DateInserted)
select SQL_BIG_RESULT DISTINCT InsertUserID, 'User_' + InsertUserID, 'letmein', 'etsyuser_' + InsertUserID + '@vanilladev.com', now()
from GDN_Comment
where InsertUserID <> 1
order by InsertUserID asc