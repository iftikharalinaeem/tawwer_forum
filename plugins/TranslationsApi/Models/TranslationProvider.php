<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2010-2018 Vanilla Forums Inc
 * @license Proprietary
 */

namespace Vanilla\TranslationsApi\Models;

use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\TranslationsApi\Models\TranslationPropertyModel;

class TranslationProvider implements TranslationProviderInterface {
    /** @var TranslationsApiController $translationApi */
    private $translationModel;

    /**
     * TranslationProvider constructor.
     * @param TranslationPropertyModel $translationModel
     */
    public function __construct(TranslationPropertyModel $translationModel) {
        $this->translationModel = $translationModel;
    }

    /**
     * @inheritDoc
     */
    public function supportContentTranslation(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function translate(string $propertyKey, $sourceValue): string {
        return Gdn::translate($propertyKey, $sourceValue);
    }

    /**
     * @inheritDoc
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
}
