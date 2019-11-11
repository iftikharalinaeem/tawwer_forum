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
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Web\JsInterpop\ReduxAction;

/**
 * Class for rendering the /kb/articles/:id-:slug page.
 */
class ArticlePage extends KbPage {

    /** @var ArticlesApiController */
    private $articlesApi;

    /**
     * @param ArticlesApiController $articlesApi
     */
    public function __construct(ArticlesApiController $articlesApi) {
        $this->articlesApi = $articlesApi;
    }

    /**
     * Initialize for the URL format /kb/articles/:path.
     *
     * @param string|null $path
     */
    public function initialize(string $path = null) {
        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
        $currentLocale = $currentSiteSection->getContentLocale();

        $article = $this->getArticleForPath($path);
        $this
            ->setSeoTitle($article['name'] ?? "")
            ->setSeoDescription($article['excerpt'] ?? "")
            ->setSeoContent($this->renderKbView('seo/pages/article.twig', ['article' => $article]))
            ->setSeoCrumbsForCategory($article['knowledgeCategoryID'], $currentLocale)
            ->setCanonicalUrl($article['url'])
            ->validateSiteSection($article['knowledgeBaseID'])
        ;

        // Preload redux actions for faster page loads.
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ARTICLE_RESPONSE,
            Data::box($article),
            [
                'articleID' => $article['articleID'],
                'locale' => $currentLocale,
            ]
        ));
        $this->preloadNavigation($article['knowledgeBaseID']);

        // Preload translation data as well
        $articleID = $article['articleID'];
        $translationResponse = $this->articlesApi->get_translations($articleID, []);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ARTICLE_LOCALES,
            Data::box($translationResponse),
            ['articleID' => $articleID]
        ));
    }

    /**
     * Get an article for our path.
     *
     * @see KbPage::parseIDFromPath()
     *
     * @param string $path The subpath of the article.
     *
     * @return array The article.
     * @throws NotFoundException If the URL can't be parsed properly.
     */
    private function getArticleForPath(?string $path): array {
        $id = $this->parseIDFromPath($path);
        if ($id === null) {
            throw new NotFoundException('Article');
        }

        $currentLocale = $this->siteSectionModel
            ->getCurrentSiteSection()
            ->getContentLocale();

        $query = [
            'expand' => 'all',
            'locale' => $currentLocale
        ];

        return $this->articlesApi->get($id, $query);
    }
}
