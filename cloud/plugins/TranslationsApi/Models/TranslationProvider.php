<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2010-2018 Vanilla Forums Inc
 * @license Proprietary
 */

namespace Vanilla\TranslationsApi\Models;

use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\TranslationsApi\Models\TranslationPropertyModel;
use Vanilla\Contracts\Site\TranslationResourceInterface;

/**
 * Class TranslationProvider
 * @package Vanilla\TranslationsApi\Models
 */
class TranslationProvider implements TranslationProviderInterface {
    /** @var TranslationsApiController $translationApi */
    private $translationModel;

    /** @var ResourceModel $resourceModel */
    private $resourceModel;

    /** @var TranslationResourceInterface[] $validResources */
    private $validResources;

    /**
     * TranslationProvider constructor.
     * @param TranslationPropertyModel $translationModel
     */
    public function __construct(TranslationPropertyModel $translationModel, ResourceModel $resourceModel) {
        $this->translationModel = $translationModel;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     */
    public function initializeResource(TranslationResourceInterface $resource) {
        $this->validResources[] = $resource;
        $resourceExists = $this->resourceModel->get(
            [
                "urlCode" => $resource->resourceKey(),
            ]
        );

        if (!$resourceExists) {
            $this->resourceModel->insert($resource->resourceRecord());
        }
    }

    /**
     * @inheritdoc
     */
    public function supportsContentTranslation(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function translate(string $propertyKey, string $sourceValue): string {
        return Gdn::translate($propertyKey, $sourceValue);
    }

    /**
     * @inheritdoc
     */
    public function translateContent(
        string $locale,
        string $resource,
        string $recordType,
        int $recordID,
        string $recordKey,
        string $propertyName,
        string $sourceValue
    ): string {
        $translation = $sourceValue;
        $where = [
            't.locale' => $locale,
            'tp.resource' => $resource,
            'tp.recordType' => $recordType,
            'tp.propertyName' => $propertyName,
        ];

        if ($recordID > 0) {
            $where['tp.recordID'] = $recordID;
        }
        if (!empty($recordKey)) {
            $where['tp.recordKey'] = $recordKey;
        }

        $translations = $this->translationModel->getTranslations($where, ['limit' => 1]);

        if (count($translations)>0) {
            $record = reset($translations);
            $translation = $record['translation'];
        }
        return $translation;
    }

    /**
     * Translate properties of some recordType items provided
     *
     * @param string $locale
     * @param string $resource
     * @param string $recordType Ex: discussion, knwoledgeCategory
     * @param string $idFieldName Ex: discussionID, categoryID, knowldegeCategoryID, etc
     * @param array $records
     * @param array $properties Ex: ['name', 'description']
     * @return array
     */
    public function translateProperties(
        string $locale,
        string $resource,
        string $recordType,
        string $idFieldName,
        array $records,
        array $properties
    ): array {

        $where = [
            't.locale' => $locale,
            'tp.resource' => $resource,
            'tp.recordType' => $recordType,
            'tp.propertyName' => $properties,
        ];

        $ids = array_column($records, $idFieldName);
        if (count($ids) > 0) {
            $where['tp.recordID'] = $ids;

            $translations = $this->translationModel->translateProperties($where, $properties);
            if (count($translations) > 0) {
                foreach ($records as &$record) {
                    foreach ($properties as $property) {
                        if (!empty($translation = $translations[$record[$idFieldName]][$property] ?? null)) {
                            $record[$property] = $translation;
                        }
                    }
                }
            }
        }

        return $records;
    }
}
