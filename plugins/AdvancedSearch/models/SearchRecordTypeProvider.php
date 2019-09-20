<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AdvancedSearch\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;

class SearchRecordTypeProvider implements SearchRecordTypeProviderInterface {
    /** @var $searchRecordTypes SearchRecordTypeInterface[] */
    private $types = [];

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return $this->types;
    }

    /**
     * @inheritdoc
     */
    public function setType(SearchRecordTypeInterface $recordType) {
        $this->types[] = $recordType;
    }

    /**
     * @inheritdoc
     */
    public function getType(string $typeKey): ?SearchRecordTypeInterface {
        $result = null;
        foreach ($this->types as $type) {
            if ($type->getKey() === $typeKey) {
                $result = $type;
                break;
            }
        }
        return $result;
    }
}
