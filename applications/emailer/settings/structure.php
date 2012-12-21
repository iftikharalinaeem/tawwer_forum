<?php if (!defined('APPLICATION')) exit();

$St = Gdn::Structure();

$St->Table('EmailLog')
   ->PrimaryKey('LogID')
   ->Column('From', 'varchar(255)', TRUE)
   ->Column('To', 'varchar(255)', TRUE)
   ->Column('Subject', 'varchar(500)', TRUE)
   ->Column('Headers', 'text', TRUE)
   ->Column('Source', 'text', TRUE)
   ->Column('Body', 'text', TRUE)
   ->Column('Format', 'varchar(100)', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('Response', 'smallint', TRUE)
   ->Column('ResponseText', 'varchar(500)', TRUE)
   ->Set();