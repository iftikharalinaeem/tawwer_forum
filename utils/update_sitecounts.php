<?php
/*
 Used for updating every database during the update of 2010-02-23
*/

$VFCnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
mysql_select_db('vanillaforumscom', $VFCnn);

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 if (substr($Row['Database'], 0, 3) == 'vf_') {
		  mysql_select_db($Row['Database'], $Cnn);
		  
		  // Get a UserCount in this forum.
		  $UserData = @mysql_fetch_assoc(@mysql_query('select count(UserID) as CountUsers from GDN_User', $Cnn));
		  $CountUsers = @$UserData['CountUsers'] or 0;
	 
		  // Get Category, Discussion, & Comment counts
		  $CategoryData = @mysql_fetch_assoc(@mysql_query('select count(CategoryID) as CountCategories from GDN_Category', $Cnn));
		  $CountCategories = @$CategoryData['CountCategories'] or 0;
		  $DiscussionData = @mysql_fetch_assoc(@mysql_query('select count(DiscussionID) as CountDiscussions from GDN_Discussion', $Cnn));
		  $CountDiscussions = @$CategoryData['CountDiscussions'] or 0;
		  $CommentData = @mysql_fetch_assoc(@mysql_query('select count(CommentID) as CountComments from GDN_Comment', $Cnn));
		  $CountComments = @$CategoryData['CountComments'] or 0;
		  
		  // Save them to the Site table
		  @mysql_query('update GDN_Site set CountUsers = '.$CountUsers
				.', CountCategories = '.$CountCategories
				.', CountDiscussions = '.$CountDiscussions
				.', CountComments = '.$CountComments
				.' where DatabaseName = `'.$Row['Database'].'`'
				, $VFCnn
		  );

		  echo '['.$Row['Database'].'] Users: '.$UserCount.'; Categories: '.$CategoryCount.'; Discussions: '.$DiscussionCount.'; Comments: '.$CommentCount.";\n";
	 }
}
mysql_close($Cnn);
mysql_close($VFCnn);