<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

if (!isset($Drop)) {
    $Drop = false;
}

if (!isset($Explicit)) {
    $Explicit = false;
}

$Sql = Gdn::SQL();
$St = Gdn::Structure();

Gdn::PermissionModel()->Define(array(
    'Groups.Group.Add' => 'Garden.Profiles.Edit',
    'Groups.Moderation.Manage' => 'Garden.Moderation.Manage'));

// Define the groups table.
$St->Table('Group');
$GroupExists = $St->TableExists();
$CountDiscussionsExists = $St->ColumnExists('CountDiscussions');
$GroupPrivacyExists = $St->ColumnExists('Privacy');

$St
    ->PrimaryKey('GroupID')
    ->Column('Name', 'varchar(150)', false, 'unique')
    ->Column('Description', 'text')
    ->Column('Format', 'varchar(10)', true)
    ->Column('CategoryID', 'int', false, 'key')
    ->Column('Icon', 'varchar(255)', true)
    ->Column('Banner', 'varchar(255)', true)
    ->Column('Privacy', array('Public', 'Private'), 'Public') // add secret later.
    ->Column('Registration', array('Public', 'Approval', 'Invite'), true) // deprecated
    ->Column('Visibility', array('Public', 'Members'), true) // deprecated
    ->Column('CountMembers', 'uint', '0')
    ->Column('CountDiscussions', 'uint', '0')
    ->Column('DateLastComment', 'datetime', true)
    ->Column('LastCommentID', 'int', null)
    ->Column('LastDiscussionID', 'int', null)
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('Attributes', 'text', true)
    ->Set($Explicit, $Drop);

if ($GroupExists && !$GroupPrivacyExists) {
    $Sql->Put('Group', array('Privacy' => 'Private'));
    $Sql->Put('Group', array('Privacy' => 'Public'), array('Registration' => 'Public', 'Visibility' => 'Public'));
}

$St->Table('UserGroup')
    ->PrimaryKey('UserGroupID')
    ->Column('GroupID', 'int', false, 'unique')
    ->Column('UserID', 'int', false, array('unique', 'key'))
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int')
    ->Column('Role', array('Leader', 'Member'))
    ->Set($Explicit, $Drop);

$St->Table('GroupApplicant')
    ->PrimaryKey('GroupApplicantID')
    ->Column('GroupID', 'int', false, 'unique')
    ->Column('UserID', 'int', false, array('unique', 'key'))
    ->Column('Type', array('Application', 'Invitation', 'Denied', 'Banned'))
    ->Column('Reason', 'varchar(200)', true) // reason for wanting to join.
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateUserID', 'int', true)
    ->Set($Explicit, $Drop);

if ($St->TableExists('Category')) {
    $St->Table('Category');
    $AllowGroupsExists = $St->ColumnExists('AllowGroups');
    $St->Table('Category')
        ->Column('AllowGroups', 'tinyint', '0')
        ->Set();

    if (!$AllowGroupsExists) {
        // Create a category for groups.
        $Model = new CategoryModel();
        $Row = CategoryModel::Categories('social-groups');
        if ($Row) {
            $Model->SetField($Row['CategoryID'], 'AllowGroups', 1);
        } else {
            $Row = array(
                'Name' => 'Social Groups',
                'UrlCode' => 'social-groups',
                'HideAllDiscussions' => 1,
                'DisplayAs' => 'Discussions',
                'AllowDiscussions' => 1,
                'AllowGroups' => 1,
                'Sort' => 1000);
            $Model->Save($Row);
        }
    }
}

if ($St->TableExists('Discussion')) {
    $St->Table('Discussion')
        ->Column('GroupID', 'int', true, 'key')
        ->Set();
}

if (!$CountDiscussionsExists) {
    $GroupModel = new GroupModel();
    $GroupModel->Counts('CountDiscussions');
}

$St->Table('Event');

$timeZoneExists = $St->columnExists('Timezone');

$St->PrimaryKey('EventID')
    ->Column('Name', 'varchar(255)')
    ->Column('Body', 'text')
    ->Column('Format', 'varchar(10)', true)
    ->Column('DateStarts', 'datetime')
    ->Column('DateEnds', 'datetime', true)
    ->Column('AllDayEvent', 'tinyint', '0')
    ->Column('Location', 'varchar(255)', true)
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int') // organizer
    ->Column('DateUpdated', 'datetime', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('GroupID', 'int', true, 'key') // eventually make events stand-alone.
    ->Set($Explicit, $Drop);

if ($timeZoneExists) {
    $St->Table('Event')->dropColumn('Timezone');
}

$St->Table('UserEvent')
    ->Column('EventID', 'int', false, 'primary')
    ->Column('UserID', 'int', false, array('primary', 'key'))
    ->Column('DateInserted', 'datetime')
    ->Column('Attending', array('Yes', 'No', 'Maybe', 'Invited'), 'Invited')
    ->Set($Explicit, $Drop);

// Make sure the activity table has an index that the event wall can use.
$St->Table('Activity')
    ->Column('RecordType', 'varchar(20)', true, 'index.Record')
    ->Column('RecordID', 'int', true, 'index.Record')
    ->Set();

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Groups');
$ActivityModel->DefineType('Events');