<?php
/*
 Used for updating all tables in all databases to utf-8 encoding on durig the 
 update of 2009-12-17
*/
$Database = 'vf_chemicalwatch_0PWGC';

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A'); // Open the db connection
mysql_select_db($Database, $Cnn);
$TableData = mysql_query('show tables from `'.$Database.'`');
while ($TableRow = mysql_fetch_array($TableData)) {
	 if (substr($TableRow[0], 0, 4) == 'GDN_') {
		  mysql_query("alter table ".$TableRow[0]." default character set utf8 collate utf8_unicode_ci", $Cnn);
		  mysql_query("alter table ".$TableRow[0]." convert to character set utf8 collate utf8_unicode_ci", $Cnn);
	 }
}
mysql_close($Cnn);