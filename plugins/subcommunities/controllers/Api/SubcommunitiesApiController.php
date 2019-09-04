<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Controllers\Api;

use Garden\Schema\Schema;
use AbstractApiController;
use SubcommunityModel;
use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\ApiUtils;

/**
 * API controller for managing the subcommunities resource.
 */
class SubcommunitiesApiController extends AbstractApiController {
   
    /** @var Schema */
    private $subcommunitySchema;

    /** @var Schema */
    private $productSchema;

    /** @var Schema */
    private $idParamSchema;
   
    /** @var SubcommunityModel */
    private $subcommunityModel;

    /** @var ProductModel */
    private $productModel;

    public function __construct(SubcommunityModel $subcommunityModel, ProductModel $productModel) {
        $this->subcommunityModel = $subcommunityModel;
        $this->productModel = $productModel;
    }

    /**
     * Simple subcommunity Schema.
     *
     * @param string $type
     * @return Schema
     */
    public function subcommunitySchema(string $type = ""): Schema {
        if ($this->subcommunitySchema === null) {
            $this->subcommunitySchema = $this->schema(Schema::parse([
                "subcommunityID:i",
                "name:s",
                "folder:s",
                "categoryID:s",
                "locale:s",
                "productID:i?",
                "product?" => $this->productModel->productFragmentSchema(),
                "localeNames:o?",
                "url:s"
            ]));
        }
        return $this->schema($this->subcommunitySchema, $type);
    }
    /**
     * Full subcommunity Schema.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "subcommunityID:i",
            "name:s",
            "folder:s" => "Subcomunity folder",
            "categoryID:i",
            "locale:s" ,
            "dateInserted:dt",
            "insertUserID:s",
            "dateUpdated:dt?",
            "updateUserID:i?",
            "attributes:s?",
            "sort:i?",
            "isDefault:i?",
            "productID:i?",
            "product?" => $this->productModel->productFragmentSchema(),
            "localeNames:o?",
            "url:s"
        ]);
    }

    /**
     * Get an ID-only schema for subcommunities.
     *
     * @param string $type
     * @return Schema
     */
    private function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema =  Schema::parse([
                "id:i" => ""
            ]);
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get a list of subcommunities.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query): array {
        $this->permission();
        $in = $this->schema([
            "expand?" => ApiUtils::getExpandDefinition(["product","category","locale"])
        ]);
        $out = $this->schema([":a" => $this->subcommunitySchema(), "out"]);

        $query = $in->validate($query);
        $results = $this->subcommunityModel::all();

        $results = array_values($results);

        foreach ($results as &$result) {
            $this->subcommunityModel::calculateRow($result);
        }

        if ($this->isExpandField('product', $query['expand'])) {
            $this->productModel->expandProduct($results);
        }

        if ($this->isExpandField('locale', $query['expand'])) {
            $locales = array_column($results, 'Locale', 'Locale');
            $this->expandLocales($results, $locales);
        }

        $results = ApiUtils::convertOutputKeys($results);
        $subcommunities = $out->validate($results, true);

        return $subcommunities;
    }

    /**
     * Get a subcommunity by it's ID.
     *
     * @param int $id
     * @param array $query
     * @return array
     */
    public function get(int $id, array $query): array {
        $this->permission("Garden.SignIn.Allow");
        $this->idParamSchema()->setDescription("Get a Subcommunity ID");

        $in = $this->schema([
            "expand?" => ApiUtils::getExpandDefinition(["product","category"])
        ]);
        $query = $in->validate($query);
        
        $result = $this->subcommunityModel->getID($id);
        $this->subcommunityModel::calculateRow($result);

        if ($this->isExpandField('product', $query['expand'])) {
            $this->productModel->expandProduct($result);
        }

        $out = $this->subcommunitySchema();
        $result = $out->validate($result);

        return $result;
    }

    /**
     * Expand the locale names.
     *
     * @param array $rows
     * @param array $locales
     */
    public function expandLocales(array &$rows, array $locales) {
        if (count($rows) === 0) {
            return;
        }
        reset($rows);
        $single = is_string(key($rows));

        $populate = function(array &$row, array $locales) {
            foreach ($locales as $locale) {
                $locales[$locale] = \Locale::getDisplayLanguage($row["Locale"], $locale);
            }
            setValue('localeNames', $row, $locales);
        };

        if ($single) {
            $populate($rows, $locales);
        } else {
            foreach ($rows as &$row) {
                $populate($row, $locales);
            }
        }
    }
}
