<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * Class for managing knowledge navigation caches.
 */
class KnowledgeNavigationCache {

    /** @var string Cache key for holding other cache keys for easy invalidation. */
    const CACHE_HOLDER_KEY = 'knowledgeNavigationCacheHolder';

    /** @var int 10 minute cache interval. */
    const CACHE_TTL = 600;

    /** @var \Gdn_Cache */
    private $cache;

    /**
     * DI.
     *
     * @param \Gdn_Cache $cache
     */
    public function __construct(\Gdn_Cache $cache) {
        $this->cache = $cache;
    }

    /**
     * Try to fetch a navigation from the cache.
     *
     * @param KnowledgeNavigationQuery $query
     * @return array|null
     */
    public function get(KnowledgeNavigationQuery $query): ?array {
        $key = $query->buildCacheKey();
        $cacheResult = $this->cache->get($key);
        return $cacheResult ?: null;
    }

    /**
     * Cache some navigation values.
     *
     * @param KnowledgeNavigationQuery $query
     * @param array $nav
     */
    public function set(KnowledgeNavigationQuery $query, array $nav) {
        $key = $query->buildCacheKey();
        $this->addCacheKey($key);
        $this->cache->store($key, $nav, [
            \Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL,
        ]);
    }

    /**
     * Clear all known caches of navigation.
     */
    public function deleteAll() {
        $allCacheKeys = $this->cache->get(self::CACHE_HOLDER_KEY) ?: [];

        foreach ($allCacheKeys as $cacheKey) {
            $result = $this->cache->remove($cacheKey);
            if ($result === \Gdn_Cache::CACHEOP_FAILURE) {
                trigger_error('Failed to clear KB navigation cache', E_USER_NOTICE);
            }
        }
        $this->cache->remove(self::CACHE_HOLDER_KEY);
    }

    /**
     * Add a cache key to the aggregate known cache keys.
     *
     * @param string $key
     */
    private function addCacheKey(string $key) {
        $allCacheKeys = $this->cache->get(self::CACHE_HOLDER_KEY, []);
        $allCacheKeys[] = $key;
        $this->cache->store(self::CACHE_HOLDER_KEY, $allCacheKeys);
    }
}