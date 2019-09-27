<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Groups\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeTrait;

class SearchRecordTypeGroup implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'sphinx';

    const TYPE = 'group';

    const API_TYPE_KEY = 'group';

    const SUB_KEY = 'group';

    const CHECKBOX_LABEL = 'groups';

    const SPHINX_DTYPE = 400;

    const SPHINX_INDEX = 'Group';

    const GUID_OFFSET = 4;

    const GUID_MULTIPLIER = 10;
}
