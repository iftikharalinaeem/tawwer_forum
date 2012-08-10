<?php if (!defined('APPLICATION')) exit(); // Make sure this file can't get accessed directly
// Use this file to do any database changes for your application.

if (!isset($Drop))
   $Drop = FALSE; // Safe default - Set to TRUE to drop the table if it already exists.
   
if (!isset($Explicit))
   $Explicit = FALSE; // Safe default - Set to TRUE to remove all other columns from table.

$Database = Gdn::Database();
$SQL = $Database->SQL(); // To run queries.
$St = $Database->Structure(); // To modify and add database tables.

// Add your tables or new columns under here (see example below).
$St->Table('Locale')
   ->PrimaryKey('LocaleID')
   ->Column('Locale', 'varchar(5)', FALSE, 'unique')
   ->Column('Name', 'varchar(50)', TRUE)
   ->Column('CountCore', 'uint', 0)
   ->Column('CountAdmin', 'uint', 0)
   ->Column('CountAddon', 'uint', 0)
   ->Column('CountOther', 'uint', 0)
   ->Column('CountApprovedCore', 'uint', 0)
   ->Column('CountApprovedAdmin', 'uint', 0)
   ->Column('CountApprovedAddon', 'uint', 0)
   ->Column('CountApprovedOther', 'uint', 0)
   ->Column('Active', 'tinyint', 1);

$St->Columns('LocaleID')->AutoIncrement = TRUE;
$St->Set($Explicit, $Drop);

$St->Table('LocaleAddon')
   ->Column('AddonKey', 'varchar(20)', FALSE, 'primary')
   ->Column('Name', 'varchar(100)', TRUE)
   ->Column('Locale', 'varchar(5)', TRUE)
   ->Column('DateLastDownload', 'datetime', TRUE)
   ->Column('VersionCurrent', 'varchar(20)', TRUE) // version from last list download
   ->Column('VersionLastDownload', 'varchar(20)', TRUE) // version from last download
   ->Column('VersionSaved', 'varchar(20)', TRUE) // version in db
   ->Set($Explicit, $Drop);

$Collate_Bak = GetValue('Collate', $Database->ExtendedProperties);
$Database->ExtendedProperties['Collate'] = 'utf8_bin';

$St->Table('LocalePrefix')
   ->Column('Prefix', 'varchar(50)')
   ->Column('CountGroup', array('Core', 'Admin', 'Addon', 'Other'), 'Other')
   ->Column('Active', 'tinyint', 1)
   ->Set($Explicit, $Drop);

$St->Table('LocaleUser')
   ->Column('Locale', 'varchar(5)', FALSE, 'primary')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('DateInserted', 'datetime')
   ->Column('Deleted', 'tinyint', '0')
   ->Set($Explicit, $Drop);

$St->Table('LocaleCode')
   ->PrimaryKey('CodeID')
   ->Column('Name', 'varchar(250)', FALSE, 'unique')
   ->Column('Prefix', 'varchar(50)')
   ->Column('Description', 'text', TRUE)
   ->Column('CountGroup', array('Core', 'Admin', 'Addon', 'Other'), 'Other')
   ->Column('IsNew', 'tinyint', 1)
   ->Column('MyDashboard', 'tinyint', TRUE)
   ->Column('CapturedDashboard', 'tinyint', TRUE)
   ->Column('Dashboard', 'tinyint', '0')
   ->Column('MyActive', 'tinyint', TRUE) // Override prefix's Avtive
   ->Column('Active', 'tinyint', 1) // coalesce(MyActive, LocalePrefix.Active)
   ->Column('NewName', 'varchar(250)', TRUE)
   ->Set($Explicit, $Drop);



$Database->ExtendedProperties['Collate'] = $Collate_Bak;

$St->Table('LocaleTranslation')
   ->PrimaryKey('TranslationID')
   ->Column('Locale', 'varchar(5)', FALSE, 'unique')
   ->Column('CodeID', 'int', FALSE, 'unique')
   ->Column('Translation', 'text', TRUE)
   ->Column('DateInserted', 'datetime', TRUE)
   ->Column('InsertUserID', 'int', TRUE)
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('Approved', array('New', 'Translated', 'Approved', 'Rejected'), 'New')
   ->Column('ApprovalUserID', 'int', TRUE)
   ->Column('DateApproved', 'datetime', TRUE)
   ->Set($Explicit, $Drop);

$St->Table('LocaleTranslationVersion')
   ->PrimaryKey('TranslationVersionID')
   ->Column('TranslationID', 'int', FALSE, 'key')
   ->Column('Translation', 'text')
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int')
   ->Set($Explicit, $Drop);

$LocalizationModel = new LocalizationModel();
$LocalizationModel->UpdateLocaleCounts();

Gdn::PermissionModel()->Define(array(
   'Localization.Locales.Edit' => 'Garden.Profiles.Edit',
    ));

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Team');

LocalizationModel::RefreshCalculatedFields();