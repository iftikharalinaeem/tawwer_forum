<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;
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
     * @return Data
     */
    public function initialize(array $query = null) {
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
        $currentLocale = $currentSiteSection->getContentLocale();
        $pageNumber = $query["page"] ?? 1;

        $siteSectionGroup = $currentSiteSection->getSectionGroup();

        $params = [
            "featured" => $query["recommended"] ?? false,
            "knowledgeBaseID" =>  $query["knowledgeBaseID"] ?? null,
            "siteSectionGroup" => ($siteSectionGroup === "vanilla") ? null : $siteSectionGroup,
            "locale" => $currentLocale,
            "expand" => "users",
            "page" => $pageNumber,
            "limit" => 10
        ];

        if ($query["knowledgeBaseID"] ?? null) {
            $this->validateSiteSection($query["knowledgeBaseID"]);
        } else {
            $this->disableSiteSectionValidation();
        }
        $this
            ->setSeoRequired(false)
            ->setSeoTitle("Featured Articles");

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

        $this->preloadArticleList($params);
        return parent::render();
    }
}
