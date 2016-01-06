<?php
define('APPLICATION', '1');

// Loop through the sites in vanillaforumscom.GDN_Site
$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
mysql_select_db('vanillaforumscom', $Cnn);
/*
// Update all of the account ids on the user table
mysql_query('update GDN_User set AccountID = UserID + 5000000 where AccountID is null or AccountID = 0', $Cnn);

// Make sure all of the AccountIDs exist
mysql_query('insert into GDN_Account (AccountID, DateInserted, InsertUserID, DateUpdated, UpdateUserID)
select u.AccountID, now(), 1, now(), 1 from GDN_User u
left join GDN_Account a on u.AccountID = a.AccountID
where a.AccountID is null', $Cnn);
*/
$Data = mysql_query('select * from GDN_Site where AccountID <= 0', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $SiteID = $Row['SiteID'];
	 $Path = $Row['Path'];
	 $DatabaseName = $Row['DatabaseName'];
	 $AccountID = 0;
	 
	 // Grab the root user's id from the configuration file
	 $Configuration = array();
	 include($Path.'/conf/config.php');
	 $UserID = $Configuration['VanillaForums']['UserID'];

	 $VFData = mysql_query("select AccountID from GDN_User where UserID = ".$UserID, $Cnn);
	 while ($VFUser = mysql_fetch_array($VFData)) {
		  $AccountID = $VFUser['AccountID'];
	 }
	 
	 if (!is_numeric($UserID) || $UserID <= 0) {
		  echo "FAIL: Could not find related userid in conf file\n";
	 } else if (!is_numeric($AccountID) || $AccountID <= 0) {
		  echo 'FAIL: No AccountID for UserID: '.$UserID."\n";
	 } else {
		  echo 'Saving: AccountID ('.$AccountID.') to '.$Path." ... ";
		  $File = $Path . '/conf/config.php';
		  if (file_exists($File)) {
				$Contents = file_get_contents($File);
				$Contents = str_replace("['AccountID'] = '';", "['AccountID'] = '".$AccountID."';", $Contents);
				file_put_contents($File, $Contents);
				mysql_query('update GDN_Site set AccountID = '.$AccountID.' where SiteID = '.$SiteID.' and AccountID = 0', $Cnn);
				
				echo "Saved\n";
		  } else {
				echo "FAIL\n";
		  }
	 }
}

mysql_close($Cnn);