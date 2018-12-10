<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge;

use Vanilla\Knowledge\Controllers\KbPageRoutes;

/**
 * Primary class for the Knowledge class, mostly responsible for pluggable operations.
 */
class KnowledgePlugin extends \Gdn_Plugin {

    /** @var \Gdn_Database */
    private $database;

    /**
     * KnowledgePlugin constructor.
     *
     * @param \Gdn_Database $database
     */
    public function __construct(\Gdn_Database $database) {
        parent::__construct();
        $this->database = $database;
    }

    /**
     * Initialize controller class detection under Knowledge base application
     *
     * @param \Garden\Container\Container $container Container to support dependency injection
     */
    public function container_init(\Garden\Container\Container $container) {
        $container->rule(\Garden\Web\Dispatcher::class)
            ->addCall('addRoute', ['route' => new \Garden\Container\Reference(KbPageRoutes::class), 'kb-page'])
        ;
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
            ->column("status", ["enum", Models\ArticleModel::getAllStatuses(),
            ], ['Null' => false, 'Default' => Models\ArticleModel::STATUS_PUBLISHED])
            ->set()
        ;

        $this->database->structure()
            ->table("articleRevision")
            ->primaryKey("articleRevisionID")
            ->column("articleID", "int", false, ["index", "unique.publishedRevision"])
            ->column("status", ["published"], true, "unique.publishedRevision")
            ->column("name", "varchar(255)")
            ->column("format", "varchar(20)")
            ->column("body", "mediumtext")
            ->column("bodyRendered", "mediumtext")
            ->column("outline", "text", true)
            ->column("plainText", "mediumtext", true)
            ->column("excerpt", "text", true)
            ->column("locale", "varchar(10)", true)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("knowledgeCategory")
            ->primaryKey("knowledgeCategoryID")
            ->column("name", "varchar(255)")
            ->column("parentID", "int")
            ->column("knowledgeBaseID", "int", true, "index")
            ->column("sortChildren", ["name", "dateInserted", "dateInsertedDesc", "manual"], true)
            ->column("sort", "int", true)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->column("lastUpdatedArticleID", "int", true)
            ->column("lastUpdatedUserID", "int", true)
            ->column("articleCount", "int", "0")
            ->column("articleCountRecursive", "int", "0")
            ->column("childCategoryCount", "int", "0")
            ->set();

        $this->database->structure()
            ->table("knowledgeBase")
            ->primaryKey("knowledgeBaseID")
            ->column("name", "varchar(255)")
            ->column("description", "text")
            ->column("urlCode", "varchar(255)", ['Null' => false, 'Default' => ''])
            ->column("icon", "varchar(255)")
            ->column("sourceLocale", "varchar(5)")
            ->column(
                "viewType",
                ["enum", Models\KnowledgeBaseModel::getAllTypes()],
                ['Null' => false, 'Default' => Models\KnowledgeBaseModel::TYPE_GUIDE]
            )
            ->column(
                "sortArticles",
                ["enum", Models\KnowledgeBaseModel::getAllSorts()],
                ['Null' => false, 'Default' => Models\KnowledgeBaseModel::ORDER_MANUAL]
            )
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->column("countArticles", "int", "0")
            ->column("countCategories", "int", "0")
            ->column("rootCategoryID", "int", ['Null' => false, 'Default' => -1])
            ->set();
    }
}
