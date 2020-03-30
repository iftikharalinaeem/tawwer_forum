<?php

use Vanilla\Models\ThemeModel;
use Vanilla\Models\ThemeModelHelper;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;


class ThemeModelTests extends AbstractAPIv2Test {

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
     * @var ThemeModel;
     */
    protected static $themeModel;

    /**
     * Setup Function function
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        /** @var Gdn_Configuration $config */
        self::$config = static::container()->get(Gdn_Configuration::class);
        /** @var MockSiteSectionProvider $siteSectionProvider */
        self::$siteSectionProvider = self::container()->get(MockSiteSectionProvider::class);
        /** @var ThemeModel self::$themeModel */
        self::$themeModel = self::container()->get(ThemeModel::class);
    }

    /**
     * Test getCurrentTheme with only Garden.Theme set.
     */
    public function testGetCurrentThemeBaseThemeKey() {
        self::$config->set('Garden.Theme', 'keystone');
        $theme = self::$themeModel->getCurrentTheme();
        $this->assertEquals('keystone', $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with only Garden.CurrentTheme set.
     */
    public function testGetCurrentThemeCurrentKey() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'keystone');

        $theme = self::$themeModel->getCurrentTheme();
        $this->assertEquals('keystone', $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with a preview in the session
     */
    public function testGetCurrentThemePreview() {
        self::$themeModel->setPreviewTheme('lavendermoon');
        $theme = self::$themeModel->getCurrentTheme();
        /** @var ThemeModelHelper self::$themeModelHelper */
        $themeModelHelper = self::container()->get(ThemeModelHelper::class);
        $themeModelHelper->cancelSessionPreviewTheme();
        $this->assertEquals('lavendermoon', $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with no valid themes in the config.
     */
    public function testGetCurrentThemeFallBackFSTheme() {
        self::$config->set('Garden.Theme', 'notheme');
        self::$config->set('Garden.CurrentTheme', 'z');

        $this->expectWarning();
        $theme = self::$themeModel->getCurrentTheme();

        $this->assertEquals('theme-foundation', $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with Garden.Theme set to DBTheme.
     */
    public function testGetCurrentThemeDBTheme() {
        $dbTheme = $this->createDBTheme('First DB Theme');

        self::$config->set('Garden.CurrentTheme', null);
        self::$config->set('Garden.Theme', $dbTheme['themeID']);
        $theme = self::$themeModel->getCurrentTheme();
        $this->assertEquals($dbTheme['themeID'], $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with Garden.CurrentTheme set to DBTheme.
     */
    public function testGetCurrentThemeBaseTheme() {
        $dbTheme = $this->createDBTheme('Second DB Theme');

        self::$config->set('Garden.CurrentTheme', $dbTheme['themeID']);
        $theme = self::$themeModel->getCurrentTheme();
        $this->assertEquals($dbTheme['themeID'], $theme['themeID']);
    }

    /**
     * Test getCurrentTheme with session preview set to DBTheme.
     */
    public function testGetCurrentThemePreviewDBTheme() {
        $dbTheme = $this->createDBTheme('Third DB Theme');

        $theme = self::$themeModel->setPreviewTheme($dbTheme["themeID"]);

        /** @var ThemeModelHelper self::$themeModelHelper */
        $themeModelHelper = self::container()->get(ThemeModelHelper::class);
        $themeModelHelper->cancelSessionPreviewTheme();

        $this->assertEquals($dbTheme["themeID"], $theme['themeID']);
    }

    /**
     * Test getCurrentTheme no valid DBThemes set in config.
     */
    public function testGetCurrentThemeFallBackDBTheme() {
        self::$config->set('Garden.Theme', '10000');
        self::$config->set('Garden.CurrentTheme', '10000');

        $this->expectWarning();
        $theme = self::$themeModel->getCurrentTheme();

        $this->assertEquals('theme-foundation', $theme['themeID']);
    }

    /**
     * test getCurrentThemeAddon with a DB Theme.
     */
    public function testGetCurrentThemeAddonDbTheme() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', '1');

        $themeAddon = self::$themeModel->getCurrentThemeAddon();

        $this->assertEquals('theme-foundation', $themeAddon->getKey());
    }

    /**
     * test getCurrentThemeAddon with a DB Theme.
     */
    public function testGetCurrentThemeAddonFsTheme() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'keystone');

        $themeAddon = self::$themeModel->getCurrentThemeAddon();

        $this->assertEquals('keystone', $themeAddon->getKey());
    }

    /**
     * Test getCurrentTheme with Garden.CurrentTheme set.
     */
    public function testGetCurrentThemeSiteSection() {
        self::$config->set('Garden.Theme', 'theme-foundation');
        self::$config->set('Garden.CurrentTheme', 'theme-foundation');

        $this->setCurrentSiteSection('newSection', 'lavendermoon');

        $theme = self::$themeModel->getCurrentTheme();

        $this->assertEquals('lavendermoon', $theme['themeID']);
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
    private function setCurrentSiteSection($name = 'site-section', $themeID = 'theme-foundation' ): void {
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
