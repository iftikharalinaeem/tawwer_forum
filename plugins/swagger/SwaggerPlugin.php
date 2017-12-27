<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Swagger;

use AssetModel;
use Gdn_Plugin;
use SettingsController;

/**
 * Handles the swagger UI menu options.
 */
class SwaggerPlugin extends Gdn_Plugin {
    /**
     * Adds "API v2" menu option to the Forum menu on the dashboard.
     *
     * @param \Gdn_Controller $sender The settings controller.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Site Settings', t('API v2'), '/settings/swagger', 'Garden.Settings.Manage', ['class' => 'nav-swagger']);
    }

    /**
     * Hook for adding Swagger UI JavaScript to the end of the page.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_afterBody_handler(SettingsController $sender, array $args = []) {
        if ($sender->RequestMethod !== 'swagger') {
            return;
        }

        $folder = 'plugins/'.$this->getAddon()->getKey();
        $scripts = [
            'swagger-ui-bundle.js',
            'swagger-ui-standalone-preset.js',
            'swagger-ui-plugin.js'
        ];
        foreach ($scripts as $path) {
            $search = AssetModel::jsPath($path, $folder);
            if (!$search) {
                continue;
            }
            echo '<script src="' . htmlspecialchars(url($search[1])) . '" type="text/javascript"></script>'."\n";
        }
    }

    /**
     * The main swagger page.
     *
     * @param SettingsController $sender The page controller.
     */
    public function settingsController_swagger_create(SettingsController $sender) {
        $sender->permission('Garden.Settings.Manage');

        $folder = 'plugins/'.$this->getAddon()->getKey();

        $sender->addCssFile('swagger-ui.css', $folder);
        $sender->addCssFile('swagger-ui-plugin.css', $folder);

        $sender->title(t('Vanilla API v2'));
        $sender->render('swagger', 'settings', $folder);
    }
}
