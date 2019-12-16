<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

/**
 * Class WebhooksPlugin
 */
class WebhooksPlugin extends \Gdn_Plugin {

    /** @var \Gdn_Database */
    private $database;

    /**
     * WebhooksPlugin constructor.
     *
     * @param \Gdn_Database $database
     */
    public function __construct(\Gdn_Database $database) {
        parent::__construct();
        $this->database = $database;
    }

    /**
     * Runs when the plugin is enabled.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Database configuration.
     */
    public function structure() {
        $this->database->structure()
            ->table('webhook')
            ->primaryKey('webhookID')
            ->column('active', 'tinyint', 1)
            ->column('name', 'varchar(100)', false)
            ->column('events', 'varchar(255)')
            ->column('url', 'varchar(255)', false)
            ->column('secret', 'varchar(100)', false)
            ->column('dateInserted', 'datetime', true)
            ->column('insertUserID', 'int', true)
            ->column('dateUpdated', 'datetime', true)
            ->column('updateUserID', 'int', true)
            ->set();
    }
}
