<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiActions;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\DummyBreadcrumbTrait;
use Vanilla\Knowledge\Models\Breadcrumb;

/**
 * Knowledge base Articles controller for article view.
 */
class ArticlesPageController extends PageController {
    use \Garden\TwigTrait;
    use DummyBreadcrumbTrait;

    /** @var ArticlesApiController */
    private $articlesApi;

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel AssetModel To get js and css.
     * @param ArticlesApiController $articlesApiController To fetch article resources.
     * @param \Gdn_Session $session Session DI object
     */
    public function __construct(
        \AssetModel $assetModel,
        ArticlesApiController $articlesApiController,
        \Gdn_Session $session
    ) {
        parent::__construct();
        $this->session = $session;
        $this->articlesApi = $articlesApiController;
        $this->inlineScripts = [$assetModel->getInlinePolyfillJSContent()];
        $this->scripts = $assetModel->getWebpackJsFiles('knowledge');
        if (\Gdn::config('HotReload.Enabled', false) === false) {
            $this->styles = ['/plugins/knowledge/js/webpack/knowledge.min.css'];
        }
        self::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';
    }

    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    private function getPageData() : array {
        $data = [
            'debug' => \Gdn::config('Debug'),
            'page' => &$this->data[self::API_PAGE_KEY],
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
        ];
        $data['page']['classes'][] = 'isLoading';
        $data['page']['userSignedIn'] = $this->session->isValid();
        $this->pageMetaInit();

        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);

        $data['meta'] = $this->meta->getPageMeta();

        return $data;
    }

    /**
     * Render out the /kb/articles/title-slug-{id} :path page.
     *
     * @param string $path URI slug page action string.
     * @return string Returns HTML page content
     */
    public function index(string $path) : string {
        $id = $this->detectArticleId($path);

        $this->data[self::API_PAGE_KEY] = $this->articlesApi->get($id, ["expand" => "all"]);

        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getDummyBreadcrumbData());

        // Put together pre-loaded redux actions.
        $reduxActions = [
            $this->createReduxAction(
                ArticlesApiActions::GET_ARTICLE_SUCCESS,
                $this->data[self::API_PAGE_KEY]
            ),
        ];

        $reduxActionScript = $this->createInlineScriptContent("__ACTIONS__", $reduxActions);
        $this->inlineScripts[] = $reduxActionScript;

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getPageData();
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
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/'.$id.'/editor');
        }
        $this->data[self::API_PAGE_KEY] = $this->articlesApi->get($id, ["expand" => "all"]);

        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getDummyBreadcrumbData());

        // Put together pre-loaded redux actions.
        $reduxActions = [
            $this->createReduxAction(
                ArticlesApiActions::GET_ARTICLE_SUCCESS,
                $this->data[self::API_PAGE_KEY]
            ),
        ];

        $reduxActionScript = $this->createInlineScriptContent("__ACTIONS__", $reduxActions);
        $this->inlineScripts[] = $reduxActionScript;

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getPageData();
        $data['template'] = 'seo/pages/article.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/add   path page.
     * @return string Returns HTML page content
     */
    public function get_add() : string {
        if (!$this->session->isValid()) {
            self::signInFirst('kb/articles/add');
        }
        $this->data[self::API_PAGE_KEY] = [];

        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getDummyBreadcrumbData());

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getPageData();
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
}
