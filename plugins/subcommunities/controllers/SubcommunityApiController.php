<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;


class subcommunityApiController extends AbstractApiController {
   
    /** @var Schema */
    private $subcommunitySchema;
    
    /** @var Schema */
    private $idParamSchema;
   
    /** @var SubcommunityModel */
    private $subcommunityModel;

    public function __construct(SubcommunityModel $subcommunityModel) {
        $this->subcommunityModel = $subcommunityModel;
    }

    /**
     *
     * @param string $type
     * @return Schema
     */
    public function subcommunitySchema(string $type = ""): Schema {
        if ($this->subcommunitySchema === null) {
            $this->subcommunitySchema = $this->schema(Schema::parse([
                "SubcommunityID?",
                "Name",
                "Folder",
                "CategoryID?",
                "Locale",
                "productID?"
            ])->add($this->fullSchema()), "Product");
        }
        return $this->schema($this->subcommunitySchema, $type);
    }
    /**
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "SubcommunityID:i" => "Unique Subcommunity ID.",
            "Name:s" =>  "Name of the Subcommunity.",
            "Folder:s" => "Subcomunity folder",
            "CategoryID:i" => [
                "allowNull" => true,
                "description" => "Category ID associated with the subcommunity",
            ],
            "Locale:s" => "Locale associated with the subcommunity",
            "DateInserted:dt" => "",
            "InsertUserID:s" => "",
            "DateUpdated:dt" => [
                "allowNull" => true,
                "description" => "",
            ],
            "UpdateUserID:i" => [
                "allowNull" => true,
                "description" => "",
            ],
            "Attributes:s" => [
                "allowNull" => true,
                "description" => "",
            ],
            "Sort:i" => "",
            "IsDefault:i" => [
                "allowNull" => true,
                "description" => "",
            ],
            "productID:i" => [
                "allowNull" => true,
                "description" => "",
            ],
        ]);
    }
    /**
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

    public function index() {
        //this->permission("Garden.SignIn.Allow");
        $out = $this->schema([
            ":a" => $this->fullSchema(),
        ], "out");
        $subcommunities = $this->subcommunityModel->get();
        $subcommunities = $out->validate($subcommunities);
        return $subcommunities;
    }
}