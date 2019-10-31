<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsAPI;

use \Gdn_Database;
use \Gdn_Plugin;


class TranslationsApiPlugin extends Gdn_Plugin {

    /** @var Gdn_Database */
    private $database;

    /**
     * Translations Plugin constructor.
     *
     * @param Gdn_Database $database
     */
    public function __construct(
        Gdn_Database $database
    ) {
        parent::__construct();
        $this->database = $database;
    }

    /**
     * Setup routine for the addon.
     *
     * @return bool|void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Ensure the database is configured.
     */
    public function structure() {
        $this->database->structure()
            ->table("Translations")
            ->column("resource", "varchar(64)", false, ["index", "unique.resourceKey"])
            ->column("key", "varchar(255)", false, ["index", "unique.resourceKey"])
            ->column("locale", "varchar(10)", false, ["index", "unique.resourceKey"])
            ->column("tanslations", "mediumtext", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("Resources")
            ->column("resource", "varchar(64)", false, ["index"])
            ->column("recordType", "varchar(64)", 0, ["index"])
            ->column("recordID", "int", false)
            ->column("recordKey", "varchar(32)", false)
            ->column("key", "varchar(255)", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;
    }
}

