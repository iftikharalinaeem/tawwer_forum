<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Schemas;

use Garden\Schema\Schema;

/** Class GroupFragmentSchema */
class GroupFragmentSchema extends Schema {
    /**
     * GroupFragmentSchema constructor.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            'groupID:i' => 'The ID of the group.',
            'name:s' => 'The name of the group.',
            'url:s' => 'The full URL to the group.',
        ]));
    }
}
