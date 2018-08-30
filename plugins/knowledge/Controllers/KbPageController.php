<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers;

use Vanilla\Knowledge\PageController;

class KbPageController extends PageController {
    use \Garden\TwigTrait;
    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel AssetModel to get js and css
     */
    public function __construct(\AssetModel $assetModel) {
        parent::__construct();
        $this->inlineScripts = [$assetModel->getInlinePolyfillJSContent()];
        $this->scripts = $assetModel->getWebpackJsFiles('knowledge');
        if (\GDN::config('HotReload.Enabled', false) === false) {
            $this->styles = ['/plugins/knowledge/js/webpack/knowledge.min.css'];
        }
        $this::$twigDefaultFolder = PATH_ROOT.'/plugins/knowledge/views';
    }

    /**
     * This function is for testing purposes only. This data all should be assembed dynamically.
     *
     * @todo Break down this creation of the data array, and implement a better method of rendering the view than
     * echo-ing it out.
     */
    private function getStaticData() {
        $data = [
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
        echo $this->twig->render('default-master.twig', $data);
    }

    /**
     * Render out the /kb/articles/:path page.
     */
    public function index_articles(string $path) {
        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getStaticData();
        echo $this->twig->render('default-master.twig', $data);
    }

    /**
     * Get canonical link
     *
     * @return string
     */
    public function getCanonicalLink() {
        return '/kb/';
    }

    /**
     * Get breadcrumb.
     *
     * @param string $format Breadcrumb format: array, json etc. Default is json
     *
     * @return string
     */
    public function getBreadcrumb(string $format = 'json') {
        return '{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Books","item":"https://example.com/books"},{"@type":"ListItem","position":2,"name":"Authors","item":"https://example.com/books/authors"},{"@type":"ListItem","position":3,"name":"Ann Leckie","item":"https://example.com/books/authors/annleckie"},{"@type":"ListItem","position":4,"name":"Ancillary Justice","item":"https://example.com/books/authors/ancillaryjustice"}]}';
    }

}
