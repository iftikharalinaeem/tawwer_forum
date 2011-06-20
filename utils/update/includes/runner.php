<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

error_reporting(E_ALL & ~E_NOTICE);
define('APPLICATION', 'VanillaUpdate');
define('PATH_CACHE', '/cache');
define('PATH_LOCAL_CACHE', '/cache');

$Root = dirname(__FILE__);
$Root = explode('/',$Root);
array_pop($Root);
$Root = '/'.trim(implode('/', $Root),'/');
define('PATH_RUNNER', $Root);

require_once('functions.php');

require_once('configuration.php');
require_once('args.php');
require_once('email.php');
require_once('proxyrequest.php');
require_once('client.php');
require_once('tasklist.php');
require_once('task.php');