<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use phpDocumentor\Reflection\Types\Boolean;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionSchema;

/**
 * KnowledgeBasesApiController schemes
 */
trait KnowledgeBasesApiSchemes {

    /** @var Schema */
    private $knowledgeBasePostSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var Schema */
    private $getKnowledgeBaseSchema;

    /** @var Schema */
    private $knowledgeBaseFragmentSchema;

    /**
     * Get a schema representing all available fields for a knowledge base.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "knowledgeBaseID" => [
                "description" => "Unique knowledge base ID.",
                "type" => "integer",
            ],
            "name" => [
                "description" => "Name for the knowledge base.",
                "minLength" => 1,
                "type" => "string",
            ],
            "description" => [
                "description" => "Description for the knowledge base.",
                "minLength" => 1,
                "maxLength" => 300,
                "type" => "string",
            ],
            "sortArticles" => [
                "allowNull" => true,
                "description" => "Sort order for articles of the knowledge base.",
                "enum" => KnowledgeBaseModel::getAllSorts(),
                "type" => "string",
            ],
            "insertUserID" => [
                "description" => "Unique ID of the user who originally created the knowledge base.",
                "type" => "integer",
            ],
            "dateInserted:dt" => [
                "description" => "When the knowledge base was created.",
                "type" => "datetime",
            ],
            "updateUserID:i" => [
                "description" => "Unique ID of the last user to update the knowledge base.",
                "type" => "integer",
            ],
            "dateUpdated:dt" => [
                "description" => "When the knowledge base was last updated.",
                "type" => "datetime",
            ],
            "countArticles" => [
                "description" => "Total articles in the knowledge base.",
                "type" => "integer",
            ],
            "countCategories" => [
                "description" => "Total categories in the knowledge base.",
                "type" => "integer",
            ],
            "urlCode" => [
                "description" => "URL code to the knowledge base.",
                "minLength" => 1,
                "type" => "string",
            ],
            "url" => [
                "description" => "Full URL to the knowledge base.",
                "type" => "string",
            ],
            "icon" => [
                "description" => "Full URL to the icon of knowledge base.",
                "type" => "string",
            ],
            "bannerImage" => [
                "description" => "Full URL to the banner image of knowledge base.",
                "type" => "string",
            ],
            "bannerContentImage" => [
                "description" => "Full URL to the banner content image of knowledge base.",
                "type" => "string",
            ],
            "sourceLocale" => [
                "description" => "sourceLocale of knowledge base.",
                "type" => "string",
                "default" => "en"
            ],
            "viewType" => [
                "allowNull" => true,
                "description" => "Sort order for articles of the knowledge base.",
                "enum" => KnowledgeBaseModel::getAllTypes(),
                "type" => "string",
            ],
            "rootCategoryID:i" => [
                "description" => "Root knowledge category ID of knowledge base.",
                "type" => "integer",
            ],
            "defaultArticleID:i" => [
                "description" => "Default article ID of knowledge base.",
                "type" => "integer",
                "allowNull" => true
            ],
            "status:s" => [
                'description' => "Knowledge base status.",
                'enum' => KnowledgeBaseModel::getAllStatuses(),
            ],
            "hasCustomPermission:b" => [
                'description' => "Knowledge base has custom permission.",
                'default' => false,
            ],
            "viewRoleIDs:a?",
            "editRoleIDs:a?",
            "siteSectionGroup:s" => [
                'description' => "Site section group. Ex: subcommunity product key",
                'default' => DefaultSiteSection::DEFAULT_SECTION_GROUP
            ],
            "siteSections:a?" => new SiteSectionSchema(),
            "isUniversalSource:b?" => [
                "description" => "Is this Knowledge-Base Universal",
                "type" => "boolean",
                "default" => false,
                "allowNull" =>  false,
            ],
            "universalTargetIDs:a?",
            "universalSourceIDs:a?",
            "universalTargets?" =>[':a' => $this->knowledgeBaseFragmentSchema()],
            "universalSources?" => [':a' => $this->knowledgeBaseFragmentSchema()],
        ]);
    }

    /**
     * Simplified knowledge-base schema.
     *
     * @param string $type
     * @return Schema
     */
    public function knowledgeBaseFragmentSchema(string $type = ""): Schema {
        if ($this->knowledgeBaseFragmentSchema === null) {
            $this->knowledgeBaseFragmentSchema = Schema::parse([
                "knowledgeBaseID",
                "name",
                "icon?",
                "sortArticles?",
                "viewType",
                "urlCode",
                "siteSectionGroup",
            ], $type);
        }
        return $this->knowledgeBaseFragmentSchema;
    }

    /**
     * Get a knowledge base POST schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function knowledgeBasePostSchema(string $type = "in"): Schema {
        if ($this->knowledgeBasePostSchema === null) {
            $this->knowledgeBasePostSchema = $this->schema(
                Schema::parse([
                    "name",
                    "description",
                    "icon?",
                    "bannerImage?",
                    "bannerContentImage?",
                    "siteSectionGroup",
                    "sourceLocale",
                    "viewType",
                    "sortArticles?",
                    "status?",
                    "urlCode",
                    "hasCustomPermission?",
                    "viewRoleIDs?",
                    "editRoleIDs?",
                    "isUniversalSource:b?",
                    "universalTargetIDs:a?",
                ])->add($this->fullSchema()),
                "KnowledgeBasePost"
            );
        }

        return $this->schema($this->knowledgeBasePostSchema, $type);
    }

    /**
     * Get an ID-only knowledge base schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                        "id:i" => "Knowledge base ID.",
                        "locale?",
                        "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["siteSections", "universalTargets", "universalSources"]),
                    ])->addValidator('locale', [$this->localeApi, 'validateLocale']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get partial schema for GET request on /Knowledge-bases.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function getKnowledgeBaseSchema(string $type = "in"): Schema {
        if ($this->getKnowledgeBaseSchema === null) {
            $this->getKnowledgeBaseSchema = $this->schema(
                Schema::parse([
                    "status" => [
                    "default" => KnowledgeBaseModel::STATUS_PUBLISHED,
                    ],
                    "sourceLocale?",
                    "locale?",
                    "siteSectionGroup?"
                ])
                    ->addValidator('locale', [$this->localeApi, 'validateLocale'])
                    ->addValidator('sourceLocale', [$this->localeApi, 'validateLocale']),
                $type
            );
        }
        return $this->schema($this->getKnowledgeBaseSchema, $type);
    }
}
