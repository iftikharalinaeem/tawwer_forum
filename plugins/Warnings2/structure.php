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
   ->Column('InsertIPAddress', 'varchar(15)', FALSE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateIPAddress', 'varchar(15)', TRUE)
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

Gdn::Structure()->Table('Warning')
   ->PrimaryKey('WarningID')
   ->Column('Type', array('Warning', 'Ban', 'Punish'))
   ->Column('WarnUserID', 'int') // who we're warning
   ->Column('Points', 'smallint')
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int') // who did the warning.
   ->Column('InsertIPAddress', 'varchar(15)')
   ->Column('Body', 'text', FALSE)
   ->Column('ModeratorNote', 'varchar(255)', TRUE)
   ->Column('Format', 'varchar(20)', TRUE)
   ->Column('DateExpires', 'datetime', TRUE)
   ->Column('Expired', 'tinyint(1)')
   ->Column('RecordType', 'varchar(10)', TRUE) // Warned for a something they posted?
   ->Column('RecordID', 'int', TRUE)
   ->Column('ConversationID', 'int', TRUE, 'index')
   ->Column('Attributes', 'text', TRUE)
   ->Set();

Gdn::Structure()->Table('User')
   ->Column('Punished', 'tinyint', '0')
   ->Set();