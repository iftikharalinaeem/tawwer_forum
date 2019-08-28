<?php


use Garden\Schema\Schema;

class SubcommunitiesApiController extends AbstractApiController {
   
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
                "SubcommunityID",
                "Name",
                "Folder",
                "CategoryID",
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
            "Name:s?" =>  "Name of the Subcommunity.",
            "Folder:s?" => "Subcomunity folder",
            "CategoryID:i?" => [
                "allowNull" => true,
                "description" => "Category ID associated with the subcommunity",
            ],
            "Locale:s?" => "Locale associated with the subcommunity",
            "DateInserted:dt?" => "",
            "InsertUserID:s?" => "",
            "DateUpdated:dt?" => [
                "allowNull" => true,
                "description" => "",
            ],
            "UpdateUserID:i?" => [
                "allowNull" => true,
                "description" => "",
            ],
            "Attributes:s?" => [
                "allowNull" => true,
                "description" => "",
            ],
            "Sort:i?" => "",
            "IsDefault:i?" => [
                "allowNull" => true,
                "description" => "",
            ],
            "productID:i?" => [
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

    /**
     * @return array
     */
    public function index() {
        $this->permission("Garden.SignIn.Allow");
        $out = $this->schema([
            ":a" => $this->fullSchema(),
        ], "out");

        $subcommunities = $this->subcommunityModel->get()->datasetType(DATASET_TYPE_ARRAY);

        $subcommunities = $out->validate($subcommunities);
        return $subcommunities;
    }

    /**
     * @param int $id
     * @return array
     */

    public function get(int $id): array {
        $this->permission("Garden.SignIn.Allow");
        $this->idParamSchema()->setDescription("Get a Subcommunity ID");
        $id = $id ?? null;
        $subcommunity = $this->subcommunityModel->getID($id);
        $out = $this->subcommunitySchema("out");
        $result = $out->validate($subcommunity);

        return $result;
    }

}