<?php if (!defined('APPLICATION')) exit;
      
Gdn::structure()->table('Poll')
   ->primaryKey('PollID')
   ->column('Name', 'varchar(255)', false)
   ->column('DiscussionID', 'int', true)
   ->column('CountOptions', 'int', '0')
   ->column('CountVotes', 'int', '0')
   ->column('Anonymous', 'int', '0')
   ->column('DateInserted', 'datetime')
   ->column('InsertUserID', 'int', false, 'key')
   ->column('DateUpdated', 'datetime', true)
   ->column('UpdateUserID', 'int', true)
   ->set();

Gdn::structure()->table('PollOption')
   ->primaryKey('PollOptionID')
   ->column('PollID', 'int', false, 'key')
   ->column('Body', 'varchar(500)', true)
   ->column('Format', 'varchar(20)', true)
   ->column('Sort', 'smallint', false, '0')
   ->column('CountVotes', 'int', '0')
   ->column('DateInserted', 'datetime')
   ->column('InsertUserID', 'int', false, 'key')
   ->column('DateUpdated', 'datetime', true)
   ->column('UpdateUserID', 'int', true)
   ->set();

Gdn::structure()->table('PollVote')
   ->column('UserID', 'int', false, 'primary')
   ->column('PollOptionID', 'int', false, ['primary', 'key'])
   ->column('DateInserted', 'datetime', true)
   ->set();

// Define permissions
$PermissionModel = Gdn::permissionModel();
$PermissionModel->Database = Gdn::database();
$PermissionModel->SQL = Gdn::sql();

// Define some permissions for the Polls categories.
$PermissionModel->define([
	'Plugins.Polls.Add' => 'Garden.Profiles.Edit'
]);
   
