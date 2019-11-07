<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\TranslationsAPI\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Gdn_Configuration;
use Exception;
use Vanilla\TranslationsAPI\models\resourceModel;
use Vanilla\TranslationsAPI\models\TranslationPropertyModel;
use Vanilla\TranslationsAPI\models\TranslationModel;

class TranslationsApiController extends AbstractApiController {

    /** @var Schema */
    private $resourceSchema;

    /** @var Schema */
    private $getResourceSchema;

    /** @var Schema */
    private $postTranslationSchema;

    /** @var Schema */
    private $translationSchema;

    /** @var resourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var Gdn_Configuration */
    private $configurationModule;

    /** @var TranslationPropertyModel */
    private $translationPropertyModel;

    /**
     * TranslationsApiController constructor.
     *
     * @param resourceModel $resourcesModel
     * @param TranslationModel $translationModel
     * @param TranslationPropertyModel $translationPropertyModel
     * @param Gdn_Configuration $configurationModule
     */
    public function __construct(
        resourceModel $resourcesModel,
        TranslationModel $translationModel,
        TranslationPropertyModel $translationPropertyModel,
        Gdn_Configuration $configurationModule
    ) {
        $this->resourceModel = $resourcesModel;
        $this->translationModel = $translationModel;
        $this->translationPropertyModel = $translationPropertyModel;
        $this->configurationModule = $configurationModule;

    }

    /**
     * Create a resource.
     *
     * @param array $body
     * @throws ClientException
     */
    public function post_resource(array $body = []){
        $this->permission("Garden.Moderation.Manage");
        $in = $this->resourceSchema("in");
        $body = $in->validate($body);

        $body["sourceLocale"] = $body["sourceLocale"] ?? $this->configurationModule->get("Garden.Locale");

        $resourceExists = $this->resourceModel->get(
            [
                "name" => $body["name"],
                "sourceLocale" => $body["sourceLocale"],
                "url" => $body["url"]
            ]
        );

        if ($resourceExists) {
            throw new ClientException(
                "The resource ". $body["url"] . "-" . $body["sourceLocale"] . "-" .  $body["name"] . " exists"
            );
        } else {
            $this->resourceModel->insert($body);
        }
    }

    /**
     * PUT /Translations/:resource
     *
     * @param string $path Resource slug
     * @param array $body
     */
    public function put(string $path, array $body = []) {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema([":a" => $this->putTranslationSchema()], "in");
        $path = substr($path, 1);

        $records = $in->validate($body);

        foreach ($records as $record) {
            $this->resourceModel->ensureResourceExists($path);
            $resourceKeyRecord = array_intersect_key($record,TranslationPropertyModel::RESOURCE_KEY_RECORD);

            $translationProperty = $this->translationPropertyModel->getTranslationProperty($resourceKeyRecord);

            if (!$translationProperty) {
                $newTranslationProperty = $this->translationPropertyModel->createTranslationProperty($path, $resourceKeyRecord);
                $key = $newTranslationProperty["key"];
            } else {
                $key = $translationProperty["key"];
            }
            $this->translationModel->createTranslation(
                $path,
                $record["locale"],
                $key,
                $record["translation"]
            );
        }
    }

    /**
     * GET /Translations/:resource
     *
     * @param string $path
     * @param array $query
     * @return array
     */
    public function get(string $path, array $query = []) {
        $this->permission("Garden.Moderation.Manage");
        $path = substr($path, 1);

        $in = $this->getTranslationsSchema("in");

        $query["url"] = $path;
        $query = $in->validate($query);

        $where["rk.resource"] = $query["url"];

        if (isset($query["recordType"])) {
            $where["rk.recordType"] = $query["recordType"];
        }
        if (isset($query["recordID"]) && isset($query["recordType"])) {
            $where["rk.recordID"] = $query["recordID"];
        }
        if (isset($query["recordKey"]) && isset($query["recordType"])) {
            $where["rk.recordKey"] = $query["recordKey"];
        }
        if (isset($query["locale"])) {
            $where["t.locale"] = $query["locale"];
        }

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $options = [
            "limit" => $limit,
            "offset" => $offset,
        ];

        $results = $this->translationPropertyModel->getTranslations($where, $options);

        $out = $this->schema([":a" => $this->translationSchema()], "out");
        $results = $out->validate($results);

        return $results;
    }

    /**
     * Simplified resource schema.
     *
     * @param string $type
     * @return Schema
     */
    public function resourceSchema(string $type = ""): Schema {
        if ($this->resourceSchema === null) {
            $this->resourceSchema = $this->schema(Schema::parse([
                "name",
                "sourceLocale?",
                "url",
            ]));
        }
        return $this->schema($this->resourceSchema, $type);
    }

    /**
     * Simplified resource schema.
     *
     * @param string $type
     * @return Schema
     */
    public function getTranslationsSchema(string $type = ""): Schema {
        if ($this->getResourceSchema === null) {
            $this->getResourceSchema = $this->schema(Schema::parse([
                "url?",
                "recordType?",
                "recordID?",
                "recordKey?",
                "locale",
                "limit" => [
                    "default" => 100,
                    "minimum" => 1,
                    "maximum" => 100,
                    "type" => "integer",
                ],
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                    "maximum" => 100,
                ],
            ]));
        }
        return $this->schema($this->getResourceSchema, $type);
    }

    /**
     * Post translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function putTranslationSchema(string $type = ""): Schema {
        if ($this->postTranslationSchema === null) {
            $this->postTranslationSchema = $this->schema(Schema::parse([
                "recordType",
                "recordID?",
                "recordKey?",
                "locale",
                "propertyName",
                "translation",
            ]));
        }
        return $this->schema($this->postTranslationSchema, $type);
    }

    /**
     * simple translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function translationSchema(string $type = ""): Schema {
        if ($this->translationSchema === null) {
            $this->translationSchema = $this->schema(Schema::parse([
                "resource",
                "recordType",
                "key",
                "locale",
                "translation",
            ]));
        }
        return $this->schema($this->translationSchema, $type);
    }
}
