<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use Vanilla\Knowledge\Controllers\Api\KnowledgeNavigationApiController;
use Vanilla\Models\SiteMeta;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\Page;

/**
 * Base knowledge base page.
 */
abstract class KbPage extends Page {

    /** @var \UsersApiController */
    protected $usersApi;

    /** @var KnowledgeBasesApiController */
    protected $kbApi;

    /** @var KnowledgeNavigationApiController */
    protected $navApi;

    /** @var KnowledgeCategoriesApiController */
    protected $categoriesApi;

    /**
     * @inheritdoc
     */
    public function setDependencies(
        SiteMeta $siteMeta,
        \Gdn_Request $request,
        \Gdn_Session $session,
        WebpackAssetProvider $assetProvider,
        \UsersApiController $usersApi = null, // Default needed for method extensions
        KnowledgeBasesApiController $kbApi = null, // Default needed for method extensions
        KnowledgeNavigationApiController $navApi = null, // Default needed for method extensions
        KnowledgeCategoriesApiController $categoriesApi = null  // Default needed for method extensions
    ) {
        parent::setDependencies($siteMeta, $request, $session, $assetProvider);
        $this->usersApi = $usersApi;
        $this->kbApi = $kbApi;
        $this->navApi = $navApi;
        $this->categoriesApi = $categoriesApi;

        // Shared initialization.
        $this->initAssets();
        $this->initSharedData();
    }

    /**
     * Initialize assets from the asset provide.
     */
    private function initAssets() {
        $this->inlineScripts[] = $this->assetProvider->getInlinePolyfillContents();
        $this->scripts = array_merge($this->scripts, $this->assetProvider->getScripts('knowledge'));
        $this->styles = array_merge($this->styles, $this->assetProvider->getStylesheets('knowledge'));
    }

    /** @var array */
    protected $knowledgeBases;

    /**
     * Add global redux actions that apply to any /kb page.
     */
    private function initSharedData() {
        $me = $this->usersApi->get_me([]);
        $this->addReduxAction(new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($me)));

        $this->knowledgeBases = $this->kbApi->index();
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ALL_KBS,
            Data::box($this->knowledgeBases),
            []
        ));

        // This is here as a stopgap. The frontend needs to not rely on this.
        $allCategories = $this->categoriesApi->index();
        $this->addReduxAction(new ReduxAction(ActionConstants::GET_ALL_CATEGORIES, Data::box($allCategories)));
    }

    /**
     * Preload the a redux response for a knowledge bases navData.
     *
     * @param int $knowledgeBaseID
     */
    protected function preloadNavigation(int $knowledgeBaseID) {
        $options = ['knowledgeBaseID' => $knowledgeBaseID, "recordType" => KnowledgeNavigationApiController::FILTER_RECORD_TYPE_ALL];
        $navigation = $this->navApi->flat($options);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_NAVIGATION_FLAT,
            Data::box($navigation),
            $options
        ));
    }

    /**
     * Render a twig template from the knowledge base views directory.
     *
     * @param string $path The view path.
     * @param array $data Data to render the view with.
     *
     * @return string The rendered view.
     */
    protected function renderKbView(string $path, array $data): string {
        return $this->renderTwig('plugins/knowledge/views/' . $path, $data);
    }


    /**
     * Parse an ID out of a path with the formulation /:id-:slug.
     * -:slug is optional.
     *
     * @param string|null $path The path to parse.
     *
     * @return int|null The parsed ID or null.
     */
    protected function parseIDFromPath(?string $path): ?int {
        if (!$path) {
            return null;
        }

        $matches = [];
        if (preg_match('/^\/(?<articleID>\d+)(-[^\/]*)?$/', $path, $matches) === 0) {
            return null;
        }

        $id = filter_var($matches["articleID"], FILTER_VALIDATE_INT);

        if ($id === false) {
            return null;
        }

        return $id;
    }
}
