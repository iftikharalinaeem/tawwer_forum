<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Sphinx\SphinxClient;
use Garden\SphinxTrait;
use Garden\Web\Exception\ClientException;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use DiscussionModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use CommentModel;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Endpoint for the Knowledge resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;

    const SPHINX_DEFAULT_LIMIT = 100;

    const TYPE_ARTICLE = 5;
    const TYPE_ARTICLE_DELETED = 6;
    const TYPE_DISCUSSION = 0;
    const TYPE_QUESTION = 1;
    const TYPE_POLL = 2;
    const TYPE_COMMENT = 100;
    const TYPE_ANSWER = 101;

    const FORMAT_RICH = 'Rich';

    const ARTICLE_STATUSES = [
        1 => ArticleModel::STATUS_PUBLISHED,
        2 => ArticleModel::STATUS_DELETED,
        3 => ArticleModel::STATUS_UNDELETED
    ];

    const RECORD_TYPES = [
        self::TYPE_DISCUSSION => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_QUESTION => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_POLL => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_COMMENT => [
            'model' => 'comment',
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_ANSWER => [
            'model' => 'comment',
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_ARTICLE => [
            'model' => 'articleRevisionModel',
            'recordType' => 'article',
            'recordID' => 'articleID',
            'offset' => 5,
            'multiplier' => 10,
            'getRecordsFunction' => 'getArticles',
            'sphinxIndexName' => ['KnowledgeArticle', 'KnowledgeArticle_Delta'],
            'sphinxIndexWeight' => 3,
        ],
        self::TYPE_ARTICLE_DELETED => [
            'model' => 'articleRevisionModel',
            'recordType' => 'article',
            'recordID' => 'articleID',
            'offset' => 5,
            'multiplier' => 10,
            'getRecordsFunction' => 'getArticles',
            'sphinxIndexName' => ['KnowledgeArticleDeleted', 'KnowledgeArticleDeleted_Delta'],
            'sphinxIndexWeight' => 3,
        ],
    ];

    /** @var Schema */
    private $searchResultSchema;

    /** @var array */
    private $results = [];

    /** @var array */
    private $query = [];

    /** @var SphinxClient */
    private $sphinx = null;

    /** @var string */
    private $sphinxQuery = '';

    /** @var string */
    private $sphinxIndexes = '';

    /** @var array */
    private $sphinxIndexWeights = [];

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var ArticleModel */
    private $articleModel;

    /** @var \UserModel */
    private $userModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /** @var \CategoryCollection */
    private $categoryCollection;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /**
     * DI.
     *
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleModel $articleModel
     * @param \UserModel $userModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param \CategoryCollection $categoryCollection
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(
        ArticleRevisionModel $articleRevisionModel,
        ArticleModel $articleModel,
        \UserModel $userModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \CategoryCollection $categoryCollection,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->categoryCollection = $categoryCollection;
        $this->breadcrumbModel = $breadcrumbModel;
    }

    /**
     * Get a schema with limited fields for representing a knowledge category row.
     *
     * @return Schema
     */
    public function searchResultSchema(): Schema {
        return $this->schema(
            [
                "name" => ["type" => "string"],
                "body?"  => ["type" => "string"],
                "url" => ["type" => "string"],
                "insertUserID" => ["type" => "integer"],
                "updateUserID?" => ["type" => "integer"],
                "recordID" => ["type" => "integer"],
                "dateInserted" => ["type" => "datetime"],
                "dateUpdated?" => ["type" => "datetime"],
                "knowledgeCategoryID?"=> ["type" => "integer"],
                "status?" => ["type" => "string"],
                "recordType" => [
                    "enum" => ["article", "knowledgeCategory", "discussion", "comment"],
                    "type" => "string",
                ],
                "updateUser?" => $this->getUserFragmentSchema(),
                "insertUser?" => $this->getUserFragmentSchema(),
                "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
            ],
            "searchResultSchema"
        );
    }

    /**
     * Search endpoint controller. Ex: /api/v2/knowledge/search
     *
     * @param array $query
     * @return array
     */
    public function get_search(array $query = []): \Garden\Web\Data {
        $this->permission("knowledge.kb.view");

        $in = $this->schema($this->inputSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");

        $out = $this->schema([":a" => $this->searchResultSchema()], "out");

        $this->query = $in->validate($query);

        $searchResults = $this->sphinxSearch();

        $results = $this->getNormalizedData($searchResults);

        $result = $out->validate($results);

        return new \Garden\Web\Data($result, [
            'paging' => \Vanilla\ApiUtils::numberedPagerInfo($searchResults['total_found'], '/api/v2/knowledge/search', $this->query, $in)
        ]);
    }

    /**
     * Prepare query for Sphinx search and gets Sphinx search results.
     *
     * @return array
     */
    protected function sphinxSearch(): array {
        $this->sphinx = $this->sphinxClient();
        $this->setLimits();

        if (($this->query['global'] ?? false)) {
            $this->defineGlobalQuery();
        } else {
            $this->defineArticlesQuery();
        }
        if (!empty($this->sphinxIndexWeights)) {
            $this->sphinx->setIndexWeights($this->sphinxIndexWeights);
        }

        if ($sphinxRes = $this->sphinx->query($this->sphinxQuery, $this->sphinxIndexes)) {
            return $sphinxRes;
        } else {
            $errorMessage = $this->sphinx->getLastError();
            if (empty($errorMessage)) {
                $errorMessage = $this->sphinx->getLastWarning();
            }
            throw new ClientException($errorMessage);
        }
    }

    /**
     * Prepare offset and limit for Sphinx search.
     */
    protected function setLimits() {
        if (isset($this->query['limit']) && isset($this->query['page'])) {
            $offset = ($this->query['page'] - 1)* $this->query['limit'];
            $this->sphinx->setLimits($offset, $this->query['limit']);
        } else {
            $this->sphinx->setLimits(0, self::SPHINX_DEFAULT_LIMIT);
        }
    }

    /**
     * Prepare Sphinx query when global search mode
     */
    protected function defineGlobalQuery() {
        $this->sphinxIndexes = $this->getIndexes([], [self::TYPE_ARTICLE_DELETED]);
        if (isset($this->query['insertUserIDs'])) {
            $this->sphinx->setFilter('insertUserID', $this->query['insertUserIDs']);
        }
        if (isset($this->query['categoryIDs'])) {
            $this->sphinx->setFilter('CategoryID', $this->query['categoryIDs']);
        }
        if (isset($this->query['name']) && !empty(trim($this->query['name']))) {
            $this->sphinxQuery .= '@name (' . $this->sphinx->escapeString($this->query['name']) . ')*';
        }
        if (isset($this->query['body']) && !empty(trim($this->query['body']))) {
            $this->sphinxQuery .= ' @body (' . $this->sphinx->escapeString($this->query['body']) . ')*';
        }
        if (isset($this->query['all']) && !empty(trim($this->query['all']))) {
            $this->sphinxQuery .= ' @(name,body) (' . $this->sphinx->escapeString($this->query['all']) . ')*';
        }
    }

    /**
     * Prepare Sphinx query when Knowledge Base Articles search mode
     */
    protected function defineArticlesQuery() {
        $articleIndexes = [self::TYPE_ARTICLE];
        if (isset($this->query['statuses'])) {
            if (array_search(ArticleModel::STATUS_DELETED, $this->query['statuses'])) {
                $this->permission("knowledge.articles.add");
            };
            $articleIndexes[] = self::TYPE_ARTICLE_DELETED;
            $statuses = array_map(
                function ($status) {
                    return array_search($status, self::ARTICLE_STATUSES);
                },
                $this->query['statuses']
            );
            $this->sphinx->setFilter('status', $statuses);
        } else {
            $this->sphinx->setFilter('status', [array_search(ArticleModel::STATUS_PUBLISHED, self::ARTICLE_STATUSES)]);
        }
        $this->sphinxIndexes = $this->getIndexes($articleIndexes);
        if (isset($this->query['insertUserIDs'])) {
            $this->sphinx->setFilter('insertUserID', $this->query['insertUserIDs']);
        }
        if (isset($this->query['updateUserIDs'])) {
            $this->sphinx->setFilter('updateUserID', $this->query['updateUserIDs']);
        }

        if (isset($this->query['knowledgeCategoryIDs'])) {
            $this->sphinx->setFilter('categoryID', $this->query['knowledgeCategoryIDs']);
        }
        if (isset($this->query['dateUpdated'])) {
            $range = DateFilterSphinxSchema::dateFilterRange($this->query['dateUpdated']);
            $range['startDate'] = $range['startDate'] ?? (new \DateTime())->setDate(1970, 1, 1)->setTime(0, 0, 0);
            $range['endDate'] = $range['endDate'] ?? (new \DateTime())->setDate(2100, 12, 31)->setTime(0, 0, 0);
            $this->sphinx->setFilterRange('dateUpdated', $range['startDate']->getTimestamp(), $range['endDate']->getTimestamp());
        }


        if (isset($this->query['name']) && !empty(trim($this->query['name']))) {
            $this->sphinxQuery .= '@name (' . $this->sphinx->escapeString($this->query['name']) . ')*';
        }
        if (isset($this->query['body']) && !empty(trim($this->query['body']))) {
            $this->sphinxQuery .= ' @body (' . $this->sphinx->escapeString($this->query['body']) . ')*';
        }
        if (isset($this->query['all']) && !empty(trim($this->query['all']))) {
            $this->sphinxQuery .= ' @(name,body) (' . $this->sphinx->escapeString($this->query['all']) . ')*';
        }
    }

    /**
     * Get all full sphinx index names needed for current search
     *
     * @param array $typesRequired
     * @param array $typesExclude
     * @return string
     */
    protected function getIndexes(array $typesRequired = [], array $typesExclude = []):string {
        $this->sphinxIndexWeights = [];
        $sphinxIndexes = [];
        $all = empty($typesRequired);
        foreach (self::RECORD_TYPES as $key => $sphinxTypes) {
            if ($all || in_array($key, $typesRequired)) {
                if (in_array($key, $typesExclude)) {
                    continue;
                }
                $idxFullNames = [];
                foreach ($sphinxTypes['sphinxIndexName'] as $idx) {
                    $idxFullNames[] = $this->sphinxIndexName($idx);
                    foreach (explode(',', $this->sphinxIndexName($idx)) as $sphinxIndex) {
                        $this->sphinxIndexWeights[$sphinxIndex] = $sphinxTypes['sphinxIndexWeight'];
                    }
                }
                $sphinxIndexes = array_merge($sphinxIndexes, $idxFullNames);
            }
        }
        return implode(', ', $sphinxIndexes);
    }

    /**
     * Get articles data from articleRevisionsModel and normalize records for output
     *
     * @param array $searchResults Result set returned by Sphinx search
     * @return array
     */
    protected function getNormalizedData(array $searchResults): array {
        $expand = $this->query['expand'] ?? [];
        $results = [];
        $this->results['matches'] = $searchResults['matches'];

        if (($searchResults['total'] ?? 0) > 0) {
            $ids = [];
            foreach ($searchResults['matches'] as $guid => $record) {
                $this->results['matches'][$guid]['orderIndex'] = $record['weight'];
                $type = self::RECORD_TYPES[$record['attrs']['dtype']];
                $ids[$record['attrs']['dtype']][] = ($guid - $type['offset']) / $type['multiplier'];
            };
            $results = [];
            foreach ($ids as $dtype => $recordIds) {
                array_push($results, ...$this->{self::RECORD_TYPES[$dtype]['getRecordsFunction']}($recordIds, $dtype, $expand));
            }
        }
        usort($results, function ($a, $b) {
            if ($a['orderIndex'] == $b['orderIndex']) {
                return 0;
            }
            return ($a['orderIndex'] > $b['orderIndex']) ? -1 : 1;
        });
        return $results;
    }

    /**
     * Get records from articleRevisionModel model
     *
     * @param array $iDs
     * @param int $type
     * @param array $expand
     * @return array
     */
    public function getArticles(array $iDs, int $type, array $expand = []): array {
        $result = $this->articleRevisionModel->get([
            'articleRevisionID' => $iDs,
            'status' => ArticleModel::STATUS_PUBLISHED
        ]);

        $type = self::RECORD_TYPES[self::TYPE_ARTICLE];
        foreach ($result as &$article) {
            $guid = $article['articleRevisionID'] * $type['multiplier'] + $type['offset'];
            $article["orderIndex"] = $this->results['matches'][$guid]['orderIndex'];
            $article["recordID"] = $article[$type['recordID']];
            $article["recordType"] = self::RECORD_TYPES[self::TYPE_ARTICLE]['recordType'];
            $article["body"] = $article["excerpt"];
            $article["url"] = $this->articleModel->url($article);

            if ($this->isExpandField('breadcrumbs', $expand)) {
                $sphinxItem = $this->results['matches'][$guid]['attrs'];
                // Casing and naming here is due to sphinx normalization.
                $knowledgeCategoryID = $sphinxItem['categoryid'];

                $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($knowledgeCategoryID));
                $article['breadcrumbs'] = $crumbs;
            }
        }

        if ($this->isExpandField('users', $expand)) {
            $this->userModel->expandUsers($result, ['insertUserID', 'updateUserID']);
        }
        return $result;
    }

    /**
     * Get records from discussionModel model
     *
     * @param array $ids
     * @param int $type
     * @param array $expand
     * @return array
     */
    public function getDiscussions(array $ids, int $type, array $expand = []): array {

        $discussions = $this->discussionModel->get(
            null,
            self::SPHINX_DEFAULT_LIMIT,
            ['d.DiscussionID' => $ids]
        )->resultArray();

        $type = self::RECORD_TYPES[$type];
        $results = [];
        foreach ($discussions as $discussion) {
            $guid = $discussion[$type['recordID']] * $type['multiplier'] + $type['offset'];

            $result = [
                "name" => $discussion['Name'],
                "body?" => \Gdn_Format::excerpt($discussion['Body'], $discussion['Format']),
                "url" => $discussion['Url'],
                "insertUserID" => $discussion['InsertUserID'],
                "updateUserID?" => $discussion['UpdateUserID'],
                "recordID" => $discussion['DiscussionID'],
                "dateInserted" => $discussion['DateInserted'],
                "dateUpdated?" => $discussion['DateUpdated'],
                "recordType" => $type['recordType'],
                // Sphinx fields
                "guid" => $guid,
                "orderIndex" => $this->results['matches'][$guid]['orderIndex'],
            ];

            $results[] = $result;
        }

        if ($this->isExpandField('users', $expand)) {
            $this->userModel->expandUsers($results, ['insertUserID', 'updateUserID']);
        }
        return $results;
    }

    /**
     * Get records from commentModel model
     *
     * @param array $ids
     * @param int $type
     * @param array $expand
     * @return array
     */
    public function getComments(array $ids, int $type, array $expand = []): array {
        $comments = $this->commentModel->getWhere(
            ['CommentID' => $ids]
        )->resultArray();
        $type = self::RECORD_TYPES[$type];

        $results = [];
        foreach ($comments as $comment) {
            $guid = $comment[$type['recordID']] * $type['multiplier'] + $type['offset'];

            $result = [
                "name" => "Comment. Make this name later.",
                "body?" => \Gdn_Format::excerpt($comment['Body'], $comment['Format']),
                "url" => commentUrl($comment),
                "insertUserID" => $comment['InsertUserID'],
                "updateUserID?" => $comment['UpdateUserID'],
                "recordID" => $comment['DiscussionID'],
                "dateInserted" => $comment['DateInserted'],
                "dateUpdated?" => $comment['DateUpdated'],
                "recordType" => $type['recordType'],
                // Sphinx fields
                "guid" => $guid,
                "orderIndex" => $this->results['matches'][$guid]['orderIndex'],
            ];

            $results[] = $result;
        }

        if ($this->isExpandField('users', $expand)) {
            $this->userModel->expandUsers($results, ['insertUserID', 'updateUserID']);
        }
        return $results;
    }

    /**
     * Check if need to expand category and return categories data.
     *
     * @return array
     */
    protected function getForumDiscussionsData(): array {
        $discussions = [];
        foreach ($this->results['matches'] as $key => $article) {
            if ($article['attrs']['dtype'] !== self::TYPE_ARTICLE) {
                $discussions[$article['attrs']['discussionid']] = true;
            }
        };

        $result = [];
        foreach ($this->discussionModel->get(
            null,
            self::SPHINX_DEFAULT_LIMIT,
            ['d.DiscussionID' => array_keys($discussions)]
        )->resultArray() as $discussion) {
            $result[$discussion['DiscussionID']] = $discussion;
        }
        return $result;
    }

    /**
     * Prepare default schema array for "in" schema
     *
     * @return array
     */
    protected function inputSchema() {
        return [
            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
            "knowledgeCategoryIDs:a?" => "Knowledge category ID to filter results.",
            "categoryIDs:a?" => "Forum category IDs to filter results. Applies only when 'global' = true.",
            "insertUserIDs:a?" => "Array of insertUserIDs (authors of article) to filter results.",
            "updateUserIDs:a?" => "Array of updateUserIDs (last editors of an article) to filter results.",
            "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["users", "breadcrumbs"]),
            'dateUpdated?' => new DateFilterSphinxSchema([
                'description' => 'Filter by date when the article was updated.',
            ]),
            "statuses:a?" => "Article statuses array to filter results.",
            "name:s?" => "Keywords to search against article name.",
            "body:s?" => "Keywords to search against article body.",
            "all:s?" => "Keywords to search against article name or body.",
            "global:b?" => "Global search flag. Default: false",
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => self::SPHINX_DEFAULT_LIMIT,
                'minimum' => 1,
                'maximum' => 100
            ],
        ];
    }
}
