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
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\TranslationsAPI\models\resourceModel;
use Vanilla\TranslationsAPI\models\ResourceKeyModel;
use Vanilla\TranslationsAPI\models\TranslationModel;

class TranslationsApiController extends AbstractApiController {

    /** @var Schema */
    private $resourceSchema;

    /** @var Schema */
    private $postTranslation;

    /** @var Schema */
    private $simpleTranslationSchema;

    /** @var resourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var Gdn_Configuration */
    private $configurationModule;

    /** @var ResourceKeyModel */
    private $resourceKeyModel;

    /**
     * TranslationsApiController constructor.
     *
     * @param resourceModel $resourcesModel
     * @param TranslationModel $translationModel
     * @param ResourceKeyModel $resourceRecordModel
     * @param Gdn_Configuration $configurationModule
     */
    public function __construct(
        resourceModel $resourcesModel,
        TranslationModel $translationModel,
        ResourceKeyModel $resourceRecordModel,
        Gdn_Configuration $configurationModule
    ) {
        $this->resourceModel = $resourcesModel;
        $this->translationModel = $translationModel;
        $this->resourceKeyModel = $resourceRecordModel;
        $this->configurationModule = $configurationModule;

    }

    /**
     * Create a resource.
     *
     * @param array $body
     * @return array
     */
    public function post_index(array $body = []): array {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->resourceSchema("in");
        $body = $in->validate($body);

        $body["sourceLocale"] = $this->configurationModule->get("Garden.Locale");
        $this->resourceModel->insert($body);

        $where = [
            "name" => $body["name"],
            "url" => $body["url"],
            "sourceLocale" => $body["sourceLocale"],
        ];

        $result = $this->resourceModel->get($where);
        $result = reset($result);
        $out = $this->resourceSchema("out");
        $result = $out->validate($result);

        return $result;
    }

    /**
     * Create a resource key with a translation.
     *
     * @param string $path Resource slug
     * @param array $body
     * @return array
     */
    public function post(string $path, array $body = []): array {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->schema([":a?" => $this->postTranslation()], "in");
        $records = $in->validate($body);
        $path = substr($path, 1);

        $results = [];

        foreach ($records as $record) {
            $resourceKeyRecord = array_intersect_key($record,ResourceKeyModel::RESOURCE_KEY_RECORD);
            $translationRecord = array_intersect_key($record,TranslationModel::TRANSLATION_RECORD);

            $resourceKey = $this->resourceKeyModel->createResourceKey($path, $resourceKeyRecord);
            $translation = $this->translationModel->createTranslation(
                $path,
                $translationRecord["locale"],
                $resourceKey["key"],
                $translationRecord["translation"]
            );
            $results[] = $this->normalizeResourceTranslations($resourceKey, $translation);
        }

        $out = $this->schema([":a?" => $this->postTranslation()], "out");
        $results = $out->validate($results);

        return $results;
    }

    /**
     * @param $recordKey
     * @param $translation
     * @return array
     */
    private function normalizeResourceTranslations($recordKey, $translation) {
        $result = [
            "resource" => $recordKey["resource"],
            "recordType" => $recordKey["recordType"],
            "recordKey" => $recordKey["recordKey"],
            "key" => $recordKey["key"],
            "locale" => $translation["locale"],
            "translation" => $translation["translation"],
        ];

        return $result;
    }

    /**
     * @param string $path
     * @param array $body
     *
     * @return array
     */
    public function put(string $path, array $body) {
        $this->permission("Garden.Moderation.Manage");
        $in = $this->patchTranslation("in");
        $body = $in->validate($body);

        $path = substr($path, 1);
        $identifier = $this->resourceKeyModel->validateRecordIdentifier($body);
        $key = ResourceKeyModel::constructKey($body["recordType"], $identifier, $body["propertyType"]);

        $translation = $this->getSingleTranslation($path, $body, $key);
        $updated = $this->translationModel->update(
            ["translation" => $body["translation"]],
            ["resource" => $path, "key" => $key, "locale" => $body["locale"]]
        );

        $result = [];
        if ($updated) {
            $result = $this->getSingleTranslation($path, $body, $key);
            $result["previousTranslation"] = $translation["translation"];
        }

        $out = $this->simpleTranslationSchema("in");
        $result = $out->validate($result);

        return $result;
    }

    /**
     * @param string $path
     * @param array $query
     * @return array
     */
    public function get(string $path, array $query) {
        $this->permission("Garden.Moderation.Manage");
        // $in = $this->resourceSchema("in");
        // $query = $in->validate($query);
        $path = substr($path, 1);
        $where["rk.resource"] = $path;

        if (isset($query["recordType"])) {
            $where["rk.recordType"] = $query["recordType"];
        }
        if (isset($query["recordID"])) {
            $where["rk.recordID"] = $query["recordID"];
        }
        if (isset($query["locale"])) {
            $where["t.locale"] = $query["locale"];
        }

        $results = $this->resourceKeyModel->getResourceWithTranslation($where);

        return $results;
    }

    /**
     * Get a resource.
     *
     * @param array $query
     * @return array
     */
    public function get_index(array $query = []): array {
        $this->permission("Garden.Moderation.Manage");
       // $in = $this->resourceSchema("in");
       // $query = $in->validate($query);

        $results = $this->resourceModel->get();

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
     * Post translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function postTranslation(string $type = ""): Schema {
        if ($this->postTranslation=== null) {
            $this->postTranslation = $this->schema(Schema::parse([
                "recordType",
                "recordID?",
                "recordKey?",
                "locale?",
                "propertyType",
                "translation?",
                "parentRecordID?",
                "parentRecordType?",
            ]));
        }
        return $this->schema($this->postTranslation, $type);
    }

    /**
     * Patch translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function patchTranslation(string $type = ""): Schema {
        if ($this->postTranslation=== null) {
            $this->postTranslation = $this->schema(Schema::parse([
                "recordType",
                "recordID?",
                "recordKey?",
                "locale",
                "propertyType",
                "translation",
                "previousTranslation?"
            ]));
        }
        return $this->schema($this->postTranslation, $type);
    }

    /**
     * Patch translation schema.
     *
     * @param string $type
     * @return Schema
     */
    public function simpleTranslationSchema(string $type = ""): Schema {
        if ($this->simpleTranslationSchema === null) {
            $this->simpleTranslationSchema = $this->schema(Schema::parse([
                "resource",
                "key",
                "locale",
                "translation",
                "previousTranslation?"
            ]));
        }
        return $this->schema($this->simpleTranslationSchema, $type);
    }



    /**
     * @param string $path
     * @param array $body
     * @param string $key
     *
     * @return array
     * @throws ClientException
     */
    protected function getSingleTranslation(string $path, array $body, string $key): array {
        try {
            $translation = $this->translationModel->selectSingle(["resource" => $path, "key" => $key, "locale" => $body["locale"]]);
        } catch (NoResultsException $e) {
            throw new ClientException("Resource not found");
        }
        return $translation;
    }

}
