<?php
/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiActions;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Knowledge\Models\ReduxAction;
use Vanilla\Knowledge\Models\Breadcrumb;

/*
 * Knowledge base Categories controller for article view.
 */
class CategoriesPageController extends KnowledgeTwigPageController {
    const ACTION_VIEW_ARTICLES = 'view';
    const CATEGORY_API_RESPONSE = 'category';

    /** @var CategoriesApiController */
    protected $categoriesApi;
    /** @var ArticlesApiController */
    protected $articlesApi;

    /**
     * CategoriesPageController constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct($container);
        $this->categoriesApi = $this->container->get(KnowledgeCategoriesApiController::class);
        $this->articlesApi = $this->container->get(ArticlesApiController::class);
    }

    /**
     * @var $action view | edit | add | delete etc...
     */
    private $action;
    /**
     * @var int $articleId Article id of current action.
     */
    private $categoryId;

    /**
     * Render out the /kb/categories/{id}-title-slug :path page.
     *
     * @param string $path URI slug page action string.
     * @return string Returns HTML page content
     */
    public function index(string $path) : string {
        $this->action = self::ACTION_VIEW_ARTICLES;
        $this->categoryId = $id = $this->detectCategoryId($path);
        $this->data[self::CATEGORY_API_RESPONSE] = $this->categoriesApi->get($id);
        //$this->data[self::API_PAGE_KEY] = $this->articlesApi->index(['KnowledgeCategoryID'=>$id]);

        // Put together pre-loaded redux actions.
//        $categoriesGetRedux = new ReduxAction(ArticlesApiActions::GET_CATEGORY_RESPONSE, $this->data[self::CATEGORY_API_RESPONSE]);
//        $articlesGetRedux = new ReduxAction(ArticlesApiActions::GET_ARTICLE_RESPONSE, $this->data[self::API_PAGE_KEY]);
//        $reduxActions = [
//            $categoriesGetRedux->getReduxAction(),
//            $articlesGetRedux->getReduxAction(),
//        ];
//        $this->addInlineScript($this->createInlineScriptContent("__ACTIONS__", $reduxActions));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/category.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }


    /**
     * Get category id.
     *
     * @param string $path The path of the category.
     *
     * @return int Returns article id as int.
     * @throws ClientException If the URL can't be parsed properly.
     */
    protected function detectCategoryId(string $path) : int {
        $matches = [];
        if (preg_match('/^\/(\d*)-.*/', $path, $matches) === 0) {
            throw new ClientException('Can\'t detect category id!', 400);
        }

        $id = (int)$matches[1];

        return $id;
    }
    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    private function getViewData() : array {
        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);
        $data = $this->getWebViewResources();
        $data['page'][self::CATEGORY_API_RESPONSE] = $this->data[self::CATEGORY_API_RESPONSE];
        $data['page'] = $this->data[self::API_PAGE_KEY] ?? [];
        $data['page']['classes'][] = 'isLoading';
        $data['page']['userSignedIn'] = $this->session->isValid();
        $data['page']['classes'][] = $data['page']['userSignedIn'] ? 'isSignedIn' : 'isSignedOut';
        return $data;
    }
    /**
     * Initialize page SEO meta data.
     *
     * (temporary solution, need to be extended and/or refactored later).
     *
     * @return $this
     */
    public function setSeoMetaData() {
        $this->meta
            ->setLink('canonical', ['rel' => 'canonical', 'href' => $this->getCanonicalLink()]);
        if ($this->action === self::ACTION_VIEW_ARTICLES) {
            $this->meta
                ->setSeo('title', $this->getCategoryApiPageData('seoName'))
                ->setSeo('description', $this->getCategoryApiPageData('seoDescription'));
        }
        $this->meta
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs()));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalLink() : string {
        $url = $this->canonicalUrl;
        if ($url === null) {
            switch ($this->action) {
                case self::ACTION_VIEW_ARTICLES:
                    if ($apiUrl = $this->data[self::CATEGORY_API_RESPONSE]['url'] ?? false) {
                        $url = $apiUrl;
                    } else {
                        $url = \Gdn::request()->url('/kb/categories/'.$this->articleId.'-', true);
                    }
                    break;
                default:
                    $url = \Gdn::request()->url('/', true);
            }
            $this->canonicalUrl = $url;
        }
        return $url;
    }

    /**
     * Get the page data from api response array
     *
     * @param string $key Data key to get
     *
     * @return string
     */
    public function getCategoryApiPageData(string $key) {
        return $this->data[self::CATEGORY_API_RESPONSE][$key] ?? '';
    }
    /**
     * Get Breadcrumbs data array
     * This is temporary implementation need to be refactored
     *
     * @return array
     */
    public function getBreadcrumbs(): array {
        return [
            new Breadcrumb('Home', \Gdn::request()->url('/', true)),
            new Breadcrumb('Knowledge', \Gdn::request()->url('/kb/', true)),
            new Breadcrumb('Knowledge', $this->getCanonicalLink()),
        ];
    }
}
