<?php
/*
 Used for updating every database during the update of 2010-03-30
*/

function CleanForUrl($Mixed) {
	 $Mixed = utf8_decode($Mixed);
	 $Mixed = preg_replace('/-+/', '-', str_replace(' ', '-', trim(preg_replace('/([^\w\d_:.])/', ' ', $Mixed))));
	 $Mixed = utf8_encode($Mixed);
	 return strtolower($Mixed);	 
}

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 if (substr($Row['Database'], 0, 3) == 'vf_') {
		  // if (in_array($Row['Database'], array('vf_mark_E3G83', 'vf_dev_C344M'))) {
				echo $Row['Database']."\n";
				mysql_select_db($Row['Database'], $Cnn);
		  
				// Add UrlCode
				mysql_query('alter table `GDN_Category` add `UrlCode` varchar(30)', $Cnn);
				
				// Populate UrlCode with properly formatted strings
				$CatData = mysql_query('select CategoryID, Name from GDN_Category', $Cnn);
				while ($Category = mysql_fetch_assoc($CatData)) {
					 mysql_query("update GDN_Category set UrlCode = '".CleanForUrl($Category['Name'])."' where CategoryID = ".$Category['CategoryID'], $Cnn);
				}
				
				// Add BookmarkComment activity type
				$BData = mysql_query("select ActivityTypeID from GDN_ActivityType where Name = 'BookmarkComment'", $Cnn);
				if (mysql_num_rows($BData) == 0) {
					 mysql_query("insert into GDN_ActivityType (AllowComments, Name, FullHeadline, ProfileHeadline, RouteCode, Notify, Public) values ('0', 'BookmarkComment', '%1\$s commented on your %8\$s.', '%1\$s commented on your %8\$s.', 'bookmarked discussion', '1', '0')", $Cnn);
				}
		  // }
	 }
}
mysql_close($Cnn);