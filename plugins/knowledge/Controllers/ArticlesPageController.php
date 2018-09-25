<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Models\ArticlesGetReduxAction;
use Vanilla\Knowledge\Models\Breadcrumb;

/**
 * Knowledge base Articles controller for article view.
 */
class ArticlesPageController extends KnowledgeTwigPageController {
    const ACTION_VIEW = 'view';
    const ACTION_ADD = 'add';
    const ACTION_EDIT = 'edit';

    /**
     * @var $action view | editor | add etc...
     */
    private $action;
    /**
     * @var int $articleId Article id of current action
     */
    private $articleId;

    /**
     * Render out the /kb/articles/title-slug-{id} :path page.
     *
     * @param string $path URI slug page action string.
     * @return string Returns HTML page content
     */
    public function index(string $path) : string {
        $this->action = self::ACTION_VIEW;
        $this->articleId = $id = $this->detectArticleId($path);

        $this->data[self::API_PAGE_KEY] = $this->articlesApi->get($id, ["expand" => "all"]);

        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs());

        // Put together pre-loaded redux actions.
        $articlesGetRedux = new ArticlesGetReduxAction($this->data[self::API_PAGE_KEY]);
        $reduxActions = [
            $articlesGetRedux->getReduxAction(),
        ];
        $this->addIlineScript($this->createInlineScriptContent("__ACTIONS__", $reduxActions));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }


    /**
     * Render out the /kb/articles/{id}/editor   path page.
     *
     * @param int $id URI article id.
     * @return string Returns HTML page content
     */
    public function get_editor(int $id) : string {
        $this->action = self::ACTION_EDIT;
        $this->articleId = $id;
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/'.$id.'/editor');
        }
        $this->data[self::API_PAGE_KEY] = $this->articlesApi->get($id, ["expand" => "all"]);
        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs());

        // Put together pre-loaded redux actions.
        $articlesGetRedux = new ArticlesGetReduxAction($this->data[self::API_PAGE_KEY]);
        $reduxActions = [
            $articlesGetRedux->getReduxAction(),
        ];
        $this->addIlineScript($this->createInlineScriptContent("__ACTIONS__", $reduxActions));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/add   path page.
     * @return string Returns HTML page content
     */
    public function get_add() : string {
        $this->action = self::ACTION_ADD;
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/add');
        }
        $this->data[self::API_PAGE_KEY] = [];
        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs());

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Get article id.
     *
     * @param string $path The path of the article.
     *
     * @return int Returns article id as int
     * @throws ClientException If the URL can't be parsed properly.
     */
    protected function detectArticleId(string $path) : int {
        $matches = [];
        if (preg_match('/^\/.*-(\d*)$/', $path, $matches) === 0) {
            throw new ClientException('Can\'t detect article id!', 400);
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
        $data['page'] = $this->data[self::API_PAGE_KEY] ?? [];
        $data['page']['classes'][] = 'isLoading';
        $data['page']['userSignedIn'] = $this->session->isValid();
        $data['page']['classes'][] = $data['page']['userSignedIn'] ? 'isSignedIn' : 'isSignedOut';
        return $data;
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
        if ($this->action === self::ACTION_VIEW) {
            $this->meta
                ->setSeo('title', $this->getApiPageData('seoName'))
                ->setSeo('description', $this->getApiPageData('seoDescription'));
        }
        $this->meta
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', $this->getData('breadcrumb-json'));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalLink() : string {
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
     * @return array
     */
    public function getApiPageData(string $key) {
        return $this->data[self::API_PAGE_KEY][$key] ?? '';
    }
    /**
     * Get Breadcrubs data array
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
