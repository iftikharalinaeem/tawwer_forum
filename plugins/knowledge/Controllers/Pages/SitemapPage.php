<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Class for rendering the /kb/:urlCode page.
 */
class SitemapPage extends KbPage {
    const LIMIT_INDEX_KB = 500;
    const LIMIT_ARTICLE = 200;

    //  KB_SITEMAP_FILENAME should be just /kb/sitemap.xml
    const KB_SITEMAP_URL = '/kb/sitemap-kb.xml';

    /** @var KnowledgeBasePage */
    private $knowledgeBasePage;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var ArticleModel */
    private $articleModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /**
     * SitemapPage constructor.
     *
     * @param KnowledgeBasePage $knowledgeBasePage
     * @param KnowledgeBaseModel $knwoledgBaseModel
     * @param ArticleModel $articleModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(
        KnowledgeBasePage $knowledgeBasePage,
        KnowledgeBaseModel $knwoledgBaseModel,
        ArticleModel $articleModel,
        KnowledgeCategoryModel $knowledgeCategoryModel
    ) {
        $this->knowledgeBasePage = $knowledgeBasePage;
        $this->knowledgeBaseModel = $knwoledgBaseModel;
        $this->articleModel = $articleModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * @inheritdoc
     */
    public function initialize() {
        $this->setSeoRequired(false);
        $this->disableSiteSectionValidation();
    }

    /**
     * Generate sitemap-index.xml
     */
    public function index(): Data {
        $knowledgeBases = $this->knowledgeBaseModel
            ->get(
                ['status' => KnowledgeBaseModel::STATUS_PUBLISHED],
                [
                    'limit' => self::LIMIT_INDEX_KB,
                    'select' => ['knowledgeBaseID', 'dateUpdated', 'countArticles']
                ]
            );
        $data = [];
        foreach ($knowledgeBases as $kb) {
            if ($kb['countArticles'] > 0) {
                $maxpage = ceil($kb['countArticles'] / self::LIMIT_ARTICLE);
                for ($page = 1; $page <= $maxpage; $page++) {
                    $data[] = [
                        'url' => \Gdn::request()->url(self::KB_SITEMAP_URL.'?kb=' . $kb['knowledgeBaseID'].'&page='.$page, true),
                        'dateUpdated' => $kb['dateUpdated']->format('c')
                    ];
                }
            }
        }
        return new Data(
            $this->renderKbView('seo/pages/sitemap-index.twig', ['sitemaps' => $data]),
            ['CONTENT_TYPE' => 'text/xml;charset=UTF-8']
        );
    }

    /**
     * Generate sitemap.xml?kb={knowledgeBaseID}&page={page}
     *
     * @param array $args Request arguments: kb (knowledge base ID), page (page number for pagination)
     *                    Note: both kb and page arguments are mandatory
     */
    public function sitemap(array $args): Data {
        $knowledgeBaseID = $args['kb'] ?? '';
        $page = $args['page'] ?? '';
        if (empty($knowledgeBaseID)) {
            return new Data('Knowledge base ID is required. (Ex: sitemap.xml?kb=1&page=1)', ['status' => 400]);
        }
        if (empty($page)) {
            return new Data('Page # is required. (Ex: sitemap.xml?kb=1&page=1)', ['status' => 400]);
        }
        $kbWhere = ["knowledgeBaseID" => $knowledgeBaseID];
        try {
            $kb = $this->knowledgeBaseModel->selectSingle($kbWhere);
        } catch (NoResultsException $e) {
            return new Data('Knowledge Base with ID: ' . $knowledgeBaseID . ' not found!', ['status' => 404]);
        }
        if ($kb['status'] === KnowledgeBaseModel::STATUS_DELETED) {
            // Do not use 410 here. 410 responses are cached by defealt regardless of your cache headers.
            // If the knowledge base was "undeleted" crawlers such as google would likely not recrawl this resource.
            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410
            return new Data('Knowledge base with ID: ' . $knowledgeBaseID . ' is not available', ['status' => 404]);
        }

        $options = [
            'select' => ['articleID', 'name', 'dateUpdated', 'a.knowledgeCategoryID'],
            'limit' => self::LIMIT_ARTICLE,
            'orderFields' => 'dateUpdated',
            'orderDirection' => 'desc'
        ];
        if ($page > 1) {
            $offset = ($page - 1) * self::LIMIT_ARTICLE;
            if ($offset >= $kb['countArticles']) {
                return new Data('Page '.$page.' not found. Max page value is ' . ceil($kb['countArticles'] / self::LIMIT_ARTICLE), ['status' => 404]);
            } else {
                $options['offset'] = $offset;
            }
        }
        $kbCategories = array_column($this->knowledgeCategoryModel->get($kbWhere, ['select' => ['knowledgeCategoryID']]), 'knowledgeCategoryID');

        $articles = $this->articleModel->getWithRevision(
            [
                'a.knowledgeCategoryID' => $kbCategories,
                'a.status' => ArticleModel::STATUS_PUBLISHED
            ],
            $options
        );
        foreach ($articles as &$article) {
            try {
                $article['url'] = $this->articleModel->url($article);
                $article['dateUpdated'] = $article['dateUpdated']->format('c');
            } catch (\Exception $e) {
                $articleID = $args['articleID'];
                trigger_error("Failed to add article '$articleID' to the sitemap.", E_USER_NOTICE);
                continue;
            }
        }
        return new Data(
            $this->renderKbView('seo/pages/sitemap.twig', ['pages' => $articles]),
            ['CONTENT_TYPE' => 'text/xml;charset=UTF-8']
        );
    }
}
