<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Controllers\Api;

use Garden\Schema\Schema;
use AbstractApiController;
use Garden\Web\Exception\ClientException;
use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\FeatureFlagHelper;
use SubcommunityModel;

/**
 * API controller for managing the products resource.
 */
class ProductsApiController extends AbstractApiController {

    /** @var Schema */
    private $productSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var ProductModel */
    private $productModel;

    /** @var SubcommunityModel */
    private $subcommunityModel;

    /** @var boolean */
    private $productFeatureEnabled;

    /**
     * ProductApiController constructor.
     *
     * @param ProductModel $productModel
     * @param SubcommunityModel $subcommunityModel
     */
    public function __construct(ProductModel $productModel, SubcommunityModel $subcommunityModel) {
        $this->productModel = $productModel;
        $this->subcommunityModel = $subcommunityModel;
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
     * Get an ID-only schema for products.
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

        $where = [];
        $options = ['orderFields' => 'name', 'orderDirection' => 'asc'];
        $products = $this->productModel->get($where, $options);

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
     * @throws ClientException If a product is associated with a subcommunity.
     */
    public function delete(int $id) {
        $this->getProductFeatureStatus();
        $this->permission("Garden.Moderation.Manage");
        $this->idParamSchema()->setDescription("Delete a product id.");

        $where = ["productID" => $id];
        // Find out if the product is associated to a subcommunity
        $subcommunitiesAsscoiated = $this->subcommunityModel->getWhere(["ProductID" => $id])->resultArray();

        if ($subcommunitiesAsscoiated) {
            $subcommunityCount = count($subcommunitiesAsscoiated);
            $message = sprintf(t("Product is associated with %s subcommunities."), $subcommunityCount);
            $subcommunityDetails = [];
            $subcommunityDetails["subcommunityCount"] = $subcommunityCount;
            $subcommunityDetails["subcommunityIDs"] = array_column($subcommunitiesAsscoiated, "SubcommunityID");

            throw new ClientException($message, 409, $subcommunityDetails);
        }

        $product = $this->productModel->selectSingle($where);

        if (is_array($product) && array_key_exists('productID', $product)) {
            $this->productModel->delete(["productID" => $product["productID"]]);
        }
    }

    /**
     * Enable/Disable the Product Feature.
     *
     * @param array $body
     * @return array
     */
    public function put_productFeatureFlag(array $body = []): array {
        $this->permission("Garden.Moderation.Manage");

        $in = $this->schema(
            Schema::parse([
                "enabled:b",
            ]),
            "in"
        );
        $out = $this->schema(
            Schema::parse([
                "enabled:b",
            ]),
            "out"
        );

        $body = $in->validate($body);

        $config = "Feature." . ProductModel::FEATURE_FLAG . ".Enabled";

        $saved = false;
        if ($body["enabled"] && array_key_exists("enabled", $body)) {
            $saved = saveToConfig($config, true);
        } elseif (!$body["enabled"] && array_key_exists("enabled", $body)) {
            $saved = saveToConfig($config, false);
        }

        // saveToConfig returns true on success and null if no changes needed.
        if ($saved || is_null($saved)) {
            $this->productFeatureEnabled  = c($config);
        }

        $enabled["enabled"] = $this->productFeatureEnabled;
        $enabled = $out->validate($enabled);

        return $enabled;
    }

    /**
     * Check if the product feature is enabled.
     */
    private function getProductFeatureStatus() {
        if (!$this->productFeatureEnabled) {
            FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        }
    }
}
