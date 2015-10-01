<?php if (!defined('APPLICATION')) exit();

Gdn::Structure()->Table('UserNote')
   ->PrimaryKey('UserNoteID')
   ->Column('Type', 'varchar(10)')
   ->Column('UserID', 'int', FALSE, 'index.userdate')
   ->Column('Body', 'text', FALSE)
   ->Column('Format', 'varchar(10)')
   ->Column('RecordType', 'varchar(20)', TRUE)
   ->Column('RecordID', 'int', TRUE)
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('DateInserted', 'datetime', FALSE, 'index.userdate')
   ->Column('InsertIPAddress', 'ipaddress', FALSE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateIPAddress', 'ipaddress', TRUE)
   ->Column('Attributes', 'text', TRUE)
   ->Set();

Gdn::Structure()->Table('UserAlert')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('WarningLevel', 'smallint', '0')
   ->Column('TimeWarningExpires', 'uint', TRUE)
   ->Column('TimeExpires', 'uint', TRUE, 'index')
   ->Column('DateInserted', 'datetime')
   ->Column('Attributes', 'text', TRUE)
   ->Set();

$WarningTypeExists = Gdn::Structure()->TableExists('WarningType');

Gdn::Structure()->Table('WarningType')
   ->PrimaryKey('WarningTypeID')
   ->Column('Name', 'varchar(20)', FALSE, 'unique')
   ->Column('Description', 'text', TRUE)
   ->Column('Points', 'smallint', '0')
   ->Column('ExpireNumber', 'smallint', '0')
   ->Column('ExpireType', array('hours', 'days', 'weeks', 'months'), TRUE)
   ->Set();

if (!$WarningTypeExists) {
   Gdn::Sql()->Replace('WarningType',
      array('Description' => '', 'Points' => '0'),
      array('Name' => 'Notice'), TRUE);

   Gdn::Sql()->Replace('WarningType',
      array('Description' => '', 'Points' => '2', 'ExpireNumber' => '1', 'ExpireType' => 'weeks'),
      array('Name' => 'Minor'), TRUE);

   Gdn::Sql()->Replace('WarningType',
      array('Description' => '', 'Points' => '3', 'ExpireNumber' => '2', 'ExpireType' => 'weeks'),
      array('Name' => 'Major'), TRUE);
}

Gdn::Structure()->Table('User')
   ->Column('Punished', 'tinyint', '0')
   ->Set();