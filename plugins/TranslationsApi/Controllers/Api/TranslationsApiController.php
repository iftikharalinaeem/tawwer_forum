<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\TranslationsAPI\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Gdn_Configuration;
use Vanilla\TranslationsAPI\models\resourceModel;
use Vanilla\TranslationsAPI\models\ResourceKeyModel;
use Vanilla\TranslationsAPI\models\TranslationModel;

class TranslationsApiController extends AbstractApiController {

    /** @var Schema */
    private $resourceSchema;

    /** @var Schema */
    private $postTranslation;

    /** @var resourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var Gdn_Configuration */
    private $configurationModule;

    /** @var ResourceKeyModel */
    private $resourceRecordModel;

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
        $this->resourceRecordModel = $resourceRecordModel;
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
        $body = $in->validate($body);
        $path = substr($path, 1);

        $results = [];

        foreach ($body as $b) {

            $resourceKeyRecord = array_intersect_key($b,ResourceKeyModel::RESOURCE_KEY_RECORD);
            $translationRecord = array_intersect_key($b,TranslationModel::TRANSLATION_RECORD);

            $resourceKey = $this->resourceRecordModel->createResourceKey($path, $resourceKeyRecord);
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

        $results = $this->resourceRecordModel->getResourceWithTranslation($where);

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
     * Simplified resource schema.
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
                "propertyType?",
                "translation?",
                "parentRecordID?",
                "parentRecordType?",
            ]));
        }
        return $this->schema($this->postTranslation, $type);
    }


}
