<?php
define('APPLICATION', '1');

// Loop through the sites in vanillaforumscom.GDN_Site
$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
mysql_select_db('vanillaforumscom', $Cnn);
$Data = mysql_query('select * from GDN_Site', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $SiteID = $Row['SiteID'];
	 $AccountID = $Row['AccountID'];
	 $Path = $Row['Path'];
	 
	 if (!file_exists($Path.'/conf/config.php')) {
		  echo 'FAIL: File Does Not Exist - '.$Path."\n";
	 } else {
		  // Grab the root user's id from the configuration file
		  $Configuration = array();
		  include($Path.'/conf/config.php');
		  $UserID = $Configuration['VanillaForums']['UserID'];
		  $ConfSiteID = $Configuration['VanillaForums']['SiteID'];
		  $ConfAccountID = $Configuration['VanillaForums']['AccountID'];
		  
		  if ($SiteID != $ConfSiteID) {
				echo "FAIL: SiteID ($SiteID) MisMatch ConfSiteID ($ConfSiteID) - $Path \n";
		  } else if (!is_numeric($AccountID) || $AccountID <= 0) {
				echo "FAIL: AccountID MisMatch - $Path \n";
		  }
	 }
}

mysql_close($Cnn);