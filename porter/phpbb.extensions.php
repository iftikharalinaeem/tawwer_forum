<?php
// Add file extension to hashed phpBB attachment filenames

$Database = 'ynab';
$Directory = '/www/ynab/files';

mysqli_connect('localhost', 'root', '');
mysqli_select_db($Database);
$r = mysqli_query("select physical_filename as name, extension as ext from phpbb_attachments");
$renamed = 0;
$failed = 0;
while ($a = mysqli_fetch_array($r)) {
   if (file_exists($Directory.$a['name'])) {
      $renamed++;
      //echo 'YS='.$Directory.$a['name'].' <br />';
      rename($Directory.$a['name'], $Directory.$a['physical_filename'].'.'.$a['ext']);     
   }
   else {
      $failed++;
      //echo 'NO='.$Directory.$a['name'].' <br />';
   }
}
echo 'RENAME='.$renamed.', FAILED='.$failed;