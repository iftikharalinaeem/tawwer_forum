<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Controllers\Api\ArticleRevisionsApiController;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ReduxAction;
use Vanilla\Knowledge\Models\Breadcrumb;
use Vanilla\Knowledge\Models\ReduxErrorAction;

/**
 * Knowledge base Articles controller for article view.
 */
class ArticlesPageController extends KnowledgeTwigPageController {
    const ACTION_VIEW = 'view';
    const ACTION_ADD = 'add';
    const ACTION_EDIT = 'edit';
    const ACTION_REVISIONS = 'revisions';

    /** @var ArticlesApiController */
    protected $articlesApi;

    /** @var ArticleRevisionsApiController */
    protected $revisionsApi;

    /**
     * ArticlesPageController constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct($container);
        $this->articlesApi = $this->container->get(ArticlesApiController::class);
        $this->revisionsApi = $this->container->get(ArticleRevisionsApiController::class);
    }

    /**
     * @var $action view | editor | add etc...
     */
    private $action;
    /**
     * @var int $articleId Article id of current action.
     */
    private $articleId;

    /**
     * Render out the /kb/articles/title-slug-{id} :path page.
     *
     * @param string $path URI slug page action string.
     * @return string Returns HTML page content
     */
    public function index(string $path) {
        $this->action = self::ACTION_VIEW;

        $this->articleId = $id = $this->detectArticleId($path);
        $article = $this->articlesApi->get($id, ["expand" => "all"]);
        $this->data[self::API_PAGE_KEY] = $article;
        $this->setPageTitle($article['articleRevision']['name'] ?? "");
        $this->setCategoryID($article["knowledgeCategoryID"]);

        // Put together pre-loaded redux actions.
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_ARTICLE_RESPONSE, Data::box($article)));
        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }


    /**
     * Render out the /kb/articles/{id}/editor path page.
     *
     * @param int $id URI article id.
     * @return string Returns HTML page content
     */
    public function get_editor(int $id): string {
        $this->action = self::ACTION_EDIT;
        $this->articleId = $id;
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/'.$id.'/editor');
        }

        $article = $this->articlesApi->get($id, ["expand" => "all"]);
        $this->setCategoryID($article["knowledgeCategoryID"]);

        // Set the title
        if (isset($article['articleRevision'])) {
            $this->setPageTitle($article['articleRevision']['name']);
        } else {
            $this->setPageTitle(\Gdn::translate('Untitled'));
        }

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/add path page.
     *
     * @return string Returns HTML page content.
     */
    public function get_add(): string {
        $this->action = self::ACTION_ADD;
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/add');
        }
        $this->data[self::API_PAGE_KEY] = [];
        $this->setPageTitle(\Gdn::translate('Untitled'));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/{id}/revisions path page.
     *
     * @param int $id URI article id.
     * @param int $revisionID URI revision ID.
     * @return string Returns HTML page content
     */
    public function get_revisions(int $id, $revisionID = null): string {
        $this->action = self::ACTION_REVISIONS;
        $this->articleId = $id;
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/'.$id.'/revisions');
        }
        $article = $this->articlesApi->get($id);
        $this->setCategoryID($article["knowledgeCategoryID"]);
        $revisions = $this->articlesApi->index_revisions($id);
        $this->data[self::API_PAGE_KEY][self::ACTION_REVISIONS] = $revisions;

        // Set the title
        $this->setPageTitle(($revisions[0]['name'] ?? 'Unknown'));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/articleRevisions.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }


    /**
     * Get article id.
     *
     * @param string $path The path of the article.
     *
     * @return int Returns article id as int.
     * @throws NotFoundException If the URL can't be parsed properly.
     */
    protected function detectArticleId(string $path): int {
        $matches = [];
        if (preg_match('/^\/(?<articleID>\d+)(-[^\/]*)?$/', $path, $matches) === 0) {
            throw new NotFoundException('Article');
        }

        $id = (int)$matches["articleID"];

        return $id;
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
        $data['page'] = $this->data[self::API_PAGE_KEY] ?? [];
        $data['page']['name'] = $this->data[self::API_PAGE_KEY]['articleRevision']['name'];
        $data['page']['bodyRendered'] = $this->data[self::API_PAGE_KEY]['articleRevision']['bodyRendered'];
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
        if ($this->action === self::ACTION_VIEW) {
            $this->meta
                ->setSeo('description', $this->getApiPageData('seoDescription'));
        }
        $this->meta
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', Breadcrumb::crumbsAsJsonLD($this->breadcrumbs()))
        ;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalLink(): string {
        $url = $this->canonicalUrl;
        if ($url === null) {
            switch ($this->action) {
                case self::ACTION_VIEW:
                    if ($apiUrl = $this->data[self::API_PAGE_KEY]['url'] ?? false) {
                        $url = $apiUrl;
                    } else {
                        $url = \Gdn::request()->url('/kb/articles/-'.$this->articleId, true);
                    }
                    break;
                case self::ACTION_EDIT:
                    $url = \Gdn::request()->url('/kb/articles/'.$this->articleId.'/editor', true);
                    break;
                case self::ACTION_ADD:
                    $url = \Gdn::request()->url('/kb/articles/add', true);
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
    public function getApiPageData(string $key) {
        return $this->data[self::API_PAGE_KEY][$key] ?? '';
    }
}
