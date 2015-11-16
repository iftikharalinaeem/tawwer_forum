<?php
/*
 Used for updating every database during the update of 2010-02-23
*/

$Cnn = mysql_connect('vfdb1', 'root', 'Va2aWu5A');
$Data = mysql_query('show databases', $Cnn);
while ($Row = mysql_fetch_assoc($Data)) {
	 if (substr($Row['Database'], 0, 3) == 'vf_') {
		  echo $Row['Database']."\n";
		  mysql_select_db($Row['Database'], $Cnn);
	 
		  // Drop obselete tables
		  @mysql_query('drop table GDN_TableType', $Cnn);
		  @mysql_query('drop table GDN_SearchDocument', $Cnn);
		  @mysql_query('drop table GDN_SearchKeyword', $Cnn);
		  @mysql_query('drop table GDN_SearchKeywordDocument', $Cnn);
	 
		  // remove obselete indexes
		  @mysql_query('alter table `GDN_Discussion` drop key FK_Discussion_FirstCommentID', $Cnn);
		  @mysql_query('alter table `GDN_Discussion` drop key FK_Discussion_LastCommentID', $Cnn);
		  
		  // Add new indexes & columns
		  @mysql_query('alter table `GDN_Conversation` add key FK_Conversation_DateInserted (`DateInserted`)', $Cnn);
		  @mysql_query('alter table `GDN_ConversationMessage` add key FK_ConversationMessage_DateInserted (`DateInserted`)', $Cnn);
		  @mysql_query('alter table `GDN_Discussion` add key FK_Discussion_DateInserted (`DateInserted`)', $Cnn);
		  @mysql_query('alter table `GDN_Discussion` add index IX_Discussion_DateLastComment (`DateLastComment`)', $Cnn);
		  @mysql_query('alter table `GDN_Discussion` add fulltext index TX_Discussion (`Name`)', $Cnn);
		  @mysql_query('alter table `GDN_Comment` add key FK_Comment_DateInserted (`DateInserted`)', $Cnn);
		  @mysql_query('alter table `GDN_Comment` add fulltext index TX_Comment (`Body`)', $Cnn);
		  @mysql_query('alter table `GDN_Comment` add `Attributes` text', $Cnn);
	 }
}
mysql_close($Cnn);