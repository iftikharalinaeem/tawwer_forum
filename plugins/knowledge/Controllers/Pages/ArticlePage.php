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
        $article = $this->getArticleForPath($path);
        $this
            ->setSeoTitle($article['name'] ?? "")
            ->setSeoDescription($article['articleRevision']['excerpt'] ?? "")
            ->setSeoContent($this->renderKbView('seo/pages/article.twig', ['article' => $article]))
            ->setSeoCrumbsForCategory($article['knowledgeCategoryID'])
            ->setCanonicalUrl($article['url'])
        ;

        // Preload redux actions for faster page loads.
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_ARTICLE_RESPONSE, Data::box($article)));
        $this->preloadNavigation($article['knowledgeBaseID']);
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

        $currentSiteSection = $this->siteSectionProvider->getCurrentSiteSection();
        $currentLocale = $currentSiteSection->getContentLocale();
        $availableTranslations = $this->articlesApi->get_translations($id, []);

        $hasTranslation = true;
        foreach ($availableTranslations as $translation) {
            if ($translation['locale'] === $currentLocale
                && $translation['translationStatus'] === ArticleRevisionModel::STATUS_TRANSLATION_NOT_TRANSLATED
            ) {
                $hasTranslation = false;
                break;
            }
        }

        $query = ["expand" => "all"];
        if ($hasTranslation) {
            $query['locale'] = $currentLocale;
        }

        return $this->articlesApi->get($id, $query);
    }
}
