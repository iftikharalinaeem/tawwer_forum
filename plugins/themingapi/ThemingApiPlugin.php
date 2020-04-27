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
use Vanilla\ThemingApi\Models\ThemeModel as DbThemeModel;
use Vanilla\ThemingApi\Models\ThemeRevisionModel;
use Vanilla\ThemingApi\Models\ThemeAssetModel;

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

    /** @var ThemeRevisionModel $themeRevisionModel */
    private $themeRevisionModel;

    /** @var DbThemeModel $themeModel */
    private $themeModel;

    /** @var ThemeAssetModel $themeAssetModel */
    private $themeAssetModel;

    /**
     * KnowledgePlugin constructor.
     *
     * @param Gdn_Database $database
     * @param Router $router
     * @param SessionInterface $session
     * @param Gdn_Request $request
     * @param ThemeRevisionModel $themeRevisionModel
     * @param DbThemeModel $themeModel
     */
    public function __construct(
        Gdn_Database $database,
        Router $router,
        SessionInterface $session,
        Gdn_Request $request,
        ThemeRevisionModel $themeRevisionModel,
        DbThemeModel $themeModel,
        ThemeAssetModel $themeAssetModel
    ) {
        parent::__construct();
        $this->database = $database;
        $this->router = $router;
        $this->session = $session;
        $this->request = $request;
        $this->themeRevisionModel = $themeRevisionModel;
        $this->themeModel = $themeModel;
        $this->themeAssetModel = $themeAssetModel;
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
     * Add the new Themes menu item.
     *
     * @param \DashboardNavModule $nav The menu to add the module to.
     */
    public function dashboardNavModule_init_handler(\DashboardNavModule $nav) {
        if ($this->session->checkPermission('Garden.Settings.Manage')) {
            $nav->addLinkToSection(
                'settings',
                t('Themes'),
                '/theme/theme-settings',
                'appearance.theme-settings',
                '',
                ['after' => 'banner'],
                ['badge' => t('New')]
            );
        }
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
            ->column("revisionID", "int", false)
            ->column("insertUserID", "int", false)
            ->column("updateUserID", "int", false)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set();

        $this->database->structure()
            ->table("themeRevision")
            ->primaryKey("revisionID")
            ->column("themeID", "int", false, ["index"])
            ->column("name", "varchar(15)", false)
            ->column("insertUserID", "int", false)
            ->column("dateInserted", "datetime")
            ->set();

        $this->database->structure()
            ->table("themeAsset")
            ->primaryKey("assetID")
            ->column("themeID", "int", false, ["index", "index.record"])
            ->column("revisionID", "int", false, ["index"])
            ->column("assetKey", "varchar(32)", false, ["index", "index.record"])
            ->column("data", "mediumtext", false)
            ->column("insertUserID", "int", false, ["index"])
            ->column("updateUserID", "int", false, ["index"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set();

        // Implement theme revisions (release: 2020.009)
        // relates to: https://github.com/vanilla/knowledge/issues/1772
        $db = $this->database->sql();

        $themes = $this->themeModel->get(['revisionID' => 0]);
        foreach ($themes as $theme) {
            $rev = $this->themeRevisionModel->insert([
                'themeID' => $theme['themeID'],
                'name' => 'rev 1.0'
            ]);
            $this->themeModel->update(
                ['revisionID' => $rev],
                ['themeID' => $theme['themeID']]
            );
            $this->themeAssetModel->update(
                ['activeRevision' => $rev],
                ['themeID' => $theme['themeID']]
            );
            $this->themeAssetModel->update(
                ['revisionID' => $rev],
                ['themeID' => $theme['themeID']]
            );
        }

    }
}
