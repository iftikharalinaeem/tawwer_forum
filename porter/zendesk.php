<?php
/**
 * ZenDesk API -> Database exporter for Users, Questions, Answers.
 */

/*$Supported['zendesk'] = array('name'=>'ZenDesk API', 'prefix'=>'');
$Supported['zendesk']['CommandLine'] = array(
   'apiuser' => array('API user (usually email).', 'Sx' => '::', 'Short' => 'au'),
   'apipass' => array(API authentication password.', 'Sx' => '::', 'Short' => 'ap', 'Default' => ''),
   'apisite' => array('Subdomain of the ZenDesk.com site.', 'Sx' => '::', 'Short' => 'site'),
   'noexport' => array('Whether or not to skip the export.', 'Sx' => '::'),
);*/

/*
CREATE TABLE `zendesk_users` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `verified` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_questions` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `details` text,
  `author_id` int(11) DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `accepted_answer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_answers` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `body` text,
  `author_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

//class ZenDesk extends ExportController {

// DB creds
$DbHost = 'localhost';
$DbName = 'kinvey';
$DbUser = 'root';
$DbPass = '';

// API creds
$Site = 'kinvey';
$User = 'caroline@kinvey.com';
$Pass = 'changeme123';

// Setup API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_USERPWD, $User.':'.$Pass);

// Setup DB connect
$c = mysqli_connect($DbHost, $DbUser, $DbPass, $DbName);


/* protected function ForumExport($Ex) {
      // Begin
      $Ex->BeginExport('', 'vBulletin 3.* and 4.*');

      if ($this->Param('noexport')) {
         $Ex->Comment('Skipping the export.');
         $Ex->EndExport();
         return;
      }

      $Ex->Query("CREATE TEMPORARY TABLE stuff (`UserID` INT NOT NULL ,`Name` VARCHAR( 255 ) NOT NULL ,`Value` text NOT NULL)");
   }
*/

