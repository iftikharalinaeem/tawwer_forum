<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Class for rendering the /kb/:urlCode page.
 */
class KnowledgeBasePage extends KbPage {

    /** @var ArticlePage */
    private $articlePage;

    /**
     * Constructor for DI.
     *
     * @param ArticlePage $articlePage
     */
    public function __construct(ArticlePage $articlePage) {
        $this->articlePage = $articlePage;
    }

    /** @var array */
    private $knowledgeBase;

    /**
     * Initialize for the URL format /kb/articles/:urlCode.
     *
     * @param string|null $urlCode
     */
    public function initialize(string $urlCode = null) {
        $urlCode = ltrim($urlCode, "/");
        $this->knowledgeBase = $this->kbApi->get_byUrlCode(['urlCode' => $urlCode]);
        $this->validateSiteSection($this->knowledgeBase['knowledgeBaseID']);
    }

    /**
     * @inheritdoc
     */
    public function render(): Data {
        switch ($this->knowledgeBase['viewType']) {
            case KnowledgeBaseModel::TYPE_HELP:
                return $this->renderHelpCenterHomepage();
            case KnowledgeBaseModel::TYPE_GUIDE:
                return $this->renderGuideHomepage();
            default:
                return parent::render();
        }
    }

    /**
     * Render the help center type homepage.
     */
    private function renderHelpCenterHomepage(): Data {
        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $currentLocale = $siteSection->getContentLocale();

        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $this->preloadArticleList([
            'knowledgeBaseID' => $this->knowledgeBase['knowledgeBaseID'],
            'featured' => true,
            'siteSectionGroup' => $siteSection->getSectionGroup(),
            'locale' => $siteSection->getContentLocale(),
        ]);
        $this->preloadNavigation($this->knowledgeBase['knowledgeBaseID']);
        $this->setSeoRequired(false)
            ->setSeoDescription($this->knowledgeBase['description'])
            ->setSeoCrumbsForCategory($this->knowledgeBase['rootCategoryID'], $currentLocale)
            ->setCanonicalUrl($this->knowledgeBase['url'])
            ->setSeoTitle($this->knowledgeBase['name']);
        return parent::render();
    }

    /**
     * Render the guide center type homepage.
     */
    private function renderGuideHomepage(): Data {
        $articleID = $this->knowledgeBase['defaultArticleID'];
        if ($articleID === null) {
            $this->preloadNavigation($this->knowledgeBase['knowledgeBaseID']);
            $this->setSeoRequired(false)
                ->blockRobots();
            return parent::render();
        } else {
            $this->articlePage->initialize("/$articleID");
            return $this->articlePage->render();
        }
    }
}
