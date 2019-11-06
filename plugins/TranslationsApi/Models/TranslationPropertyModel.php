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
class TranslationPropertyModel extends PipelineModel {

    /** @var resourceModel */
    private $resourceModel;

    /** @var TranslationModel */
    private $translationModel;

    /** @var Gdn_Session */
    private $session;

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 100;

    const RESOURCE_KEY_RECORD = [
        "recordType" => true,
        "recordID" => true,
        "recordKey" => true,
        "propertyName" =>  true,
        "propertyType" => true,
        "key" => true,
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
        parent::__construct("translationProperty");

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
        $record["resource"] = $path;

        $identifier = $this->getRecordIdentifier($record);

        $record["key"] = self::constructKey($record["propertyName"], $record["recordType"], $identifier);
        $this->insert($record);
        $resourceKey = $this->get(["key" => $record["key"]]);

        $result = reset($resourceKey);
        return $result;
    }

    /**
     * Get a Translation-Property.
     *
     * @param array $record
     * @return array
     */
    public function getTranslationProperty(array $record) :array {
        $recordIdentifier = $this->getRecordIdentifier($record);
        $key = self::constructKey($record["propertyName"], $record["recordType"], $recordIdentifier);
        $translationProperty = $this->get(["key" =>  $key]);
        if ($translationProperty) {
            $translationProperty = reset($translationProperty);
        }

        return $translationProperty;
    }

    /**
     * Construct a key for the resource translations.
     *
     * @param string $recordProperty
     * @param string $recordType
     * @param mixed $recordID
     *
     * @return string;
     */
    public static function constructKey(string $recordProperty, string $recordType = null, $recordID = null): string {
        return $recordType.'.'.$recordID.'.'.$recordProperty;
    }

    /**
     * Get translations.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function getTranslations(array $where = [], array $options = []) {
        $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
        $offset = $options["offset"] ?? 0;

        $sql = $this->sql();
        $sql->from($this->getTable() . " as rk")
            ->join("translations t", "rk.key = t.key", 'inner');

        $sql->where($where);
        $sql->limit($limit, $offset);

        $result = $sql->get()->resultArray();

        return $result;
    }

    /**
     * Get the record identifier used to build a translation-property key.
     *
     * @param array $record
     * @return mixed
     * @throws ClientException
     */
    private function getRecordIdentifier(array $record) {
        $identifier = null;
        if (isset($record["recordID"]) && isset($record["recordKey"])) {
            throw new ClientException("A resource key can't have both a recordID or recordKey");
        } else {
            $identifier = $record["recordID"] ?? $record["recordKey"];
        }
        return $identifier;
    }
}
