<?php
/*
 Used for updating conf files in all forums so they all log garden errors. Run 2009-12-30.
*/

if ($DirectoryHandle = opendir('/srv/www/vhosts')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  // if ($Item == 'mark.vanillaforums.com') {
				$File = '/srv/www/vhosts/' . $Item . '/conf/config.php';
				if (file_exists($File)) {
					 $Contents = file_get_contents($File);
					 // Remove existing log entries
					 $Contents = str_replace(
						  "\$Configuration['Garden']['Errors']['LogEnabled'] = TRUE;
\$Configuration['Garden']['Errors']['LogFile'] = '/srv/log/vhosts/garden.log';
",
						  "",
						  $Contents
					 );
					 
					 // Implement new log entries
					 $Contents = str_replace(
					 "// Garden",
					 "// Garden
\$Configuration['Garden']['Errors']['LogEnabled'] = TRUE;", $Contents);
				
					 file_put_contents($File, $Contents);
					 echo 'Updating: '.$File."\n";
				}
		  // }
	 }
    closedir($DirectoryHandle);
}
