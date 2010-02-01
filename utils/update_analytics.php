<?php
/*
 Used for updating conf files in all forums during the update of 2010-01-20

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-12713112-1");
pageTracker._setDomainName(".vanillaforums.com");
pageTracker._trackPageview();
} catch(err) {}</script>
*/

if ($DirectoryHandle = opendir('/srv/www/vhosts')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  $File = '/srv/www/vhosts/' . $Item . '/conf/config.php';
		  if (file_exists($File)) {
				$Contents = file_get_contents($File);
				if (strpos($Contents, "\$Configuration['Plugins']['GoogleAnalytics']['TrackerCode'])") === false) {
					 echo 'Updating: '.$File."\n";
					 
					 $Find = '// Plugins';
					 if (strpos($Contents, $Find === FALSE))
						  $Find = '// Adsense.';
						  
					 if (strpos($Contents, $Find === FALSE)) {
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
					 break;
				}
				
		  }
    }
    closedir($DirectoryHandle);
}
