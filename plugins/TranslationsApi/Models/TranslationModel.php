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
     * Create a translation record.
     *
     * @param string $resource
     * @param string $locale
     * @param string $key
     * @param string $translationString
     *
     * @return bool
     */
    public function createTranslation(string $resource, string $locale, string $key, string $translationString): bool {
        $sourceLocale =  $this->configurationModule->get("Garden.Locale");
        if ($locale !== $sourceLocale) {
            $this->validateLocale($locale, $sourceLocale);
        }

        $translation = $this->get(["resource" => $resource, "translationPropertyKey" => $key, "locale" => $locale]);

        if (!$translation) {
            $translationRecord = [
                "resource" => $resource,
                "translationPropertyKey" => $key,
                "locale" => $locale,
                "translation" => $translationString,
            ];
           $result = $this->insert($translationRecord);
        } else {
           $result = $this->update(
               ["translation" => $translationString],
               ["resource" => $resource, "translationPropertyKey" => $key, "locale" => $locale]
           );
        }

        return $result;
    }

    /**
     * Validate a locale exists.
     *
     * @param string $locale
     * @param string $sourceLocale
     * @throws ClientException
     */
    protected function validateLocale(string $locale, string $sourceLocale) {
        $availableLocales = $this->localesApiController->index();
        $availableLocales = array_column($availableLocales, 'localeKey');
        $valid = in_array($locale, $availableLocales);

        if (!$valid) {
            throw new ClientException("Locale '". $locale . "' is not available");
        }

    }

}
