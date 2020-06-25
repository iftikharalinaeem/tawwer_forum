<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Addon;

/**
 * Constants for cloud bootstrapping.
 */

define('ADDON_DIRECTORIES', [
    Addon::TYPE_ADDON => [
        '/cloud/addons/addons',
        '/cloud/plugins',
        '/cloud/applications',
        '/addons/addons',
        '/applications',
        '/plugins',
    ],
    Addon::TYPE_THEME => [
        '/cloud/addons/themes',
        '/cloud/themes',
        '/addons/themes',
        '/themes',
    ],
    Addon::TYPE_LOCALE => '/locales'
]);
