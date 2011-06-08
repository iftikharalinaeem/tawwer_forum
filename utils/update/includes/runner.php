<?php

error_reporting(E_ALL & ~E_NOTICE);
define('APPLICATION', 'VanillaUpdate');
define('PATH_CACHE', '/cache');
define("VERBOSE", TRUE);
define("LAME", FALSE);

require_once('configuration.php');
require_once('args.php');
require_once('proxyrequest.php');
require_once('tasklist.php');
require_once('task.php');