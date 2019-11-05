<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsAPI\models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 *
 */
class ResourceKeyModel extends PipelineModel {

    /** @var resourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var Gdn_Session */
    private $session;

    const RESOURCE_KEY_RECORD = [
        "recordType" => true,
        "recordID" => true,
        "recordKey" => true,
        "propertyType" => true,
        "key" => true,
        "parentRecordID" => true,
        "parentRecordType" => true,
    ];

    /**
     * resourceKeyModel constructor.
     *
     * @param Gdn_Session $session
     * @param resourceModel $resourcesModel
     * @param TranslationModel $translationModel
     */
    public function __construct(
        Gdn_Session $session,
        resourceModel $resourcesModel,
        TranslationModel $translationModel
    ) {
        parent::__construct("resourceKey");

        $this->session = $session;
        $this->resourceModel = $resourcesModel;
        $this->translationModel = $translationModel;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Create a resource key.
     *
     * @param string $path
     * @param array $record
     *
     * @return array
     */
    public function createResourceKey(string $path, array $record): array {
        $this->resourceModel->ensureResourceExists($path, $record["recordType"]);
        $record["resource"] = $path;

        $identifier = $this->getRecordIdentifier($record);

        $record["key"] = self::constructKey($record["recordType"], $identifier, $record["propertyType"]);
        $resourceKey = $this->get(["key" =>  $record["key"]]);

        if (!$resourceKey) {
            $this->insert($record);
            $resourceKey = $this->get(["key" => $record["key"]]);
        }

        $result = reset($resourceKey);
        return $result;
    }

    /**
     * Construct a key for the resource translations.
     *
     * @param string $recordType
     * @param mixed $recordID
     * @param string $recordProperty
     *
     * @return string;
     */
    public static function constructKey(string $recordType, $recordID, string $recordProperty): string {
        return $recordType.'.'.$recordID.'.'.$recordProperty;
    }

    /**
     * Get a resource key with the translations.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function getResourceWithTranslation(array $where = [], array $options = []) {

        $sql = $this->sql();
        $sql->from($this->getTable() . " as rk")
            ->join("translations t", "rk.key = t.key", 'inner');

        $sql->where($where);
        $result = $sql->get()->resultArray();

        return $result;
    }

    /**
     * @param array $record
     * @return mixed
     * @throws ClientException
     */
    public function getRecordIdentifier(array $record) {
        $identifier = null;

        if (isset($record["recordID"]) && isset($record["recordKey"])) {
            throw new ClientException("A resource key can't have both a recordID or recordKey");
        } elseif (!isset($record["recordID"]) && !isset($record["recordKey"])) {
            throw new ClientException("A resource key must have either a recordID or recordKey");
        } else {
            $identifier = $record["recordID"] ?? $record["recordKey"];
        }
        return $identifier;
    }
}
