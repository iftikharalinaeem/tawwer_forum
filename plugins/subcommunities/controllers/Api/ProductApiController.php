<?php
namespace Vanilla\Subcommunities\Controllers\Api;

use Garden\Schema\Schema;
use AbstractApiController;
use productModel;
use Vanilla\FeatureFlagHelper;

class ProductApiController extends AbstractApiController {

    /** @var Schema */
    private $productSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var productModel */
    private $productModel;

    /** @var boolean */
    private $productFeatureEnabled;

    /**
     * productApiController constructor.
     *
     * @param productModel $productModel
     */
    public function __construct(productModel $productModel) {
        $this->productModel = $productModel;
        $this->productFeatureEnabled = false;
    }

    /**
     * Simplified product schema.
     *
     * @param string $type
     * @return Schema
     */
    public function productSchema(string $type = ""): Schema {
        if ($this->productSchema === null) {
            $this->productSchema = $this->schema(Schema::parse([
                "productID",
                "name",
                "body",
                "dateInserted",
                "dateUpdated?"
            ])->add($this->fullSchema()), "Product");
        }

        return $this->schema($this->productSchema, $type);
    }

    /**
     * Full product schema.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "productID:i" => "Unique product ID.",
            "name:s" => [
                "allowNull" => true,
                "description" => "Name of the product.",
            ],
            "body:s" => [
                "allowNull" => true,
                "description" => "Description of the product.",
            ],
            "dateInserted:dt" => "When the product was created.",
            "dateUpdated:dt?" => "When the product was updated.",
        ]);
    }

    /**
     * Get an ID-only schema for products
     *
     * @param string $type
     * @return Schema
     */
    private function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = Schema::parse([
                "id:i" => "The article ID."
            ]);
        }

        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get all products.
     *
     * @return array|mixed
     */
    public function index(): array {
        $this->getProductFeatureStatus();
        $this->permission("'Garden.SignIn.Allow'");
        $out = $this->schema([
            ":a" => $this->productSchema(),
        ], "out");

        $products = $this->productModel->get();

        $products = $out->validate($products);

        return $products;
    }

    /**
     * Get a product by it's ID.
     *
     * @param int $id
     * @return array
     */
    public function get(int $id): array {
        $this->getProductFeatureStatus();
        $this->permission("Garden.SignIn.Allow");
        $this->idParamSchema()->setDescription("Get an product id.");

        $id = $id ?? null;
        $where = ["productID" => $id];
        $product = $this->productModel->selectSingle($where);

        $out = $this->productSchema("out");
        $result = $out->validate($product);

        return $result;
    }

    /**
     * Create a new product.
     *
     * @param array $body
     * @return array|mixed
     */
    public function post(array $body): array {
        $this->getProductFeatureStatus();
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema(
            Schema::parse([
                "name",
                "body?",
            ]),
            "in"
        );

        $body = $in->validate($body);

        $productID = $this->productModel->insert($body);

        $product = $this->productModel->selectSingle(["productID" => $productID]);
        $out = $this->productSchema("out");
        $product = $out->validate($product);

        return $product;
    }

    /**
     * Update an existing product.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch(int $id, array $body = []): array {
        $this->getProductFeatureStatus();
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema(
            Schema::parse([
                "name:s",
                "body:s?",
            ]),
            "in"
        );

        $out = $this->productSchema("out");

        $body = $in->validate($body);

        $where = ["productID" => $id];

        $this->productModel->update($body, $where);
        $product = $this->productModel->selectSingle($where);
        $product = $out->validate($product);

        return $product;
    }

    /**
     * Delete a specified product.
     *
     * @param int $id
     */
    public function delete(int $id) {
        $this->getProductFeatureStatus();
        $this->permission("Garden.Moderation.Manage");
        $this->idParamSchema()->setDescription("Delete a product id.");
        $product = $this->productModel->selectSingle(["productID" => $id]);

        if (is_array($product) && array_key_exists('productID', $product)) {
            $this->productModel->delete(["productID" => $product["productID"]]);
        }
    }

    /**
     * Enable/Disable the Product Feature.
     * 
     * @return array
     */
    public function put_setProductFeatureFlag() {
        $this->permission("Garden.Moderation.Manage");

        $config = "Feature." . productModel::FEATURE_FLAG . ".Enabled";

        if (!FeatureFlagHelper::featureEnabled(productModel::FEATURE_FLAG)) {
            saveToConfig($config, true);
            $this->productFeatureEnabled = true;
        } else {
            saveToConfig($config, false);
            $this->productFeatureEnabled = false;
        }

        $enabled["status"] = ($this->productFeatureEnabled) ? productModel::ENABLED : productModel::DISABLED;

        return $enabled;
    }

    /**
     * Check if the product feature is enabled.
     */
    private function getProductFeatureStatus() {
        if (!$this->productFeatureEnabled) {
            FeatureFlagHelper::ensureFeature(productModel::FEATURE_FLAG);
        }
    }
}
