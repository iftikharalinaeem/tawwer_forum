#!/usr/bin/php
<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

// Set current working dir to the real location of the script
chdir(dirname(__FILE__));

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