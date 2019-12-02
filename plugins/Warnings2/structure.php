<?php if (!defined('APPLICATION')) exit();

Gdn::structure()->table('UserNote')
    ->primaryKey('UserNoteID')
    ->column('Type', 'varchar(10)')
    ->column('UserID', 'int', false, 'index.userdate')
    ->column('Body', 'text', false)
    ->column('Format', 'varchar(10)')
    ->column('RecordType', 'varchar(20)', true)
    ->column('RecordID', 'int', true)
    ->column('InsertUserID', 'int', false, 'key')
    ->column('DateInserted', 'datetime', false, 'index.userdate')
    ->column('InsertIPAddress', 'ipaddress', false)
    ->column('UpdateUserID', 'int', true)
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateIPAddress', 'ipaddress', true)
    ->column('Attributes', 'text', true)
    ->column('RuleID', 'int', true)
    ->set();

Gdn::structure()->table('UserAlert')
    ->column('UserID', 'int', false, 'primary')
    ->column('WarningLevel', 'smallint', '0')
    ->column('TimeWarningExpires', 'uint', true)
    ->column('TimeExpires', 'uint', true, 'index')
    ->column('DateInserted', 'datetime')
    ->column('Attributes', 'text', true)
    ->set();

$WarningTypeExists = Gdn::structure()->tableExists('WarningType');

Gdn::structure()->table('WarningType')
    ->primaryKey('WarningTypeID')
    ->column('Name', 'varchar(20)', false, 'unique')
    ->column('Description', 'text', true)
    ->column('Points', 'smallint', '0')
    ->column('ExpireNumber', 'smallint', '0')
    ->column('ExpireType', ['hours', 'days', 'weeks', 'months'], true)
    ->set();

Gdn::structure()->table('Rule')
    ->primaryKey('RuleID')
    ->column('Name', 'varchar(255)', false)
    ->column('Description', 'varchar(500)', false)
    ->column('InsertUserID', 'int', false, 'key')
    ->column('DateInserted', 'datetime', false)
    ->column('UpdateUserID', 'int', true)
    ->column('DateUpdated', 'datetime', true)
    ->set();


if (!$WarningTypeExists) {
    Gdn::sql()->replace(
        'WarningType',
        ['Description' => '', 'Points' => '0'],
        ['Name' => 'Notice'],
        true
    );

    Gdn::sql()->replace(
        'WarningType',
        ['Description' => '', 'Points' => '2', 'ExpireNumber' => '1', 'ExpireType' => 'weeks'],
        ['Name' => 'Minor'],
        true
    );

    Gdn::sql()->replace(
        'WarningType',
        ['Description' => '', 'Points' => '3', 'ExpireNumber' => '2', 'ExpireType' => 'weeks'],
        ['Name' => 'Major'],
        true
    );
}

Gdn::structure()->table('User')
    ->column('Punished', 'tinyint', '0')
    ->set();
