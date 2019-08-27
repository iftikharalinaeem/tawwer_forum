<?php

use Garden\Schema\Schema;
use Vanilla\FeatureFlagHelper;

class productApiController extends AbstractApiController {

    /** @var Schema */
    private $productSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var productModel */
    private $productModel;

    /**
     * productApiController constructor.
     *
     * @param productModel $productModel
     */
    public function __construct(productModel $productModel) {
        $this->productModel = $productModel;
        FeatureFlagHelper::ensureFeature(productModel::FEATURE_FLAG);
    }

    /**
     *
     * @param string $type
     * @return Schema
     */
    public function productSchema(string $type = ""): Schema {
        if ($this->productSchema === null) {
            $this->productSchema = $this->schema(Schema::parse([
                "productID?",
                "name",
                "body",
                "dateInserted?",
                "dateUpdated?"
            ])->add($this->fullSchema()), "Product");
        }
        return $this->schema($this->productSchema, $type);
    }

    /**
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
                "description" => "description of the product",
            ],
            "dateInserted:dt?" => "When the product was created.",
            "dateUpdated:dt?" => "When the product was updated.",
        ]);
    }

    /**
     * @param string $type
     * @return Schema
     */
    private function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema =  Schema::parse([
                "id:i" => "The article ID."
            ]);
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * @return array|mixed
     */
    public function index() {
        $this->permission("Garden.Moderation.Manage");
        $out = $this->schema([
            ":a" => $this->fullSchema(),
        ], "out");

        $products = $this->productModel->get();

        $products = $out->validate($products);

        return $products;
    }

    /**
     * @param int $id
     * @return array
     */
    public function get(int $id): array {
        $this->permission("Garden.Moderation.Manage");
        $this->idParamSchema()->setDescription("Get an product id.");

        $id = $id ?? null;
        $where = ["productID" => $id];
        $product = $this->productModel->selectSingle($where);

        $out = $this->productSchema("out");
        $result = $out->validate($product);

        return $result;
    }

    /**
     * @param array $body
     * @return array|mixed
     */
    public function post(array $body) {
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
     * @param int $id
     * @param array $body
     * @return array
     */

    public function patch(int $id, array $body = []): array {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema(
            Schema::parse([
                "name:s",
                "body:s?",
            ]),
            "in"
        );

        $out = $this->schema(Schema::parse([
            "productID",
        ]));

        $product = $this->productModel->selectSingle(["productID" => $id]);

        if ($product) {
            $body = $in->validate($body);
            $where = ["productID" => $id];
            $updated = $this->productModel->update($body, $where);
            if ($updated) {
                $product["productID"] = $out->validate(["productID" => $product["productID"]]);
                return $product["productID"];
            }
        }

    }

    /**
     * @param int $id
     */
    public function delete(int $id) {
        $this->permission("Garden.Moderation.Manage");
        $this->idParamSchema()->setDescription("Delete a product id.");
        $product = $this->productModel->selectSingle(["productID" => $id]);

        if (is_array($product) && array_key_exists('productID', $product))
        $this->productModel->delete(["productID" => $product["productID"]]);
    }

}
