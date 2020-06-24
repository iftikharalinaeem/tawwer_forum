<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\Subcommunities\Models\SubcommunitySiteSection;

/**
 * Test the APIv2 subcommunities endpoints.
 */
class SubcommunitiesTests extends AbstractAPIv2Test {

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'subcommunities'];
        parent::setupBeforeClass();
        self::createSubcommunity();
    }

    /**
     * Test /subcommunities endpoint
     */
    public function testIndex() {
        self::createSubcommunity();
        $result = $this->api()->get(
            'subcommunities'
        );

        $body = $result->getBody();
        $this->assertEquals(2, count($body));
    }

    /**
     * Test /subcommunities/{ID} endpoint
     */
    public function testGetByID() {
        $subcommunityID = self::createSubcommunity();
        $result = $this->api()->get(
            'subcommunities/'.$subcommunityID
        );

        $body = $result->getBody();
        $this->assertEquals($subcommunityID, $body["subcommunityID"]);
    }

    /**
     * Test product implementation with subcommunities.
     */
    public function testProductImplementation() {
        $productModel = self::container()->get(ProductModel::class);
        $productID= $productModel->insert([
            'name' => 'New Product Name',
            'body' => 'New Product Body'
        ]);

        $subcommunityID = self::createSubcommunity($productID);

        $result = $this->api()->get(
            'subcommunities/'.$subcommunityID.'?expand=product'
        )->getBody();

        $this->assertEquals("New Product Name", $result["product"]["name"]);
        $this->assertEquals("New Product Body", $result["product"]["body"]);
        $this->assertEquals(SubcommunitySiteSection::SUBCOMMUNITY_GROUP_PREFIX . $productID, $result["siteSectionGroup"]);
        $this->assertEquals(SubcommunitySiteSection::SUBCOMMUNITY_SECTION_PREFIX . $subcommunityID, $result["siteSectionID"]);
    }

    /**
     * Create a Subcommunity with a product.
     *
     * @param int $id
     * @return int
     */
    protected static function createSubcommunity($id = 1): int {
        $subcommunityModel = self::container()->get(\SubcommunityModel::class);
        $subcommunity = [
            'Name' => 'Test_Subcommunity',
            'Folder' => 'Subcommunity_'. round(microtime(true) * 1000) . rand(1, 1000),
            'Category' => 1,
            'Locale' => 'en',
            'ProductID' => $id,
        ];

        $id = $subcommunityModel->insert($subcommunity);

        return $id;
    }
}
