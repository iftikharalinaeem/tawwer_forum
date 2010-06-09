/* Existing: `Description` varchar(200), New: `Description` varchar(500) */
alter table `GDN_Role`  change Description `Description` varchar(500);

/* Existing: `Deletable` enum('1','0') not null default 1, New: `Deletable` tinyint not null default 1 */
alter table `GDN_Role`  change Deletable `Deletable` tinyint not null default 1;

/* Existing: `CanSession` enum('1','0') not null default 1, New: `CanSession` tinyint not null default 1 */
alter table `GDN_Role`  change CanSession `CanSession` tinyint not null default 1;

update `GDN_Role` set `Deletable` = case `Deletable` when 1 then 1 when 2 then 0 else `Deletable` end;

update `GDN_Role` set `CanSession` = case `CanSession` when 1 then 1 when 2 then 0 else `CanSession` end;

update GDN_Role Role set
 Name = 'Banned',
 Sort = '1',
 Deletable = '1',
 CanSession = '0',
 Description = 'Banned users are not allowed to participate or sign in.'
where RoleID = '1';

update GDN_Role Role set
 Name = 'Guest',
 Sort = '2',
 Deletable = '0',
 CanSession = '0',
 Description = 'Guests can only view content. Anyone browsing the site who is not signed in is considered to be a \"Guest\".'
where RoleID = '2';

update GDN_Role Role set
 Name = 'Applicant',
 Sort = '3',
 Deletable = '0',
 CanSession = '0',
 Description = 'Users who have applied for membership, but have not yet been accepted. They have the same permissions as guests.'
where RoleID = '4';

update GDN_Role Role set
 Name = 'Member',
 Sort = '4',
 Deletable = '1',
 CanSession = '1',
 Description = 'Members can participate in discussions.'
where RoleID = '8';

insert GDN_Role
(Name, Sort, Deletable, CanSession, Description, RoleID)
values ('Moderator', '5', '1', '1', 'Moderators have permission to edit most content.', '32');

update GDN_Role Role set
 Name = 'Administrator',
 Sort = '6',
 Deletable = '1',
 CanSession = '1',
 Description = 'Administrators have permission to do anything.'
where RoleID = '16';

/* Existing: `Password` varbinary(34) not null, New: `Password` varbinary(50) not null */
alter table `GDN_User`  change Password `Password` varbinary(50) not null;

alter table `GDN_User`  add `HashMethod` varchar(10) after `Password`;

/* Existing: `About` mediumtext, New: `About` text */
alter table `GDN_User`  change About `About` text;

/* Existing: `ShowEmail` enum('1','0') not null default 0, New: `ShowEmail` tinyint not null default 0 */
alter table `GDN_User`  change ShowEmail `ShowEmail` tinyint not null default 0;

/* Existing: `CountNotifications` int not null default 0, New: `CountNotifications` int */
alter table `GDN_User`  change CountNotifications `CountNotifications` int;

/* Existing: `DiscoveryText` mediumtext, New: `DiscoveryText` text */
alter table `GDN_User`  change DiscoveryText `DiscoveryText` text;

/* Existing: `Preferences` mediumtext, New: `Preferences` text */
alter table `GDN_User`  change Preferences `Preferences` text;

/* Existing: `Permissions` mediumtext, New: `Permissions` text */
alter table `GDN_User`  change Permissions `Permissions` text;

/* Existing: `Attributes` mediumtext, New: `Attributes` text */
alter table `GDN_User`  change Attributes `Attributes` text;

alter table `GDN_User`  add `Score` float after `HourOffset`;

/* Existing: `Admin` enum('1','0') not null default 0, New: `Admin` tinyint not null default 0 */
alter table `GDN_User`  change Admin `Admin` tinyint not null default 0;

alter table `GDN_User`  add `Deleted` tinyint not null default 0 after `Admin`;

update `GDN_User` set `ShowEmail` = case `ShowEmail` when 1 then 1 when 2 then 0 else `ShowEmail` end;

update `GDN_User` set `Admin` = case `Admin` when 1 then 1 when 2 then 0 else `Admin` end;

