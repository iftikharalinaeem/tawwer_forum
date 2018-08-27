<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers;

class KnowledgePageController {

    /** @var \Twig_Environment */
    protected $twig;

    /** @var \AssetModel */
    private $assetModel;

    const DEFAULT_META_TAGS = [
        [
            'charset' => 'utf-8',
        ], [
            'http-equiv' => 'X-UA-Compatible',
            'content' => 'IE=edge',
        ], [
            'name' => 'viewport',
            'content' => 'width=device-width, initial-scale=1',
        ],
        [
            'name' => 'format-detection',
            'content' => 'telephone=no',
        ],
    ];

    /**
     * KnowledgePageController constructor.
     *
     * @param \AssetModel $assetModel
     */
    public function __construct(\AssetModel $assetModel) {
        $this->assetModel = $assetModel;
        $loader = new \Twig_Loader_Filesystem(PATH_ROOT.'/plugins/knowledge/views');
        $this->twig = new \Twig_Environment($loader);
    }

    public function index() {
        die($this->twig->render('default.master.twig', $this->getTemplateData()));
    }

    private function getTemplateData() {
        return [
            'meta' => [
                'metaTags' => self::DEFAULT_META_TAGS,
            ],
            'page' => [
                'title' => 'Knowledge Base Title',
                'locale' => 'en',
                'classes' => [
                    'testClass',
                    'testClass2',
                ],
                'content' => '<p>Put SEO friendly content here</p>',
            ],
            'css' => $this->getStyleSheets(),
            'scripts' => $this->getScripts(),
        ];
    }

    private function getStyleSheets() {
        return [
            'src' => '/plugins/knowledge/design/knowledge.css',
        ];
    }

    private function getScripts() {
        $polyfillContent = $this->assetModel->getInlinePolyfillJSContent();
        $webpackJSFiles = $this->assetModel->getWebpackJsFiles('knowledge');
        $scripts = [[
            'content' => $polyfillContent,
        ]];

        foreach ($webpackJSFiles as $webpackJSFile) {
            $scripts[] = [
                'src' => $webpackJSFile,
            ];
        }

        return $scripts;
    }
}
