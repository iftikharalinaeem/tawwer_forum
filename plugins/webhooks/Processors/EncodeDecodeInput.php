<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Processors;

use Vanilla\Database\Operation\Processor;
use Vanilla\Database\Operation;

/**
 * Database operation processor for encoding/decoding fields.
 */
class EncodeDecode implements Processor {

    /**
     * Add dbencode/dbdecode.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        $getOperationType = $databaseOperation->getType();
        $set = $databaseOperation->getSet();
        if (in_array($getOperationType, [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            if (isset($set['events']) && is_array($set['events'])) {
                $set['events'] = dbencode($set['events']);
                $databaseOperation->setSet($set);
            }
        }
        $dbStack = $stack($databaseOperation);
        if ($getOperationType === Operation::TYPE_SELECT) {
            foreach ($dbStack as $key => $value) {
                $dbStack[$key]['events'] = dbdecode($value['events']);
            }
        }
        return $dbStack;
    }
}
