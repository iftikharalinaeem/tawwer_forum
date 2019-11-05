<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsAPI\models;

use Garden\Web\Exception\ClientException;
use Gdn_Configuration;
use Gdn_Session;
use LocalesApiController;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Translation Model
 */
class TranslationModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /** @var LocalesApiController */
    private $localesApiController;

    /** @var Gdn_Configuration */
    private $configurationModule;

    const TRANSLATION_RECORD = [
        "locale" => true,
        "translation" => true
    ];


    /**
     *  constructor.
     *
     * @param Gdn_Session $session
     * @param LocalesApiController $localesApiController
     * @param Gdn_Configuration $configurationModule
     */
    public function __construct(Gdn_Session $session, LocalesApiController $localesApiController, Gdn_Configuration $configurationModule) {
        parent::__construct("translations");
        $this->session = $session;
        $this->localesApiController = $localesApiController;
        $this->configurationModule = $configurationModule;

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
     * @param string $resource
     * @param string $locale
     * @param $key
     * @param $translationString
     */
    public function createTranslation(string $resource, string $locale, string $key, string $translationString): array {
        $sourceLocale =  $this->configurationModule->get("Garden.Locale");
        if ($locale !== $sourceLocale) {
            $this->validateLocale($locale, $sourceLocale);
        }

        // Get translations by the requested locale or source locale.
        $translations = $this->getTranslationsByLocale($resource, $locale, $key, $sourceLocale);
        $translationsLocales = array_column($translations, "locale");
        $translationInSource = in_array($sourceLocale, $translationsLocales);
        $translationInRequestedLocale = in_array($locale, $translationsLocales);

        if (!$translations) {
            $translationRecord = [
                "resource" => $resource,
                "key" => $key,
                "locale" => $locale,
                "translation" => $translationString,
            ];
            if (!$translationInSource  && ($locale !== $sourceLocale)) {
                $translationSourceRecord = [
                    "resource" => $resource,
                    "key" => $key,
                    "locale" => $sourceLocale,
                    "translation" => '',
                ];
                $this->insert($translationSourceRecord);
            }
            $this->insert($translationRecord);
            $translation =  $this->get(["resource" => $resource, "key" => $key, "locale" => $locale]);
        } elseif (!$translationInRequestedLocale && $translationInSource) {
            $translationRecord = [
                "resource" => $resource,
                "key" => $key,
                "locale" => $locale,
                "translation" => $translationString,
            ];
            $this->insert($translationRecord);
            $translation =  $this->get(["resource" => $resource, "key" => $key, "locale" => $locale]);
        } else {
            $translation =  $this->get(["resource" => $resource, "key" => $key, "locale" => $locale]);
        }

        $result = reset($translation);

        return $result;
    }

    /**
     * @param string $locale
     * @param string $sourceLocale
     * @throws ClientException
     */
    protected function validateLocale(string $locale, string $sourceLocale) {
        $availableLocales = $this->localesApiController->index();
        $availableLocales = array_column($availableLocales, 'localeKey');
        $valid = in_array($locale, $availableLocales);

        if (!$valid) {
            throw new ClientException("locale". $locale . "is not available");
        }

    }

    /**
     * @param string $resource
     * @param string $locale
     * @param string $key
     * @param $sourceLocale
     * @return array
     */
    protected function getTranslationsByLocale(string $resource, string $locale, string $key, string $sourceLocale = null) {
        $locales = (isset($sourceLocale)) ? [$locale, $sourceLocale] : [$locale];

        $sql = $this->sql();
        $sql->from($this->getTable() . " as t");
        $sql->whereIn("locale", $locales);
        $sql->where(["resource" => $resource, "key" => $key]);
        $result = $sql->get()->resultArray();

        return $result;
    }

}