// USERS: Pagination loop
$url = "https://{$Site}.zendesk.com/api/v2/users.json";
do {
   $called_uls[] = $url;

   // USERS: API call
   curl_setopt($ch, CURLOPT_URL, $url);
   $Response = curl_exec($ch);
   $ReplyObject = json_decode($Response);
   if (!isset($ReplyObject->users) || !is_array($ReplyObject->users)) {
      print_r($ReplyObject);
      echo "Bad users response.";
      break;
   }

   // USERS: Output CSV
   foreach ($ReplyObject->users as $User) {
      //print_r($User);
      mysqli_query($c, "insert into zendesk_users (id, name, created_at, updated_at, role, verified, email)
      values (
         '".mysqli_real_escape_string($c, $User->id)."',
         '".mysqli_real_escape_string($c, $User->name)."',
         '".mysqli_real_escape_string($c, $User->created_at)."',
         '".mysqli_real_escape_string($c, $User->updated_at)."',
         '".mysqli_real_escape_string($c, $User->role)."',
         '".mysqli_real_escape_string($c, $User->verified)."',
         '".mysqli_real_escape_string($c, $User->email)."'
      )");
      print_r(mysqli_error_list($c));
   }
   $url = $ReplyObject->next_page;
} while ($url != 'null' && !in_array($url, $called_uls));

// QUESTIONS (with Answers, Topics joined in): Pagination loop
$url = "https://{$Site}.zendesk.com/api/v2/help_center/questions.json?include=answers,topics";
do {
   $called_uls[] = $url;
   // API call
   curl_setopt($ch, CURLOPT_URL, $url);
   $Response = curl_exec($ch);
   $ReplyObject = json_decode($Response);
   if (!isset($ReplyObject->questions) || !is_array($ReplyObject->questions)) {
      print_r($ReplyObject);
      echo "Bad questions response.";
      break;
   }

   // QUESTIONS: Output CSV
   foreach ($ReplyObject->questions as $Question) {
      // Only allow 1 topic (category)
      $topic_id = 0;
      if (is_array($Question->topic_ids) && count($Question->topic_ids)) {
         $topic_id = array_shift($Question->topic_ids);
      }

      // Insert in DB
      mysqli_query($c, "insert into `zendesk_questions` (id, title, details, author_id, topic_id, accepted_answer_id, created_at, updated_at)
      values (
         '".mysqli_real_escape_string($c, $Question->id)."',
         '".mysqli_real_escape_string($c, $Question->title)."',
         '".mysqli_real_escape_string($c, $Question->details)."',
         '".mysqli_real_escape_string($c, $Question->author_id)."',
         '".mysqli_real_escape_string($c, $topic_id)."',
         '".mysqli_real_escape_string($c, $Question->accepted_answer_id)."',
         '".mysqli_real_escape_string($c, $Question->created_at)."',
         '".mysqli_real_escape_string($c, $Question->updated_at)."'
      )");
      print_r(mysqli_error_list($c));
   }

   // ANSWERS: Insert in DB
   foreach ($ReplyObject->answers as $Answer) {
      //print_r($Answer);
      mysqli_query($c, "insert into `zendesk_answers` (id, body, author_id, question_id, created_at, updated_at)
      values (
         '".mysqli_real_escape_string($c, $Answer->id)."',
         '".mysqli_real_escape_string($c, $Answer->body)."',
         '".mysqli_real_escape_string($c, $Answer->author_id)."',
         '".mysqli_real_escape_string($c, $Answer->question_id)."',
         '".mysqli_real_escape_string($c, $Answer->created_at)."',
         '".mysqli_real_escape_string($c, $Answer->updated_at)."'
      )");
      print_r(mysqli_error_list($c));
   }

   // TOPICS: Insert in DB
   $FoundTopics = array();
   foreach ($ReplyObject->topics as $Topic) {
      //print_r($Answer);
      // API will provide a copy of the topic for every question so weed out duplicates as we go
      if (!in_array($Topic->id, $FoundTopics)) {
         mysqli_query($c, "insert into `zendesk_topics` (id, name, html_url, description, created_at, updated_at)
         values (
            '".mysqli_real_escape_string($c, $Topic->id)."',
            '".mysqli_real_escape_string($c, $Topic->name)."',
            '".mysqli_real_escape_string($c, $Topic->html_url)."',
            '".mysqli_real_escape_string($c, $Topic->description)."',
            '".mysqli_real_escape_string($c, $Topic->created_at)."',
            '".mysqli_real_escape_string($c, $Topic->updated_at)."'
         )");
         $FoundTopics[] = $Topic->id;
         print_r(mysqli_error_list($c));
      }
   }

   $url = $ReplyObject->next_page;

} while ($url != 'null' && !in_array($url, $called_uls));

// Donezo
curl_close($ch);
mysqli_close($c);

//}

/*
insert into GDN_User (UserID, Name, Email, DateInserted, DateUpdated, DateFirstVisit, DateLastActive, Admin, HashMethod)
select id, name, email, created_at, updated_at, created_at, updated_at, if(role='admin',1,0), 'Reset' from zendesk_users;

insert into GDN_UserRole (UserID, RoleID) select id, 8 from zendesk_users where role = 'end-user';
insert into GDN_UserRole (UserID, RoleID) select id, 16 from zendesk_users where role = 'admin';
insert into GDN_UserRole (UserID, RoleID) select id, 32 from zendesk_users where role = 'agent';

insert into GDN_Category (CategoryID, Name, UrlCode, Description, DateInserted, DateUpdated)
select id, name, html_url, description, created_at, updated_at from zendesk_topics;

insert into GDN_Discussion (DiscussionID, Name, Body, InsertUserID, QnA, DateInserted, DateUpdated, Format, `Type`, CategoryID)
select id, title, details, author_id, IF(accepted_answer_id>0,'Accepted','Answered'), created_at, updated_at, 'Markdown', 'Question', topic_id from zendesk_questions;

insert into GDN_Comment (CommentID, Body, InsertUserID, DiscussionID, DateInserted, DateUpdated, Format)
select id, body, author_id, question_id, created_at, updated_at, 'Markdown' from zendesk_answers;

update GDN_Comment set QnA = 'Accepted' where CommentID in (select accepted_answer_id from zendesk_questions);

# Run /dba/counts (increase chunksize to 10000000 or skip Discussion.LastCommentUserID)

update GDN_Discussion set QnA = 'Unanswered' where CountComments = 0;
*/