create table `GDN_UserMeta` (
`UserID` int not null,
`Name` varchar(255) not null,
`Value` text,
primary key (`UserID`, `Name`)
) default character set utf8 collate utf8_unicode_ci;

alter table `GDN_UserAuthentication`  add `ForeignUserKey` varchar(255) not null;

alter table `GDN_UserAuthentication`  add `ProviderKey` varchar(64) not null after `ForeignUserKey`;

alter table `GDN_UserAuthentication` drop primary key;
alter table `GDN_UserAuthentication` add primary key (`ForeignUserKey`, `ProviderKey`);

create table `GDN_UserAuthenticationProvider` (
`AuthenticationKey` varchar(64) not null,
`AuthenticationSchemeAlias` varchar(32) not null,
`URL` varchar(255) not null,
`AssociationSecret` text not null,
`AssociationHashMethod` enum('HMAC-SHA1','HMAC-PLAINTEXT') not null,
`RegistrationUrl` varchar(255),
`SignInUrl` varchar(255),
`SignOutUrl` varchar(255),
primary key (`AuthenticationKey`)
) default character set utf8 collate utf8_unicode_ci;

create table `GDN_UserAuthenticationNonce` (
`Nonce` varchar(200) not null,
`Token` varchar(64) not null,
`Timestamp` timestamp not null,
primary key (`Nonce`)
) default character set utf8 collate utf8_unicode_ci;

create table `GDN_UserAuthenticationToken` (
`Token` varchar(64) not null,
`ProviderKey` varchar(64) not null,
`ForeignUserKey` varchar(255),
`TokenSecret` varchar(64) not null,
`TokenType` enum('request','access') not null,
`Authorized` tinyint not null,
`Timestamp` timestamp not null,
`Lifetime` int not null,
primary key (`Token`, `ProviderKey`)
) default character set utf8 collate utf8_unicode_ci;

update GDN_Permission Permission set
 `Garden.Email.Manage` = '2',
 `Garden.Settings.Manage` = '2',
 `Garden.Routes.Manage` = '2',
 `Garden.Messages.Manage` = '2',
 `Garden.Applications.Manage` = '2',
 `Garden.Plugins.Manage` = '2',
 `Garden.Themes.Manage` = '2',
 `Garden.SignIn.Allow` = '2',
 `Garden.Registration.Manage` = '2',
 `Garden.Applicants.Manage` = '2',
 `Garden.Roles.Manage` = '2',
 `Garden.Users.Add` = '2',
 `Garden.Users.Edit` = '2',
 `Garden.Users.Delete` = '2',
 `Garden.Users.Approve` = '2',
 `Garden.Activity.Delete` = '2'
where RoleID = '0'
  and JunctionTable is null
  and JunctionColumn is null;

update GDN_Permission Permission set
 `Garden.Signin.Allow` = '1'
where RoleID = '8'
  and JunctionTable is null
  and JunctionColumn is null
  and JunctionID is null;

insert GDN_Permission
(`Garden.Signin.Allow`, RoleID, JunctionTable, JunctionColumn, JunctionID)
values ('1', '32', null, null, null);

update GDN_Permission Permission set
 `Garden.Settings.Manage` = '1',
 `Garden.Routes.Manage` = '1',
 `Garden.Applications.Manage` = '1',
 `Garden.Plugins.Manage` = '1',
 `Garden.Themes.Manage` = '1',
 `Garden.SignIn.Allow` = '1',
 `Garden.Registration.Manage` = '1',
 `Garden.Applicants.Manage` = '1',
 `Garden.Roles.Manage` = '1',
 `Garden.Users.Add` = '1',
 `Garden.Users.Edit` = '1',
 `Garden.Users.Delete` = '1',
 `Garden.Users.Approve` = '1',
 `Garden.Activity.Delete` = '1'
where RoleID = '16'
  and JunctionTable is null
  and JunctionColumn is null
  and JunctionID is null;

/* Existing: `AllowComments` enum('1','0') not null default 0, New: `AllowComments` tinyint not null default 0 */
alter table `GDN_ActivityType`  change AllowComments `AllowComments` tinyint not null default 0;

