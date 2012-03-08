<?php
// 2. Update each database
$Cnn = mysql_connect('localhost', 'root', 'Va2aWu5A'); // Open the db connection
mysql_select_db('vfcom', $Cnn);
if ($DirectoryHandle = opendir('/srv/www/vhosts')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  $Data = mysql_query("select SiteID from GDN_Site where Name = '$Item'", $Cnn);
		  if (mysql_num_rows($Data) == 0) {
				echo $Item."\n";
		  }
    }
    closedir($DirectoryHandle);
}
mysql_close($Cnn);
