<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Vanilla\Knowledge\Models\Breadcrumb;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Knowledge base controller for article view.
 */
class KbRootController extends KnowledgeTwigPageController {

    /** @var KnowledgeBasesApiController */
    private $basesApi;

    /** @var ArticlesPageController */
    private $articlesPageController;

    /** @var SearchPageController */
    private $searchPageController;

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $basesApi
     * @param ArticlesPageController $articlesPageController
     * @param SearchPageController $searchPageController
     */
    public function __construct(
        KnowledgeBasesApiController $basesApi,
        ArticlesPageController $articlesPageController,
        SearchPageController $searchPageController
    ) {
        parent::__construct();
        $this->basesApi = $basesApi;
        $this->articlesPageController = $articlesPageController;
        $this->searchPageController = $searchPageController;
    }


    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    protected function getViewData(): array {
        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);
        $data = $this->getWebViewResources();
        return $data;
    }

    /**
     * Render out the /kb page.
     */
    public function index() : string {
        // Temporarily use the search page instead of the knowledge base homepage.
        return $this->searchPageController->index();
    }

    /**
     * Render out the /kb/debug page.
     */
    public function get_debug(): string {
        $this->setPageTitle(\Gdn::translate('Debug - Internal Links'));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['page']['classes'][] = 'isLoading';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out a knowledge base homepage.
     *
     * Routing for /kb/:slug
     *
     * @param string $path The knowledge base slug.
     *
     * @return string
     */
    public function get(string $path): string {
        $urlCode = ltrim($path, "/");
        $knowledgeBase = $this->basesApi->get_byUrlCode(['urlCode' => $urlCode]);

        switch ($knowledgeBase['viewType']) {
            case KnowledgeBaseModel::TYPE_HELP:
                // Temporarily use the search page instead of the knowledge base homepage.
                return $this->searchPageController->index();
            case KnowledgeBaseModel::TYPE_GUIDE:
                $articleID = $knowledgeBase['defaultArticleID'];
                if ($articleID === null) {
                    return $this->noArticlesPage();
                } else {
                    $articleController = $this->articlesPageController;
                    $articleController->preloadNavigation($knowledgeBase['knowledgeBaseID']);
                    return $articleController->index("/$articleID");
                }
        }
    }

    /**
     * No articles Page.
     */
    private function noArticlesPage() {
        $this->setPageTitle('No Articles Created Yet');
        $data = $this->getViewData();
        $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/{id}/organize-categories page.
     *
     * @param int $id Knowledge base ID
     *
     * @return string
     */
    public function get_organizeCategories(int $id): string {
        $this->setPageTitle(\Gdn::translate('Organize Categories'));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['page']['classes'][] = 'isLoading';
        $data['template'] = 'seo/pages/organize-categories.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Initialize page SEO meta data.
     *
     * (temporary solution, need to be extended and/or refactored later)
     *
     * @return $this
     */
    public function setSeoMetaData() {
        $this->meta
            ->setLink('canonical', ['rel' => 'canonical', 'href' => $this->getCanonicalLink()]);
        $this->meta
            ->setSeo('title', $this->data['title'] ?? 'Knowledge')
            ->setSeo('description', $this->data['description'] ?? 'Knowledge Base')
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs()));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalLink() : string {
        return \Gdn::request()->url('/kb/', true);
    }

    /**
     * Get Breadcrubs data array
     *
     * @return array
     */
    public function getBreadcrumbs(): array {
        return [
            new Breadcrumb('Home', \Gdn::request()->url('/', true)),
            new Breadcrumb('Knowledge', $this->getCanonicalLink()),
        ];
    }
}
