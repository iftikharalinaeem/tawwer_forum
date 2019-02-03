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
        KnowledgeNavigationApiController $navApi = null // Default needed for method extensions
    ) {
        parent::setDependencies($siteMeta, $request, $session, $assetProvider);
        $this->usersApi = $usersApi;
        $this->kbApi = $kbApi;
        $this->navApi = $navApi;

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
    }

    /**
     * Preload the a redux response for a knowledge bases navData.
     *
     * @param int $knowledgeBaseID
     */
    protected function preloadNavigation(int $knowledgeBaseID) {
        $options = ['knowledgeBaseID' => $knowledgeBaseID];
        $navigation = $this->navigationApi->get_flat($options);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_NAVIGATION_FLAT,
            Data::box($navigation),
            $options
        ));
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
