<?php
/**
 * ZenDesk API -> Database exporter for Users, Questions, Answers.
 */

$Supported['zendesk'] = array('name' => 'ZenDesk API', 'prefix' => '');
$Supported['zendesk']['CommandLine'] = array(
    'apiuser' => array('API user (usually email).', 'Sx' => '::', 'Short' => 'au'),
    'apipass' => array('API authentication password.', 'Sx' => '::', 'Short' => 'ap', 'Default' => ''),
    'apisite' => array('Subdomain of the ZenDesk.com site.', 'Sx' => '::', 'Short' => 'site'),
    'noexport' => array('Whether or not to skip the export.', 'Sx' => '::'),
);

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
CREATE TABLE `zendesk_sections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `locale` varchar(11) DEFAULT NULL,
  `source_locale` varchar(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `outdated` tinyint(1) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `sorting` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=200574537 DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `locale` varchar(11) DEFAULT NULL,
  `source_locale` varchar(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `outdated` tinyint(1) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=200519777 DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_article_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `body` text,
  `author_id` int(11) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=201436257 DEFAULT CHARSET=utf8;
CREATE TABLE `zendesk_articles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `body` text,
  `locale` varchar(50) DEFAULT NULL,
  `source_locale` varchar(50) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `comments_disabled` tinyint(1) DEFAULT NULL,
  `outdated` tinyint(1) DEFAULT NULL,
  `label_names` varchar(50) DEFAULT NULL,
  `draft` tinyint(1) DEFAULT NULL,
  `promoted` tinyint(1) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `vote_sum` int(11) DEFAULT NULL,
  `vote_count` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=203408964 DEFAULT CHARSET=utf8;
*/

//class ZenDesk extends ExportController {

// DB creds
$DbHost = 'localhost';
$DbName = 'kinvey';
$DbUser = 'root';
$DbPass = '';

// API creds
$Site = 'kinvey';
$User = 'some@one.com';
$Pass = 'pass';

// Setup API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $User . ':' . $Pass);

// Setup DB connect
$c = mysqli_connect($DbHost, $DbUser, $DbPass, $DbName);

