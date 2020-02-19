<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsApi;

use \Gdn_Database;
use \Gdn_Plugin;
use Vanilla\TranslationsApi\Models\TranslationProvider;
use Garden\Container\Reference;

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
     * Initialize container dependencies
     *
     * @param \Garden\Container\Container $container Container to support dependency injection
     */
    public function container_init(\Garden\Container\Container $container) {
        $container->rule(\Vanilla\Site\TranslationModel::class)
            ->addCall('addProvider', [new Reference(TranslationProvider::class)])
        ;
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
            ->table("translation")
            ->column("resource", "varchar(64)", false, ["index", "unique.translation"])
            ->column("translationPropertyKey", "varchar(255)", false, ["index", "unique.translation"])
            ->column("locale", "varchar(10)", false, ["index", "unique.translation"])
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
            ->column("translationPropertyKey", "varchar(255)", false, ["index","unique.translationPropertyKey"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("resource")
            ->primaryKey("resourceID")
            ->column("name", "varchar(64)", false)
            ->column("sourceLocale", "varchar(10)", false)
            ->column("urlCode", "varchar(32)", false, ["index","unique.resourecUrlCode"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set()
        ;
    }
}

