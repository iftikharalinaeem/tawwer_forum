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
		  $UserData = @mysql_fetch_assoc(@mysql_query('select count(UserID) as UserCount from GDN_User', $Cnn));
		  $UserCount = @$UserData['UserCount'] or 0;
	 
		  // Get Category, Discussion, & Comment counts
		  $CategoryData = @mysql_fetch_assoc(@mysql_query('select count(CategoryID) as CategoryCount from GDN_Category', $Cnn));
		  $CategoryCount = @$CategoryData['CategoryCount'] or 0;
		  $DiscussionData = @mysql_fetch_assoc(@mysql_query('select sum(CountDiscussions) as DiscussionCount from GDN_Category', $Cnn));
		  $DiscussionCount = @$CategoryData['DiscussionCount'] or 0;
		  $CommentData = @mysql_fetch_assoc(@mysql_query('select sum(CountComments) as CommentCount from GDN_Discussion', $Cnn));
		  $CommentCount = @$CategoryData['CommentCount'] or 0;
		  
		  // Save them to the Site table
		  @mysql_query('update GDN_Site set CountUsers = '.$UserCount
				.', CountCategories = '.$CategoryCount
				.', CountDiscussions = '.$DiscussionCount
				.', CountComments = '.$CommentCount
				.' where DatabaseName = `'.$Row['Database'].'`'
				, $VFCnn
		  );

		  echo '['.$Row['Database'].'] Users: '.$UserCount.'; Categories: '.$CategoryCount.'; Discussions: '.$DiscussionCount.'; Comments: '.$CommentCount.";\n";
	 }
}
mysql_close($Cnn);
mysql_close($VFCnn);