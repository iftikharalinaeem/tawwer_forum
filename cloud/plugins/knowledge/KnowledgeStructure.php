<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge;

use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\DefaultArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeNavigationCache;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;
use Vanilla\Site\DefaultSiteSection;

/**
 * Database structures and updates for knowledge base.
 */
final class KnowledgeStructure {

    /** @var \Gdn_Database */
    private $database;

    /** @var \Gdn_Router */
    private $router;

    /** @var \PermissionModel $permissionModel */
    private $permissionModel;

    /** @var DefaultArticleModel $defaultArticleModel */
    private $defaultArticleModel;

    /** @var KnowledgeNavigationModel $knowledgeNavigationModel */
    private $knowledgeNavigationModel;

    /** @var KnowledgeNavigationCache */
    private $navCache;

    /** @var KnowledgeBaseModel $kbModel */
    private $kbModel;

    /**
     * DI.
     * @inheritdoc
     */
    public function __construct(
        \Gdn_Database $database,
        \Gdn_Router $router,
        \PermissionModel $permissionModel,
        DefaultArticleModel $defaultArticleModel,
        KnowledgeNavigationModel $knowledgeNavigationModel,
        KnowledgeNavigationCache $navCache,
        KnowledgeBaseModel $kbModel
    ) {
        $this->database = $database;
        $this->router = $router;
        $this->permissionModel = $permissionModel;
        $this->defaultArticleModel = $defaultArticleModel;
        $this->knowledgeNavigationModel = $knowledgeNavigationModel;
        $this->navCache = $navCache;
        $this->kbModel = $kbModel;
    }

    /**
     * Ensure the database is configured.
     */
    public function run() {
        $this->router->setRoute(
            "kb/sitemap-kb\\.xml(\\.*)",
            "/kb/sitemap-kb/xml$1",
            "Internal"
        );
        $this->router->setRoute(
            "kb/sitemap-index\\.xml",
            "/kb/sitemap-index/xml",
            "Internal"
        );

        $this->database->structure()
            ->table("article")
            ->primaryKey("articleID")
            ->column('foreignID', 'varchar(32)', true, 'index')
            ->column("knowledgeCategoryID", "int", true, "index")
            ->column("sort", "int", true)
            ->column("score", "int", "0")
            ->column("views", "int", "0")
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->column("featured", "tinyint(1)", 0, 'index')
            ->column("dateFeatured", "datetime", true)
            ->column(
                "status",
                ["enum", Models\ArticleModel::getAllStatuses()],
                ['Null' => false, 'Default' => Models\ArticleModel::STATUS_PUBLISHED],
                'index'
            )
            ->set()
        ;
        $this->database->structure()
            ->table("articleReaction")
            ->primaryKey("articleReactionID")
            ->column("articleID", "int", true, "index")
            ->column("reactionType", 'varchar(64)', ArticleReactionModel::TYPE_HELPFUL)
            ->column("positiveCount", "int", "0")
            ->column("negativeCount", "int", "0")
            ->column("neutralCount", "int", "0")
            ->column("allCount", "int", "0")
            ->set()
        ;

        $this->database->structure()
            ->table("pageRouteAlias")
            ->primaryKey("pageRouteAliasID")
            ->column("recordID", "int", false, "index.byType")
            ->column("recordType", 'varchar(32)', false, "index.byType")
            ->column("alias", "varchar(255)", false, ["index"])
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
            ->column('seoImage', 'varchar(255)', true)
            ->column("locale", "varchar(10)", false, "unique.publishedRevision")
            ->column(
                "translationStatus",
                ArticleRevisionModel::getTranslationStatuses(),
                ArticleRevisionModel::STATUS_TRANSLATION_OUT_TO_DATE,
                'index'
            )
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->set()
        ;

        $this->database->structure()
            ->table("knowledgeCategory")
            ->primaryKey("knowledgeCategoryID")
            ->column('foreignID', 'varchar(32)', true, 'index')
            ->column("name", "varchar(255)")
            ->column("parentID", "int")
            ->column("knowledgeBaseID", "int", false, "index")
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
            ->column('foreignID', 'varchar(32)', true, 'index')
            ->column("name", "varchar(255)")
            ->column("siteSectionGroup", "varchar(64)", DefaultSiteSection::DEFAULT_SECTION_GROUP)
            ->column("description", "text")
            // Size of this cannot be larger than 191 UT8-mb4 to be an index.
            ->column("urlCode", "varchar(191)", false, 'unique.urlCode')
            ->column("icon", "varchar(255)", ['Null' => false, 'Default' => ''])
            ->column("bannerImage", "varchar(255)", ['Null' => false, 'Default' => ''])
            ->column("bannerContentImage", "varchar(255)", ['Null' => false, 'Default' => ''])
            ->column("sourceLocale", "varchar(5)", ['Null' => false, 'Default' => c("Garden.Locale")])
            ->column(
                "viewType",
                Models\KnowledgeBaseModel::getAllTypes(),
                Models\KnowledgeBaseModel::TYPE_GUIDE
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
            ->column("defaultArticleID", "int", ['Null' => true])
            ->column("hasCustomPermission", "int", ['Null' => false, 'Default' => 0])
            ->column("permissionKnowledgeBaseID", "int", ['Null' => false, 'Default' => -1])
            ->column("sort", "int", true)
            ->column("isUniversalSource", "tinyint(1)", 0)
            ->column(
                "status",
                KnowledgeBaseModel::getAllStatuses(),
                KnowledgeBaseModel::STATUS_PUBLISHED,
                'index'
            )
            ->set();

        $this->permissionModel->define(
            [
                'knowledge.kb.view' => 0,
                'knowledge.articles.add' => 0,
            ],
            'tinyint',
            'knowledgeBase',
            'permissionKnowledgeBaseID'
        );

        $this->database->structure()
            ->table("knowledgeUniversalSource")
            ->column("sourceKnowledgeBaseID", "int", null, 'unique.universalPair')
            ->column("targetKnowledgeBaseID", "int", null, 'unique.universalPair')
            ->set();

        // Update knowledge base when missing defaultArticleID (release: 2020.005)
        // relates to: https://github.com/vanilla/knowledge/issues/1582
        $kbs = $this->kbModel->get(
            [
                'viewType' => KnowledgeBaseModel::TYPE_GUIDE
            ],
            [
                'select' => [KnowledgeBaseModel::RECORD_ID_FIELD, 'defaultArticleID', 'viewType']
            ]
        );
        foreach ($kbs as $kb) {
            if (empty($kb['defaultArticleID']) && $kb['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                $kbID = $kb[KnowledgeBaseModel::RECORD_ID_FIELD];
                $defaultArticleID = $this->knowledgeNavigationModel->getDefaultArticleID($kbID);
                $this->defaultArticleModel->update(['defaultArticleID' => $defaultArticleID], [KnowledgeBaseModel::RECORD_ID_FIELD => $kbID]);
            }
        }

        // Clear the navigation cache.
        $this->navCache->deleteAll();
    }
}
