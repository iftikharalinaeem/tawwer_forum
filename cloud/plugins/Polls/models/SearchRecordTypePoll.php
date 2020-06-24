<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Polls\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeTrait;

class SearchRecordTypePoll implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'sphinx';

    const INFRASTRUCTURE_TEMPLATE = 'standard';

    const TYPE = 'discussion';

    const API_TYPE_KEY = 'poll';

    const SUB_KEY = 'poll';

    const CHECKBOX_LABEL = 'polls';

    const SPHINX_DTYPE = 2;

    const SPHINX_INDEX = 'Discussion';

    const GUID_OFFSET = 1;

    const GUID_MULTIPLIER = 10;

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        $result = $searchModel->getDiscussions($IDs);
        foreach ($result as &$record) {
            $record['type'] = self::SUB_KEY;
            $record['guid'] = $record['PrimaryID'] * self::GUID_MULTIPLIER + self::GUID_OFFSET;
        }
        return $result;
    }
}
