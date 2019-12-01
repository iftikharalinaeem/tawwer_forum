<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\Subcommunities\Models\SubcommunitySiteSection;
use Vanilla\Subcommunities\Models\SubcomunitiesSiteSectionProvider;

/**
 * Test the /api/v2/product endpoint.
 */
class ProductsTest extends AbstractAPIv2Test {

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'subcommunities'];
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'. ProductModel::FEATURE_FLAG.'.Enabled', false, true, false);
    }

    /**
     * Test Put setProductFeatureFlag.
     */
    public function testPutProductFeatureFlag() {
        // ensure that we can enable the product feature.
        $result = $this->api()->put(
            'products/product-feature-flag',
            ['enabled' => true]
        );

        $this->assertEquals(200, $result->getStatusCode());
        $response = $result->getBody();
        $this->assertEquals(true, c( 'Feature.' . ProductModel::FEATURE_FLAG . '.Enabled'));
        $this->assertEquals(true, $response['enabled']);
    }

    /**
     * Test GET /products
     */
    public function testGetProduct() {
        $record = $this->getRecord();

        $result = $this->api()->post(
            'products',
            $record
        );

        $body = $result->getBody();
        $this->api()->get('products/'.$body['productID']);

        $this->assertEquals($record['name'], $body['name']);
        $this->assertEquals($record['body'], $body['body']);
    }

    /**
     * Test POST /products
     */
    public function testPostProduct() {
        $record = $this->getRecord();

        $result = $this->api()->post(
            'products',
            $record
        );

        $this->assertEquals(201, $result->getStatusCode());
        $body = $result->getBody();

        $this->assertEquals($record['name'], $body['name']);
        $this->assertEquals($record['body'], $body['body']);
    }

    /**
     * Test PATCH /products
     */
    public function testPatchProduct() {
        $record = $this->getRecord();
        $result = $this->api()->post(
            'products',
            $record
        );
        $body = $result->getBody();

        $updatedRecord = [
            'name' =>'Update Product',
            'body' => 'Update Test product',
        ];

        $result = $this->api()->patch(
            'products/'.$body['productID'],
            $updatedRecord
        );

        $this->assertEquals(200, $result->getStatusCode());
        $body = $result->getBody();

        $this->assertEquals($body['name'],'Update Product');
        $this->assertEquals($body['body'], 'Update Test product');
    }

    /**
     * Test Delete /products
     */
    public function testDeleteProduct() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found.');
   
        $record = $this->getRecord();
        $result = $this->api()->post(
            'products',
            $record
        );
        $body = $result->getBody();

        $result = $this->api()->delete(
            'products/'.$body['productID']
        );
        $this->assertEquals(204, $result->getStatusCode());
        $this->api()->get('products/'.$body['productID']);
    }

    /**
     * Test Delete /products with associated subcommunity.
     */
    public function testDeleteProductWithSubcommunity() {
        $this->expectException(\Garden\Web\Exception\ClientException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessageMatches('/Product \\d is associated with \\d subcommunities./');
   
        $record = $this->getRecord();
        $result = $this->api()->post(
            'products',
            $record
        );
        $body = $result->getBody();

        $this->createSubcommunity($body['productID']);

        $this->api()->delete(
            'products/'.$body['productID']
        );
    }

    /**
     * Test that a site-section-group is added if there is one.
     */
    public function testGetProductWithSiteSectionGroup() {
        $record = $this->getRecord();
        $result = $this->api()->post(
            'products',
            $record
        );
        $body = $result->getBody();

        $this->createSubcommunity($body['productID']);

        $product =$this->api()->get('products/'.$body['productID'])
            ->getBody();

        $this->assertEquals(
            SubcommunitySiteSection::SUBCOMMUNITY_GROUP_PREFIX.$body['productID'],
            $product['siteSectionGroup']
        );
    }

    /**
     * Create a record.
     *
     * @return array
     */
    protected function getRecord(): array {
        $record = [
            'name' => uniqid('Product'),
            'body' => 'Test product',
        ];
        return $record;
    }

    /**
     * Create a Subcommunity with a product.
     *
     * @param int $id
     */
    protected function createSubcommunity($id): void {
        $subcommunityModel = self::container()->get(\SubcommunityModel::class);

        $record = [
            'Name' => 'Test_Subcommunity',
            'Folder' => 'Test_Subcommunity',
            'Category' => 1,
            'Locale' => 'en',
            'ProductID' => $id
        ];

        $subcommunityModel->insert($record);
    }
}
