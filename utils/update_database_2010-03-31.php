<?php
/*
 Used for updating every database during the update of 2010-03-31
*/

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 if (substr($Row['Database'], 0, 3) == 'vf_') {
		  // if (in_array($Row['Database'], array('vf_mark_E3G83', 'vf_dev_C344M'))) {
				echo $Row['Database']."\n";
				mysql_select_db($Row['Database'], $Cnn);
		  
				// Add Update LastCommentUserID value
				mysql_query('update GDN_Discussion d join GDN_Comment c on d.LastCommentID = c.CommentID set d.LastCommentUserID = c.InsertUserID', $Cnn);
		  // }
	 }
}
mysql_close($Cnn);