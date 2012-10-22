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

// Identify folders without a siteid
if ($DirectoryHandle = opendir('/srv/www/subdomains')) {
    while (($Item = readdir($DirectoryHandle)) !== FALSE) {
        $File = '/srv/www/subdomains/' . $Item . '/conf/config.php';
		  if (file_exists($File)) {
				$Contents = file_get_contents($File);
				$SiteID = substr($Contents, strpos($Contents, "['SiteID']") + 14, 7);
				if (!is_numeric($SiteID))
					 echo $Item.': '.$SiteID."\n";
		  }
    }
    closedir($DirectoryHandle);
}

/*

// Loop through the sites in vanillaforumscom.GDN_Site
$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
mysql_select_db('vanillaforumscom', $Cnn);
$Data = mysql_query('select * from GDN_Site where AccountID is null or AccountID <= 0', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $SiteID = $Row['SiteID'];
	 $Path = $Row['Path'];
	 $DatabaseName = $Row['DatabaseName'];
	 $Email = '';
	 
	 // Grab the root user's email address from the database
	 mysql_select_db($DatabaseName, $Cnn);
	 $UserData = mysql_query('select Email from GDN_User where UserID = 1', $Cnn);
	 while ($UserRow = mysql_fetch_array($UserData)) {
		  $Email = $UserRow['Email'];
	 }
	 
	 $UserID = 0;
	 $AccountID = 0;
	 mysql_select_db('vanillaforumscom', $Cnn);
	 $VFData = mysql_query("select UserID, AccountID from GDN_User where Email = '".mysql_real_escape_string($Email, $Cnn)."'", $Cnn);
	 while ($VFUser = mysql_fetch_array($VFData)) {
		  $UserID = $VFUser['UserID'];
		  $AccountID = $VFUser['AccountID'];
	 }
	 
	 if ($UserID == 0) {
		  echo 'Failed to find related user for email address: '.$Email."\n";
	 } else if ($AccountID > 0) {
		  echo 'AccountID ('.$AccountID.') already assigned for email address: '.$Email."\n";
	 } else {
		  
	 }
}

mysql_close($Cnn);

 */