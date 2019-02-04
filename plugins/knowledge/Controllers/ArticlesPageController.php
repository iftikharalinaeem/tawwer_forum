<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Data;
use Vanilla\Knowledge\Controllers\Pages\ArticlePage;
use Vanilla\Knowledge\Controllers\Pages\SimpleKbPage;
use Vanilla\Web\PageDispatchController;

/**
 * Dispatch controller for /kb/articles
 */
class ArticlesPageController extends PageDispatchController {

    protected $simplePageClass = SimpleKbPage::class;

    /**
     * Render out the /kb/articles/:id-:article page.
     *
     * @param string $path The path from the dispatcher.
     * @return Data
     */
    public function index(string $path) {
        /** @var ArticlePage $page */
        $page = $this->usePage(ArticlePage::class);
        $page->initialize($path);

        return $page->render();
    }

    /**
     * Render out the /kb/articles/{id}/editor page.
     *
     * @param int $id URI article id.
     * @return Data
     */
    public function get_editor(int $id) {
        return $this
            ->useSimplePage(\Gdn::translate('Editor'))
            ->blockRobots()
            ->requiresSession("/kb/articles/$id/editor")
            ->render()
        ;
    }

    /**
     * Render out the /kb/articles/add page.
     */
    public function get_add() {
        return $this
            ->useSimplePage(\Gdn::translate('New Article'))
            ->blockRobots()
            ->requiresSession("/kb/articles/add")
            ->render()
        ;
    }

    /**
     * Render out the /kb/articles/:id/revisions/:revisionID page.
     *
     * @param int $id URI article id.
     * @param int $revisionID URI revision ID.
     * @return Data
     */
    public function get_revisions(int $id, $revisionID = null) {
        return $this
            ->useSimplePage(\Gdn::translate('Revisions'))
            ->blockRobots()
            ->requiresSession("/kb/articles/$id/revisions/$revisionID")
            ->render()
        ;
    }
}
