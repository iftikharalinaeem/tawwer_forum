#!/usr/bin/php
<?php

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", TRUE);

define('DATABASE_HOST', 'vfdb1');
define('DATABASE_USER', 'root');
define('DATABASE_PASSWORD', 'Va2aWu5A');
define('DATABASE_MAIN', 'vfcom');

if ($argc < 2) exit();

$Tasks = new TaskList('update','/srv/www/vhosts');
$Tasks->RunChunked($argv[1], array(
   'backup',
   'offline',
   'uncache',
   'filesystem',
   'online'
));
