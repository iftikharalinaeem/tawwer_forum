<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\ThemingApi\Controllers;

use Garden\Container\Container;

use Vanilla\Theme\Controllers\Pages\ThemeEditorPage;
use Vanilla\Web\PageDispatchController;

/**
 * Dispatch controller for /kb/articles
 */
class ThemeSettingsPageController extends PageDispatchController {

    protected $simplePageClass = ThemeEditorPage::class;

    /**
     * Render out the /theme/theme-settings/{id}/edit page.
     *
     * @param int $id
     * @return \Garden\Web\Data
     */
    public function index() {

        return $this
            ->useSimplePage(\Gdn::translate('Theme Editor'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/edit")
            ->render()
            ;
    }

    /**
     * Render out the /theme/theme-settings/{id}/edit page.
     *
     * @param int $id
     * @return \Garden\Web\Data
     */
    public function get_edit(int $id) {
        return $this
            ->useSimplePage(\Gdn::translate('Theme Editor'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/$id/edit")
            ->render()
            ;
    }

    /**
     * Render out the /theme/theme-settings/add?{query}.
     *
     * @param array $query
     * @return \Garden\Web\Data
     */
    public function get_add(array $query) {
        $query = $query . 'test';
        return $this
            ->useSimplePage(\Gdn::translate('Theme Editor'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/add")
            ->render()
            ;
    }

}

