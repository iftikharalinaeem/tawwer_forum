<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

use DashboardController;

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
            ->column('status', ['active', 'disabled'], 'active')
            ->column('name', 'varchar(100)', false)
            ->column('events', 'varchar(255)')
            ->column('url', 'varchar(255)', false)
            ->column('secret', 'varchar(100)', false)
            ->column('dateInserted', 'datetime', true)
            ->column('insertUserID', 'int', true)
            ->column('dateUpdated', 'datetime', true)
            ->column('updateUserID', 'int', true)
            ->set();

        $this->database->structure()
            ->table("webhookDelivery")
            ->column("webhookDeliveryID", "varchar(36)", false, ["index"])
            ->column("webhookID", "int", false, ["index"])
            ->column("requestBody", "mediumtext")
            ->column("requestHeaders", "mediumtext")
            ->column("requestDuration", "int", true)
            ->column("responseBody", "mediumtext", true)
            ->column("responseCode", "int", true)
            ->column("responseHeaders", "mediumtext", true)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->set();
    }

    /**
     * Event handler for adding navigation items into the dashboard.
     *
     * @param \Gdn_Pluggable $sender
     *
     * @return void
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var \NestedCollectionAdapter */
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Site Settings', t('Webhooks'), '/webhook-settings', 'Garden.Settings.Manage');
    }
}
