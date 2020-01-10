<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Controller for serving the /theming-ui-settings pages.
 */
class ThemingUiSettingsController extends SettingsController {

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     */
    public function themes() {
        $this->render('index');
    }
}
