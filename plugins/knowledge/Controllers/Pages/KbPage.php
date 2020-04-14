<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Exception;
use Garden\EventManager;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Knowledge\Models\SearchJsonLD;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\ThemePreloadProvider;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Web\JsInterpop\ReduxErrorAction;
use Vanilla\Web\MasterViewRenderer;
use Vanilla\Web\PageHead;
use Vanilla\Web\ThemedPage;
use Vanilla\Contracts\Analytics\ClientInterface as AnalyticsClient;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;

/**
 * Base knowledge base page.
 */
abstract class KbPage extends ThemedPage {

    /** Regex pattern for retrieving the record ID from a URL path. */
    protected const ID_PATH_PATTERN = "/^\/(?<recordID>\d+)(-[^\/]*)?/";

    protected const PAGE_PATH_PATTERN = "/.*\/p(?<pageNumber>\d+)$/";

    const TWIG_VIEWS_PATH = 'plugins/knowledge/views/';

    /** @var AnalyticsClient */
    protected $analyticsClient;

    /** @var \UsersApiController */
    protected $usersApi;

    /** @var KnowledgeBasesApiController */
    protected $kbApi;

    /** @var KnowledgeApiController */
    protected $rootApi;

    /** @var KnowledgeCategoriesApiController */
    protected $categoriesApi;

    /** @var DeploymentCacheBuster */
    public $deploymentCacheBuster;

    /** @var array */
    protected $knowledgeBases;

    /** @var SiteSectionModel */
    protected $siteSectionModel;

    /** @var KnowledgeBaseModel $kbModel */
    protected $kbModel;

    protected $breadcrumbModel;

    /** @var bool */
    private $siteSectionValidated = false;

    /**
     * @inheritdoc
     */
    public function getAssetSection(): string {
        return 'knowledge';
    }

    /**
     * @inheritdoc
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        PageHead $pageHead,
        MasterViewRenderer $masterViewRenderer,
        ThemePreloadProvider $themePreloadProvider = null, // Default needed for method extensions
        BreadcrumbModel $breadcrumbModel = null,
        \UsersApiController $usersApi = null, // Default needed for method extensions
        KnowledgeApiController $rootApi = null, // Default needed for method extensions
        KnowledgeBasesApiController $kbApi = null, // Default needed for method extensions
        KnowledgeCategoriesApiController $categoriesApi = null, // Default needed for method extensions
        DeploymentCacheBuster $deploymentCacheBuster = null, // Default needed for method extensions
        AnalyticsClient $analyticsClient = null, // Default needed for method extensions
        SiteSectionModel $siteSectionModel = null, // Default needed for method extensions
        KnowledgeBaseModel $kbModel = null // Default needed for method extensions
    ) {
        parent::setDependencies(
            $siteMeta,
            $request,
            $session,
            $pageHead,
            $masterViewRenderer,
            $themePreloadProvider
        );
        $this->breadcrumbModel = $breadcrumbModel;
        $this->usersApi = $usersApi;
        $this->rootApi = $rootApi;
        $this->kbApi = $kbApi;
        $this->categoriesApi = $categoriesApi;
        $this->deploymentCacheBuster = $deploymentCacheBuster;
        $this->analyticsClient = $analyticsClient;
        $this->siteSectionModel = $siteSectionModel;
        $this->kbModel = $kbModel;

        // Shared initialization.
        $this->initSharedData();
    }

    /**
     * Initialize assets from the asset provide.
     */
    protected function initAssets() {
        $this->addJsonLDItem(new SearchJsonLD($this->request));
        parent::initAssets();
    }

    /**
     * Override render to ensure we've validated our site section.
     * @inheritdoc
     */
    public function render(): Data {
        $kbEnabled = $this->siteSectionModel->getCurrentSiteSection()->applicationEnabled(SiteSectionInterface::APP_KB);
        if (!$kbEnabled) {
            throw new NotFoundException();
        }
        if ($this->siteMeta->getDebugModeEnabled() && !$this->siteSectionValidated) {
            throw new ServerException(
                "Site Section must be validated.",
                500,
                ["description" => "User either `validateSiteSection()` or `disableSiteSectionValidation()`"]
            );
        }
        return parent::render();
    }

    /**
     * Disable site section validation for the page.
     *
     * @return $this
     */
    protected function disableSiteSectionValidation(): KbPage {
        $this->siteSectionValidated = true;
        return $this;
    }

    /**
     * Validate that a knowledge base has a correct site section for request.
     *
     * @param int $kbID The knowledge base ID to validate the current site section against.
     *
     * @return $this
     */
    protected function validateSiteSection(int $kbID): KbPage {
        $this->siteSectionValidated = true;
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
        if ($currentSiteSection instanceof DefaultSiteSection) {
            // Any knowledge base is allowed in the default site section to prevent broken URLs.
            return $this;
        }

        try {
            $knowledgeBase = $this->kbModel->selectSingle(["knowledgeBaseID" => $kbID]);
        } catch (NoResultsException $e) {
            // Rethrow as a more generic exception.
            throw new NotFoundException();
        }

        if ($knowledgeBase["siteSectionGroup"] !== $currentSiteSection->getSectionGroup()) {
            // The knowledge base doesn't exist in this site section group.
            throw new NotFoundException();
        }

        return $this;
    }

