<?php
/*
 Used for updating every database during the update of 2010-02-23
*/

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 if (substr($Row['Database'], 0, 3) == 'vf_') {
		  mysql_select_db($Row['Database'], $Cnn);
		  
		  // Get a UserCount in this forum.
		  $UserData = mysql_fetch_assoc(mysql_query('select count(UserID) as CountUsers from GDN_User', $Cnn));
		  $CountUsers = $UserData['CountUsers'];
	 
		  // Get Category, Discussion, & Comment counts
		  $CategoryData = mysql_fetch_assoc(mysql_query('select count(CategoryID) as CountCategories from GDN_Category', $Cnn));
		  $CountCategories = $CategoryData['CountCategories'];
		  $DiscussionData = mysql_fetch_assoc(mysql_query('select count(DiscussionID) as CountDiscussions from GDN_Discussion', $Cnn));
		  $CountDiscussions = $DiscussionData['CountDiscussions'];
		  $CommentData = mysql_fetch_assoc(mysql_query('select count(CommentID) as CountComments from GDN_Comment', $Cnn));
		  $CountComments = $CommentData['CountComments'];
		  
		  // Save them to the Site table
		  $Query = 'update GDN_Site set CountUsers = '.$CountUsers
				.', CountCategories = '.$CountCategories
				.', CountDiscussions = '.$CountDiscussions
				.', CountComments = '.$CountComments
				." where DatabaseName = '".$Row['Database']."'";
		  
		  mysql_select_db('vanillaforumscom', $Cnn);
		  mysql_query($Query, $Cnn);
		  echo $Query."\n";
	 }
}
mysql_close($Cnn);
