<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Exception;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ArticleJsonLD;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxErrorAction;

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

        // URL Validation
        $this->validateSiteSection($article['knowledgeBaseID']);

        // Apply common SEO information.
        $this
            ->setSeoTitle($article['name'] ?? "")
            ->setSeoDescription($article['excerpt'] ?? "")
            ->setSeoContent($this->renderKbView('seo/pages/article.twig', ['article' => $article]))
            ->setSeoCrumbsForCategory($article['knowledgeCategoryID'], $currentLocale)
            ->setCanonicalUrl($article['url'])
            ->addOpenGraphTag('og:type', 'article')
            ->addJsonLDItem(new ArticleJsonLD($article, $this->siteMeta))
        ;

        // Image may or may not be present.
        $ogImage = $article['seoImage'] ?? $this->siteMeta->getShareImage();
        if ($ogImage !== null) {
            $this->addOpenGraphTag('og:image', $ogImage);
        }


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

        // Prepare translation data
        $articleID = $article['articleID'];
        $translationResponse = $this->articlesApi->get_translations($articleID, []);
        $translationData = Data::box($translationResponse);

        // Add translation meta tags for alternative language versions.
        foreach ($translationData->getData() as $translation) {
            $this->addLinkTag([
                'rel' => 'alternate',
                'hreflang' => $translation['locale'],
                'href' => $translation['url'],
            ]);
        }

        // Preload the data for the frontend.
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ARTICLE_LOCALES,
            $translationData,
            ['articleID' => $articleID]
        ));

        try {
            $relatedArticlesResponse = $this->articlesApi->get_articlesRelated($articleID, [
                'locale' => $currentLocale,
                'limit' => ArticleModel::RELATED_ARTICLES_LIMIT,
                'minimumArticles' => ArticleModel::RELATED_ARTICLES_LIMIT,
            ]);
            $relatedArticlesResponse = Data::box($relatedArticlesResponse);


            // Preload the data for the frontend.
            $this->addReduxAction(new ReduxAction(
                ActionConstants::GET_RELATED_ARTICLES,
                $relatedArticlesResponse,
                ['articleID' => $articleID]
            ));
        } catch (Exception $e) {
            // Preload the data for the frontend.
            $this->addReduxAction(new ReduxAction(
                ActionConstants::GET_RELATED_ARTICLES_FAILED,
                Data::box(['error' => ['message' => $e->getMessage()], 'params' => ['articleID' => $articleID]]),
                null,
                true
            ));
        }
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
