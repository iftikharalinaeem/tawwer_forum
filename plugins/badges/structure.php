<?php if (!defined('APPLICATION')) exit();

if (!isset($Drop))
    $Drop = FALSE;

if (!isset($Explicit))
    $Explicit = FALSE;

$Database = Gdn::Database();
$SQL = $Database->SQL();
$Construct = $Database->Structure();
$Validation = new Gdn_Validation();

// Badges
$Construct->Table('Badge')
    ->PrimaryKey('BadgeID')
    ->Column('Name', 'varchar(64)')
    ->Column('Slug', 'varchar(32)', TRUE, 'unique')
    ->Column('Type', 'varchar(20)', TRUE)
    ->Column('Body', 'text', TRUE)
    ->Column('Photo', 'varchar(255)', TRUE)
    ->Column('Points', 'int', TRUE)
    ->Column('Active', 'tinyint', 1)
    ->Column('Visible', 'tinyint', 1)
    ->Column('Secret', 'tinyint', 0)
    ->Column('CanDelete', 'tinyint', 1)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime', TRUE)
    ->Column('InsertUserID', 'int')
    ->Column('UpdateUser', 'int', TRUE)
    ->Column('CountRecipients', 'int', 0)
    ->Column('Threshold', 'int', 0)
    ->Column('Class', 'varchar(20)', TRUE)
    ->Column('Level', 'smallint', TRUE)
    ->Column('Attributes', 'text', TRUE)
    ->Set($Explicit, $Drop);

// Badge Types
$Construct->Table('BadgeType')
    ->Column('BadgeType', 'varchar(20)', FALSE, 'primary')
    ->Column('EditUrl', 'varchar(255)', TRUE)
    ->Column('Attributes', 'text', TRUE)
    ->Set($Explicit, $Drop);

// User Badges
$Construct->Table('UserBadge')
    ->Column('UserID', 'int', FALSE, 'primary')
    ->Column('BadgeID', 'int', FALSE, 'primary')
    ->Column('Attributes', 'text', TRUE)
    ->Column('Reason', 'varchar(255)', TRUE)
    ->Column('ShowReason', 'tinyint', 1)
    ->Column('DateRequested', 'datetime', TRUE)
    ->Column('RequestReason', 'varchar(255)', TRUE)
    ->Column('Declined', 'tinyint', 0)
    ->Column('Count', 'int', 0)
    ->Column('DateCompleted', 'datetime', TRUE)
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

$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Define(array(
    'Reputation.Badges.View' => 1,
    'Reputation.Badges.Request',
    'Reputation.Badges.Give' => 'Garden.Settings.Manage',
    'Reputation.Badges.Manage' => 'Garden.Settings.Manage'
));

require_once(dirname(__FILE__).'/defaultbadges.php');
