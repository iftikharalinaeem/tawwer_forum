<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
        $articles = $this->articlesApi->index([
            "expand" => "excerpt",
            "knowledgeCategoryID" => $category['knowledgeCategoryID'],
        ]);
        $this
            ->setSeoRequired(false)
            ->setSeoTitle($category['name'] ?? "")
            ->setSeoContent($this->renderKbView('seo/pages/flatCategories.twig', [
                'category' => $category,
                'articles' => $articles
            ]))
            ->setCanonicalUrl($category['url'])
        ;

        // Preload redux actions for faster page loads.
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_CATEGORY_RESPONSE, Data::box($category)));
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
