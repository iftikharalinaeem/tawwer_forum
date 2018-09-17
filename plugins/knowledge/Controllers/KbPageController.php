<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\DummyBreadcrumbTrait;
use Vanilla\Knowledge\Models\Breadcrumb;

/**
 * Knowledge base controller for article view.
 */
class KbPageController extends PageController {
    use \Garden\TwigTrait;
    use DummyBreadcrumbTrait;

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel AssetModel To get js and css.
     */
    public function __construct(\AssetModel $assetModel) {
        parent::__construct();

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
    private function getPageData() {
        $data = [
            'debug' => \Gdn::config('Debug'),
            'page' => &$this->data[self::API_PAGE_KEY],
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
        ];
        $data['page']['classes'][] = 'isLoading';
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
        $this->data['breadcrumb-json'] = Breadcrumb::crumbsAsJsonLD($this->getDummyBreadcrumbData());
        $this->data['title'] = 'Knowledge Base Title';
        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getPageData();
        $data['template'] = 'seo/pages/home.twig';

        echo $this->twigInit()->render('default-master.twig', $data);
    }
}
