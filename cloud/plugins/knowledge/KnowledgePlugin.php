<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge;

use Garden\Container\Reference;
use Vanilla\Adapters\SphinxClient;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;
use Vanilla\Knowledge\Controllers\KbPageRoutes;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KbBreadcrumbProvider;
use Vanilla\Knowledge\Models\KnowledgeArticleSearchType;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;
use Vanilla\Knowledge\Models\SearchRecordTypeArticleDeleted;
use Vanilla\Models\ModelFactory;
use Vanilla\Search\AbstractSearchDriver;
use Vanilla\THeme\ThemeSectionModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\Robots;
use Gdn_Session as SessionInterface;
use Vanilla\Theme\ThemeService;
use Vanilla\Knowledge\Models\KnowledgeVariablesProvider;
use Vanilla\Knowledge\Models\SearchRecordTypeArticle;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleDraftCounterProvider;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\SmartIDMiddleware;

/**
 * Primary class for the Knowledge class, mostly responsible for pluggable operations.
 */
class KnowledgePlugin extends \Gdn_Plugin {

    const NAV_SECTION = "knowledge";

    /** @var SessionInterface */
    private $session;

    /** @var SiteSectionModel $siteSectionModel */
    private $siteSectionModel;

    /**
     * KnowledgePlugin constructor.
     *
     * @param SessionInterface $session
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        SessionInterface $session,
        SiteSectionModel $siteSectionModel
    ) {
        parent::__construct();
        $this->session = $session;
        $this->siteSectionModel = $siteSectionModel;
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
            ->rule(ThemeService::class)
            ->addCall("addVariableProvider", [new Reference(KnowledgeVariablesProvider::class)])
            ->rule(SearchRecordTypeProviderInterface::class)
            ->addCall('setType', [new SearchRecordTypeArticle()])
            ->addCall('setType', [new SearchRecordTypeArticleDeleted()])
            ->rule(\Vanilla\Contracts\Site\ApplicationProviderInterface::class)
            ->addCall('add', [new Reference(\Vanilla\Site\Application::class, ['knowledge-base', ['kb']])])

            ->rule(\Vanilla\Menu\CounterModel::class)
            ->addCall('addProvider', [new Reference(ArticleDraftCounterProvider::class)])
        ;
        $container->rule(TranslationProviderInterface::class)
            ->addCall('initializeResource', [new Reference(KnowledgeTranslationResource::class)])
            ->rule('@smart-id-middleware')
            ->addCall('addSmartID', ['knowledgeBaseID', 'knowledge-bases', ['foreignID'], 'knowledgeBase'])
            ->addCall('addSmartID', ['knowledgeCategoryID', 'knowledge-categories', ['foreignID'], 'knowledgeCategory'])
            ->addCall('addSmartID', ['parentID', 'knowledge-categories', ['foreignID'], [$this, 'parentSmartIDResolver']])
            ->addCall('addSmartID', ['articleID', 'articles', ['foreignID'], 'article'])
        ;
        $container->rule(AbstractSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(KnowledgeArticleSearchType::class)]);

        $mf = ModelFactory::fromContainer($container);
        $mf->addModel('article', ArticleModel::class, 'a');
    }

    /**
     * ParentID resolver.
     *
     * @param SmartIDMiddleware $sender
     * @param string $pk
     * @param string $column
     * @param string $value
     * @return mixed
     */
    public function parentSmartIDResolver(SmartIDMiddleware $sender, string $pk, string $column, string $value) {
        return $sender->fetchValue('knowledgeCategory', 'knowledgeCategoryID', [$column => $value]);
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
     * Run the Kb structure.
     *
     * This is instantiated lazily to prevent early DI injection of unneeded resources.
     */
    public function structure() {
        /** @var KnowledgeStructure $structure */
        $structure = \Gdn::getContainer()->get(KnowledgeStructure::class);
        $structure->run();
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
            ->addLink(self::NAV_SECTION, t('Knowledge Bases'), '/knowledge-settings/knowledge-bases', 'Garden.Settings.Manage');

        // CANNOT be DIed or it causes initializes the features too early.
        if (!\Gdn::themeFeatures()->disableKludgedVars()) {
            $navCollection->addLink(self::NAV_SECTION, t('General Appearance'), '/knowledge-settings/general-appearance', 'Garden.Settings.Manage');
        }
    }

    /**
     * Add the knowledge base "Help" link to the main menu.
     *
     * @param \Gdn_Controller $sender Sender object.
     */
    public function base_render_before($sender) {
        $menu  = is_object($sender) ? $sender->Menu ?? null : null;
        $kbEnabled = $this->siteSectionModel->getCurrentSiteSection()->applicationEnabled(SiteSectionInterface::APP_KB);
        if ($kbEnabled) {
            if (is_object($menu) && $this->session->checkPermission('knowledge.kb.view')) {
                $menu->addLink('Help', t('Help Menu', 'Help'), '/kb/', false, ['class' => 'Knowledge']);
            }
        }
    }

    /**
     * Add the knowledge base "Help" link to the DiscussionFilters menu.
     *
     * @param mixed $sender Sender object.
     */
    public function base_afterDiscussionFilters_handler($sender) {
        if ($this->session->checkPermission('knowledge.kb.view')) {
            echo '<li class="Knowledge">'.anchor(sprite('SpKnowledge').' '.t('Help Menu', 'Help'), '/kb').'</li> ';
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
     * Set filters for search endpoint.
     *
     * @param SphinxClient $sphinx
     * @param array $args
     */
    public function searchModel_setKnowledgeFilters_handler(SphinxClient $sphinx, array $args) {
        if ($args['knowledgebaseid'] ?? []) {
            $args['knowledgeBaseID'] = $args['knowledgebaseid'];
        }
        if ($args['sitesectiongroup'] ?? []) {
            $args['siteSectionGroup'] = $args['sitesectiongroup'];
        }

        $types = $args['types'] ?? [];
        $hasKbType = false;
        foreach ($types as $type) {
            if ($type instanceof SearchRecordTypeArticle || $type instanceof SearchRecordTypeArticleDeleted) {
                $hasKbType = true;
                break;
            }
        }

        if (!$hasKbType) {
            return $sphinx;
        }

        /** @var KnowledgeApiController $knowledgeApiController */
        $knowledgeApiController = \Gdn::getContainer()->get(KnowledgeApiController::class);
        $sphinx = $knowledgeApiController->applySearchFilters($sphinx, $args);
        return $sphinx;
    }

    /**
     * Add knowledge base sitemap-index to robots.txt
     *
     * @param Robots $robots
     */
    public function robots_init(Robots $robots) {
        $robots->addSitemap('kb/sitemap-index.xml');
    }
}
