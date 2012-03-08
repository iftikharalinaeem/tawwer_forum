<?php
/*
Extension Name: GravityInsights
Extension Url: http://gravity.com
Description: The Gravity Insights Community Plugin
Version: 1.0.0
Author: Gravity
Author Url: http://gravity.com/
*/

$insights_config = parse_ini_file('siteconfig.ini');

$Context->SetDefinition('InsightsSiteGuid', $insights_config['site_guid']);
$Context->SetDefinition('InsightsHiddenForums', $insights_config['hidden_forums']);
$Context->SetDefinition('InsightsUrl', 'input.insights.gravity.com');


$Context->AddToDelegate('DiscussionForm','PostSaveComment','insights_comment_posted');
$Context->AddToDelegate('DiscussionForm','PostSaveDiscussion','insights_newthread_posted');
$Context->AddToDelegate('SearchForm','PreSearchQuery','insights_hook_search');
$Context->AddToDelegate('ApplyForm','PostCreateUser','insights_hook_adduser');
$Context->AddToDelegate('PageEnd','PreRender','insights_hook_beacon');



function insights_comment_posted($DiscussionForm)
{
	$comment = $DiscussionForm->Comment;
	$Context = $DiscussionForm->Context;
	
	$p = array();
	$p['site_guid'] = $Context->GetDefinition('InsightsSiteGuid');
	$p['action'] = 'newpost_complete';
	$p['thread_id'] = $comment->DiscussionID;
	$p['post_id'] = $comment->CommentID;
	$p['forum_id'] = $DiscussionForm->Discussion->CategoryID;
	$p['user_id'] = $DiscussionForm->Context->Session->User->UserID;
	$p['forum_title'] = $DiscussionForm->Discussion->Category;
	$p['thread_title'] = urlencode($DiscussionForm->Discussion->Name);
	$p['post_title'] = "";
	$p['user_name'] = urlencode($DiscussionForm->Context->Session->User->Name);
	$p['forum_description'] = "";
	$p['post_content'] = urlencode($comment->Body);
	$p['poster_ip'] = $_SERVER['REMOTE_ADDR'];
	if(insights_not_hidden_forum($p['forum_id'], $Context)) {
		insights_send_post($p, $Context);
	}

	
}

function insights_newthread_posted($DiscussionForm)
{
	$discussion = $DiscussionForm->Discussion;
	$comment = $discussion->Comment;
	$Context = $DiscussionForm->Context;
	
	// GET THE FORUM TITLE - if anyone knows a better way to no have to do the sql query
	// let me know, I didn't see the category name in the available objects I found in this 
	// context, it's not super bad because it is a primary key based lookup
	$sql = "SELECT Name FROM {$Context->Configuration['DATABASE_TABLE_PREFIX']}Category
			WHERE CategoryId = ".mysql_real_escape_string((int)$DiscussionForm->Discussion->CategoryID)."";
	$result = mysql_query($sql,$Context->Database->Connection);
	$forum = mysql_fetch_assoc($result);
	
	$p = array();
	$p['site_guid'] = $Context->GetDefinition('InsightsSiteGuid');
	$p['action'] = 'newthread_post_complete';
	$p['thread_id'] = $discussion->DiscussionID;
	$p['post_id'] = 0;
	$p['forum_id'] = $DiscussionForm->Discussion->CategoryID;
	$p['user_id'] = $DiscussionForm->Context->Session->User->UserID;
	$p['forum_title'] = $forum['Name'];
	$p['thread_title'] = urlencode($DiscussionForm->Discussion->Name);
	$p['post_title'] = "";
	$p['user_name'] = urlencode($DiscussionForm->Context->Session->User->Name);
	$p['forum_description'] = "";
	$p['post_content'] = urlencode($comment->Body);
	$p['poster_ip'] = $_SERVER['REMOTE_ADDR'];
	if(insights_not_hidden_forum($p['forum_id'], $Context)) {
		insights_send_post($p, $Context);
	}
}

function insights_hook_search(&$Search)
{
	$Context = $Search->Context;
	$p = array();
	$p['site_guid'] = $Context->GetDefinition('InsightsSiteGuid');
	$p['action'] = 'search_process_fullsearch';
	$p['user_id'] = $Context->Session->User->UserID;
	$p['user_name'] = urlencode($Context->Session->User->Name);
	$p['post_content'] = urlencode($Search->Search->Query);
	insights_send_post($p, $Context);
}