/* Existing: `ShowIcon` enum('1','0') not null default 0, New: `ShowIcon` tinyint not null default 0 */
alter table `GDN_ActivityType`  change ShowIcon `ShowIcon` tinyint not null default 0;

/* Existing: `Notify` enum('1','0') not null default 0, New: `Notify` tinyint not null default 0 */
alter table `GDN_ActivityType`  change Notify `Notify` tinyint not null default 0;

/* Existing: `Public` enum('1','0') not null default 1, New: `Public` tinyint not null default 1 */
alter table `GDN_ActivityType`  change Public `Public` tinyint not null default 1;

update `GDN_ActivityType` set `AllowComments` = case `AllowComments` when 1 then 1 when 2 then 0 else `AllowComments` end;

update `GDN_ActivityType` set `ShowIcon` = case `ShowIcon` when 1 then 1 when 2 then 0 else `ShowIcon` end;

update `GDN_ActivityType` set `Notify` = case `Notify` when 1 then 1 when 2 then 0 else `Notify` end;

update `GDN_ActivityType` set `Public` = case `Public` when 1 then 1 when 2 then 0 else `Public` end;

/* Existing: `Story` mediumtext, New: `Story` text */
alter table `GDN_Activity`  change Story `Story` text;

/* Existing: `Content` mediumtext not null, New: `Content` text not null */
alter table `GDN_Message`  change Content `Content` text not null;

/* Existing: `AllowDismiss` enum('1','0') not null default 1, New: `AllowDismiss` tinyint not null default 1 */
alter table `GDN_Message`  change AllowDismiss `AllowDismiss` tinyint not null default 1;

/* Existing: `Enabled` enum('1','0') not null default 1, New: `Enabled` tinyint not null default 1 */
alter table `GDN_Message`  change Enabled `Enabled` tinyint not null default 1;

update `GDN_Message` set `AllowDismiss` = case `AllowDismiss` when 1 then 1 when 2 then 0 else `AllowDismiss` end;

update `GDN_Message` set `Enabled` = case `Enabled` when 1 then 1 when 2 then 0 else `Enabled` end;

/* Existing: `DateInserted` datetime not null, New: `DateInserted` datetime */
alter table `GDN_Conversation`  change DateInserted `DateInserted` datetime;

alter table `GDN_Conversation`  add `CountMessages` int not null after `DateUpdated`;

alter table `GDN_Conversation`  add `LastMessageID` int not null after `CountMessages`;

alter table `GDN_UserConversation`  add `CountReadMessages` int not null default 0 after `ConversationID`;

/* Existing: `Bookmarked` enum('1','0') not null default 0, New: `Bookmarked` tinyint not null default 0 */
alter table `GDN_UserConversation`  change Bookmarked `Bookmarked` tinyint not null default 0;

alter table `GDN_UserConversation`  add `Deleted` tinyint not null default 0 after `Bookmarked`;

update `GDN_UserConversation` set `Bookmarked` = case `Bookmarked` when 1 then 1 when 2 then 0 else `Bookmarked` end;

/* Existing: `Body` mediumtext not null, New: `Body` text not null */
alter table `GDN_ConversationMessage`  change Body `Body` text not null;

/* Existing: `InsertUserID` int not null, New: `InsertUserID` int */
alter table `GDN_ConversationMessage`  change InsertUserID `InsertUserID` int;

alter table `GDN_ConversationMessage` add key FK_ConversationMessage_ConversationID (`ConversationID`);

update GDN_Conversation c
set CountMessages = (
   select count(MessageID)
   from GDN_ConversationMessage m
   where c.ConversationID = m.ConversationID);

update GDN_Conversation c
set LastMessageID = (
   select max(MessageID)
   from GDN_ConversationMessage m
   where c.ConversationID = m.ConversationID);

update GDN_UserConversation uc
set CountReadMessages = (
  select count(cm.MessageID)
  from GDN_ConversationMessage cm
  where cm.ConversationID = uc.ConversationID
    and cm.MessageID <= uc.LastMessageID);

/* Existing: `CountUnreadConversations` int not null default 0, New: `CountUnreadConversations` int */
alter table `GDN_User`  change CountUnreadConversations `CountUnreadConversations` int;

