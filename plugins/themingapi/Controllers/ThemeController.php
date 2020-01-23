<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Controller for theme editor.
 */
class ThemeController extends SettingsController {

    /**
     * Controller to render the /themes/theme-settings page.
     */
    public function themeSettings() {
        $this->permission("Garden.Settings.Manage");
        $this->render('themesettings');
    }
}
