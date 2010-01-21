<?php
/*
 Used for updating conf files in all forums during the update of 2010-01-20
*/

if ($DirectoryHandle = opendir('/srv/www/vhosts')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  $File = '/srv/www/vhosts/' . $Item . '/conf/config.php';
		  if (file_exists($File)) {
				$Contents = file_get_contents($File);
				$Contents = str_replace(
					 "// EnabledPlugins",
					 "// EnabledPlugins
\$Configuration['EnabledPlugins']['CustomCSS'] = 'CustomCSS';",
					 $Contents
				);
				file_put_contents($File, $Contents);
				echo 'Updating: '.$File."\n";
		  }
    }
    closedir($DirectoryHandle);
}
