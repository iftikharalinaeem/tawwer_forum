<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\AdvancedSearch\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;

class SearchRecordTypeDiscussion extends BasicSearchRecordType {
    const TYPE = 'discussion';

    const CHECKBOX_ID = 'd';

    const CHECKBOX_LABEL = 'discussions';

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
