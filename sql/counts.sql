set @minid = 0;

update GDN_Discussion d
set CountComments = (select count(CommentID) from GDN_Comment c where c.DiscussionID = d.DiscussionID)
where d.DiscussionID >= @minid;

update GDN_Discussion d
set DateLastComment = (select max(DateInserted) from GDN_Comment c where c.DiscussionID = d.DiscussionID)
where d.DiscussionID >= @minid;

update GDN_Discussion d
set DateLastComment = DateInserted
where DateLastComment is null
	and d.DiscussionID >= @minid;;

update GDN_Discussion d
join GDN_Comment c
	on c.DiscussionID = d.DiscussionID and c.DateInserted = d.DateLastComment
set d.LastCommentID = c.CommentID
where d.DiscussionID >= @minid;

update GDN_Discussion d
join GDN_Comment c
	on d.LastCommentID = c.CommentID
set d.LastCommentUserID = c.InsertUserID
where d.DiscussionID >= @minid;

update GDN_Category c
set CountDiscussions = (select count(DiscussionID) from GDN_Discussion d where d.CategoryID = c.CategoryID);

update GDN_Category c
set CountComments = (select sum(CountComments) from GDN_Discussion d where d.CategoryID = c.CategoryID);

update GDN_Category c
set LastDateInserted = (select max(DateLastComment) from GDN_Discussion d where d.CategoryID = c.CategoryID);

update GDN_Category cat
join GDN_Discussion d
	on d.CategoryID = cat.CategoryID and d.DateLastComment = cat.LastDateInserted
set cat.LastCommentID = d.LastCommentID,
	cat.LastDiscussionID = d.DiscussionID;

update GDN_User
set DateFirstVisit = DateInserted
where DateFirstVisit is null;

update GDN_User u
set CountDiscussions = (select count(d.InsertUserID) from GDN_Discussion d where d.InsertUserID = u.UserID);

update GDN_User u
set CountComments = (select count(d.InsertUserID) from GDN_Comment d where d.InsertUserID = u.UserID);

update GDN_Tag t
set CountDiscussions = (select count(td.DiscussionID) from GDN_TagDiscussion td where t.TagID = td.TagID);

update GDN_UserDiscussion ud
join GDN_Discussion d
	on d.DiscussionID = ud.DiscussionID and d.InsertUserID = ud.UserID
set ud.Participated = 1;

update GDN_UserDiscussion ud
join GDN_Comment d
	on d.DiscussionID = ud.DiscussionID and d.InsertUserID = ud.UserID
set ud.Participated = 1;

# These updates for sites that have the groups application enabled.
update GDN_Group g
    set DateLastComment = (select max(c.DateInserted) from GDN_Comment c join GDN_Discussion d on (c.DiscussionID = d.DiscussionID) where d.GroupID = g.GroupID);

update GDN_Group g
    set CountMembers = (select count(ug.UserGroupID) from GDN_UserGroup ug where ug.GroupID = g.GroupID and g.GroupID = ug.GroupID);

update GDN_Group g
    set CountDiscussions = (select count(d.DiscussionID) from GDN_Discussion d where d.GroupID = g.GroupID);
