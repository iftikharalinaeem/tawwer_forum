<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Theme\Controllers\Pages;

use Vanilla\Theme\ThemePreloadProvider;

/**
 * Base page for the theme editor.
 */
class ThemeEditorPage extends \Vanilla\Web\Page {

    /**
     * DI.
     *
     * @param ThemePreloadProvider $themePreloader
     */
    public function __construct(ThemePreloadProvider $themePreloader) {
        $themePreloader->setForcedThemeKey('theme-foundation');
        $this->registerReduxActionProvider($themePreloader);
    }

    /**
     * @inheritdoc
     */
    public function initialize(string $title = "") {
        $this
            ->setSeoRequired(false)
            ->setSeoTitle($title)
        ;
    }

    /**
     * Get the section of the site we are serving assets for.
     */
    public function getAssetSection(): string {
        return "admin";
    }
}
