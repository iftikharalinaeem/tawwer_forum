<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Primary class for the Knowledge class, mostly responsible for pluggable operations.
 */
class KnowledgePlugin extends Gdn_Plugin {

    /** @var Gdn_Database */
    private $database;

    /**
     * KnowledgePlugin constructor.
     *
     * @param Gdn_Database $database
     */
    public function __construct(Gdn_Database $database) {
        parent::__construct();
        $this->database = $database;
    }

    /**
     * Setup routine for the addon.
     *
     * @return bool|void
     */
    public function setup() {
        parent::setup();
        $this->structure();
    }

    /**
     * Ensure the database is configured.
     */
    public function structure() {
        $this->database->structure()
            ->table("article")
            ->primaryKey("articleID")
            ->column("knowledgeCategoryID", "int", true, "index")
            ->column("sort", "int", true)
            ->column("score", "int", "0")
            ->column("views", "int", "0")
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->set();

        $this->database->structure()
            ->table("articleRevision")
            ->primaryKey("articleRevisionID")
            ->column("articleID", "int", false, ["index", "unique.publishedRevision"])
            ->column("status", ["published"], true, "unique.publishedRevision")
            ->column("name", "varchar(255)")
            ->column("format", "varchar(20)")
            ->column("body", "text")
            ->column("bodyRendered", "text")
            ->column("locale", "varchar(10)", true)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->set();

        $this->database->structure()
            ->table("knowledgeCategory")
            ->primaryKey("knowledgeCategoryID")
            ->column("name", "varchar(255)")
            ->column("parentID", "int")
            ->column("isSection", "tinyint", "0")
            ->column("displayType", ["help", "guide", "search"], true)
            ->column("sortChildren", ["name", "dateInserted", "dateInsertedDesc", "manual"], true)
            ->column("sort", "int", true)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->column("lastUpdatedArticleID", "int", true)
            ->column("articleCount", "int", "0")
            ->column("articleCountRecursive", "int", "0")
            ->column("childCategoryCount", "int", "0")
            ->set();
    }
}
