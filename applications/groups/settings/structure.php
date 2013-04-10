<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;

$Sql = Gdn::SQL();
$St = Gdn::Structure();

Gdn::PermissionModel()->Define(array(
   'Groups.Group.Add' => 'Garden.Profiles.Edit'));

// Define the groups table.
$St->Table('Group')
   ->PrimaryKey('GroupID')
   ->Column('Name', 'varchar(255)', FALSE, 'unique')
   ->Column('Description', 'text')
   ->Column('Format', 'varchar(10)', TRUE)
   ->Column('CategoryID', 'int', FALSE, 'key')
   ->Column('Icon', 'varchar(255)', TRUE)
   ->Column('Banner', 'varchar(255)', TRUE)
   ->Column('Registration', array('Public', 'Approval', 'Invite'), 'Public')
   ->Column('Visibility', array('Public', 'Members'))
   ->Column('CountMembers', 'uint', '0')
   ->Column('DateLastComment', 'datetime', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('Attributes', 'text', TRUE)
   ->Set($Explicit, $Drop);

$St->Table('UserGroup')
   ->PrimaryKey('UserGroupID')
   ->Column('GroupID', 'int', FALSE, 'unique')
   ->Column('UserID', 'int', FALSE, array('unique', 'key'))
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int')
   ->Column('Role', array('Leader', 'Member'))
   ->Set($Explicit, $Drop);

$St->Table('GroupApplicant')
   ->PrimaryKey('GroupApplicantID')
   ->Column('GroupID', 'int', FALSE, 'unique')
   ->Column('UserID', 'int', FALSE, array('unique', 'key'))
   ->Column('Type', array('Application', 'Invitation', 'Denied', 'Banned'))
   ->Column('Reason', 'varchar(200)', TRUE) // reason for wanting to join.
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
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
            'AllowGroups' => 1,
            'Sort' => 1000);
         $Model->Save($Row);
      }
   }
}

if ($St->TableExists('Discussion')) {
   $St->Table('Discussion')
      ->Column('GroupID', 'int', TRUE, 'key')
      ->Set();
}

$St->Table('Event')
   ->PrimaryKey('EventID')
   ->Column('Name', 'varchar(255)')
   ->Column('Body', 'text')
   ->Column('Format', 'varchar(10)', TRUE)
   ->Column('DateStarts', 'datetime')
   ->Column('DateEnds', 'datetime', TRUE)
   ->Column('Timezone', 'varchar(64)')
   ->Column('AllDayEvent', 'tinyint', '0')
   ->Column('Location', 'varchar(255)', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int') // organizer
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('GroupID', 'int', TRUE, 'key') // eventually make events stand-alone.
   ->Set($Explicit, $Drop);

$St->Table('UserEvent')
   ->Column('EventID', 'int', FALSE, 'primary')
   ->Column('UserID', 'int', FALSE, array('primary', 'key'))
   ->Column('DateInserted', 'datetime')
   ->Column('Attending', array('Yes', 'No', 'Maybe', 'Invited'), 'Invited')
   ->Set($Explicit, $Drop);

// Make sure the activity table has an index that the event wall can use.
$St->Table('Activity')
   ->Column('RecordType', 'varchar(20)', TRUE, 'index.Record')
   ->Column('RecordID', 'int', TRUE, 'index.Record')
   ->Set();

$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Groups');
$ActivityModel->DefineType('Events');