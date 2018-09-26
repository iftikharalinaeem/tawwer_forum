<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;

/**
 * Knowledge Twig & ArticlesApi controller abstract class.
 *
 * This controller expects most content to come from api
 */
abstract class KnowledgeTwigPageController extends PageController {
    use \Garden\TwigTrait;

    const API_PAGE_KEY = 'page';
    /** @var ArticlesApiController */
    protected $articlesApi;

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel AssetModel To get js and css.
     * @param \Gdn_Session $session Session DI object
     * @param ArticlesApiController $articlesApiController To fetch article resources.
     */
    public function __construct(
        \AssetModel $assetModel,
        \Gdn_Session $session,
        ArticlesApiController $articlesApiController
    ) {
        parent::__construct();
        $this->articlesApi = $articlesApiController;
        $this->session = $session;
        self::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';
        $this->inlineScripts = [$assetModel->getInlinePolyfillJSContent()];
        $this->scripts = $assetModel->getWebpackJsFiles('knowledge');
        if (\Gdn::config('HotReload.Enabled', false) === false) {
            $this->styles = ['/' . \AssetModel::WEBPACK_DIST_DIRECTORY_NAME . '/knowledge/addons/knowledge.min.css'];
        }
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
