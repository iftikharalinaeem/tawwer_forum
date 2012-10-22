<?php

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A'); // Open the db connection
mysql_select_db('vanillaforumscom', $Cnn);
$Data = mysql_query('select * from GDN_Site', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $DBName = $Row['DatabaseName'];
	 $SiteID = $Row['SiteID'];
	 mysql_select_db($DBName, $Cnn);
	 $TableData = mysql_query("show tables like 'GDN_Message'", $Cnn);
	 if (mysql_num_rows($TableData) == 0) {
		  echo 'DB: '.$DBName."\n";
	 }
}
mysql_free_result($TableData);
mysql_close($Cnn);