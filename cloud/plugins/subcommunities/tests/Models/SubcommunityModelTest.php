<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;
use Vanilla\Fixtures\SubcommunityModelFixture as SubcommunityModel;

/**
 * Test capabilities of SubcommunityModel.
 */
class SubcommunityModelTest extends TestCase {

    use SiteTestTrait {
        setupBeforeClass as private siteTestBeforeClass;
    }

    /**
     * {@inheritDoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["subcommunities", "vanilla"];
        static::siteTestBeforeClass();
    }

    /** @var SubcommunityModel */
    private $subcommunityModel;

    /**
     * Create a new Subcommunity.
     *
     * @param string $name
     * @param string $folder
     * @param string $locale
     * @param integer|null $categoryID
     * @param boolean|null $isDefault
     * @return array
     */
    private function addSubcommunity(
        string $name,
        ?string $folder = null,
        ?string $locale = null,
        ?int $categoryID = null,
        ?bool $isDefault = false
    ): array {
        $subcommunityID = $this->subcommunityModel->insert([
            "CategoryID" => $categoryID ?: 1,
            "Folder" => $folder ?: preg_replace("/[^A-Za-z0-9\\-\\_]+/", "-", strtolower($name)),
            "IsDefault" => (int)$isDefault,
            "Locale" => $locale ?: "en",
            "Name" => $name,
        ]);
        $row = $this->subcommunityModel->getWhere(
            ["SubcommunityID" => $subcommunityID],
            "SubcommunityID",
            "asc",
            1
        )->firstRow(\DATASET_TYPE_ARRAY);
        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function setup(): void {
        SubcommunityModel::resetStaticProperties();
        $instance = self::container()->get(SubcommunityModel::class);
        $this->subcommunityModel = $instance;
        SubcommunityModel::instance($instance);
    }

    /**
     * Verify proper URL generation for a subcommunity.
     *
     * @return void
     */
    public function testCalculateSubcommunityUrl(): void {
        $row = $this->addSubcommunity(__FUNCTION__);
        SubcommunityModel::calculateRow($row);

        // Bootstrap trait sets up the test case name as the webroot and asset root. Expect to see that included.
        $this->assertEquals("http://vanilla.test/subcommunitymodeltest/testcalculatesubcommunityurl", $row["Url"]);
    }
}
