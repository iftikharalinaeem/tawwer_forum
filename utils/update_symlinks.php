<?php
/*
 Used for updating symlinks in all forums (removing obselete ones and adding new ones)
 during the update of 2009-11-27
*/

function RemoveSymLink($Link) {
	 if (file_exists($Link)) {
		  // echo '/bin/rm "'.$Link.'"'."\n";
 		  // exec('/bin/rm "'.$Link.'"');
		  unlink($Link);
	 }
}
function CreateSymLink($Folder, $LinkSuffix, $AltSuffix = '') {
	 if ($AltSuffix == '')
		  $AltSuffix = $LinkSuffix;
		  
	 // echo('/bin/ln -s "/srv/www/misc'.$LinkSuffix.'" "'.$Folder.$AltSuffix.'"'."\n");
	 // exec('/bin/ln -s "/srv/www/misc'.$LinkSuffix.'" "'.$Folder.$AltSuffix.'"');
}

if ($DirectoryHandle = opendir('/srv/www/subdomains')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
		  if (in_array($Item, array(
				//	 'marktest',
					 'carsonified'
				))) {
				$Folder = '/srv/www/subdomains/' . $Item;
				echo "Working: $Folder \n";
				RemoveSymLink($Folder.'/themes/vfcom');
				// Delete existing symlinks first
				// WARNING: Do not use a trailing slash on symlinked folders when rm'ing, or it will remove the source!
				/*
				RemoveSymLink($Folder.'/plugins/GettingStarted');
				
				RemoveSymLink($Folder.'/plugins/googleadsense');
				RemoveSymLink($Folder.'/plugins/vfoptions');
				RemoveSymLink($Folder.'/plugins/downtime');
				RemoveSymLink($Folder.'/themes/vanillaforumscom');
				RemoveSymLink($Folder.'/themes/vfcom');
				RemoveSymLink($Folder.'/themes/default');
				*/
				// Add correct symlinks
				/*
				CreateSymLink($Folder, '/plugins/GettingStarted');
				CreateSymLink($Folder, '/plugins/googleadsense');
				CreateSymLink($Folder, '/plugins/vfoptions');
				CreateSymLink($Folder, '/themes/vfcom');
				CreateSymLink($Folder, '/themes/vfcom', '/themes/default');
				*/
		  }
    }
    closedir($DirectoryHandle);
}
