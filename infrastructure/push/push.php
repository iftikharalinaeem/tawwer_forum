#!/usr/bin/php
<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

require_once('classes/class.push.php');

try {
   $Push = new Push();
   $Push->Execute();
} catch (Exception $e) {
   echo $e->getMessage()."\n";
   echo basename($e->getFile()).":".$e->getLine()."\n";
   exit(1);
}