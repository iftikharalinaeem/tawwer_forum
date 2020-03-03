<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Controllers\Pages;

use Vanilla\Models\ThemePreloadProvider;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\Page;

/**
 *
 */
class ThemePreviewPage extends Page {

    /** @var ThemePreloadProvider */
    private $themePreloader;

    /**
     * DI.
     *
     * @param ThemePreloadProvider $themePreloader
     */
    public function __construct(ThemePreloadProvider $themePreloader) {
        $this->themePreloader = $themePreloader;
    }

    /**
     * @param string|number $themeID The theme ID.
     */
    public function initialize($themeID = null) {
        $this->session->checkPermission('Garden.Settings.Manage');

        $this->setSeoTitle('Theme Preview')
            ->setSeoRequired(false)
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/$themeID/preview");

        $this->themePreloader->setForcedThemeKey($themeID);
        $this->registerReduxActionProvider($this->themePreloader);
    }

    /**
     * Get the section of the site we are serving assets for.
     */
    public function getAssetSection(): string {
        return "admin";
    }
}
