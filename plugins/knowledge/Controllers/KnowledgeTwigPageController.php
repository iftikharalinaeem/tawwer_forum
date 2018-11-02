<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Garden\Web\Data;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Knowledge\Models\Breadcrumb;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\ReduxAction;
use Vanilla\Knowledge\Models\SiteMeta;
use Vanilla\Web\Asset\WebpackAssetProvider;

/**
 * Knowledge Twig & ArticlesApi controller abstract class.
 *
 * This controller expects most content to come from api
 */
abstract class KnowledgeTwigPageController extends PageController {
    use \Garden\TwigTrait;

    const API_PAGE_KEY = 'page';

    /** @var int Category of the current page. Used for breadcrumbs. */
    private $categoryID;

    /** @var KnowledgeCategoryModel */
    protected $knowledgeCategoryModel;

    /** @var Container */
    protected $container;

    /** @var SiteMeta */
    protected $siteMeta;

    /**
     * @var mixed Gdn_Session
     */
    protected $session;

    /**
     * KnowledgeTwigPageController constructor.
     *
     * @param Container $container Interface to DI container.
     */
    public function __construct(Container $container) {
        parent::__construct();
        $this->container = $container;
        $this->knowledgeCategoryModel = $container->get(KnowledgeCategoryModel::class);
        $this->siteMeta = $container->get(SiteMeta::class);
        $this->session = $this->container->get(\Gdn_Session::class);
        /** @var WebpackAssetProvider $assetProvider */
        $assetProvider = $this->container->get(WebpackAssetProvider::class);
        self::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';

        $this->inlineScripts = [$assetProvider->getInlinePolyfillContents()];

        $mapAssetToPath = function(AssetInterface $asset) { return $asset->getWebPath(); };
        $this->scripts = array_map($mapAssetToPath, $assetProvider->getScripts('knowledge'));
        $this->styles = array_map($mapAssetToPath, $assetProvider->getStylesheets('knowledge'));

        $this->addGdnScript();
        $this->scripts[] = $assetProvider->getLocaleAsset()->getWebPath();
        $this->addGlobalReduxActions();

    }

    /**
     * Preload redux actions that are present on every page.
     */
    private function addGlobalReduxActions() {
        /** @var KnowledgeCategoriesApiController $categoriesApi */
        $categoriesApi = $this->container->get(KnowledgeCategoriesApiController::class);
        $categories = $categoriesApi->index();
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_ALL_CATEGORIES, Data::box($categories)));

        /** @var \UsersApiController $usersApi */
        $usersApi = $this->container->get(\UsersApiController::class);
        $me = $usersApi->get_me([]);
        $this->addReduxAction(new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($me)));
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
        $categoryID = $this->getCategoryID();
        $result = [];

        if ($categoryID) {
            $categories = $this->knowledgeCategoryModel->selectWithAncestors($categoryID);
            $index = 1;
            foreach ($categories as $category) {
                $result[$index++] = new Breadcrumb(
                    $category["name"],
                    $this->knowledgeCategoryModel->url($category)
                );
            }
        }

        return $result;
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
    public function getWebViewResources() : array {
        return [
            'debug' => \Gdn::config('Debug'),
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
            'meta' => $this->meta->getPageMeta(),
        ];
    }
}
