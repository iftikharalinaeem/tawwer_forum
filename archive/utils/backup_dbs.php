<?php
// Back up each database
$Cnn = mysql_connect('localhost', 'root', 'Va2aWu5A'); // Open the db connection
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
    if (substr($Row['Database'], 0, 3)  == 'vf_') {
	$Db = $Row['Database'].'.sql'; // .'-'.date('Y-m-d-His').'.sql';
	exec('mysqldump -uroot -pVa2aWu5A '.$Row['Database'].' > '.$Db);
	exec('gzip '.$Db);
    }
}
mysql_close($Cnn);