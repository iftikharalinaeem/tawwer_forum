<?php if (!defined('APPLICATION')) exit();

Gdn::Structure()->Table('UserNote')
   ->PrimaryKey('UserNoteID')
   ->Column('Type', 'varchar(10)')
    ->Column('UserID', 'int', false, 'index.userdate')
    ->Column('Body', 'text', false)
   ->Column('Format', 'varchar(10)')
   ->Column('RecordType', 'varchar(20)', true)
   ->Column('RecordID', 'int', true)
   ->Column('InsertUserID', 'int', false, 'key')
   ->Column('DateInserted', 'datetime', false, 'index.userdate')
   ->Column('InsertIPAddress', 'ipaddress', false)
   ->Column('UpdateUserID', 'int', true)
   ->Column('DateUpdated', 'datetime', true)
   ->Column('UpdateIPAddress', 'ipaddress', true)
   ->Column('Attributes', 'text', true)
   ->Set();

Gdn::Structure()->Table('UserAlert')
    ->Column('UserID', 'int', false, 'primary')
   ->Column('WarningLevel', 'smallint', '0')
    ->Column('TimeWarningExpires', 'uint', true)
    ->Column('TimeExpires', 'uint', true, 'index')
   ->Column('DateInserted', 'datetime')
    ->Column('Attributes', 'text', true)
   ->Set();

$WarningTypeExists = Gdn::Structure()->TableExists('WarningType');

Gdn::Structure()->Table('WarningType')
   ->PrimaryKey('WarningTypeID')
    ->Column('Name', 'varchar(20)', false, 'unique')
    ->Column('Description', 'text', true)
   ->Column('Points', 'smallint', '0')
   ->Column('ExpireNumber', 'smallint', '0')
    ->Column('ExpireType', array('hours', 'days', 'weeks', 'months'), true)
   ->Set();

if (!$WarningTypeExists) {
    Gdn::Sql()->Replace(
        'WarningType',
      array('Description' => '', 'Points' => '0'),
        array('Name' => 'Notice'),
        true
    );

    Gdn::Sql()->Replace(
        'WarningType',
      array('Description' => '', 'Points' => '2', 'ExpireNumber' => '1', 'ExpireType' => 'weeks'),
        array('Name' => 'Minor'),
        true
    );

    Gdn::Sql()->Replace(
        'WarningType',
      array('Description' => '', 'Points' => '3', 'ExpireNumber' => '2', 'ExpireType' => 'weeks'),
        array('Name' => 'Major'),
        true
    );
}

Gdn::Structure()->Table('User')
   ->Column('Punished', 'tinyint', '0')
   ->Set();
