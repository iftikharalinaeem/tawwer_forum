<?php if (!defined('APPLICATION')) exit;

// include_once dirname(__FILE__).'/class.reactionmodel.php';
      
$St = Gdn::Structure();
$Sql = Gdn::SQL();
$Database = Gdn::Database();

$St->Table('Poll')
   ->PrimaryKey('PollID')
   ->Column('Name', 'varchar(255)', FALSE)
   ->Column('DiscussionID', 'int', TRUE)
   ->Column('CountOptions', 'int', '0')
   ->Column('CountVotes', 'int', '0')
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Set();

$St->Table('PollOption')
   ->PrimaryKey('PollOptionID')
   ->Column('PollID', 'int', FALSE, 'key')
   ->Column('Body', 'varchar(500)', TRUE)
   ->Column('Format', 'varchar(20)', TRUE)
   ->Column('Sort', 'smallint', FALSE, '0')
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Set();

$St->Table('PollVote')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('PollOptionID', 'int', FALSE, 'primary')
   ->Set();

// Define permissions
//$PermissionModel = Gdn::PermissionModel();
//$PermissionModel->Database = $Database;
//$PermissionModel->SQL = $SQL;

// Define some permissions for the Polls categories.
//$PermissionModel->Define(array(
//	'Vanilla.Polls.Add' => ''
//));
   
