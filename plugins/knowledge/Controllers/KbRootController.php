<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Controllers\Pages\CategoryPage;
use Vanilla\Knowledge\Controllers\Pages\HomePage;
use Vanilla\Knowledge\Controllers\Pages\KnowledgeBasePage;
use Vanilla\Knowledge\Controllers\Pages\SimpleKbPage;
use Vanilla\Web\PageDispatchController;

/**
 * Knowledge base controller for article view.
 */
class KbRootController extends PageDispatchController {

    protected $simplePageClass = SimpleKbPage::class;

    /**
     * Render out the /kb page.
     */
    public function index(): Data {
        $page = $this->usePage(HomePage::class);
        $page->initialize();
        return $page->render();
    }

    /**
     * Render out the /kb/debug page.
     */
    public function get_debug(): Data {
        $page = $this->useSimplePage('Debug');
        if (!debug()) {
            throw new NotFoundException();
        }

        return $page->render();
    }

    /**
     * Render out the /kb/search page.
     */
    public function get_search(): Data {
        return $this
            ->useSimplePage('Search')
            ->blockRobots()
            ->render()
        ;
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
