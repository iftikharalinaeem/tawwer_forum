<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Theme\ThemePreloadProvider;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\Page;

/**
 *
 */
class ThemePreviewPage extends Page {

    /** @var ThemePreloadProvider */
    private $themePreloader;

    /** @var \UsersApiController */
    private $usersApi;

    /**
     * DI.
     *
     * @param ThemePreloadProvider $themePreloader
     * @param \UsersApiController $usersApi
     */
    public function __construct(ThemePreloadProvider $themePreloader, \UsersApiController $usersApi) {
        $this->themePreloader = $themePreloader;
        $this->usersApi = $usersApi;
    }

    /**
     * @param string|number $themeID The theme ID.
     * @param array $query
     */
    public function initialize($themeID = null, $query = []) {
        $this->session->checkPermission('Garden.Settings.Manage');

        $this->setSeoTitle('Theme Preview')
            ->setSeoRequired(false)
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/$themeID/preview?revisionID={$query['revisionID']}");

        $me = $this->usersApi->get_me([]);
        $this->addReduxAction(new ReduxAction(\UsersApiController::ME_ACTION_CONSTANT, Data::box($me), []));

        $this->themePreloader->setForcedThemeKey($themeID);
        $this->themePreloader->setForcedRevisionID($query['revisionID']);
        $this->registerReduxActionProvider($this->themePreloader);
    }

    /**
     * Get the section of the site we are serving assets for.
     */
    public function getAssetSection(): string {
        return "admin";
    }
}
