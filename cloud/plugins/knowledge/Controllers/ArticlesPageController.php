<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Garden\Web\Data;
use Vanilla\Knowledge\Controllers\Pages\ArticlePage;
use Vanilla\Knowledge\Controllers\Pages\ArticlesListPage;
use Vanilla\Knowledge\Controllers\Pages\SimpleKbPage;
use Vanilla\Web\PageDispatchController;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;

/**
 * Dispatch controller for /kb/articles
 */
class ArticlesPageController extends PageDispatchController {

    protected $simplePageClass = SimpleKbPage::class;

    /**
     * @var ArticlesApiController
     */
    private $articlesApi;

    /**
     * ArticlesPageController constructor.
     * @param ArticlesApiController $articlesApi
     */
    public function __construct(ArticlesApiController $articlesApi) {
        $this->articlesApi = $articlesApi;
    }

    /**
     * Render out the /kb/articles/:id-:article page.
     *
     * @param string $path The path from the dispatcher.
     * @return Data
     */
    public function get(string $path) {
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

    /**
     * Render out the /kb/articles page.
     *
     * @param array $query Query string.
     * @return Data
     */
    public function index(array $query) {
        /** @var ArticlesListPage $page */
        $page = $this->usePage(ArticlesListPage::class);
        $page->initialize($query);

        return $page->render();
    }

    /**
     * Redirect out the /kb/articles/aliases{$path} request.
     *
     * @param string $path Alias part of url. Should start with slash symbol.
     *
     * @return Data
     */
    public function get_aliases(string $path): Data {
        $this->useSimplePage(''); // No title because this is either a redirect or an error.
        $article = $this->articlesApi->get_byAlias(['alias' => $path]);

        return (new Data('', ['status' => 301]))->setHeader('Location', $article['url']);
    }
}
