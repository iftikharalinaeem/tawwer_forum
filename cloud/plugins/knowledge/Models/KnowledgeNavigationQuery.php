<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * Simple Object used for querying the knowledge base navigation.
 */
final class KnowledgeNavigationQuery {

    const CACHE_KEY = "knowledgeNavigation";

    /** @var int */
    private $knowledgeBaseID;

    /** @var bool */
    private $flat;

    /** @var ?string */
    private $locale;

    /** @var bool */
    private $onlyTranslated;

    /**
     * Constructor.
     *
     * @param int $knowledgeBaseID
     * @param string $locale
     * @param bool $flat
     * @param bool $onlyTranslated
     */
    public function __construct(
        int $knowledgeBaseID,
        ?string $locale,
        bool $flat,
        ?bool $onlyTranslated = false
    ) {
        $this->knowledgeBaseID = $knowledgeBaseID;
        $this->locale = $locale;
        $this->flat = $flat;
        $this->onlyTranslated = $onlyTranslated ?? false;
    }

    /**
     * Get the cache key for the query.
     *
     * @return string
     */
    public function buildCacheKey(): string {
        return self::CACHE_KEY
            . '-' . $this->knowledgeBaseID
            . '-' . $this->locale
            . '-flat:' . $this->flat
            . '-onlyTranslated:' . $this->onlyTranslated;
    }

    /**
     * @return int
     */
    public function getKnowledgeBaseID(): int {
        return $this->knowledgeBaseID;
    }

    /**
     * @return bool
     */
    public function isFlat(): bool {
        return $this->flat;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string {
        return $this->locale;
    }

    /**
     * @return bool
     */
    public function isOnlyTranslated(): bool {
        return $this->onlyTranslated;
    }
}
