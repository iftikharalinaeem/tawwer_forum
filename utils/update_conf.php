<?php
/*
 Used for updating conf files in all forums during the update of 2009-11-27
*/

if ($DirectoryHandle = opendir('/srv/www/subdomains')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  if (in_array($Item, array(
					 'dodgeball',
//					 'carsonified'
				))) {
				$File = '/srv/www/subdomains/' . $Item . '/conf/config.php';
				if (file_exists($File)) {
					 $Contents = file_get_contents($File);
					 $Contents = str_replace(
						  array(
								"// EnabledPlugins",
								"\$Configuration['EnabledPlugins']['downtime'] = 'downtime';\n",
								"\$Configuration['EnabledPlugins']['CssThemes'] = 'cssthemes';\n",
								"\$Configuration['Garden']['Theme'] = 'vanillaforumscom';"
						  ),
						  array(
								"// EnabledPlugins
\$Configuration['EnabledPlugins']['GettingStarted'] = 'GettingStarted';",
								"",
								"",
								"\$Configuration['Garden']['Theme'] = 'vfcom';"
								
						  ),
						  $Contents
					 );
					 file_put_contents($File, $Contents);
					 echo 'Updating: '.$File."\n";
				}
		  }
    }
    closedir($DirectoryHandle);
}
