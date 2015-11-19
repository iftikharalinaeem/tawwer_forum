<?php if (!defined('APPLICATION')) exit();

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = false;
}

$Database = Gdn::Database();
$SQL = $Database->SQL();
$Construct = $Database->Structure();
$Validation = new Gdn_Validation();

// Badges
$Construct->Table('Badge')
    ->PrimaryKey('BadgeID')
    ->Column('Name', 'varchar(64)')
    ->Column('Slug', 'varchar(32)', true, 'unique')
    ->Column('Type', 'varchar(20)', true)
    ->Column('Body', 'text', true)
    ->Column('Photo', 'varchar(255)', true)
    ->Column('Points', 'int', true)
    ->Column('Active', 'tinyint', 1)
    ->Column('Visible', 'tinyint', 1)
    ->Column('Secret', 'tinyint', 0)
    ->Column('CanDelete', 'tinyint', 1)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('InsertUserID', 'int')
    ->Column('UpdateUser', 'int', true)
    ->Column('CountRecipients', 'int', 0)
    ->Column('Threshold', 'int', 0)
    ->Column('Class', 'varchar(20)', true)
    ->Column('Level', 'smallint', true)
    ->Column('Attributes', 'text', true)
    ->Set($Explicit, $Drop);

// Badge Types
$Construct->Table('BadgeType')
    ->Column('BadgeType', 'varchar(20)', false, 'primary')
    ->Column('EditUrl', 'varchar(255)', true)
    ->Column('Attributes', 'text', true)
    ->Set($Explicit, $Drop);

// User Badges
$Construct->Table('UserBadge')
    ->Column('UserID', 'int', false, 'primary')
    ->Column('BadgeID', 'int', false, 'primary')
    ->Column('Attributes', 'text', true)
    ->Column('Reason', 'varchar(255)', true)
    ->Column('ShowReason', 'tinyint', 1)
    ->Column('DateRequested', 'datetime', true)
    ->Column('RequestReason', 'varchar(255)', true)
    ->Column('Declined', 'tinyint', 0)
    ->Column('Count', 'int', 0)
    ->Column('DateCompleted', 'datetime', true)
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int')
    ->Set($Explicit, $Drop);

$Construct
    ->Table('UserBadge')
    ->Column('BadgeID', 'int', false, 'index.earned')
    ->Column('DateCompleted', 'datetime', true, 'index.earned')
    ->Set();

// Add badge count to Users
$Construct->Table('User')
    ->Column('CountBadges', 'int', 0)
    ->Set();

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Badge');

require_once(dirname(__FILE__).'/defaultbadges.php');
