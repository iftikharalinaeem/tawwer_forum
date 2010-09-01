#!/usr/bin/php
<?php

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", FALSE);
define("FAST", ($argc > 2 && $argv[2] == 'fast') ? TRUE : FALSE);
define("VERYFAST", ($argc > 3 && $argv[3] == 'fast') ? TRUE : FALSE);

define('DATABASE_HOST', 'vfdb1');
define('DATABASE_USER', 'root');
define('DATABASE_PASSWORD', 'Va2aWu5A');
define('DATABASE_MAIN', 'vfcom');

if ($argc < 2) exit();

$Tasks = new TaskList(array('maintain'),'/srv/www/vhosts');
$Tasks->RunChunked($argv[1], array(
   'uncache',
   'filesystem'
));