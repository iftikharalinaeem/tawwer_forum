<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Webhook');
$webhooksExists = Gdn::structure()->tableExists();

Gdn::structure()
    ->table('webhook')
    ->primaryKey('webhookID')
    ->column('active', 'tinyint')
    ->column('name', 'varchar(100)', false, ['unique'])
    ->column('events', ['*', 'comment', 'discussion', 'user'])
    ->column('url', 'varchar(255)', false)
    ->column('secret', 'varchar(100)', false)
    ->column('dateInserted', 'datetime', true)
    ->column('insertUserID', 'int', true)
    ->column('dateUpdated', 'datetime', true)
    ->column('updateUserID', 'int', true)
    ->set();

