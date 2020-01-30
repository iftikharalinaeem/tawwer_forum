<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge;

use Garden\Container\Reference;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Controllers\KbPageRoutes;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\KbBreadcrumbProvider;
use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Web\Robots;
use Gdn_Session as SessionInterface;
use Vanilla\Models\ThemeModel;
use Vanilla\Knowledge\Models\KnowledgeVariablesProvider;
use Vanilla\Knowledge\Models\SearchRecordTypeArticle;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleDraftCounterProvider;

/**
 * Primary class for the Knowledge class, mostly responsible for pluggable operations.
 */
class KnowledgePlugin extends \Gdn_Plugin {

    /** @var \Gdn_Database */
    private $database;

    /** @var \Gdn_Request */
    private $request;

    /** @var SessionInterface */
    private $session;

    /** @var KnowledgeBaseModel $kbModel */
    private $kbModel;

    /**
     * KnowledgePlugin constructor.
     *
     * @param \Gdn_Database $database
     * @param SessionInterface $session
     * @param \Gdn_Request $request
     * @param KnowledgeBaseModel $kbModel
     */
    public function __construct(
        \Gdn_Database $database,
        SessionInterface $session,
        \Gdn_Request $request,
        KnowledgeBaseModel $kbModel
    ) {
        parent::__construct();
        $this->database = $database;
        $this->session = $session;
        $this->request = $request;
        $this->kbModel = $kbModel;
    }

    /**
     * Add discussion menu options.
     *
     * @param mixed $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionOptionsDropdown_handler($sender, $args) {
        $discussion = $args["Discussion"] ?? null;
        if (!$discussion || !$this->session->checkPermission("knowledge.articles.add")) {
            return;
        }


        /** @var \DropdownModule $dropdown */
        $dropdown = $args['DiscussionOptionsDropdown'] ?? null;
        if (!$dropdown instanceof \DropdownModule) {
            return;
        }

        $attributes = $discussion->Attributes ?? [];
        $canonicalUrl = $attributes["CanonicalUrl"] ?? null;
        $label = $canonicalUrl ? t("Remove Article Link") : t("Convert to Article");
        $class = $canonicalUrl ? "js-unlinkDiscussion" : "js-convertDiscussionToArticle";

        $dropdown->addLink($label, "#", "discussionArticleConvert", $class, [], [
            "attributes" => [
                "data-discussionID" => $discussion->DiscussionID,
            ],
        ]);
    }

    /**
     * Initialize controller class detection under Knowledge base application
     *
     * @param \Garden\Container\Container $container Container to support dependency injection
     */
    public function container_init(\Garden\Container\Container $container) {
        $container->rule(\Garden\Web\Dispatcher::class)
            ->addCall('addRoute', ['route' => new Reference(KbPageRoutes::class), 'kb-page'])
            ->rule(BreadcrumbModel::class)
            ->addCall('addProvider', [new Reference(KbBreadcrumbProvider::class)])
            ->rule(ThemeModel::class)
            ->addCall("addVariableProvider", [new Reference(KnowledgeVariablesProvider::class)])
            ->rule(SearchRecordTypeProviderInterface::class)
            ->addCall('setType', [new SearchRecordTypeArticle()])
            ->rule(\Vanilla\Contracts\Site\ApplicationProviderInterface::class)
            ->addCall('add', [new Reference(\Vanilla\Site\Application::class, ['knowledge-base', ['kb']])])

            ->rule(\Vanilla\Menu\CounterModel::class)
                ->addCall('addProvider', [new Reference(ArticleDraftCounterProvider::class)])

            ->rule(TranslationProviderInterface::class)
            ->addCall('initializeResource', [new Reference(KnowledgeTranslationResource::class)])
            ;
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

    const NAV_SECTION = "knowledge";

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
        $this->createDashboardMenus($menu);
    }

    /**
     * Construct the knowledge base dashboard menu items.
     *
     * @param \NestedCollectionAdapter $navCollection
     */
    private function createDashboardMenus(\NestedCollectionAdapter $navCollection) {
        $navCollection
            ->addItem(self::NAV_SECTION, t('Knowledge'), 'Garden.Settings.Manage')
            ->addLink(self::NAV_SECTION, t('Knowledge Bases'), '/knowledge-settings/knowledge-bases', 'Garden.Settings.Manage')
            ->addLink(self::NAV_SECTION, t('General Appearance'), '/knowledge-settings/general-appearance', 'Garden.Settings.Manage');
    }

    /**
     * Add the knowledge base "Help" link to the main menu.
     *
     * @param \Gdn_Controller $sender Sender object.
     */
    public function base_render_before($sender) {
        $menu  = is_object($sender) ? $sender->Menu ?? null : null;
        if (is_object($menu) && $this->session->checkPermission('knowledge.kb.view')) {
            $menu->addLink('Help', t('Help Menu', 'Help'), '/kb/', false, ['class' => 'Knowledge']);
        }
    }

    /**
     * Add the knowledge base "Help" link to the DiscussionFilters menu.
     *
     * @param mixed $sender Sender object.
     */
    public function base_afterDiscussionFilters_handler($sender) {
        if ($this->session->checkPermission('knowledge.kb.view')) {
            echo '<li class="Knowledge">'.anchor(t('Help Menu', 'Help'), '/kb').'</li> ';
        }
    }

    /**
     * Add articles to search result schema.
     *
     * @param Schema $schema
     */
    public function searchResultSchema_init(Schema $schema) {
        $recordTypes = $schema->getField('properties.recordType.enum');
        $recordTypes[] = 'article';
        $schema->setField('properties.recordType.enum', $recordTypes);
        $types = $schema->getField('properties.type.enum');
        $types[] = 'article';
        $schema->setField('properties.type.enum', $types);
    }

    /**
     * Add knowledge base sitemap-index to robots.txt
     *
     * @param Robots $robots
     */
    public function robots_init(Robots $robots) {
        $robots->addSitemap('kb/sitemap-index.xml');
    }

    /**
     * Ensure the database is configured.
     */
    public function structure() {
        $router = \Gdn::getContainer()->get(\Gdn_Router::class);
        $router->setRoute(
            "kb/sitemap-kb\\.xml(\\.*)",
            "/kb/sitemap-kb/xml$1",
            "Internal"
        );
        $router->setRoute(
            "kb/sitemap-index\\.xml",
            "/kb/sitemap-index/xml",
            "Internal"
        );

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
            ->column("name", "varchar(255)")
            ->column("siteSectionGroup", "varchar(64)", DefaultSiteSection::DEFAULT_SECTION_GROUP)
            ->column("description", "text")
            // Size of this cannot be larger than 191 UT8-mb4 to be an index.
            ->column("urlCode", "varchar(191)", false, 'unique.urlCode')
            ->column("icon", "varchar(255)", ['Null' => false, 'Default' => ''])
            ->column("bannerImage", "varchar(255)", ['Null' => false, 'Default' => ''])
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
            ->column(
                "status",
                KnowledgeBaseModel::getAllStatuses(),
                KnowledgeBaseModel::STATUS_PUBLISHED,
                'index'
            )
            ->set();

        // Update knowledge baese when missing sourceLocale
        /* @var \Vanilla\Knowledge\Models\KnowledgeBaseModel $kbModel */
        $kbNoLocales = $this->kbModel->get(['sourceLocale' => '']);
        if (!empty($kbNoLocales)) {
            $this->kbModel->update(['sourceLocale' => \Gdn::locale()->current()], ['sourceLocale' => '']);
        }
    }
}
