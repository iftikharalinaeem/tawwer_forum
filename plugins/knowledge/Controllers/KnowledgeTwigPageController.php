<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\SiteMeta;

/**
 * Knowledge Twig & ArticlesApi controller abstract class.
 *
 * This controller expects most content to come from api
 */
abstract class KnowledgeTwigPageController extends PageController {
    use \Garden\TwigTrait;

    const API_PAGE_KEY = 'page';

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
        $this->siteMeta = $container->get(SiteMeta::class);
        $this->session = $this->container->get(\Gdn_Session::class);
        $assetModel = $this->container->get(\AssetModel::class);
        self::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';

        // Scripts
        // Assemble our site context for the frontend.
        $locale = $container->get(\Gdn_Locale::class);
        $this->inlineScripts = [$assetModel->getInlinePolyfillJSContent()];
        $this->scripts = $assetModel->getWebpackJsFiles('knowledge');
        $this->scripts[] = $assetModel->getJSLocalePath($locale->current());
        $this->addGdnScript();

        // Stylesheets
        if (\Gdn::config('HotReload.Enabled', false) === false) {
            $this->styles = [
                '/' . \AssetModel::WEBPACK_DIST_DIRECTORY_NAME . '/knowledge/addons/knowledge.min.css',
                '/' . \AssetModel::WEBPACK_DIST_DIRECTORY_NAME . '/knowledge/addons/rich-editor.min.css',
            ];
        }
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
