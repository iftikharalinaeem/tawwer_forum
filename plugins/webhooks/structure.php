<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Webhook');
$webhooksExists = Gdn::structure()->tableExists();

Gdn::structure()
    ->table('Webhook')
    ->primaryKey('WebhookID')
    ->column('Active', 'tinyint')
    ->column('Name', 'varchar(100)', false, ['unique'])
    ->column('Events', ['*', 'Comment', 'Discussion', 'User'])
    ->column('Url', 'varchar(255)', false)
    ->column('Secret', 'varchar(100)', false)
    ->column('DateInserted', 'datetime', false)
    ->column('InsertUserID', 'int', false)
    ->column('DateUpdated', 'datetime', true)
    ->column('UpdateUserID', 'int', true)
    ->set();
