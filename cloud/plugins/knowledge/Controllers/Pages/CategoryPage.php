<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Web\JsInterpop\ReduxAction;

/**
 * Class for rendering the /kb/categories/:id-:slug page.
 */
class CategoryPage extends KbPage {

    /** @var KnowledgeCategoriesApiController */
    protected $categoriesApi;

    /** @var ArticlesApiController */
    protected $articlesApi;

    /**
     * Dependency Injection.
     *
     * @param KnowledgeCategoriesApiController $categoriesApi
     * @param ArticlesApiController $articlesApi
     */
    public function __construct(KnowledgeCategoriesApiController $categoriesApi, ArticlesApiController $articlesApi) {
        $this->categoriesApi = $categoriesApi;
        $this->articlesApi = $articlesApi;
    }

    /**
     * Initialize for the URL format /kb/articles/:path.
     *
     * @param string|null $path
     */
    public function initialize(string $path = null) {
        $category = $this->getCategoryForPath($path);
        $pageNumber = $this->parsePageNumberFromPath($path);
        
        $articles = $this->articlesApi->index([
            "expand" => "excerpt",
            "knowledgeCategoryID" => $category['knowledgeCategoryID'],
            "includeSubcategories" => true,
            "limit" => 10,
            "page" => $pageNumber,
        ]);

        // Apply paging headers.
        $categoryUrl = $category['url'];
        $pagingInfo = $articles->getMeta('paging');
        $pageCount = $pagingInfo['pageCount'] ?? 1;
        if ($pageNumber === 1) {
            $this->setCanonicalUrl($categoryUrl);
        } else {
            $this->setCanonicalUrl($categoryUrl . '/p' . $pageNumber);
        }

        if ($pageNumber < $pageCount) {
            $nextUrl = $categoryUrl . '/p' . ($pageNumber + 1);
            $this->addLinkTag(['rel' => 'next', 'href' => $nextUrl]);
        }

        if ($pageNumber > 1) {
            $prevNumber = $pageNumber - 1;
            $prevUrl = $prevNumber === 1 ? $categoryUrl :$categoryUrl . '/p' . ($pageNumber - 1);
            $this->addLinkTag(['rel' => 'prev', 'href' => $prevUrl]);
        }

        $currentLocale = $this->siteSectionModel
            ->getCurrentSiteSection()
            ->getContentLocale();

        $this
            ->setSeoRequired(false)
            ->setSeoCrumbsForCategory($category['knowledgeCategoryID'], $currentLocale)
            ->setSeoTitle($category['name'] ?? "")
            ->setSeoContent($this->renderKbView('seo/pages/flatCategories.twig', [
                'category' => $category,
                'articles' => $articles
            ]))
            ->validateSiteSection($category['knowledgeBaseID'])
        ;

        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_CATEGORY_RESPONSE,
            Data::box($category),
            ['id' => $category['knowledgeCategoryID']]
        ));
        $this->preloadNavigation($category['knowledgeBaseID']);
    }

    /**
     * Get a category for our path.
     *
     * @param string $path The subpath of the category.
     *
     * @return array The category.
     * @throws NotFoundException If the URL can't be parsed properly.
     */
    private function getCategoryForPath(?string $path): array {
        $id = $this->parseIDFromPath($path);
        if ($id === null) {
            throw new NotFoundException('Category');
        }

        return $this->categoriesApi->get($id);
    }
}
