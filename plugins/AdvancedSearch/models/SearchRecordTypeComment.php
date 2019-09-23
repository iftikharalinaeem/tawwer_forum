<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\AdvancedSearch\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;

class SearchRecordTypeComment extends BasicSearchRecordType {
    const TYPE = 'comment';

    const CHECKBOX_ID = 'c';

    const CHECKBOX_LABEL = 'comments';

    public function __construct() {
        $this->key = self::TYPE;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getCheckBoxId(): string {
        return self::TYPE.'_'.self::CHECKBOX_ID;
    }

    public function getCheckBoxLabel(): string {
        return self::CHECKBOX_LABEL;
    }
}
