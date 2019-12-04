<?php if (!defined('APPLICATION')) exit;
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

Gdn::structure()
    ->table('webhook')
    ->primaryKey('webhookID')
    ->column('active', 'tinyint')
    ->column('name', 'varchar(100)', false)
    ->column('events', ['*', 'comment', 'discussion', 'user'])
    ->column('url', 'varchar(255)', false)
    ->column('secret', 'varchar(100)', false)
    ->column('dateInserted', 'datetime', true)
    ->column('insertUserID', 'int', true)
    ->column('dateUpdated', 'datetime', true)
    ->column('updateUserID', 'int', true)
    ->set();
