<?php if (!defined('APPLICATION')) exit();

$St = Gdn::Structure();

$St->Table('EmailLog')
   ->PrimaryKey('LogID')
   ->Column('MessageID', 'varchar(255)', TRUE)
   ->Column('ReplyTo', 'varchar(255)', TRUE)
   ->Column('From', 'varchar(255)', TRUE)
   ->Column('To', 'varchar(255)', TRUE)
   ->Column('Subject', 'varchar(500)', TRUE)
   ->Column('Headers', 'text', TRUE)
   ->Column('Source', 'text', TRUE)
   ->Column('Charsets', 'varchar(500)', TRUE)
   ->Column('Body', 'text', TRUE)
   ->Column('Format', 'varchar(100)', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('Url', 'varchar(255)', TRUE)
   ->Column('Post', 'text', TRUE)
   ->Column('Response', 'smallint', TRUE)
   ->Column('ResponseText', 'varchar(500)', TRUE)
   ->Set();