// USERS: Pagination loop
$url = "https://{$Site}.zendesk.com/api/v2/users.json";
$resultsProcessed = 0;
do {
    $called_uls[] = $url;

    // USERS: API call
    curl_setopt($ch, CURLOPT_URL, $url);
    $Response = curl_exec($ch);
    $ReplyObject = json_decode($Response);
    if (!isset($ReplyObject->users) || !is_array($ReplyObject->users)) {
        print_r($ReplyObject);
        echo "Bad users response." . PHP_EOL;
        break;
    }

    // USERS: Output CSV
    $resultsProcessed = count($called_uls) * 100;
    $ReplyObject->count = 0;
    if ($ReplyObject->count == 0) {
        echo "There is no users to export." . PHP_EOL;
        break;
    }

    foreach ($ReplyObject->users as $User) {
        $Result = mysqli_query(
            $c,
            "replace into zendesk_users (id, name, created_at, updated_at, role, verified, email)
                 values (
                     '" . mysqli_real_escape_string($c, $User->id) . "',
                     '" . mysqli_real_escape_string($c, $User->name) . "',
                     '" . mysqli_real_escape_string($c, $User->created_at) . "',
                     '" . mysqli_real_escape_string($c, $User->updated_at) . "',
                     '" . mysqli_real_escape_string($c, $User->role) . "',
                     '" . mysqli_real_escape_string($c, $User->verified) . "',
                     '" . mysqli_real_escape_string($c, $User->email) . "'
                  )"
        );
        if (!$Result) {
            print_r(mysqli_error_list($c));
        }
        $resultsProcessed++;
        show_status($resultsProcessed, $ReplyObject->count);


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
    if ($ReplyObject->count == 0) {
        echo "There is no questions to export. \n";
        break;
    }
    // QUESTIONS: Output CSV
    $resultsProcessed = count($called_uls) * 100;
    foreach ($ReplyObject->questions as $Question) {
        // Only allow 1 topic (category)
        $topic_id = 0;
        if (is_array($Question->topic_ids) && count($Question->topic_ids)) {
            $topic_id = array_shift($Question->topic_ids);
        }

        // Insert in DB
        $Result = mysqli_query(
            $c,
            "insert into `zendesk_questions` (id, title, details, author_id, topic_id, accepted_answer_id, created_at, updated_at)
                 values (
                     '" . mysqli_real_escape_string($c, $Question->id) . "',
                     '" . mysqli_real_escape_string($c, $Question->title) . "',
                     '" . mysqli_real_escape_string($c, $Question->details) . "',
                     '" . mysqli_real_escape_string($c, $Question->author_id) . "',
                     '" . mysqli_real_escape_string($c, $topic_id) . "',
                     '" . mysqli_real_escape_string($c, $Question->accepted_answer_id) . "',
                     '" . mysqli_real_escape_string($c, $Question->created_at) . "',
                     '" . mysqli_real_escape_string($c, $Question->updated_at) . "'
                  )"
        );
        if (!$Result) {
            print_r(mysqli_error_list($c));
        }

    }
    show_status($resultsProcessed, $ReplyObject->count);

    // ANSWERS: Insert in DB
    foreach ($ReplyObject->answers as $Answer) {
        //print_r($Answer);
        $Result = mysqli_query(
            $c,
            "insert into `zendesk_answers` (id, body, author_id, question_id, created_at, updated_at)
                 values (
                    '" . mysqli_real_escape_string($c, $Answer->id) . "',
                     '" . mysqli_real_escape_string($c, $Answer->body) . "',
                     '" . mysqli_real_escape_string($c, $Answer->author_id) . "',
                     '" . mysqli_real_escape_string($c, $Answer->question_id) . "',
                     '" . mysqli_real_escape_string($c, $Answer->created_at) . "',
                     '" . mysqli_real_escape_string($c, $Answer->updated_at) . "'
                  )"
        );
        if (!$Result) {
            print_r(mysqli_error_list($c));
        }
    }

    // TOPICS: Insert in DB
    $FoundTopics = array();
    foreach ($ReplyObject->topics as $Topic) {
        //print_r($Answer);
        // API will provide a copy of the topic for every question so weed out duplicates as we go
        if (!in_array($Topic->id, $FoundTopics)) {
            $Result = mysqli_query(
                $c,
                "insert into `zendesk_topics` (id, name, html_url, description, created_at, updated_at)
                        values (
                            '" . mysqli_real_escape_string($c, $Topic->id) . "',
                            '" . mysqli_real_escape_string($c, $Topic->name) . "',
                            '" . mysqli_real_escape_string($c, $Topic->html_url) . "',
                            '" . mysqli_real_escape_string($c, $Topic->description) . "',
                            '" . mysqli_real_escape_string($c, $Topic->created_at) . "',
                            '" . mysqli_real_escape_string($c, $Topic->updated_at) . "'
                         )"
            );
            $FoundTopics[] = $Topic->id;
            if(!$Result) {
                print_r(mysqli_error_list($c));
            }        }
    }

    $url = $ReplyObject->next_page;

} while ($url != 'null' && !in_array($url, $called_uls));

// ARTICLES
echo "Exporting Articles" . PHP_EOL;
$url = "https://{$Site}.zendesk.com/api/v2/help_center/articles.json";
$resultsProcessed = 0;
do {
    break;
    $called_uls[] = $url;

    //API call
    curl_setopt($ch, CURLOPT_URL, $url);
    $Response = curl_exec($ch);
    $ReplyObject = json_decode($Response);
    if (!isset($ReplyObject->articles) || !is_array($ReplyObject->articles)) {
        print_r($ReplyObject);
        echo "Bad articles response.". PHP_EOL;
        break;
    }

    if ($ReplyObject->count == 0) {
        echo "There is no articles to export.\n";
        break;
    }

    foreach ($ReplyObject->articles as $Article) {
        $Result = mysqli_query(
            $c,
            "replace into zendesk_articles (id, url, html_url, author_id, comments_disabled, label_names,
                       draft, promoted, position, vote_sum, vote_count, section_id, created_at, updated_at, name, title, body,
                       source_locale, locale, outdated)
                         values (
                            '" . mysqli_real_escape_string($c, $Article->id) . "',
                 '" . mysqli_real_escape_string($c, $Article->url) . "',
                 '" . mysqli_real_escape_string($c, $Article->html_url) . "',
                 '" . mysqli_real_escape_string($c, $Article->author_id) . "',
                 '" . mysqli_real_escape_string($c, $Article->comments_disabled) . "',
                 '" . mysqli_real_escape_string($c, '') . "',
                 '" . mysqli_real_escape_string($c, $Article->draft) . "',
                 '" . mysqli_real_escape_string($c, $Article->promoted) . "',
                 '" . mysqli_real_escape_string($c, $Article->position) . "',
                 '" . mysqli_real_escape_string($c, $Article->vote_sum) . "',
                 '" . mysqli_real_escape_string($c, $Article->vote_count) . "',
                 '" . mysqli_real_escape_string($c, $Article->section_id) . "',
                 '" . mysqli_real_escape_string($c, $Article->created_at) . "',
                 '" . mysqli_real_escape_string($c, $Article->updated_at) . "',
                 '" . mysqli_real_escape_string($c, $Article->name) . "',
                 '" . mysqli_real_escape_string($c, $Article->title) . "',
                 '" . mysqli_real_escape_string($c, $Article->body) . "',
                 '" . mysqli_real_escape_string($c, $Article->source_locale) . "',
                 '" . mysqli_real_escape_string($c, $Article->locale) . "',
                 '" . mysqli_real_escape_string($c, $Article->outdated) . "'
              )"
        );
        $resultsProcessed++;
        show_status($resultsProcessed, $ReplyObject->count);

        if (!$Result) {
            print_r(mysqli_error_list($c));
        }

    }

    $url = $ReplyObject->next_page;
} while ($url != null && !in_array($url, $called_uls));

