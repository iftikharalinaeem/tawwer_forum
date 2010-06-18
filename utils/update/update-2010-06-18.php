#!/usr/local/bin/php
<?php

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", FALSE);

define('DATABASE_HOST', 'localhost');
define('DATABASE_USER', 'root');
define('DATABASE_PASSWORD', 'okluijfire4');
define('DATABASE_MAIN', 'vanilla.tim');

$Tasks = new TaskList('launch','/www/vanilla/');
$Tasks->RunSelectiveRegex('/^vanilla$/',array(
   'uncache'
));