<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Database\Operation;
use Vanilla\Database\Operation\Processor;

/**
 * Processor to clear the navigaiton cache.
 */
class NavigationCacheProcessor implements Processor {

    /** @var KnowledgeNavigationCache */
    private $cache;

    /**
     * @param KnowledgeNavigationCache $cache
     */
    public function __construct(KnowledgeNavigationCache $cache) {
        $this->cache = $cache;
    }

    /**
     * Clear the cache on certain operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed|void
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        if (in_array($databaseOperation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_DELETE, Operation::TYPE_UPDATE])) {
            $this->cache->deleteAll();
        }
    }
}
