<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\TranslationsApi\Models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use LocalesApiController;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * TranslationModel
 */
class TranslationModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /** @var LocalesApiController */
    private $localesApiController;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * TranslationModel constructor.
     *
     * @param Gdn_Session $session
     * @param LocalesApiController $localesApiController
     * @param ConfigurationInterface $config
     */
    public function __construct(
        Gdn_Session $session,
        LocalesApiController $localesApiController,
        ConfigurationInterface $config
    ) {
        parent::__construct("translation");
        $this->session = $session;
        $this->localesApiController = $localesApiController;
        $this->config = $config;

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
        $sourceLocale =  $this->config->get("Garden.Locale");
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
        $locales = $this->localesApiController->index();
        $availableLocales = array_column($locales, 'localeKey');
        $availableRegionalLocales = array_column($locales, 'regionalKey');
        $valid = in_array($locale, $availableLocales) || in_array($locale, $availableRegionalLocales);

        if (!$valid) {
            throw new ClientException("Locale '". $locale . "' is not available");
        }

    }

}
