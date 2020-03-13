<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Controller for webhook settings.
 */
class WebhookSettingsController extends SettingsController {

    /**
     * Serve all paths.
     *
     * @param string $path Any path.
     */
    public function index(string $path = null) {
        $this->permission('Garden.Settings.Manage');

        $this->render();
    }
}
