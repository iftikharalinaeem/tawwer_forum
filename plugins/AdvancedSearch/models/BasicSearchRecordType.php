<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\AdvancedSearch\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;

class BasicSearchRecordType implements SearchRecordTypeInterface {
    private $key;

    private $structure;

    public function __construct(string $key, array $structure) {
        $this->key = $key;
        $this->structure = $structure;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getCheckBoxId(): string {
        return $this->structure['checkboxId'] ?? '';
    }

    public function getFeatures(): array {
        return $this->structure;
    }

    public function getModel() {
        return '';
    }
}
