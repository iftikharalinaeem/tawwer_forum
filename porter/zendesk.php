<?php
/**
 * ZenDesk API -> Database exporter for Users, Questions, Answers.
 */

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
CREATE TABLE `zendesk_questions` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `details` text,
  `author_id` int(11) DEFAULT NULL,
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

// DB creds
$DbHost = 'localhost';
$DbName = 'kinvey';
$DbUser = 'root';
$DbPass = '';

// API creds
$Site = 'subdomain';
$User = 'email@domain';
$Pass = 'pass';

// Setup API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_USERPWD, $User.':'.$Pass);

// Setup DB connect
$c = mysqli_connect($DbHost, $DbUser, $DbPass, $DbName);

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

// QUESTIONS (with Answers joined in): Pagination loop
$url = "https://{$Site}.zendesk.com/api/v2/help_center/questions.json?include=answers";
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
      //print_r($Question);
      mysqli_query($c, "insert into `zendesk_questions` (id, title, details, author_id, accepted_answer_id, created_at, updated_at)
      values (
         '".mysqli_real_escape_string($c, $Question->id)."',
         '".mysqli_real_escape_string($c, $Question->title)."',
         '".mysqli_real_escape_string($c, $Question->details)."',
         '".mysqli_real_escape_string($c, $Question->author_id)."',
         '".mysqli_real_escape_string($c, $Question->accepted_answer_id)."',
         '".mysqli_real_escape_string($c, $Question->created_at)."',
         '".mysqli_real_escape_string($c, $Question->updated_at)."'
      )");
      print_r(mysqli_error_list($c));
   }

   // ANSWERS: Output CSV
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
   $url = $ReplyObject->next_page;
} while ($url != 'null' && !in_array($url, $called_uls));

// Donezo
curl_close($ch);
mysqli_close($c);