    /**
     * Add global redux actions that apply to any /kb page.
     */
    private function initSharedData() {

        $currentSection = $this->siteSectionModel->getCurrentSiteSection();
        $kbArgs = [
            'siteSectionGroup' => $currentSection->getSectionGroup(),
            'expand' => 'all',
            'locale' => $currentSection->getContentLocale()
        ];
        if ($currentSection instanceof DefaultSiteSection) {
            unset($kbArgs['siteSectionGroup']);
        }

        try {
            $me = $this->usersApi->get_me([]);
            $this->addReduxAction(new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($me), []));

            $permissions = $this->usersApi->get_permissions($this->session->UserID);
            $this->addReduxAction(
                new ReduxAction(\UsersApiController::PERMISSIONS_ACTION_CONSTANT, Data::box($permissions), [])
            );

            $this->knowledgeBases = $this->kbApi->index($kbArgs);
            $this->addReduxAction(new ReduxAction(
                ActionConstants::GET_ALL_KBS,
                Data::box($this->knowledgeBases),
                $kbArgs
            ));

            $this->addReduxAction(new ReduxAction(
                ActionConstants::SET_LOCAL_DEPLOYMENT_KEY,
                new Data($this->deploymentCacheBuster->value()),
                []
            ));

            if ($this->analyticsClient !== null) {
                $this->addReduxAction(new ReduxAction(
                    \Vanilla\Analytics\ActionConstants::GET_CONFIG,
                    new Data($this->analyticsClient->config()),
                    []
                ));

                $this->addReduxAction(new ReduxAction(
                    \Vanilla\Analytics\ActionConstants::GET_EVENT_DEFAULTS,
                    new Data($this->analyticsClient->eventDefaults()),
                    []
                ));
            }
        } catch (\Exception $e) {
            $this->addReduxAction(new ReduxErrorAction($e));
        }
    }

    /**
     * Preload some list of articles from the search endpoint.
     *
     * @param array $params Parameters to pass to the search endpoint.
     */
    protected function preloadArticleList(array $params) {
        try {
            $result = $this->rootApi->get_search($params);

            $this->addReduxAction(new ReduxAction(ActionConstants::GET_ARTICLE_LIST, Data::box([
                'body' => $result,
                'pagination' => $result->getMeta('paging')
            ]), $params));
        } catch (Exception $e) {
            $this->addReduxAction(new ReduxAction(
                ActionConstants::GET_ARTICLE_LIST_FAILED,
                Data::box([
                    'error' => ['message' => $e->getMessage()],
                    'params' => $params
                ]),
                null,
                true
            ));
        }
    }

    /**
     * Preload the a redux response for a knowledge bases navData.
     *
     * @param int $knowledgeBaseID
     */
    protected function preloadNavigation(int $knowledgeBaseID) {
        $options = [
            "knowledgeBaseID" => $knowledgeBaseID,
            "locale" => $this->siteSectionModel->getCurrentSiteSection()->getContentLocale(),
        ];


        $navigation = $this->kbApi->get_navigationFlat($knowledgeBaseID, $options);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_NAVIGATION_FLAT,
            Data::box($navigation),
            $options
        ));
    }

    /**
     * Set the SEO breadcrumbs based off of our knowledge base ID.
     *
     * @param int $knowledgeBaseID
     * @param string $locale
     *
     * @return $this Own instance for chaining.
     */
    protected function setSeoCrumbsForCategory(int $knowledgeBaseID, string $locale): self {
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($knowledgeBaseID), $locale);
        $this->setSeoBreadcrumbs($crumbs);
        return $this;
    }

    /**
     * Render a twig template from the knowledge base views directory.
     *
     * @param string $path The view path.
     * @param array $data Data to render the view with.
     *
     * @return string The rendered view.
     */
    protected function renderKbView(string $path, array $data): string {
        return $this->renderTwig(self::TWIG_VIEWS_PATH . $path, $data);
    }


    /**
     * Parse an ID out of a path with the formulation /:id-:slug.
     * -:slug is optional.
     *
     * @param string|null $path The path to parse.
     *
     * @return int|null The parsed ID or null.
     * @internal Marked public for testing.
     */
    public function parseIDFromPath(?string $path): ?int {
        if (!$path) {
            return null;
        }

        $matches = [];
        if (preg_match(static::ID_PATH_PATTERN, $path, $matches) === 0) {
            return null;
        }

        $id = filter_var($matches["recordID"], FILTER_VALIDATE_INT);

        if ($id === false) {
            return null;
        }

        return $id;
    }

    /**
     * Get the page number out of some path.
     *
     * Works in the following format:
     * "/path/some/path" -> 1
     * "/path/some/path/p2" -> 2
     * "/path/some/path/p142" -> 142
     *
     * @param string|null $path
     *
     * @return int
     * @internal Marked public for testing.
     */
    public function parsePageNumberFromPath(?string $path): int {
        if (!$path) {
            return 1;
        }

        $matches = [];
        if (preg_match(static::PAGE_PATH_PATTERN, $path, $matches) === 0) {
            return 1;
        }

        $pageNumber = filter_var($matches["pageNumber"], FILTER_VALIDATE_INT);

        if ($pageNumber === false) {
            return 1;
        }

        return $pageNumber;
    }
}
