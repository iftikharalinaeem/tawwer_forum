<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Garden\CustomExceptionHandler;
use Garden\Web\Exception\NotFoundException;
use Vanilla\InjectableInterface;
use Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeNavigationApiController;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Knowledge\Models\ReduxErrorAction;
use Vanilla\Exception\PermissionException;
use Garden\Web\Data;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Knowledge\Models\ReduxAction;
use Vanilla\Models\SiteMeta;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\Asset\WebpackAssetProvider;

/**
 * Knowledge Twig & ArticlesApi controller abstract class.
 *
 * This controller expects most content to come from api
 */
abstract class KnowledgeTwigPageController extends PageController implements CustomExceptionHandler, InjectableInterface {
    use \Garden\TwigTrait;

    const API_PAGE_KEY = 'page';

    /** @var int Category of the current page. Used for breadcrumbs. */
    private $categoryID;

    /** @var BreadcrumbModel */
    protected $breadcrumbModle;

    /** @var KnowledgeBasesApiController $kbApi */
    protected $kbApi;

    /** @var SiteMeta */
    protected $siteMeta;

    /** @var mixed Gdn_Session */
    protected $session;

    /** @var KnowledgeCategoriesApiController */
    protected $categoriesApi;

    /** @var KnowledgeNavigationApiController */
    protected $navigationApi;

    /** @var \UsersApiController */
    protected $usersApi;

    /** @var BreadcrumbModel */
    protected $breadcrumbModel;

    /**
     * KnowledgeTwigPageController constructor.
     */
    public function __construct() {
        parent::__construct();
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
    }


    /**
     * Dependency Injection that we child controllers to need to implement.
     *
     * @param BreadcrumbModel $breadcrumbModel
     * @param KnowledgeCategoriesApiController $categoriesApi
     * @param KnowledgeBasesApiController $kbApi
     * @param KnowledgeNavigationApiController $navigationApi
     * @param \UsersApiController $usersApi
     * @param SiteMeta $siteMeta
     * @param \Gdn_Session $session
     * @param WebpackAssetProvider $assetProvider
     */
    public function setDependencies(
        BreadcrumbModel $breadcrumbModel,
        KnowledgeCategoriesApiController $categoriesApi,
        KnowledgeBasesApiController $kbApi,
        KnowledgeNavigationApiController $navigationApi,
        \UsersApiController $usersApi,
        SiteMeta $siteMeta,
        \Gdn_Session $session,
        WebpackAssetProvider $assetProvider
    ) {
        $this->breadcrumbModel = $breadcrumbModel;
        $this->categoriesApi = $categoriesApi;
        $this->kbApi = $kbApi;
        $this->navigationApi = $navigationApi;
        $this->usersApi = $usersApi;
        $this->siteMeta = $siteMeta;
        $this->session = $session;
        $this->initAssets($assetProvider);
    }

    /**
     * Initialize assets from the asset provide.
     *
     * @param WebpackAssetProvider $assetProvider
     */
    private function initAssets(WebpackAssetProvider $assetProvider) {
        $this->inlineScripts = [$assetProvider->getInlinePolyfillContents()];

        $mapAssetToPath = function (AssetInterface $asset) {
            return $asset->getWebPath();
        };
        $this->scripts = array_map($mapAssetToPath, $assetProvider->getScripts('knowledge'));
        $this->styles = array_map($mapAssetToPath, $assetProvider->getStylesheets('knowledge'));

        $this->addGdnScript();
        $this->addGlobalReduxActions();
    }

    /**
     * @inheritdoc
     */
    public function hasHandler(\Throwable $e): bool {
        switch ($e->getCode()) {
            case 404:
            case 403:
                return true;
                break;
            default:
                return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function handle(\Throwable $e): Data {
        if ($e instanceof NotFoundException
            || $e instanceof PermissionException) {
            return $this->errorPage($e);
        }

        return new Data();
    }

    /**
     * Render twig-redux error page
     *
     * @param \Throwable $e
     * @return Data Return Garden\Web\Data object to global dispatcher.
     */
    protected function errorPage(\Throwable $e): Data {
        $status = $e->getCode();
        $reduxAction = new ReduxErrorAction(ActionConstants::PAGE_ERROR, new Data($e));
        $this->addReduxAction($reduxAction);
        $this->setPageTitle($e->getMessage());
        $this->meta->setTag('robots', ['name' => 'robots', 'content' => 'noindex']);
        $data = $this->getViewData();
        $data[self::API_PAGE_KEY]['status'] = $e->getCode();
        $data[self::API_PAGE_KEY]['message'] = $e->getMessage();
        $data['template'] = 'seo/pages/error.twig';

        return new Data($this->twigInit()->render('default-master.twig', $data), $status);
    }

    /** @var array An array of all the knowledge bases of the site.*/
    protected $knowledgeBases;

    /**
     * Preload redux actions that are present on every page.
     */
    private function addGlobalReduxActions() {
        $categories = $this->categoriesApi->index();
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_ALL_CATEGORIES, Data::box($categories)));

        $me = $this->usersApi->get_me([]);
        $this->addReduxAction(new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($me)));

        $this->knowledgeBases = $this->kbApi->index();
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ALL_KBS,
            Data::box($this->knowledgeBases),
            []
        ));
    }

    /**
     * Preload the a redux response for a knowledge bases navData.
     *
     * @param int $knowledgeBaseID
     */
    public function preloadNavigation(int $knowledgeBaseID) {
        $options = ['knowledgeBaseID' => $knowledgeBaseID];
        $navigation = $this->navigationApi->get_flat($options);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_NAVIGATION_FLAT,
            Data::box($navigation),
            $options
        ));
    }

    /**
     * Add the an inline script for global context to the frontend.
     */
    private function addGdnScript() {
        $gdnData = [
            'meta' => $this->siteMeta,
        ];

        $this->addInlineScript($this->createInlineScriptContent("gdn", $gdnData));
    }

    /**
     * Get page's breadcrumb trail.
     *
     * @return array
     * @throws \Exception If attempting to generate a URL for an invalid category row.
     */
    protected function breadcrumbs(): array {
        $categoryRecordType = new KbCategoryRecordType($this->getCategoryID());
        return $this->breadcrumbModel->getForRecord($categoryRecordType);
    }

    /**
     * Get the ID of the current page's category.
     *
     * @return int|null
     */
    protected function getCategoryID() {
        return $this->categoryID;
    }

    /**
     * Set the ID of the current page's category.
     *
     * @param int $categoryID
     * @return KnowledgeTwigPageController
     */
    protected function setCategoryID(int $categoryID): self {
        $this->categoryID = $categoryID;

        return $this;
    }

    /**
     * Set the page title (in the browser tab).
     *
     * @param string $title The title to set.
     * @param bool $withSiteTitle Whether or not to append the global site title.
     */
    public function setPageTitle(string $title, bool $withSiteTitle = true) {
        if ($withSiteTitle) {
            if ($title === "") {
                $title = $this->siteMeta->getSiteTitle();
            } else {
                $title .= " - " . $this->siteMeta->getSiteTitle();
            }
        }
        $this->meta->setSeo('title', $title);
    }

    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    public function getWebViewResources(): array {
        return [
            'debug' => \Gdn::config('Debug'),
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
            'meta' => $this->meta->getPageMeta(),
        ];
    }

    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    abstract protected function getViewData(): array;
}
