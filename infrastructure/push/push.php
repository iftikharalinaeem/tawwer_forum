#!/usr/local/bin/php
<?php
   
require_once('classes/class.push.php');

try {
   $Push = new Push();
} catch (Exception $e) {
   echo $e->getMessage()."\n";
   echo basename($e->getFile()).":".$e->getLine()."\n";
   exit(1);
}