// ARTICLE COMMENTS

$Result = mysqli_query($c, 'SELECT id from zendesk_articles');
$resultsProcessed = 1;
echo "Exporting Article Comments" . PHP_EOL;
while (false && $Article = mysqli_fetch_assoc($Result)) {
    $url = "https://{$Site}.zendesk.com/api/v2/help_center/articles/{$Article['id']}/comments.json";
    do {
        $called_uls[] = $url;

        //API call
        curl_setopt($ch, CURLOPT_URL, $url);
        $Response = curl_exec($ch);
        $ReplyObject = json_decode($Response);
        if (!isset($ReplyObject->comments) || !is_array($ReplyObject->comments)) {
            print_r($ReplyObject);
            echo "Bad article comments response.";
            break;
        }
        $resultsProcessed++;
        show_status($resultsProcessed, $Result->num_rows);

        if ($ReplyObject->count == 0) {
            //echo "There is no article comments to export.\n";
            continue;
        }

        foreach ($ReplyObject->comments as $ArticleComment) {
            $InsertResult = mysqli_query(
                $c,
                "replace into zendesk_article_comments (id, url, body, author_id, source_id, source_type,
                           locale, html_url, created_at, updated_at)
                             values (
                                '" . mysqli_real_escape_string($c, $ArticleComment->id) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->url) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->body) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->author_id) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->source_id) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->source_type) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->locale) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->html_url) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->created_at) . "',
                                 '" . mysqli_real_escape_string($c, $ArticleComment->updated_at) . "'
                                )"
            );
            if(!$InsertResult) {
                print_r(mysqli_error_list($c));
            }
        }
        $url = $ReplyObject->next_page;
    } while ($url != null && !in_array($url, $called_uls));


}


// SECTIONS
echo "Exporting Sections" . PHP_EOL;
$url = "https://{$Site}.zendesk.com/api/v2/help_center/sections.json";
$resultsProcessed = 0;
do {
    $called_uls[] = $url;

    //API call
    curl_setopt($ch, CURLOPT_URL, $url);
    $Response = curl_exec($ch);
    $ReplyObject = json_decode($Response);
    if (!isset($ReplyObject->sections) || !is_array($ReplyObject->sections)) {
        print_r($ReplyObject);
        echo "Bad sections response.". PHP_EOL;
        break;
    }

    if ($ReplyObject->count == 0) {
        echo "There is no articles to export.\n";
        break;
    }

    foreach ($ReplyObject->sections as $Section) {
        $Result = mysqli_query(
            $c,
            "replace into zendesk_sections (id, name, description, locale, source_locale, url, html_url,
                category_id, outdated, position, sorting, created_at, updated_at)
                         values (
                            '" . mysqli_real_escape_string($c, $Section->id) . "',
                            '" . mysqli_real_escape_string($c, $Section->name) . "',
                            '" . mysqli_real_escape_string($c, $Section->description) . "',
                            '" . mysqli_real_escape_string($c, $Section->locale) . "',
                            '" . mysqli_real_escape_string($c, $Section->source_locale) . "',
                            '" . mysqli_real_escape_string($c, $Section->url) . "',
                            '" . mysqli_real_escape_string($c, $Section->html_url) . "',
                            '" . mysqli_real_escape_string($c, $Section->category_id) . "',
                            '" . mysqli_real_escape_string($c, $Section->outdated) . "',
                            '" . mysqli_real_escape_string($c, $Section->position) . "',
                            '" . mysqli_real_escape_string($c, $Section->sorting) . "',
                            '" . mysqli_real_escape_string($c, $Section->created_at) . "',
                            '" . mysqli_real_escape_string($c, $Section->updated_at) . "'
              )"
        );
        $resultsProcessed++;
        show_status($resultsProcessed, $ReplyObject->count);

        if (!$Result) {
            print_r(mysqli_error_list($c));
        }

    }

    $url = $ReplyObject->next_page;
} while ($url != null && !in_array($url, $called_uls));


