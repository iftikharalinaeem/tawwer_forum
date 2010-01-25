<?php

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A'); // Open the db connection
mysql_select_db('vanillaforumscom', $Cnn);
$Data = mysql_query('select * from GDN_Site', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $DBName = $Row['DatabaseName'];
	 $SiteID = $Row['SiteID'];
	 mysql_select_db($DBName, $Cnn);
	 $MData = mysql_query('select * from GDN_Message', $Cnn);
	 if (mysql_num_rows($MData) > 0) {
		  while ($M = mysql_fetch_array($MData)) {
				if (substr($M['Content'], 0, 46) == "<p>Here's some stuff you might want to do:</p>") {
					 echo 'DB: '.$DBName.'; MessageID: '.$M['MessageID']."\n";
					 mysql_query('delete from GDN_Message where MessageID = '.$M['MessageID'], $Cnn);
				}
		  }
	 }
	 mysql_free_result($MData);
}
mysql_free_result($Data);
mysql_close($Cnn);