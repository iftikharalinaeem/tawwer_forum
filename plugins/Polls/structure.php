<?php if (!defined('APPLICATION')) exit;

// include_once dirname(__FILE__).'/class.reactionmodel.php';
      
$St = Gdn::Structure();
$Sql = Gdn::SQL();
$Database = Gdn::Database();

$St->Table('Poll')
   ->PrimaryKey('PollID')
   ->Column('DiscussionID', 'int', TRUE)
   ->Column('CountOptions', 'int', '0')
   ->Column('CountPollVotes', 'int', '0')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Set($Explicit, $Drop);

$St->Table('PollOption')
   ->PrimaryKey('PollOptionID')
   ->Column('Description', 'varchar(500)', TRUE)
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime')
   ->Set($Explicit, $Drop);

$St->Table('PollPollOption')
   ->Column('PollID', 'int', FALSE, 'primary')
   ->Column('PollOptionID', 'int', FALSE, 'primary')
   ->Column('Sort', 'int', TRUE)
   ->Column('CountVotes', 'int', '0')
   ->Set($Explicit, $Drop);

$St->Table('PollPollOptionUser')
   ->Column('PollID', 'int', FALSE, 'primary')
   ->Column('PollOptionID', 'int', FALSE, 'primary')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Set($Explicit, $Drop);

// Define permissions
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;

// Define some permissions for the Polls categories.
$PermissionModel->Define(array(
	'Vanilla.Polls.Add' => 0),
	'tinyint',
	'Category',
	'PermissionCategoryID'
	);

// Get the root category so we can assign permissions to it.
$GeneralCategoryID = $SQL->GetWhere('Category', array('Name' => 'General'))->Value('PermissionCategoryID', 0);
   
// Assign permissions to roles

   // Set the intial member permissions.
   $PermissionModel->Save(array(
      'Role' => 'Member',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Polls.Add' => 1
      ), TRUE);

   // Set the initial moderator permissions.
   $PermissionModel->Save(array(
      'Role' => 'Moderator',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Polls.Add' => 1
      ), TRUE);

   // Set the initial administrator permissions.
   $PermissionModel->Save(array(
      'Role' => 'Administrator',
      'JunctionTable' => 'Category',
      'JunctionColumn' => 'PermissionCategoryID',
      'JunctionID' => $GeneralCategoryID,
      'Vanilla.Polls.Add' => 1
      ), TRUE);
   
