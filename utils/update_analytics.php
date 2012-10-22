<?php
if ($DirectoryHandle = opendir('/srv/www/vhosts')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  $File = '/srv/www/vhosts/' . $Item . '/conf/config.php';
		  if (file_exists($File)) {
				$Contents = file_get_contents($File);
				if (strpos($Contents, "['GoogleAnalytics']['TrackerCode']") === false) {
					 echo 'Updating: '.$File."\n";

					 $Find = '// Plugins';
					 if (strpos($Contents, $Find) === FALSE)
						  $Find = '// Adsense.';

					 if (strpos($Contents, $Find) === FALSE) {
						  echo "No insertion point.\n";
						  break;
					 }

					 $Contents = str_replace(
						  $Find,
						  $Find . "
\$Configuration['Plugins']['GoogleAnalytics']['TrackerCode'] = 'UA-12713112-1';
\$Configuration['Plugins']['GoogleAnalytics']['TrackerDomain'] = '.vanillaforums.com';",
						  $Contents
					 );
					 file_put_contents($File, $Contents);
					 // break;
				}
		  }
	 }
	 closedir($DirectoryHandle);
}