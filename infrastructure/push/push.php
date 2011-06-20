#!/usr/bin/php
<?php

/**
 * This file is part of Push.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

// Set current working dir to the real location of the script
chdir(dirname(realpath(__FILE__)));

// Include the main application class
require_once('classes/class.push.php');

try {
   $Push = new Push();
   $Push->Execute();
} catch (Exception $e) {
   echo $e->getMessage()."\n";
   echo basename($e->getFile()).":".$e->getLine()."\n";
   exit(1);
}