/* Existing: `AllowDiscussions` enum('1','0') not null default 1, New: `AllowDiscussions` tinyint not null default 1 */
alter table `GDN_Category`  change AllowDiscussions `AllowDiscussions` tinyint not null default 1;

update `GDN_Category` set `AllowDiscussions` = case `AllowDiscussions` when 1 then 1 when 2 then 0 else `AllowDiscussions` end;

alter table `GDN_Discussion`  add `Body` text not null after `Name`;

alter table `GDN_Discussion`  add `Format` varchar(20) after `Body`;

/* Existing: `Closed` enum('1','0') not null default 0, New: `Closed` tinyint not null default 0 */
alter table `GDN_Discussion`  change Closed `Closed` tinyint not null default 0;

/* Existing: `Announce` enum('1','0') not null default 0, New: `Announce` tinyint not null default 0 */
alter table `GDN_Discussion`  change Announce `Announce` tinyint not null default 0;

/* Existing: `Sink` enum('1','0') not null default 0, New: `Sink` tinyint not null default 0 */
alter table `GDN_Discussion`  change Sink `Sink` tinyint not null default 0;

/* Existing: `DateInserted` datetime not null, New: `DateInserted` datetime */
alter table `GDN_Discussion`  change DateInserted `DateInserted` datetime;

/* Existing: `DateLastComment` datetime not null, New: `DateLastComment` datetime */
alter table `GDN_Discussion`  change DateLastComment `DateLastComment` datetime;

alter table `GDN_Discussion`  add `LastCommentUserID` int after `DateLastComment`;

alter table `GDN_Discussion`  add `Score` float after `LastCommentUserID`;

/* Existing: `Attributes` mediumtext, New: `Attributes` text */
alter table `GDN_Discussion`  change Attributes `Attributes` text;

alter table `GDN_Discussion` add key FK_Discussion_FirstCommentID (`FirstCommentID`);

alter table `GDN_Discussion` drop index TX_Discussion;
alter table `GDN_Discussion` add fulltext index TX_Discussion (`Name`, `Body`);

update `GDN_Discussion` set `Closed` = case `Closed` when 1 then 1 when 2 then 0 else `Closed` end;

update `GDN_Discussion` set `Announce` = case `Announce` when 1 then 1 when 2 then 0 else `Announce` end;

update `GDN_Discussion` set `Sink` = case `Sink` when 1 then 1 when 2 then 0 else `Sink` end;

/* Existing: `DateLastViewed` datetime not null, New: `DateLastViewed` datetime */
alter table `GDN_UserDiscussion`  change DateLastViewed `DateLastViewed` datetime;

/* Existing: `Dismissed` varchar(1), New: `Dismissed` tinyint not null default 0 */
alter table `GDN_UserDiscussion`  change Dismissed `Dismissed` tinyint not null default 0;

/* Existing: `Bookmarked` varchar(1), New: `Bookmarked` tinyint not null default 0 */
alter table `GDN_UserDiscussion`  change Bookmarked `Bookmarked` tinyint not null default 0;

/* Existing: `Body` mediumtext not null, New: `Body` text not null */
alter table `GDN_Comment`  change Body `Body` text not null;

/* Existing: `DateInserted` datetime not null, New: `DateInserted` datetime */
alter table `GDN_Comment`  change DateInserted `DateInserted` datetime;

/* Existing: `Flag` int default 0, New: `Flag` tinyint not null default 0 */
alter table `GDN_Comment`  change Flag `Flag` tinyint not null default 0;

alter table `GDN_Comment`  add `Score` float after `Flag`;

/* Existing: `CountDiscussions` int not null default 0, New: `CountDiscussions` int */
alter table `GDN_User`  change CountDiscussions `CountDiscussions` int;

/* Existing: `CountUnreadDiscussions` int not null default 0, New: `CountUnreadDiscussions` int */
alter table `GDN_User`  change CountUnreadDiscussions `CountUnreadDiscussions` int;

/* Existing: `CountComments` int not null default 0, New: `CountComments` int */
alter table `GDN_User`  change CountComments `CountComments` int;

/* Existing: `CountDrafts` int not null default 0, New: `CountDrafts` int */
alter table `GDN_User`  change CountDrafts `CountDrafts` int;

