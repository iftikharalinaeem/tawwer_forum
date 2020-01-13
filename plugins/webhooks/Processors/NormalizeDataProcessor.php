<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Processors;

use Vanilla\Database\Operation\Processor;
use Vanilla\Database\Operation;

/**
 * Normalize data for database read/write operations.
 */
class NormalizeDataProcessor implements Processor {

    /** Valid database operations for this processor. */
    private const VALID_OPERATIONS = [Operation::TYPE_INSERT, Operation::TYPE_SELECT, Operation::TYPE_UPDATE];

    /** @var string[] */
    private $booleanFields = [];

    /** @var string[] */
    private $serializedFields = [];

    /**
     * Add a boolean field.
     *
     * @param string $field
     * @return void
     */
    public function addBooleanField(string $field): self {
        $field = strtolower($field);
        $this->booleanFields[$field] = true;
        return $this;
    }

    /**
     * Add a serialized field.
     *
     * @param string $field
     * @return void
     */
    public function addSerializedField(string $field): self {
        $field = strtolower($field);
        $this->serializedFields[$field] = true;
        return $this;
    }

    /**
     * Add field to write operations.
     *
     * @param Operation $databaseOperation
     * @param callable $stack
     * @return mixed
     */
    public function handle(Operation $databaseOperation, callable $stack) {
        $type = $databaseOperation->getType();

        if (!in_array($type, self::VALID_OPERATIONS)) {
            // Nothing to do here.
            return $stack($databaseOperation);
        } elseif ($type === Operation::TYPE_SELECT) {
            $rows = $stack($databaseOperation);
            $result = $this->translateRead($rows);
            return $result;
        } else {
            $databaseOperation = $this->translateWrite($databaseOperation);
            return $stack($databaseOperation);
        }
    }

    /**
     * Translate an array of rows read from the database.
     *
     * @param array $rows
     * @return array
     */
    private function translateRead(array $rows): array {
        foreach ($rows as &$row) {
            foreach ($row as $field => $value) {
                $compareField = strtolower($field);

                if (array_key_exists($compareField, $this->booleanFields)) {
                    $row[$field] = boolval($value);
                } elseif (array_key_exists($compareField, $this->serializedFields)) {
                    $row[$field] = dbDecode($value);
                }
            }
        }

        return $rows;
    }

    /**
     * Translate field values for writing to the database.
     *
     * @param Operation $databaseOperation
     * @return Operation
     */
    private function translateWrite(Operation $databaseOperation): Operation {
        $result = clone $databaseOperation;

        $set = $result->getSet();

        foreach ($set as $field => $value) {
            $compareField = strtolower($field);

            if (array_key_exists($compareField, $this->booleanFields)) {
                $set[$field] = $value ? 1 : 0;
            } elseif (array_key_exists($compareField, $this->serializedFields)) {
                $set[$field] = dbEncode($value);
            }
        }

        $result->setSet($set);

        return $result;
    }
}
