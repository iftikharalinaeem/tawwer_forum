<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeTrait;
use Vanilla\Knowledge\Controllers\Api\KnowledgeApiController;

/**
 * Class SearchRecordTypeArticle
 * @package Vanilla\Knowledge\Models
 */
class SearchRecordTypeArticle implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'sphinx';

    const INFRASTRUCTURE_TEMPLATE = 'knowledgearticle';

    const TYPE = 'article';

    const API_TYPE_KEY = 'article';

    const SUB_KEY = 'article';

    const CHECKBOX_LABEL = 'articles';

    const SPHINX_DTYPE = 5;

    const GUID_OFFSET = 5;

    const GUID_MULTIPLIER = 10;

    const SPHINX_INDEX = 'KnowledgeArticle';

    const SPHINX_INDEX_WEIGHT = 3;

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        /** @var KnowledgeApiController $api */
        $api = \Gdn::getContainer()->get(KnowledgeApiController::class);
        $result = $api->getArticlesAsDiscussions($IDs, self::SPHINX_DTYPE);
        foreach ($result as &$record) {
            $record['guid'] = $record['articleRevisionID'] * self::GUID_MULTIPLIER + self::GUID_OFFSET;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(\Gdn_Session $session): bool {
        return $session->getPermissions()->hasAny(["knowledge.kb.view"]);
    }
}
