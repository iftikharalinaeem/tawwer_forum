#!/usr/bin/php
<?php

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", TRUE);

define('DATABASE_HOST', 'localhost');
define('DATABASE_USER', 'root');
define('DATABASE_PASSWORD', 'Va2aWu5A');
define('DATABASE_MAIN', 'vfcom');

//define('DATABASE_PASSWORD', 'okluijfire4');
//define('DATABASE_MAIN', 'vanilla.tim');

$Proceed = TaskList::Question("Would you like to continue anyway? 'yes' to continue, 'no' to halt, 'skip' to skip this client:",
   "Continue?", array('yes','no','skip'), 'yes');

die('ans: '.$Proceed);

$Tasks = new TaskList('launch','/srv/www/vhosts');
$Tasks->RunAll(array(
   'backup',
   'filesystem'
));
