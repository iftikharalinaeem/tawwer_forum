#!/usr/bin/php
<?php

/*
Copyright 2011, Tim Gunter
This file is part of Push.
Push is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Push is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Push.  If not, see <http://www.gnu.org/licenses/>.
Contact Tim Gunter at tim [at] vanillaforums [dot] com
*/

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