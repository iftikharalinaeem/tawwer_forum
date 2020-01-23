<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Controller for theme editor.
 */
class ThemeController extends SettingsController {
    public function themeSettings() {
        $this->render('themesettings');
    }
}
