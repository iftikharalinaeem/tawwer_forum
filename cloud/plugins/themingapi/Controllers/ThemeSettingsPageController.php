<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\ThemingApi\Controllers;

use Vanilla\Theme\Controllers\Pages\ThemeEditorPage;
use Vanilla\Theme\Controllers\Pages\ThemePreviewPage;
use Vanilla\Theme\Controllers\Pages\ThemeRevisionsPage;
use Vanilla\Web\ContentSecurityPolicyMiddleware;
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
    public function get_edit(int $id) {
        $response = $this
            ->useSimplePage(\Gdn::translate('Theme Editor'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/$id/edit")
            ->render()
        ;
        $response->setMeta(ContentSecurityPolicyMiddleware::SCRIPT_BYPASS, true);
        return $response;
    }

    /**
     * Render out the /theme/theme-settings/add?{query}.
     *
     * @param array $query
     * @return \Garden\Web\Data
     */
    public function get_add(array $query) {
        $response = $this
            ->useSimplePage(\Gdn::translate('Theme Editor'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/add")
            ->render()
        ;
        $response->setMeta(ContentSecurityPolicyMiddleware::SCRIPT_BYPASS, true);
        return $response;
    }

    /**
     * Render out the /theme/theme-settings/preview.
     *
     * @param string $id
     * @param array $query
     * @return \Garden\Web\Data
     */
    public function get_preview(string $id, array $query) {
        /** @var ThemePreviewPage $page */
        $page = $this->usePage(ThemePreviewPage::class);

        $page->initialize($id, $query);
        return $page->render();
    }

    /**
     * Render out the /theme/theme-settings/$id/revisions.
     *
     * @param string $id
     * @return \Garden\Web\Data
     */
    public function get_revisions(string $id) {
        $response = $this
            ->useSimplePage(\Gdn::translate('Theme Revisions'))
            ->blockRobots()
            ->requiresSession("/theme/theme-settings/$id/revisions")
            ->render()
        ;
        return $response;
    }
}