<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\ThemingApi;

use \Gdn_Router as Router;
use Garden\Container\Reference;
use \Gdn_Request;
use \Gdn_Database;
use \Gdn_Plugin;
use Gdn_Session as SessionInterface;
use Vanilla\Models\ThemeModel;

/**
 * Primary class for the Knowledge class, mostly responsible for pluggable operations.
 */
class ThemingApiPlugin extends Gdn_Plugin {
    const NAV_SECTION = "appearance";

    /** @var Gdn_Database */
    private $database;

    /** @var Gdn_Request */
    private $request;

    /** @var Router */
    private $router;

    /** @var SessionInterface */
    private $session;

    /**
     * KnowledgePlugin constructor.
     *
     * @param Gdn_Database $database
     * @param Router $router
     * @param SessionInterface $session
     * @param Gdn_Request $request
     */
    public function __construct(
        Gdn_Database $database,
        Router $router,
        SessionInterface $session,
        Gdn_Request $request
    ) {
        parent::__construct();
        $this->database = $database;
        $this->router = $router;
        $this->session = $session;
        $this->request = $request;
    }


    /**
     * Initialize controller class detection under Knowledge base application
     *
     * @param \Garden\Container\Container $container Container to support dependency injection
     */
    public function container_init(\Garden\Container\Container $container) {
        $container->rule(ThemeModel::class)
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
    }

    /**
     * Setup routine for the addon.
     *
     * @return bool|void
     */
    public function setup() {
        parent::setup();
        $this->structure();
    }

    /**
     * Event handler for adding navigation items into the dashboard.
     *
     * @param \Gdn_Pluggable $sender
     *
     * @return void
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var \NestedCollectionAdapter */
        $menu = $sender->EventArguments['SideMenu'];
        $this->createDashboardMenus($menu);
    }

    /**
     * Construct the Theming UI dashboard menu items.
     *
     * @param \NestedCollectionAdapter $navCollection
     */
    private function createDashboardMenus(\NestedCollectionAdapter $navCollection) {
        $navCollection->addLink(
            self::NAV_SECTION,
            t('Theme Editor'),
            '/theme/theme-settings',
            'Garden.Settings.Manage'
        );
    }

    /**
     * Ensure the database is configured.
     */
    public function structure() {
        $this->database->structure()
            ->table("theme")
            ->primaryKey("themeID")
            ->column("name", "varchar(64)", false, ["index"])
            ->column("current", "tinyint(1)", 0, ["index"])
            ->column("parentTheme", "varchar(32)", 0)
            ->column("parentVersion", "varchar(32)", 0)
            ->column("insertUserID", "int", false)
            ->column("updateUserID", "int", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set();

        $this->database->structure()
            ->table("themeAsset")
            ->primaryKey("assetID")
            ->column("themeID", "int", false, ["index", "index.record"])
            ->column("assetKey", "varchar(32)", false, ["index", "index.record"])
            ->column("data", "text", false)
            ->column("insertUserID", "int", false, ["index"])
            ->column("updateUserID", "int", false, ["index"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set();
    }
}
