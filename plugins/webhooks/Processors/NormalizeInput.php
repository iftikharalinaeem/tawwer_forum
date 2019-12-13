<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Webhooks\Processors;

use Vanilla\Database\Operation\Processor;
use Vanilla\Database\Operation;

/**
 * Database operation processor for normalizing input.
 */
class NormalizeInput implements Processor {

    /**
     * Add field to write operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        if (!in_array($databaseOperation->getType(), [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            // Nothing to do here. Shortcut return.
            return $stack($databaseOperation);
        }
        $set = $databaseOperation->getSet();
        if (isset($set['active'])) {
            $set['active'] = $set['active'] ? 1 : 0;
        }
        $databaseOperation->setSet($set);
        return $stack($databaseOperation);
    }
}
