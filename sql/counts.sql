update GDN_Discussion d
set CountComments = (select count(CommentID) from GDN_Comment c where c.DiscussionID = d.DiscussionID);

update GDN_Discussion d
set DateLastComment = (select max(DateInserted) from GDN_Comment c where c.DiscussionID = d.DiscussionID);

update GDN_Discussion d
set DateLastComment = DateInserted
where DateLastComment is null;

update GDN_Discussion d
join GDN_Comment c
	on c.DiscussionID = d.DiscussionID and c.DateInserted = d.DateLastComment
set d.LastCommentID = c.CommentID;

update GDN_Discussion d
join GDN_Comment c
	on d.LastCommentID = c.CommentID
set d.LastCommentUserID = c.InsertUserID;

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