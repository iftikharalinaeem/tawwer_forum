<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Swagger;

use Vanilla\Addon;

class SwaggerPlugin extends \Gdn_Plugin {
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
     * The main swagger page.
     *
     * @param \SettingsController $sender
     */
    public function settingsController_swagger_create(\SettingsController $sender) {
        $folder = 'plugins/'.$this->getAddon()->getKey();

        $sender->addJsFile('swagger-ui-bundle.js', $folder);
        $sender->addJsFile('swagger-ui-standalone-preset.js', $folder);
        $sender->addJsFile('swagger.js', $folder);
        $sender->addCssFile('swagger-ui.css', $folder);

        $sender->render('swagger', 'settings', $folder);
    }
}
