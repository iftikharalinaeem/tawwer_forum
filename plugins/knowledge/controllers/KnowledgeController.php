<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

class KnowledgeController extends VanillaController {
    public function initialize() {
        parent::initialize();
        $this->Application = 'knowledge';
    }

    public function index() {
        $loader = new Twig_Loader_Filesystem(PATH_ROOT.'/plugins/knowledge/views');
        $twig = new Twig_Environment($loader);

        die($twig->render('default-master.twig', [
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
                'scripts' => [
                    "/plugins/knowledge/js/knowledge.js",
                ],
                'styles' => [
                    '/plugins/knowledge/design/knowledge.css',
                ],
                'breadcrumb' => "{\"@context\":\"http://schema.org\",\"@type\":\"BreadcrumbList\",\"itemListElement\":[{\"@type\":\"ListItem\",\"position\":1,\"name\":\"Books\",\"item\":\"https://example.com/books\"},{\"@type\":\"ListItem\",\"position\":2,\"name\":\"Authors\",\"item\":\"https://example.com/books/authors\"},{\"@type\":\"ListItem\",\"position\":3,\"name\":\"Ann Leckie\",\"item\":\"https://example.com/books/authors/annleckie\"},{\"@type\":\"ListItem\",\"position\":4,\"name\":\"Ancillary Justice\",\"item\":\"https://example.com/books/authors/ancillaryjustice\"}]}",
            ],
            'page' => [
                'classes' => [
                    'testClass',
                    'testClass2'
                ],
                'content' => '<p>Put SEO friendly content here</p>'
            ]
        ]));
    }
}
