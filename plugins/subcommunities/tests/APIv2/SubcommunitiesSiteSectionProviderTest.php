<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Subcommunities\Models\SubcomunitiesSiteSectionProvider;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Tests for SubcommunitiesSiteSectionProvider.
 */
class SubcommunitiesSiteSectionProviderTest extends AbstractAPIv2Test {

    /** @var SiteSectionModel */
    private static $provider;

    protected static $addons = ['vanilla', 'subcommunities'];

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        self::createSubcommunities();
        $subcommunityModel = self::container()->get(\SubcommunityModel::class);
        $config = self::container()->get(ConfigurationInterface::class);
        $router = self::container()->get(\Gdn_Router::class);
        $provider = new SubcomunitiesSiteSectionProvider($subcommunityModel, $config, $router);
        static::container()->setInstance(SiteSectionProviderInterface::class, $provider);
        self::$provider = static::container()->get(SiteSectionModel::class);
    }

    /**
     * Test for getAll
     */
    public function testGetAll() {
        $all = self::$provider->getAll();
        $this->assertCount(5, $all);
    }

    /**
     * Test for getByBasePath.
     */
    public function testGetByBasePath() {
        $subcomunnitySiteSection = self::$provider->getByBasePath("/ru");
        $this->assertEquals("ru", $subcomunnitySiteSection->getContentLocale());
    }

    /**
     * Test for getForLocale.s
     */
    public function testGetForLocale() {
        $subcomunnitySiteSections = self::$provider->getForLocale("en");
        $this->assertCount(2, $subcomunnitySiteSections);
    }

    /**
     * Test for getCurrentSiteSection.
     */
    public function testGetCurrentSiteSection() {
        $subcomunnitySiteSections = self::$provider->getCurrentSiteSection();
        $this->assertEquals("es", $subcomunnitySiteSections->getContentLocale() );
    }

    /**
     * Create subcommunities for Tests.
     */
    private static function createSubcommunities() {
        /** @var \SubcommunityModel $subcommunityModel */
       $subcommunityModel = static::container()->get(\SubcommunityModel::class);
       $rows = [
            [
                "locale" => "es",
                "folder" => "es",
                "isDefault" => true
            ],
            [
                "locale" => "ru",
                "folder" => "ru",
                "isDefault" => false
            ],
            [
                "locale" => "fr",
                "folder" => "fr",
                "isDefault" => false
            ],
            [
                "locale" => "en",
                "folder" => "en_1",
                "isDefault" => false
            ],
            [
                "locale" => "en",
                "folder" => "en_2",
                "isDefault" => false
            ]
        ];

        $subCommunities = [];
        foreach ($rows as $row) {
            $subCommunities[] = [
                "Name" => uniqid("test-subcommunity"),
                "Folder" => $row["folder"],
                "CategoryID" => 1,
                "Locale" => $row["locale"],
                "Sort" => 1,
                "ProductID" => 5,
                "isDefault" => $row["isDefault"]
            ];
        }
        foreach ($subCommunities as $subCommunity) {
             $subcommunityModel->insert($subCommunity);
        }

        $currentSubcommunity = $subcommunityModel->getID(1, DATASET_TYPE_ARRAY);
        $subcommunityModel::setCurrent($currentSubcommunity);
    }
}
