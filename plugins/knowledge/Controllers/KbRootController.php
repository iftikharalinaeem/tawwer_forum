<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\Controllers\SearchRootController;
use Vanilla\Knowledge\Controllers\Pages\CategoryPage;
use Vanilla\Knowledge\Controllers\Pages\HomePage;
use Vanilla\Knowledge\Controllers\Pages\SitemapPage;
use Vanilla\Knowledge\Controllers\Pages\KnowledgeBasePage;
use Vanilla\Knowledge\Controllers\Pages\SimpleKbPage;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Web\PageDispatchController;

/**
 * Knowledge base controller for article view.
 */
class KbRootController extends PageDispatchController {

    protected $simplePageClass = SimpleKbPage::class;

    /** @var SearchRootController */
    private $searchRootController;

    /** @var ThemeFeatures */
    private $themeFeatures;

    /**
     * DI.
     *
     * @param SearchRootController $searchRootController
     * @param ThemeFeatures $themeFeatures
     */
    public function __construct(SearchRootController $searchRootController, ThemeFeatures $themeFeatures) {
        $this->searchRootController = $searchRootController;
        $this->themeFeatures = $themeFeatures;
    }

    /**
     * Render out the /kb page.
     */
    public function index(): Data {
        $page = $this->usePage(HomePage::class);
        $page->initialize();
        return $page->render();
    }

    /**
     * Render out the /kb/search page.
     *
     * @param \Gdn_Request $request
     */
    public function get_search(\Gdn_Request $request): Data {
        if ($this->themeFeatures->get(SearchRootController::ENABLE_FLAG)) {
            return new Data(
                [],
                [
                    'status' => 302,
                ],
                [
                    'location' => $request->url('/search?domain=knowledge')
                ]
            );
        } else {
            return $this
                ->useSimplePage(t('Search'))
                ->blockRobots()
                ->render()
            ;
        }
    }

    /**
     * Render out the /kb/sitemap-index.xml page.
     *
     * @param  string $path Path = '/xml'
     *         Note: technically we don't need any param here
     *         that is done just for compatability with current routing system.
     *         We can get rid of this param once we have better routing approach.
     * @return Data
     */
    public function get_sitemapIndex(string $path): Data {
        if ($path === '/xml') {
            $page = $this->usePage(SitemapPage::class);
            return $page->index();
        } else {
            return $this->get('/sitemap-index'.$path);
        }
    }

    /**
     * Render out the /kb/sitemap.xml page.
     *
     * @param  string $path Path = '/xml'
     *         Note: technically we don't need any param here
     *         that is done just for compatability with current routing system.
     *         We can get rid of this param once we have better routing approach.
     * @param array $args Request arguments: kb (knowledge base ID), page (page number for pagination)
     * @return Data
     */
    public function get_sitemapKb(string $path, array $args): Data {
        if ($path === '/xml') {
            $page = $this->usePage(SitemapPage::class);
            return $page->sitemap($args);
        } else {
            return $this->get('/sitemap'.$path);
        }
    }

    /**
     * Render out the /kb/drafts page.
     */
    public function get_drafts(): Data {
        return $this
            ->useSimplePage('Drafts')
            ->blockRobots()
            ->requiresSession('/kb/drafts')
            ->render()
        ;
    }

    /**
     * Render out the /kb/categories page.
     *
     * @param string $path The path from the dispatcher.
     * @return Data
     */
    public function get_categories(string $path): Data {
        /** @var CategoryPage $page */
        $page = $this->usePage(CategoryPage::class);
        $page->initialize($path);
        return $page->render();
    }

    /**
     * Render out the /kb/:urlCode page.
     *
     * @param string $path The path from the dispatcher.
     * @return Data
     */
    public function get(string $path): Data {
        /** @var KnowledgeBasePage $page */
        $page = $this->usePage(KnowledgeBasePage::class);
        $page->initialize($path);
        return $page->render();
    }


    /**
     * Render out the /kb/:id/organize-categories page.
     *
     * @param int $id Knowledge base ID
     * @return Data
     */
    public function get_organizeCategories(int $id): Data {
        return $this
            ->useSimplePage('OrganizeCategories')
            ->blockRobots()
            ->requiresSession("/kb/$id/organize-categories")
            ->render()
        ;
    }
}
