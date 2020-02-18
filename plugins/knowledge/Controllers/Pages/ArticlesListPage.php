<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;


use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;
use Vanilla\Web\JsInterpop\ReduxAction;

/**
 * Class for rendering the /kb/articles
 */
class ArticlesListPage extends KbPage {


    /** @var ArticlesApiController */
    protected $articlesApi;

    /** @var KnowledgeApiController */
    protected $knowledgeApiController;


    /**
     * Dependency Injection.
     *
     * @param ArticlesApiController $articlesApi
     * @param KnowledgeApiController $knowledgeApiController
     */
    public function __construct(ArticlesApiController $articlesApi, KnowledgeApiController $knowledgeApiController) {
        $this->articlesApi = $articlesApi;
        $this->knowledgeApiController = $knowledgeApiController;
    }

    /**
     * Initialize for the URL format /kb/articles
     *
     * @param array|null $query
     */
    public function initialize(array $query = null) {
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
        $currentLocale = $currentSiteSection->getContentLocale();
        $pageNumber = $query["page"] ?? 1;

        $params = [
            "featured" => true,
            "locale" => $currentLocale,
            "page" => $pageNumber,
            "limit" => 10
        ];

        if ($query["knowledgeBaseID"] ?? null) {
            $params["knowledegBaseID"] = $query["knowledgeBaseID"];
        } else {
            $params["siteSectionGroup"] = $currentSiteSection->getSectionGroup();
        }

        $articles = $this->knowledgeApiController->get_search($params);

        $pagingInfo = $articles->getMeta('paging');
        $pageCount = $pagingInfo['pageCount'] ?? 1;

        $articleListPageUrl = url("/kb/articles", true);

        if ($pageNumber < $pageCount) {
            $nextUrl = $articleListPageUrl . '/?page=' . ($pageNumber + 1);
            $this->addLinkTag(['rel' => 'next', 'href' => $nextUrl]);
        }

        if ($pageNumber > 1) {
            $prevNumber = $pageNumber - 1;
            $prevUrl = $prevNumber === 1 ? $articleListPageUrl :$articleListPageUrl . '/?page=' . ($pageNumber - 1);
            $this->addLinkTag(['rel' => 'prev', 'href' => $prevUrl]);
        }

        $this->addReduxAction(new ReduxAction(
            ActionConstants:: GET_ARTICLE_LIST,
            $articles,
            $params
        ));
    }

}