// CATEGORIES
echo "Exporting Categories" . PHP_EOL;
$url = "https://{$Site}.zendesk.com/api/v2/help_center/categories.json";
$resultsProcessed = 0;
do {
    $called_uls[] = $url;

    //API call
    curl_setopt($ch, CURLOPT_URL, $url);
    $Response = curl_exec($ch);
    $ReplyObject = json_decode($Response);
    if (!isset($ReplyObject->categories) || !is_array($ReplyObject->categories)) {
        print_r($ReplyObject);
        echo "Bad categories response.". PHP_EOL;
        break;
    }

    if ($ReplyObject->count == 0) {
        echo "There is no articles to export.\n";
        break;
    }

    foreach ($ReplyObject->categories as $Category) {
        $Result = mysqli_query(
            $c,
            "replace into zendesk_categories (id, name, description, locale, source_locale, url, html_url,
                outdated, position, created_at, updated_at)
                         values (
                            '" . mysqli_real_escape_string($c, $Category->id) . "',
                            '" . mysqli_real_escape_string($c, $Category->name) . "',
                            '" . mysqli_real_escape_string($c, $Category->description) . "',
                            '" . mysqli_real_escape_string($c, $Category->locale) . "',
                            '" . mysqli_real_escape_string($c, $Category->source_locale) . "',
                            '" . mysqli_real_escape_string($c, $Category->url) . "',
                            '" . mysqli_real_escape_string($c, $Category->html_url) . "',
                            '" . mysqli_real_escape_string($c, $Category->outdated) . "',
                            '" . mysqli_real_escape_string($c, $Category->position) . "',
                            '" . mysqli_real_escape_string($c, $Category->created_at) . "',
                            '" . mysqli_real_escape_string($c, $Category->updated_at) . "'
              )"
        );
        $resultsProcessed++;
        show_status($resultsProcessed, $ReplyObject->count);

        if (!$Result) {
            print_r(mysqli_error_list($c));
        }

    }

    $url = $ReplyObject->next_page;
} while ($url != null && !in_array($url, $called_uls));


echo "Export Complete" . PHP_EOL;

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
select id, name, lower(replace(name,' ','-')), description, created_at, updated_at from zendesk_topics;

insert into GDN_Discussion (DiscussionID, Name, Body, InsertUserID, QnA, DateInserted, DateUpdated, Format, `Type`, CategoryID)
select id, title, details, author_id, IF(accepted_answer_id>0,'Accepted','Answered'), created_at, updated_at, 'Markdown', 'Question', topic_id from zendesk_questions;

insert into GDN_Comment (CommentID, Body, InsertUserID, DiscussionID, DateInserted, DateUpdated, Format)
select id, body, author_id, question_id, created_at, updated_at, 'Markdown' from zendesk_answers;

update GDN_Comment set QnA = 'Accepted' where CommentID in (select accepted_answer_id from zendesk_questions);

# Run /dba/counts (increase chunksize to 10000000 or skip Discussion.LastCommentUserID)

update GDN_Discussion set QnA = 'Unanswered' where CountComments = 0;
*/


/*

Copyright (c) 2010, dealnews.com, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
 * Neither the name of dealnews.com, Inc. nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

 */

/**
 * show a status bar in the console
 *
 * <code>
 * for($x=1;$x<=100;$x++){
 *
 *     show_status($x, 100);
 *
 *     usleep(100000);
 *
 * }
 * </code>
 *
 * @param   int $done how many items are completed
 * @param   int $total how many items are to be done total
 * @param   int $size optional size of the status bar
 * @return  void
 *
 */

function show_status($done, $total, $size = 30) {

    static $start_time;

    // if we go over our bound, just ignore it
    if ($done > $total) {
        return;
    }

    if (empty($start_time)) {
        $start_time = time();
    }
    $now = time();

    $perc = (double)($done / $total);

    $bar = floor($perc * $size);

    $status_bar = "\r[";
    $status_bar .= str_repeat("=", $bar);
    if ($bar < $size) {
        $status_bar .= ">";
        $status_bar .= str_repeat(" ", $size - $bar);
    } else {
        $status_bar .= "=";
    }

    $disp = number_format($perc * 100, 0);

    $status_bar .= "] $disp%  $done/$total";

    $rate = ($now - $start_time) / $done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $status_bar .= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";

    echo "$status_bar  ";

    flush();

    // when done, send a newline
    if ($done == $total) {
        echo "\n";
    }

}

