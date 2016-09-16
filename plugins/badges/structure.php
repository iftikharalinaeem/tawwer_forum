<?php if (!defined('APPLICATION')) exit();

$Drop = false;
$Explicit = false;
$Database = Gdn::database();
$SQL = $Database->sql();
$Construct = $Database->structure();
$Validation = new Gdn_Validation();

// Badges
$Construct->table('Badge')
    ->primaryKey('BadgeID')
    ->column('Name', 'varchar(64)')
    ->column('Slug', 'varchar(32)', true, 'unique')
    ->column('Type', 'varchar(20)', true)
    ->column('Body', 'text', true)
    ->column('Photo', 'varchar(255)', true)
    ->column('Points', 'int', true)
    ->column('Active', 'tinyint', 1)
    ->column('Visible', 'tinyint', 1)
    ->column('Secret', 'tinyint', 0)
    ->column('CanDelete', 'tinyint', 1)
    ->column('DateInserted', 'datetime')
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertUserID', 'int')
    ->column('UpdateUser', 'int', true)
    ->column('CountRecipients', 'int', 0)
    ->column('Threshold', 'int', 0)
    ->column('Class', 'varchar(20)', true)
    ->column('Level', 'smallint', true)
    ->column('Attributes', 'text', true)
    ->set($Explicit, $Drop);

// Badge Types
$Construct->table('BadgeType')
    ->column('BadgeType', 'varchar(20)', false, 'primary')
    ->column('EditUrl', 'varchar(255)', true)
    ->column('Attributes', 'text', true)
    ->set($Explicit, $Drop);

// User Badges
$Construct->table('UserBadge')
    ->column('UserID', 'int', false, 'primary')
    ->column('BadgeID', 'int', false, 'primary')
    ->column('Attributes', 'text', true)
    ->column('Reason', 'varchar(255)', true)
    ->column('ShowReason', 'tinyint', 1)
    ->column('DateRequested', 'datetime', true)
    ->column('RequestReason', 'varchar(255)', true)
    ->column('Declined', 'tinyint', 0)
    ->column('Count', 'int', 0)
    ->column('DateCompleted', 'datetime', true)
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int')
    ->set($Explicit, $Drop);

$Construct
    ->table('UserBadge')
    ->column('BadgeID', 'int', false, 'index.earned')
    ->column('DateCompleted', 'datetime', true, 'index.earned')
    ->set();

// Add badge count to Users
$Construct->table('User')
    ->column('CountBadges', 'int', 0)
    ->set();

$ActivityModel = new ActivityModel();
$ActivityModel->defineType('Badge');

require_once(dirname(__FILE__).'/defaultbadges.php');
