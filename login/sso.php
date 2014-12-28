<?php
require_once dirname(__FILE__).'/functions.jsconnect.php';

// 1. Get your client ID and secret here. These must match those in your jsConnect settings.
$clientID = "terrible";
$secret = "sekrits";

// 2. Grab the current user from your session management system or database here.
include_once('config.php');
include_once('functions.php');
$signedIn = GetLogin();

// 3. Fill in the user information in a way that Vanilla can understand.
$user = array();

if ($signedIn) {
   $getuser = GetUser($signedIn);
   $user['uniqueid'] = $getuser['UserID'];
   $user['name'] = $getuser['Name'];
   $user['email'] = $getuser['Email'];
   $user['photourl'] = '';
}

// 4. Generate the jsConnect string.

// This should be true unless you are testing. 
// You can also use a hash name like md5, sha1 etc which must be the name as the connection settings in Vanilla.
$secure = true; 
WriteJsConnect($user, $_GET, $clientID, $secret, $secure);
