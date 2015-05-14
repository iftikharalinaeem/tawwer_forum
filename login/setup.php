<?php
include_once('config.php');
include_once('functions.php');

// Build tables.
WriteTables();

// Create default users.
$UserPass = array(
   'venkman' => 'cats&dogs',
   'ray' => 'staypuft',
   'egon' => 'crossthestreams',
   'winston' => 'yousayYES!',
   'janine' => 'wegotalive1'
);

foreach ($UserPass as $User => $Pass) {
   $TerribleSalt = mt_rand(1000,9999);
   WriteUser(array(
      'Name' => $User,
      'Password' => $Pass,
      'Hash' => TerribleHash($Pass, $TerribleSalt),
      'Salt' => $TerribleSalt,
      'Role' => 'Member',
      'Email' => $User.'@ghostbusters.com'
   ));
}

echo "<p>Done</p>";