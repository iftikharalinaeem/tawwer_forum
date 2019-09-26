<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Subcommunities\Models\SubcommunitySiteSection;
use Vanilla\Subcommunities\Models\SubcomunitiesSiteSectionProvider;


/**
 * Tests for SubcommunitiesSiteSectionProvider.
 */
class SubcommunitiesSiteSectionProviderTest extends AbstractAPIv2Test {

    /** @var SubcomunitiesSiteSectionProvider */
    private $provider;

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'subcommunities'];
        parent::setupBeforeClass();
        self::createSubcommunities();
    }

    /**
     * @inheritdoc
     */
    public function setUp() {
        $this->provider = static::container()->get(SubcomunitiesSiteSectionProvider::class);
    }

    /**
     * Test for getAll.
     */
    public function testGetAll() {
        $all = $this->provider->getAll();
        $this->assertCount(6, $all);
    }

    /**
     * Test for getByID.
     */
    public function testGetByID() {
        $subcomunnitySiteSection = $this->provider->getByID(1);
        $this->assertEquals("es", $subcomunnitySiteSection->getContentLocale());
    }

    /**
     * Test for getByBasePath.
     */
    public function testGetByBasePath() {
        $subcomunnitySiteSection = $this->provider->getByBasePath("ru");
        $this->assertEquals("ru", $subcomunnitySiteSection->getContentLocale());
    }

    /**
     * Test for getForLocale.
     */
    public function testGetForLocale() {
        $subcomunnitySiteSections = $this->provider->getForLocale("en");
        $this->assertCount(2, $subcomunnitySiteSections);
    }

    /**
     * Test for getCurrentSiteSection.
     */
    public function testGetCurrentSiteSection() {
        $subcomunnitySiteSections = $this->provider->getCurrentSiteSection();
        $this->assertEquals("es", $subcomunnitySiteSections->getContentLocale() );
    }
    
    private static function createSubcommunities() {
       $subcommunityModel = static::container()->get(\SubcommunityModel::class);
       $rows = [
            [
                "locale" => "es",
                "folder" => "es"
            ],
            [
                "locale" => "ru",
                "folder" => "ru"
            ],
            [
                "locale" => "fr",
                "folder" => "fr"
            ],
            [
                "locale" => "en",
                "folder" => "en_1"
            ],
            [
                "locale" => "en",
                "folder" => "en_2"
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
                "ProductID" => 5
            ];
        }
        foreach ($subCommunities as $subCommunity) {
             $subcommunityModel->insert($subCommunity);
        }

        $currentSubcommunity = $subcommunityModel->getID(1, DATASET_TYPE_ARRAY);
        $subcommunityModel::setCurrent($currentSubcommunity);
    }
}