/* Existing: `CountBookmarks` int not null default 0, New: `CountBookmarks` int */
alter table `GDN_User`  change CountBookmarks `CountBookmarks` int;

/* Existing: `Closed` enum('1','0') not null default 0, New: `Closed` tinyint not null default 0 */
alter table `GDN_Draft`  change Closed `Closed` tinyint not null default 0;

/* Existing: `Announce` enum('1','0') not null default 0, New: `Announce` tinyint not null default 0 */
alter table `GDN_Draft`  change Announce `Announce` tinyint not null default 0;

/* Existing: `Sink` enum('1','0') not null default 0, New: `Sink` tinyint not null default 0 */
alter table `GDN_Draft`  change Sink `Sink` tinyint not null default 0;

/* Existing: `Body` mediumtext not null, New: `Body` text not null */
alter table `GDN_Draft`  change Body `Body` text not null;

update `GDN_Draft` set `Closed` = case `Closed` when 1 then 1 when 2 then 0 else `Closed` end;

update `GDN_Draft` set `Announce` = case `Announce` when 1 then 1 when 2 then 0 else `Announce` end;

update `GDN_Draft` set `Sink` = case `Sink` when 1 then 1 when 2 then 0 else `Sink` end;

update GDN_Permission Permission set
 `Vanilla.Settings.Manage` = '2',
 `Vanilla.Categories.Manage` = '2',
 `Vanilla.Spam.Manage` = '2'
where RoleID = '0'
  and JunctionTable is null
  and JunctionColumn is null;

update GDN_Permission Permission set
 `Vanilla.Discussions.View` = '3',
 `Vanilla.Discussions.Add` = '3',
 `Vanilla.Discussions.Edit` = '2',
 `Vanilla.Discussions.Announce` = '2',
 `Vanilla.Discussions.Sink` = '2',
 `Vanilla.Discussions.Close` = '2',
 `Vanilla.Discussions.Delete` = '2',
 `Vanilla.Comments.Add` = '3',
 `Vanilla.Comments.Edit` = '2',
 `Vanilla.Comments.Delete` = '2'
where RoleID = '0'
  and JunctionTable = 'Category'
  and JunctionColumn = 'CategoryID';

update GDN_Discussion, GDN_Comment
set GDN_Discussion.Body = GDN_Comment.Body,
   GDN_Discussion.Format = GDN_Comment.Format
where GDN_Discussion.FirstCommentID = GDN_Comment.CommentID;

update GDN_Discussion set LastCommentID = null where LastCommentID = FirstCommentID;

delete GDN_Comment
from GDN_Comment inner join GDN_Discussion
where GDN_Comment.CommentID = GDN_Discussion.FirstCommentID;

update GDN_Discussion d
inner join GDN_Comment c
   on c.DiscussionID = d.DiscussionID
inner join (
   select max(c2.CommentID) as CommentID
   from GDN_Comment c2
   group by c2.DiscussionID
) c2
on c.CommentID = c2.CommentID
set d.LastCommentID = c.CommentID,
   d.LastCommentUserID = c.InsertUserID
where d.LastCommentUserID is null;

update GDN_Permission p2
   inner join GDN_Category c
    on c.CategoryID = p2.JunctionID
       and p2.JunctionTable = 'Category'
      and c.Name = 'General'
   inner join GDN_Permission p
     on p.RoleID = p2.RoleID
       and p.JunctionTable is null
   set
      p.`Vanilla.Discussions.Add` = p2.`Vanilla.Discussions.Add`,
      p.`Vanilla.Discussions.Edit` = p2.`Vanilla.Discussions.Edit`,
      p.`Vanilla.Discussions.Announce` = p2.`Vanilla.Discussions.Announce`,
      p.`Vanilla.Discussions.Sink` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Discussions.Close` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Discussions.Delete` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Discussions.View` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Comments.Add` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Comments.Edit` = p2.`Vanilla.Discussions.Sink`,
      p.`Vanilla.Comments.Delete` = p2.`Vanilla.Discussions.Sink`
   where p.RoleID <> 0;

alter table `GDN_Discussion` drop column `FirstCommentID`;