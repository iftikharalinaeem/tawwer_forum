<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Reference;
use Vanilla\Theme\ThemeService;
use Vanilla\ThemingApi\DbThemeProvider;

$container = \Gdn::getContainer();
$container->rule(ThemeService::class)
    ->addCall("addThemeProvider", [new Reference(DbThemeProvider::class)])
    ->addCall("setThemeManagePageUrl", ["/theme/theme-settings"])
;

$container
    ->rule('@theming-editor-route') // Choose a name for our route instance.
    ->setClass(\Garden\Web\ResourceRoute::class)
    // Set the route prefix & the pattern of files to match.
    ->setConstructorArgs(['/theme/', '*\\themingapi\\Controllers\\%sPageController'])
    // Set a default content type.
    ->addCall('setMeta', ['CONTENT_TYPE', 'text/html; charset=utf-8'])
    ->rule(\Garden\Web\Dispatcher::class)
    ->addCall('addRoute', ['route' => new Reference('@theming-editor-route'), 'theming-editor-route'])
;
