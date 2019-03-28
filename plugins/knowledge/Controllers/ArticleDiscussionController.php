<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\StaticCacheTranslationTrait;
use Vanilla\Web\TwigRenderTrait;
use Garden\Web\Exception\ClientException;

/**
 * Controller for serving the /article-discussion pages.
 */
class ArticleDiscussionController extends \Gdn_Controller {

    use TwigRenderTrait;

    use StaticCacheTranslationTrait;

    /** @var DiscussionsApiController */
    private $discussionsApiController;

    /** @var Gdn_Request */
    private $request;

    /**
     * Constructor for DI.
     *
     * @param Gdn_Request $request
     * @param \DiscussionsApiController $discussionsApiController
     */
    public function __construct(\Gdn_Request $request, \DiscussionsApiController $discussionsApiController) {
        $this->request = $request;
        $this->discussionsApiController = $discussionsApiController;
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
        parent::__construct();
    }

    /**
     * Convert a discussion to an article.
     *
     * @param int|string $discussionID
     */
    public function convert($discussionID = "") {
        $this->permission("knowledge.articles.add");

        if (empty($discussionID) || !is_numeric($discussionID)) {
            throw new ClientException("Invalid discussion ID");
        }
        $discussionID = (int)$discussionID;

        $this->setData("editorUrl", $this->request->url("kb/articles/add?discussionID={$discussionID}"));
        $this->render("convert");
    }

    /**
     * Convert a discussion to an article.
     *
     * @param int|string $discussionID
     */
    public function unlink($discussionID = "") {
        $this->permission("knowledge.articles.add");

        if (empty($discussionID) || !is_numeric($discussionID)) {
            throw new ClientException("Invalid discussion ID");
        }
        $discussionID = (int)$discussionID;

        if ($this->request->isAuthenticatedPostBack(true)) {
            $this->discussionsApiController->delete_canonicalUrl($discussionID);
            $this->jsonTarget('', '', 'Refresh');
        }

        $this->setData("editorUrl", $this->request->url("kb/articles/add?discussionID={$discussionID}"));
        $this->setData("form", new \Gdn_Form());
        $this->render("unlink");
    }
}
