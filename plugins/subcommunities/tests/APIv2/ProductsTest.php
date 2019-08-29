<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use ProductModel;

/**
 * Test the /api/v2/product endpoint.
 */
class ProductsTest extends AbstractAPIv2Test {

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'subcommunities'];
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'. ProductModel::FEATURE_FLAG.'.Enabled', true, true, false);
    }

    /**
     * Test Put setProductFeatureFlag.
     */
    public function testPutSetProductFeatureFlag() {
        // ensure that we can disable the product feature.
        $result = $this->api()->put(
            'products/setProductFeatureFlag'
        );

        $this->assertEquals(200, $result->getStatusCode());
        $response = $result->getBody();
        $this->assertEquals(false, c( 'Feature.' . ProductModel::FEATURE_FLAG . '.Enabled'));
        $this->assertEquals(ProductModel::DISABLED, $response['status']);
    }

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
     * Test POST /product
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
     * Test PATCH /product
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
     * Test Delete /product
     *
     * @expectedException \Exception
     * @expectedExceptionMessage No rows matched the provided criteria.
     */
    public function testDeleteProduct() {
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
}