function insights_hook_adduser(&$ApplyForm) 
{
	$p = array();
	$Context = $ApplyForm->Context;
	$Application = $ApplyForm->Applicant;
	$p['site_guid'] = $Context->GetDefinition('InsightsSiteGuid');
	$p['action'] = 'register_addmember_complete';
	$p['user_id'] = $Application->UserID;
	$p['user_name'] = urlencode($Application->Name);
	insights_send_post($p, $Context);
}

function insights_hook_beacon(&$PageEnd)
{
	$Context = $PageEnd->Context;
	$tmp_userid = $Context->Session->User->UserID;
	$tmp_usertitle=urlencode(urlencode($Context->Session->User->Name));
	$tmp_posttitle='';
	$tmp_postid = 0;
	$tmp_threadid = (int)@$_GET['DiscussionID']; // convert to int to prevent injection
	$tmp_forumid = (int)@$_GET['CategoryID']; // convert to int to prevent injection
	
	if($tmp_threadid === 0 && $tmp_forumid === 0) {
		$tmp_postid = 0;
		$tmp_forumid = 0;
		$tmp_threadtitle='';
		$tmp_forumtitle='';
	} else if($tmp_threadid !== 0 && $tmp_forumid === 0) {
		$sql = "SELECT cat.CategoryID as catid, d.Name as threadname, cat.Name as catname
			FROM {$Context->Configuration['DATABASE_TABLE_PREFIX']}Discussion d
			INNER JOIN {$Context->Configuration['DATABASE_TABLE_PREFIX']}Category cat ON d.CategoryId = cat.CategoryID
			WHERE DiscussionID = ".mysql_real_escape_string($tmp_threadid)."";
		$result = mysql_query($sql,$Context->Database->Connection);
		$row = mysql_fetch_assoc($result);
		$tmp_forumid = (int)$row['catid'];
		$tmp_threadtitle=urlencode($row['threadname']);
		$tmp_forumtitle=urlencode($row["catname"]);
	} else if ($tmp_threadid === 0 && $tmp_forumid !== 0) {
		$sql = "SELECT Name FROM {$Context->Configuration['DATABASE_TABLE_PREFIX']}Category
			WHERE CategoryId = ".mysql_real_escape_string($tmp_forumid)."";
		$result = mysql_query($sql,$Context->Database->Connection);
		$row = mysql_fetch_assoc($result);
		$tmp_threadtitle='';
		$tmp_forumtitle=urlencode($row["Name"]);
	}
	
	
	
	
	$output="<div id='insights_div_x99'></div>
	<script language='javascript'>
			vb_a_stracker='{$Context->GetDefinition('InsightsSiteGuid')}';
			vb_a_threadid={$tmp_threadid};
			vb_a_postid={$tmp_postid};
			vb_a_forumid={$tmp_forumid};
			vb_a_userid={$tmp_userid};
			vb_a_username='{$tmp_usertitle}';
			vb_a_posttitle='{$tmp_posttitle}';
			vb_a_threadtitle='{$tmp_threadtitle}';
			vb_a_forumtitle='{$tmp_forumtitle}';
			document.write(unescape('%3Cscript src=\'http://input.insights.gravity.com/pigeons/capture_moth.php\' type=\'text/javascript\'%3E%3C/script%3E'));
			</script>";
	echo $output;
}


function insights_send_post($p, &$Context) 
{
	$queryStr = insights_generate_query_string($p);
	$url = $Context->GetDefinition('InsightsUrl');
	$postListener = "/pigeons/capture.php";
	$fp = fsockopen($url, 80, $errno, $errstr, 2);
	stream_set_timeout($fp, 2);
	if ($fp) {    
		$out = "POST {$postListener} HTTP/1.1\r\n";
		$out .= "Host: {$url}\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-Length: ".strlen($queryStr)."\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite($fp, $out);
		fwrite($fp, $queryStr."\r\n\r\n");
		fclose($fp);
	}
}




function insights_generate_query_string($params)
{
	$query = '';
	foreach($params as $k=>$v) {
		$query .= "{$k}={$v}&";
	}

	$query = trim($query, "&");
	return $query;
}

/**
 * will determine if this forum should be hidden from insights or not
 * if the forum is ok to send, it will return false, other wise true means it's hidden
 * @param int $forumId
 */
function insights_not_hidden_forum($forumId, &$Context)
{
	$forums = $Context->GetDefinition('InsightsHiddenForums');
	if(!empty($forums)) {
		$hiddenForums = array_map('trim', explode(',',$forums));
	}
	if(in_array($forumId, $hiddenForums)) {
		return false;
	}
	return true;
}