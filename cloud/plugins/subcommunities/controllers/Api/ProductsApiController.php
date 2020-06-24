<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Controllers\Api;

use Garden\Schema\Schema;
use AbstractApiController;
use Garden\Web\Exception\ClientException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Subcommunities\Models\ProductModel;
use SubcommunityModel;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;

/**
 * API controller for managing the products resource.
 */
class ProductsApiController extends AbstractApiController {

    const ERR_PRODUCT_IS_ATTACHED_TO_A_SUBCOMMUNITY = "ERR_PRODUCT_IS_ATTACHED_TO_A_SUBCOMMUNITY";
    const FEATURE_FLAG_CONFIG_KEY = "Feature." . ProductModel::FEATURE_FLAG . ".Enabled";

    /** @var Schema */
    private $productSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var ProductModel */
    private $productModel;

    /** @var SubcommunityModel */
    private $subcommunityModel;

    /** @var \Gdn_Configuration */
    private $config;

    /**
     * ProductApiController constructor.
     *
     * @param ProductModel $productModel
     * @param SubcommunityModel $subcommunityModel
     * @param \Gdn_Configuration $config
     */
    public function __construct(
        ProductModel $productModel,
        SubcommunityModel $subcommunityModel,
        \Gdn_Configuration $config
    ) {
        $this->productModel = $productModel;
        $this->subcommunityModel = $subcommunityModel;
        $this->config = $config;
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
                "siteSectionGroup?",
                "dateInserted",
                "insertUserID",
                "dateUpdated?",
                "updateUserID?"
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
                "description" => "Name of the product.",
            ],
            "body:s" => [
                "allowNull" => true,
                "description" => "Description of the product.",
            ],
            "siteSectionGroup:s?" => [
                "allowNull" => true,
                "description" => "The site section group associated to the product.",
            ],
            "dateInserted:dt" => "When the product was created.",
            "insertUserID:i" => "Unique ID of the user who originally created the product.",
            "dateUpdated:dt?" => "When the product was updated.",
            "updateUserID:i?" =>  "Unique ID of the last user to update the product.",
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
     * @return array
     */
    public function index(): array {
        FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        $this->permission();
        $out = $this->schema([
            ":a" => $this->productSchema(),
        ], "out");

        $where = [];
        $options = ['orderFields' => 'name', 'orderDirection' => 'asc'];
        $products = $this->productModel->get($where, $options);

        foreach ($products as &$product) {
            $product["siteSectionGroup"] = $this->getSiteSectionGroup($product["productID"]);
        }

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
        FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        $this->permission();
        $this->idParamSchema()->setDescription("Get an product id.");

        $product = $this->productByID($id);
        $product["siteSectionGroup"]= $this->getSiteSectionGroup($product["productID"]);

        $out = $this->productSchema("out");
        $result = $out->validate($product);

        return $result;
    }

    /**
     * Create a new product.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array {
        FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        $this->permission("Garden.Moderation.Manage");

        $in = $this->schema(
            Schema::parse([
                "name:s",
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
        FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema(
            Schema::parse([
                "name:s?",
                "body:s?",
            ]),
            "in"
        );

        $out = $this->productSchema("out");
        $body = $in->validate($body);

        if (isset($body["name"]) || isset($body["body"])) {
            $where = ["productID" => $id];
            $this->productModel->update($body, $where);
        }

        $product = $this->productByID($id);
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
        FeatureFlagHelper::ensureFeature(ProductModel::FEATURE_FLAG);
        $this->permission("Garden.Moderation.Manage");
        $this->idParamSchema()->setDescription("Delete a product id.");


        // Find out if the product is associated to a subcommunity
        $subcommunitiesAsscoiated = $this->subcommunityModel->getWhere(["ProductID" => $id])->resultArray();

        if ($subcommunitiesAsscoiated) {
            $subcommunityCount = count($subcommunitiesAsscoiated);
            $message = sprintf(t("Product %s is associated with %s subcommunities."), $id, $subcommunityCount);
            $details = [];
            $details["errorType"] = self::ERR_PRODUCT_IS_ATTACHED_TO_A_SUBCOMMUNITY;
            $details["subcommunityCount"] = $subcommunityCount;
            $details["subcommunityIDs"] = array_column($subcommunitiesAsscoiated, "SubcommunityID");

            throw new ClientException($message, 409, $details);
        }
        $product = $this->productByID($id);

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

        $this->config->saveToConfig(self::FEATURE_FLAG_CONFIG_KEY, $body['enabled']);
        $enabled = $out->validate([
            'enabled' => FeatureFlagHelper::featureEnabled(ProductModel::FEATURE_FLAG),
        ]);

        return $enabled;
    }

    /**
     * Get a product by it's ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException If the product could not be located.
     */
    protected function productByID(int $id): array {
        $where = ["productID" => $id];
        try {
            $product = $this->productModel->selectSingle($where);
        } catch (NoResultsException $ex) {
            throw new NotFoundException('Product');
        }

        return $product;
    }

    /**
     * Get the site-section-group the product is associated to.
     *
     * @param $productID
     * @return string
     */
    protected function getSiteSectionGroup($productID) {
        $siteSectionGroup = $this->productModel::makeSiteSectionGroupKey($productID);
        return $siteSectionGroup;
    }
}
