<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\ThemingApi\DbThemeProvider;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Class ThemeModelTests
 */
class ThemeModelTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'themingapi'];

    /**
     * @var string The resource route.
     */
    protected $baseUrl = "/themes";

    /**
     * @var ConfigurationModule;
     */
    protected static $config;

    /**
     * @var MockSiteSectionProvider;
     */
    protected static $siteSectionProvider;
    /**
     * @var ThemeService
     */
    protected static $themeService;

    /**
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        $container->rule(ThemeService::class)
            ->addCall("addThemeProvider", [new Reference(FsThemeProvider::class)])
            ->addCall("addThemeProvider", [new Reference(DbThemeProvider::class)])
        ;
    }

    /**
     * Setup Function function
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var Gdn_Configuration $config */
        self::$config = static::container()->get(Gdn_Configuration::class);
        /** @var MockSiteSectionProvider $siteSectionProvider */
        self::$siteSectionProvider = self::container()->get(MockSiteSectionProvider::class);
        /** @var ThemeService self::$themeModel */
        self::$themeService = self::container()->get(ThemeService::class);
    }

    /**
     * Test getCurrentTheme with only Garden.Theme set.
     */
    public function testGetCurrentThemeBaseThemeKey() {
        self::$config->set('Garden.Theme', 'keystone');
        $theme = self::$themeService->getCurrentTheme();
        $this->assertEquals('keystone', $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with only Garden.CurrentTheme set.
     */
    public function testGetCurrentThemeCurrentKey() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'keystone');

        $theme = self::$themeService->getCurrentTheme();
        $this->assertEquals('keystone', $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with a preview in the session
     */
    public function testGetCurrentThemePreview() {
        self::$themeService->setPreviewTheme('lavendermoon');
        $theme = self::$themeService->getCurrentTheme();
        /** @var ThemeServiceHelper self::$themeModelHelper */
        $themeModelHelper = self::container()->get(ThemeServiceHelper::class);
        $themeModelHelper->cancelSessionPreviewTheme();
        $this->assertEquals('lavendermoon', $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with no valid themes in the config.
     */
    public function testGetCurrentThemeFallBackFSTheme() {
        self::$config->set('Garden.Theme', 'notheme');
        self::$config->set('Garden.CurrentTheme', 'z');

        $theme = @self::$themeService->getCurrentTheme();

        $this->assertEquals(ThemeService::FALLBACK_THEME_KEY, $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with Garden.Theme set to DBTheme.
     */
    public function testGetCurrentThemeDBTheme() {
        $dbTheme = $this->createDBTheme('First DB Theme');

        self::$config->set('Garden.CurrentTheme', null);
        self::$config->set('Garden.Theme', $dbTheme['themeID']);
        $theme = self::$themeService->getCurrentTheme();
        $this->assertEquals($dbTheme['themeID'], $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with Garden.CurrentTheme set to DBTheme.
     */
    public function testGetCurrentThemeBaseTheme() {
        $dbTheme = $this->createDBTheme('Second DB Theme');

        self::$config->set('Garden.CurrentTheme', $dbTheme['themeID']);
        $theme = self::$themeService->getCurrentTheme();
        $this->assertEquals($dbTheme['themeID'], $theme->getThemeID());
    }

    /**
     * Test getCurrentTheme with session preview set to DBTheme.
     */
    public function testGetCurrentThemePreviewDBTheme() {
        $dbTheme = $this->createDBTheme('Third DB Theme');

        $theme = self::$themeService->setPreviewTheme($dbTheme["themeID"]);

        /** @var ThemeServiceHelper self::$themeModelHelper */
        $themeModelHelper = self::container()->get(ThemeServiceHelper::class);
        $themeModelHelper->cancelSessionPreviewTheme();

        $this->assertEquals($dbTheme["themeID"], $theme->getThemeID());
    }

    /**
     * Test getCurrentThemeAddon with a DB Theme.
     */
    public function testGetCurrentThemeAddonDbTheme() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', '1');

        $themeAddon = self::$themeService->getCurrentThemeAddon();

        $this->assertEquals('theme-foundation', $themeAddon->getKey());
    }

    /**
     * Test getCurrentThemeAddon with a DB Theme.
     */
    public function testGetCurrentThemeAddonFsTheme() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'keystone');

        $themeAddon = self::$themeService->getCurrentThemeAddon();

        $this->assertEquals('keystone', $themeAddon->getKey());
    }

    /**
     * Test getCurrentThemeAddon with invalid themes.
     */
    public function testGetCurrentThemeAddonFsThemeFail() {
        self::$config->set('Garden.Theme', 'zzzzzzz');
        self::$config->set('Garden.CurrentTheme', 'zzz');

        $themeAddon = @self::$themeService->getCurrentThemeAddon();

        $this->assertEquals(ThemeService::FALLBACK_THEME_KEY, $themeAddon->getKey());
    }

    /**
     * Test Getting a theme's master key.
     */
    public function testGetMasterThemeKey() {
        $masterKey = self::$themeService->getMasterThemeKey(1);
        $this->assertEquals('theme-foundation', $masterKey);
    }

    /**
     * Test Getting a theme's master key with invalid id.
     */
    public function testGetMasterThemeKeyNonExistentTheme() {
        $masterKey = self::$themeService->getMasterThemeKey(100000);
        $this->assertEquals('theme-foundation', $masterKey);
    }

    /**
     * Test Getting a theme's master key with invalid key.
     */
    public function testGetMasterThemeKeyNonExistentThemeFs() {
        $masterKey = @self::$themeService->getMasterThemeKey('zz');
        $this->assertEquals('theme-foundation', $masterKey);
    }

    /**
     * Test that switching the provider properly cleans up the DB.
     */
    public function testProviderSwitch() {
        $newDBTheme = $this->createDBTheme('Swapping DB theme');
        @$this->api()->put('/themes/current', ['themeID' => $newDBTheme['themeID']]);
        /** @var \Vanilla\ThemingApi\Models\ThemeModel $dbThemeModel */
        $dbThemeModel = self::container()->get(\Vanilla\ThemingApi\Models\ThemeModel::class);

        $result = $dbThemeModel->get(['current' => 1]);
        $this->assertCount(1, $result);

        // Change theme theme.
        self::$themeService->setCurrentTheme('theme-foundation');
        // Provider switched. Our DB rows should be cleaned up.

        $result = $dbThemeModel->get(['current' => 1]);
        $this->assertCount(0, $result, 'No DB rows should match.');
    }

    /**
     * Test getting a theme's asset data.
     */
    public function testGetThemeAssetDataDB() {
        $assetData = self::$themeService->getTheme(1)->getAsset('header')->getValue();
        $this->assertEquals('<header>First DB Theme</header>', $assetData);
    }

    /**
     * Test getCurrentTheme with Garden.CurrentTheme set.
     */
    public function testGetCurrentThemeSiteSection() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'theme-foundation');

        $this->setCurrentSiteSection('newSection', 'lavendermoon');

        $theme = self::$themeService->getCurrentTheme();

        $this->assertEquals('lavendermoon', $theme->getThemeID());
    }

    /**
     * Create a DB Theme.
     *
     * @param string $name
     * @return array
     */
    private function createDBTheme($name = 'DB Theme') {
        $body = [
            'name' => $name,
            'assets' => [
                'header' => [
                    "data" => "<header>{$name}</header>",
                    "type" => "html"
                ],
                'variables' => [
                    "data" => '{}',
                    "type" => "json"
                ]
            ],
            'parentTheme' => 'theme-foundation',
            'parentVersion' => '1.0.0'
        ];
        $theme = $this->api()->post('themes', $body)->getBody();

        return $theme;
    }

    /**
     * Change the current site section.
     *
     * @param string $name
     * @param string $themeID
     */
    private function setCurrentSiteSection($name = 'site-section', $themeID = 'theme-foundation'): void {
        $mockSiteSection = new MockSiteSection(
            $name,
            'en',
            '/'.$name,
            'mockSiteSection'.$name,
            "mockSiteSectionGroup-".$name,
            [
                'Destination' => 'discussions',
                'Type' => 'Internal'
            ],
            $themeID
        );

        self::$siteSectionProvider->setCurrentSiteSection($mockSiteSection);
    }
}
