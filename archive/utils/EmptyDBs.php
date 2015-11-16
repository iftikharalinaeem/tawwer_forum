<?php

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A'); // Open the db connection
mysql_select_db('vanillaforumscom', $Cnn);
$Data = mysql_query('select * from GDN_Site', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 $DBName = $Row['DatabaseName'];
	 $SiteID = $Row['SiteID'];
	 mysql_select_db($DBName, $Cnn);
	 $TableData = mysql_query('show tables', $Cnn);
	 if (mysql_num_rows($TableData) == 0) {
		  echo 'Delete: '.$DBName."\n";
		  mysql_select_db('vanillaforumscom', $Cnn);
		  mysql_query('delete from GDN_Site where SiteID = '.$SiteID, $Cnn);
		  mysql_query('drop database `'.$DBName.'`', $Cnn);
	 }
}
mysql_close($Cnn);