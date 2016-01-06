<?php

// 1. Update the configuration files in each subdomain:
/*
if ($DirectoryHandle = opendir('/srv/www/subdomains')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
        $File = '/srv/www/subdomains/' . $Item . '/conf/config.php';
		  if ($Item != 'carsonified' && file_exists($File)) {
				$Contents = file_get_contents($File);
				$Contents = str_replace("['Theme'] = 'default';", "['Theme'] = 'vanillaforumscom';", $Contents);
				file_put_contents($File, $Contents);
				echo 'Updating: '.$File."\n";
		  }
    }
    closedir($DirectoryHandle);
}
*/

// 2. Update each database
$Cnn = mysql_connect('localhost', 'root', 'Va2aWu5A'); // Open the db connection
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 // if ($Row['Database'] == 'vforg') {
		  mysql_select_db($Row['Database'], $Cnn);
		  $TableData = mysql_query('show tables from `'.$Row['Database'].'`');
		  while ($TableRow = mysql_fetch_array($TableData)) {
				if ($TableRow[0] == 'GDN_ActivityType') {
					 echo 'Updating '.$Row['Database'].'.'.$TableRow[0]."\n";
					 mysql_query("update GDN_ActivityType set ProfileHeadline = '".'%1$s mentioned %3$s in a %8$s.'."', FullHeadline = '".'%1$s mentioned %3$s in a %8$s.'."' where Name in ('DiscussionMention', 'CommentMention', 'AddonCommentMention')", $Cnn);
					 mysql_query("update GDN_ActivityType set AllowComments = '1' where Name = 'AboutUpdate'", $Cnn);
				}
		  }
	 // }
}
mysql_close($Cnn);