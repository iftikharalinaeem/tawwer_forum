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
            ->table("translations")
            ->column("resource", "varchar(64)", false, ["index", "unique.resourceRecordKey"])
            ->column("key", "varchar(255)", false, ["index", "unique.resourceRecordKey"])
            ->column("locale", "varchar(10)", false, ["index", "unique.resourceRecordKey"])
            ->column("translation", "mediumtext", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("translationProperty")
            ->column("resource", "varchar(64)", false, ["index"])
            ->column("recordType", "varchar(64)", false, ["index"])
            ->column("recordID", "int", true)
            ->column("recordKey", "varchar(32)", true)
            ->column("propertyName", "varchar(32)", false, ["index"])
            ->column("propertyType", "varchar(32)", true)
            ->column("key", "varchar(255)", false, ["index","unique.Key"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("resource")
            ->primaryKey("resourceID")
            ->column("name", "varchar(64)", false, "unique.resourceKey")
            ->column("sourceLocale", "varchar(10)", false, "unique.resourceKey")
            ->column("url", "varchar(32)", false, "unique.resourceKey")
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;
    }
}

