<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Contracts\Site\TranslationResourceInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class KnowledgeTranslationResource
 * @package Vanilla\Knowledge\Models
 */
class KnowledgeTranslationResource implements TranslationResourceInterface {
    const RESOURCE_KEY = 'kb';
    const RESOURCE_NAME = 'Knowledge Base';

    /** @var ConfigurationInterface $config */
    private $config;

    /**
     * KnowledgeTranslationResource constructor.
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function resourceKey(): string {
        return self::RESOURCE_KEY;
    }

    /**
     * @inheritdoc
     */
    public function resourceRecord(): array {
        return [
            'name' => self::RESOURCE_NAME,
            'sourceLocale' => $this->config->get("Garden.Locale"),
            'urlCode' => self::RESOURCE_KEY,
        ];
    }
}
