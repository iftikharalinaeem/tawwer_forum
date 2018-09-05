<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiActions;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\SiteContextModel;
use Vanilla\Knowledge\PageController;

/**
 * Knowledge base controller for article view.
 */
class KbPageController extends PageController {
    use \Garden\TwigTrait;

    /** @var bool */
    private $hotReloadEnabled;

    /** @var ArticlesApiController */
    private $articlesApi;

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel AssetModel To get js and css.
     * @param \Gdn_Configuration $config To read some configuration values.
     * @param SiteContextModel $siteContext Get data about the site.
     * @param ArticlesApiController $articlesApiController To fetch article resources.
     */
    public function __construct(
        \AssetModel $assetModel,
        \Gdn_Configuration $config,
        ArticlesApiController $articlesApiController
    ) {
        parent::__construct();
        $this->hotReloadEnabled = $config->get('HotReload.Enabled', false);
        $this->articlesApi = $articlesApiController;
        $this->inlineScripts = [$assetModel->getInlinePolyfillJSContent()];
        $this->scripts = $assetModel->getWebpackJsFiles('knowledge');
        if ($this->hotReloadEnabled === false) {
            $this->styles = ['/plugins/knowledge/js/webpack/knowledge.min.css'];
        }
        self::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';
    }

    /**
     * This function is for testing purposes only. This data all should be assembed dynamically.
     *
     * @todo Break down this creation of the data array, and implement a better method of rendering the view than
     * echo-ing it out.
     */
    private function getStaticData() {
        $data = [
            'debug' => \Gdn::config('Debug'),
            'page' => &$this->data[self::API_PAGE_KEY],
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
        ];
        $this->pageMetaInit();

        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);

        $data['meta'] = $this->meta->getPageMeta();

        return $data;
    }

    /**
     * Render out the /kb page.
     */
    public function index() {
        $this->data['breadcrumb-json'] = $this->getBreadcrumb();
        $this->data['title'] = 'Knowledge Base Title';
        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getStaticData();
        $data['template'] = 'seo/pages/home.twig';

        echo $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/:path page.
     *
     * @param string $path URI slug page action string.
     *
     * @throws \Exception if no session is available.
     * @throws ClientException If the URL can't be parsed properly.
     * @throws HttpException If the resource could not be fetched from the API endpoint.
     * @throws PermissionException If the resource could not be fetched from the API endpoint.
     * @throws ValidationException If the resource could not be fetched from the API endpoint.
     * @throws NotFoundException If the resource could not be fetched from the API endpoint.
     * @throws ServerException If the resource could not be fetched from the API endpoint.
     */
    public function index_articles(string $path) {
        $id = $this->detectArticleId($path);

        $this->data[self::API_PAGE_KEY] = $this->articlesApi->get($id);
        $this->data['breadcrumb-json'] = $this->getBreadcrumb();

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
        $data = $this->getStaticData();
        $data['template'] = 'seo/pages/article.twig';

        echo $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Get breadcrumb data.
     *
     * @param string $format Breadcrumb format: array, json etc. Default is json
     *
     * @return string
     */
    public function getBreadcrumb(string $format = 'json') {
        return '{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Books","item":"https://example.com/books"},{"@type":"ListItem","position":2,"name":"Authors","item":"https://example.com/books/authors"},{"@type":"ListItem","position":3,"name":"Ann Leckie","item":"https://example.com/books/authors/annleckie"},{"@type":"ListItem","position":4,"name":"Ancillary Justice","item":"https://example.com/books/authors/ancillaryjustice"}]}';
    }

    /**
     * Get article id
     *
     * @return string
     * @throws ClientException If the URL can't be parsed properly.
     */
    public function detectArticleId($path) {
        $matches = [];
        if (preg_match('/^\/.*-(\d*)$/', $path, $matches) === 0) {
            throw new ClientException('Can\'t detect article id!', 400);
        }
        $id = (int)$matches[1];
        return $id;
    }

}
