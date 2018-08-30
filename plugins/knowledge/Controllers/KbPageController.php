<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers;

class KbPageController {

    /** @var \Twig_Environment */
    protected $twig;

    /** @var \AssetModel */
    private $assetModel;

    /** @var \Gdn_Configuration */
    private $configuration;

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel
     */
    public function __construct(\AssetModel $assetModel, \Gdn_Configuration $configuration) {
        $this->assetModel = $assetModel;
        $this->configuration = $configuration;
        $loader = new \Twig_Loader_Filesystem(PATH_ROOT.'/plugins/knowledge/views');
        $this->twig = new \Twig_Environment($loader);
    }

    /**
     * Use the asset model to get the external JS assets for the knowledge section.
     */
    private function getScripts() {
        $webpackJSFiles = $this->assetModel->getWebpackJsFiles('knowledge');
        foreach ($webpackJSFiles as $webpackJSFile) {
            $scripts[] = [
                'src' => $webpackJSFile,
            ];
        }
        return $scripts;
    }

    /**
     * Use the asset model to get the inline JS assets for the knowledge section.
     */
    private function getInlineScripts() {
        $polyfillContent = $this->assetModel->getInlinePolyfillJSContent();
        $scripts = [[
            'content' => $polyfillContent,
        ]];
        return $scripts;
    }

    /**
     * Get the stylesheets for a knowledge page. Knowledge has it's own stylesheet to load. Hardcoded for now.
     */
    private function getStyles() {
        // Don't load the production stylesheets in a development build.
        if ($this->configuration->get('HotReload.Enabled', false) === true) {
            return [];
        } else {
            return [[
                'src' => '/plugins/knowledge/js/webpack/knowledge.min.css',
            ]];
        }
    }
    /**
     * Get the stylesheets for a knowledge page. Knowledge has it's own stylesheet to load. Hardcoded for now.
     */
    private function getInlineStyles() {
        return [];
    }

    /**
     * This function is for testing purposes only. This data all should be assembed dynamically.
     *
     * @todo Break down this creation of the data array, and implement a better method of rendering the view than
     * echo-ing it out.
     */
    private function getStaticData() {
        return [
            'meta' => [
                'title' => 'Knowledge Base Title',
                'locale' => 'en',
                'tags' => [
                    [
                        'charset' => 'utf-8'
                    ],[
                        'http-equiv' => 'X-UA-Compatible',
                        'content' => 'IE=edge',
                    ],[
                        'name' => 'viewport',
                        'content' => 'width=device-width, initial-scale=1',
                    ],[
                        'name' => 'format-detection',
                        'content' => 'telephone=no',
                    ],[
                        'property' => 'og:site_name',
                        'content' => 'Vanilla',
                    ]
                ],
                'links' => [ // Can be for canonical urls, alternate urls, next/previous, etc
                    [
                        'rel' => 'canonical',
                        'href' => '/',
                    ],[
                        'locale' => 'fr',
                        'title' => 'Français',
                        'url' => '/fr',
                        'rel' => 'alternate'
                    ],[
                        'locale' => 'de',
                        'title' => 'German',
                        'url' => '/de',
                        'rel' => 'alternate',
                    ],[
                        'href' => '/feed.rss',
                        'title' => 'Example RSS',
                        'type' => 'application/rss+xml',
                        'rel' => 'alternate',
                    ],[
                        'rel' => 'next',
                        'href' => '/discussions/p3'
                    ],[
                        'rel' => 'prev',
                        'href' => '/discussions/p1'
                    ]
                ],
                'breadcrumb' => "{\"@context\":\"http://schema.org\",\"@type\":\"BreadcrumbList\",\"itemListElement\":[{\"@type\":\"ListItem\",\"position\":1,\"name\":\"Books\",\"item\":\"https://example.com/books\"},{\"@type\":\"ListItem\",\"position\":2,\"name\":\"Authors\",\"item\":\"https://example.com/books/authors\"},{\"@type\":\"ListItem\",\"position\":3,\"name\":\"Ann Leckie\",\"item\":\"https://example.com/books/authors/annleckie\"},{\"@type\":\"ListItem\",\"position\":4,\"name\":\"Ancillary Justice\",\"item\":\"https://example.com/books/authors/ancillaryjustice\"}]}",
            ],
            'page' => [
                'classes' => [
                    'testClass',
                    'testClass2'
                ],
                'content' => '<p>Put SEO friendly content here</p>'
            ],
            'scripts' => $this->getScripts(),
            'inlineScripts' => $this->getInlineScripts(),
            'styles' => $this->getStyles(),
            'inlineStyles' => $this->getInlineStyles(),
        ];
    }

    /**
     * Render out the /kb page.
     */
    public function index() {
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
}
