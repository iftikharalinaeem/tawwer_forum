<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models\Entities;

/**
 * Base entity class.
 */
abstract class Entity {
    /**
     * Entity constructor.
     *
     * @param array $fields
     */
    public function __construct(array $fields = []) {
        foreach ($fields as $k => $v) {
            if (in_array($k, $this->getEntityFields())) {
                $this->__set($k, $v);
            }
        }
    }

    /**
     * Entity getter, check if property exist for current class.
     *
     * @param string $propName Entity property name to get
     * @return mixed
     * @throws \Exception If unknown property name requested.
     */
    public function __get(string $propName) {
        if (property_exists($this, $propName)) {
            return $this->$propName;
        } else {
            throw new \Exception('Call to undefined property '.$propName.' of '.__CLASS__);
        }
    }

    /**
     * Entity setter, check if entity property is "writable" has appropriate setMethod
     *
     * @param string $propName Entity property name to set.
     * @param mixed $value
     * @return KnowledgeBaseEntity
     * @throws \Exception If setter is not defined for entity property.
     */
    public function __set(string $propName, $value): KnowledgeBaseEntity {
        $setter = 'set'.ucfirst($propName);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } else {
            throw new \Exception('Method '.$propName.'() is not supported by '.__CLASS__);
        }
    }

    /**
     * Return entity properties as array
     *
     * @return array
     */
    public function asArray(string $mode = 'all'): array {
        $res = [];
        switch ($mode) {
            case 'insert':
                $fields = $this->getEntityInsertFields();
                break;
            case 'all':
            default:
            $fields = $this->getEntityInFields();
        }

        foreach ($fields as $fieldKey) {
            $res[$fieldKey] = $this->$fieldKey;
        }
        return $res;
    }

    /**
     * Get list of entity properties
     *
     * @return array
     */
    abstract function getEntityFields(): array;

    /**
     * Get list of entity properties to insert
     *
     * @return array
     */
    abstract function getEntityInsertFields(): array;
}
