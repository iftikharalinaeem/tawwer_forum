<?php
define('APPLICATION', 'Garden');
define('APPLICATION_VERSION', '1.0');

define('DS', DIRECTORY_SEPARATOR);

if($argc > 1)
	$App = $argv[1];
else
	$App = 'garden';
if($argc > 2)
	$Count = $argv[2];
else
	$Count = 25;

define('PATH_ROOT', dirname(__FILE__).DS.'..'.DS.$App);

require_once(PATH_ROOT.DS.'bootstrap.php');


$Model = new CommentModel();
$Model->Reindex(NULL, $Count, TRUE);