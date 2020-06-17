<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Vanilla\Adapters\SphinxClient;
use Vanilla\Adapters\SphinxClient as SphinxAdapter;
use Garden\SphinxTrait;
use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeUniversalSourceModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use DiscussionModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use CommentModel;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Utility\InstanceValidatorSchema;
use CategoryModel;
use Garden\Web\Exception\ServerException;
use Gdn_Session;

/**
 * Endpoint for the Knowledge resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;
    use CheckGlobalPermissionTrait;

    const SPHINX_DEFAULT_LIMIT = 100;

    const TYPE_ARTICLE = 5;
    const TYPE_ARTICLE_DELETED = 6;
    const TYPE_DISCUSSION = 0;
    const TYPE_QUESTION = 1;
    const TYPE_POLL = 2;
    const TYPE_COMMENT = 100;
    const TYPE_ANSWER = 101;

    const SPHINX_PSEUDO_CATEGORY_ID = 0;

    const FORMAT_RICH = 'Rich';

    const ARTICLE_STATUSES = [
        1 => ArticleModel::STATUS_PUBLISHED,
        2 => ArticleModel::STATUS_DELETED,
        3 => ArticleModel::STATUS_UNDELETED,
    ];

    const RECORD_TYPES = [
        self::TYPE_DISCUSSION => [
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_QUESTION => [
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_POLL => [
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_COMMENT => [
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'sphinxGUID' => 'CommentID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'namePrefix' => 'RE:',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_ANSWER => [
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'sphinxGUID' => 'CommentID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'namePrefix' => 'RE:',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
            'sphinxIndexWeight' => 1,
        ],
        self::TYPE_ARTICLE => [
            'recordType' => 'article',
            'recordID' => 'articleID',
            'offset' => 5,
            'multiplier' => 10,
            'getRecordsFunction' => 'getArticles',
            'sphinxIndexName' => ['KnowledgeArticle', 'KnowledgeArticle_Delta'],
            'sphinxIndexWeight' => 3,
        ],
        self::TYPE_ARTICLE_DELETED => [
            'recordType' => 'article',
            'recordID' => 'articleID',
            'offset' => 6,
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

    /** @var SphinxAdapter */
    private $sphinx = null;

    /** @var string */
    private $sphinxQuery = '';

    /** @var string */
    private $sphinxIndexes = '';

    /** @var array */
    private $sphinxIndexWeights = [];

    /** @var ArticleModel */
    private $articleModel;

    /** @var \UserModel */
    private $userModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeBaseModel $knowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /** @var \CategoryCollection */
    private $categoryCollection;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var KnowledgeUniversalSourceModel */
    private $knowledgeUniversalSourceModel;

    /** @var Gdn_Session $session */
    private $session;

    /** @var array $knowledgeCategories */
    private $knowledgeCategories = null;

    /**
     * DI.
     *
     * @param ArticleModel $articleModel
     * @param \UserModel $userModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     * @param \CategoryCollection $categoryCollection
     * @param BreadcrumbModel $breadcrumbModel
     * @param SiteSectionModel $siteSectionModel
     * @param KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param Gdn_Session $session
     */
    public function __construct(
        ArticleModel $articleModel,
        \UserModel $userModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \CategoryCollection $categoryCollection,
        BreadcrumbModel $breadcrumbModel,
        SiteSectionModel $siteSectionModel,
        KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        Gdn_Session $session
    ) {
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->categoryCollection = $categoryCollection;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->knowledgeUniversalSourceModel = $knowledgeUniversalSourceModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->session = $session;
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
                "body?" => ["type" => "string"],
                "url" => ["type" => "string"],
                "insertUserID" => ["type" => "integer"],
                "updateUserID?" => ["type" => "integer"],
                "recordID" => ["type" => "integer"],
                "dateInserted" => ["type" => "datetime"],
                "dateUpdated?" => ["type" => "datetime"],
                "knowledgeCategoryID?" => ["type" => "integer"],
                "status?" => ["type" => "string"],
                "locale?" => ["type" => "string"],
                "siteSectionGroup?" => ["type" => "string"],
                "recordType" => [
                    "enum" => ["article", "knowledgeCategory", "discussion", "comment"],
                    "type" => "string",
                ],
                "updateUser?" => $this->getUserFragmentSchema(),
                "insertUser?" => $this->getUserFragmentSchema(),
                "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
                "seoImage:s?" => ["type" => "string"],
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
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $in = $this->schema($this->inputSchema(), "in");

        $out = $this->schema([":a" => $this->searchResultSchema()], "out");
        $this->query = $in->validate($query);
        $searchResults = $this->sphinxSearch();

        $results = $this->getNormalizedData($searchResults, $query);

        $result = $out->validate($results);

        return new \Garden\Web\Data($result, [
            'paging' => \Vanilla\ApiUtils::numberedPagerInfo($searchResults['total_found'], '/api/v2/knowledge/search', $this->query, $in),
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
            throw new ServerException("Sphinx error: $errorMessage");
        }
    }

    /**
     * Prepare offset and limit for Sphinx search.
     */
    protected function setLimits() {
        if (isset($this->query['limit']) && isset($this->query['page'])) {
            $offset = ($this->query['page'] - 1) * $this->query['limit'];
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

        $categoryIDs = [];
        $categories = array_keys(CategoryModel::getByPermission('Discussions.View'));
        if (isset($this->query['categoryIDs'])) {
            foreach ($this->query['categoryIDs'] as $categoryID) {
                if (in_array($categoryID, $categories)) {
                    $categoryIDs[] = $categoryID;
                }
            }
        } else {
            $categoryIDs = $categories;
            $categoryIDs[] = self::SPHINX_PSEUDO_CATEGORY_ID;
        }
        $this->sphinx->setFilter('CategoryID', $categoryIDs);
        if (isset($this->query['name']) && !empty(trim($this->query['name']))) {
            $this->sphinxQuery .= '@name (' . $this->sphinx->escapeString($this->query['name']) . ')*';
        }
        if (isset($this->query['body']) && !empty(trim($this->query['body']))) {
            $this->sphinxQuery .= ' @body (' . $this->sphinx->escapeString($this->query['body']) . ')*';
        }
        if (isset($this->query['all']) && !empty(trim($this->query['all']))) {
            $this->sphinxQuery .= ' @(name,body) (' . $this->sphinx->escapeString($this->query['all']) . ')*';
        }
        $this->sphinx->setRankingMode(SphinxAdapter::RANK_SPH04);
        $this->sphinx->setSelect("*, WEIGHT() + IF(dtype=5,2,1)*dateinserted/1000 AS sorter");
        $this->sphinx->setSortMode(SphinxAdapter::SORT_EXTENDED, "sorter DESC");
    }

    /**
     * Prepare Sphinx query when Knowledge Base Articles search mode
     */
    protected function defineArticlesQuery() {
        $articleIndexes = [self::TYPE_ARTICLE];
        if (isset($this->query['statuses'])) {
            [$articleIndexes, $statuses] = $this->getStatusFilters($articleIndexes);
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
        if (isset($this->query['knowledgeBaseID'])) {
            $knowledgeCategories = $this->getKnowledgeCategories();
            $this->prepareKnowledgeCategoryFilter($knowledgeCategories);
        }
        if (isset($this->query['knowledgeCategoryIDs'])) {
            $this->prepareKnowledgeCategoryFilter($this->query['knowledgeCategoryIDs']);
        }
        $this->setKnowledgeCategoryFilter();
        if (isset($this->query['dateUpdated'])) {
            $range = DateFilterSphinxSchema::dateFilterRange($this->query['dateUpdated']);
            $range['startDate'] = $range['startDate'] ?? (new \DateTime())->setDate(1970, 1, 1)->setTime(0, 0, 0);
            $range['endDate'] = $range['endDate'] ?? (new \DateTime())->setDate(2100, 12, 31)->setTime(0, 0, 0);
            $this->sphinx->setFilterRange('dateUpdated', $range['startDate']->getTimestamp(), $range['endDate']->getTimestamp());
        }

        if (isset($this->query['locale'])) {
            $this->sphinx->setFilterString('locale', $this->query['locale']);
        } else {
            $siteSection = $this->siteSectionModel->getCurrentSiteSection();
            $this->sphinx->setFilterString('locale', $siteSection->getContentLocale());
        }
        if (isset($this->query['siteSectionGroup'])) {
            $this->sphinx->setFilterString('siteSectionGroup', $this->query['siteSectionGroup']);
        }

        if ($this->query['featured'] ?? false) {
            $this->sphinx->setFilter('featured', [1]);
            $this->sphinx->setSortMode(SphinxAdapter::SORT_ATTR_DESC, 'dateFeatured');
        }

        if ($this->query['sort'] ?? false) {
            $this->query['sort'] = str_replace('name', 'title', $this->query['sort']);
            $field = ltrim($this->query['sort'], '-');
            if ($field === $this->query['sort']) {
                $this->sphinx->setSortMode(SphinxAdapter::SORT_ATTR_ASC, $field);
            } else {
                $this->sphinx->setSortMode(SphinxAdapter::SORT_ATTR_DESC, $field);
            }
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
     * Store selected knowledgeCategoryIDs to filter
     *
     * @param array $ids
     */
    private function prepareKnowledgeCategoryFilter(array $ids = []) {
        if (empty($this->knowledgeCategories)) {
            $this->knowledgeCategories = $ids;
        } else {
            $this->knowledgeCategories = array_intersect($ids, $this->knowledgeCategories);
        }
    }

    /**
     * Check selected knowledgeCategoryIDs and restrict according to the user perissions settings
     * Apply Sphinx filter after
     */
    private function setKnowledgeCategoryFilter() {
        if (!$this->session->checkPermission('Garden.Settings.Manage')) {
            $filterIDs = $this->filterKnowledgeBases();
            $this->sphinx->setFilter('knowledgeCategoryID', $filterIDs);
        } else {
            if (!is_null($this->knowledgeCategories)) {
                $this->sphinx->setFilter('knowledgeCategoryID', $this->knowledgeCategories);
            }
        }
    }


    /**
     * Get all full sphinx index names needed for current search
     *
     * @param array $typesRequired
     * @param array $typesExclude
     * @return string
     */
    protected function getIndexes(array $typesRequired = [], array $typesExclude = []): string {
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
     * @param array $query GET query params
     * @return array
     */
    protected function getNormalizedData(array $searchResults, array $query = []): array {
        $results = [];
        $this->results['matches'] = $searchResults['matches'] ?? [];

        $keepSphinxOrder = (bool)($query['sort'] ?? false);
        if (!$keepSphinxOrder) {
            $keepSphinxOrder = (bool)($query['featured'] ?? false);
        }
        $i = 0;
        if (($searchResults['total'] ?? 0) > 0) {
            $ids = [];
            foreach ($searchResults['matches'] as $guid => $record) {
                $this->results['matches'][$guid]['orderIndex'] = $keepSphinxOrder ? $i++ : -$record['weight'];
                $type = self::RECORD_TYPES[$record['attrs']['dtype']];
                $ids[$record['attrs']['dtype']][] = ($guid - $type['offset']) / $type['multiplier'];
            };
            $results = [];
            foreach ($ids as $dtype => $recordIds) {
                array_push($results, ...$this->{self::RECORD_TYPES[$dtype]['getRecordsFunction']}($recordIds, $dtype));
            }
        }

        usort($results, function ($a, $b) {
            return $a['orderIndex'] <=> $b['orderIndex'];
        });
        return $results;
    }

    /**
     * Get records from article model
     *
     * @param array $iDs
     * @param int $dtype
     * @param array $expand
     * @return array
     */
    public function getArticles(array $iDs, int $dtype, array $expand = []): array {
        $articles = $this->articleModel->getWithRevision([
            'ar.articleRevisionID' => $iDs,
        ]);
        $expand = $this->query['expand'];
        $typeData = self::RECORD_TYPES[$dtype];

        foreach ($articles as &$articleWithRevision) {
            $guid = $articleWithRevision['articleRevisionID'] * $typeData['multiplier'] + $typeData['offset'];
            $sphinxItem = $this->results['matches'][$guid]['attrs'];
            $knowledgeCategoryID = $sphinxItem['knowledgecategoryid'] ?? $articleWithRevision['knowledgeCategoryID'];
            $articleWithRevision["orderIndex"] = $this->results['matches'][$guid]['orderIndex'];
            $articleWithRevision["recordID"] = $articleWithRevision[$typeData['recordID']];
            $articleWithRevision["recordType"] = $typeData['recordType'];
            $articleWithRevision["body"] = $articleWithRevision["excerpt"];
            $articleWithRevision["url"] = $this->articleModel->url($articleWithRevision);
            $articleWithRevision["status"] = self::ARTICLE_STATUSES[$sphinxItem['status']];

            if ($this->isExpandField('category', $expand)) {
                $articleWithRevision["category"] = $this->results['kbCategories'][$knowledgeCategoryID];
            }

            if ($this->isExpandField('breadcrumbs', $expand)) {
                // Casing and naming here is due to sphinx normalization.
                $crumbs = $this->breadcrumbModel->getForRecord(
                    new KbCategoryRecordType($knowledgeCategoryID),
                    $articleWithRevision['locale'] ?? null
                );
                $articleWithRevision['breadcrumbs'] = $crumbs;
            }
        }

        if ($this->isExpandField('users', $expand)) {
            $this->userModel->expandUsers($articles, ['insertUserID', 'updateUserID']);
        }

        return $articles;
    }

    /**
     * Get records from article model for advanced search plugin
     *
     * @param array $iDs
     * @param int $dtype
     * @return array
     */
    public function getArticlesAsDiscussions(array $iDs, int $dtype): array {
        $articles = $this->articleModel->getWithRevision([
            'ar.articleRevisionID' => $iDs,
        ]);

        $typeData = self::RECORD_TYPES[$dtype];

        foreach ($articles as &$articleWithRevision) {
            $articleWithRevision["PrimaryID"] = $articleWithRevision[$typeData['recordID']];
            $articleWithRevision["CategoryID"] = 0;
            $articleWithRevision["RecordType"] = $typeData['recordType'];
            $articleWithRevision["Format"] = $articleWithRevision['format'];

            $articleWithRevision["Summary"] = $articleWithRevision["body"];//$articleWithRevision["excerpt"];
            $articleWithRevision["Url"] = $this->articleModel->url($articleWithRevision);
            $articleWithRevision["Title"] = $articleWithRevision['name'];
            $articleWithRevision["UserID"] = $articleWithRevision['insertUserID'];
            $articleWithRevision["DateInserted"] = $articleWithRevision['dateInserted']->format('Y-m-d H:i:s');
            $articleWithRevision["Type"] = $typeData['recordType'];

            $crumbs = $this->breadcrumbModel->getForRecord(
                new KbCategoryRecordType($articleWithRevision['knowledgeCategoryID']),
                $articleWithRevision['locale'] ?? null
            );
            $articleWithRevision['Breadcrumbs'] = $this->breadcrumbModel->crumbsAsArray($crumbs);
        }

        return $articles;
    }

    /**
     * Normalize some forum records.
     *
     * @param array $records
     * @param int $type
     * @return array
     */
    private function normalizeForumRecords(array $records, int $type): array {
        $typeData = self::RECORD_TYPES[$type];
        $expand = $this->query['expand'];
        $results = [];
        foreach ($records as $record) {
            $recordID = $record[$typeData['recordID']];
            $guid = $record[$typeData['sphinxGUID'] ?? $typeData['recordID']] * $typeData['multiplier'] + $typeData['offset'];
            $sphinxItem = $this->results['matches'][$guid]['attrs'];
            $url = $record['Url'];
            if (in_array($type, [self::TYPE_COMMENT, self::TYPE_ANSWER])) {
                // CommentModel doesn't currently put urls on their records.
                // The global function usage here is a kludge until we restructure subcommunities.
                $url = commentUrl($record);
            }

            $result = [
                "name" => t($typeData['namePrefix'] ?? '').' '.$record['Name'],
                "body" => \Gdn_Format::excerpt($record['Body'], $record['Format']),
                "url" => $url,
                "insertUserID" => $record['InsertUserID'],
                "updateUserID" => $record['UpdateUserID'] ?? $record['InsertUserID'],
                "recordID" => $recordID,
                "dateInserted" => $record['DateInserted'],
                "dateUpdated" => $record['DateUpdated'],
                "recordType" => $typeData['recordType'],

                // Sphinx fields
                "guid" => $guid,
                "orderIndex" => $this->results['matches'][$guid]['orderIndex'],
            ];

            if ($this->isExpandField('breadcrumbs', $expand)) {
                // Casing and naming here is due to sphinx normalization.
                $categoryID = $sphinxItem['categoryid'];
                $crumbs = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($categoryID));
                $result['breadcrumbs'] = $crumbs;
            }

            $results[] = $result;
        }

        if ($this->isExpandField('users', $expand)) {
            $this->userModel->expandUsers($results, ['insertUserID', 'updateUserID']);
        }

        return $results;
    }

    /**
     * Get records from discussionModel model
     *
     * @param array $ids
     * @param int $dtype
     * @return array
     */
    public function getDiscussions(array $ids, int $dtype): array {
        $discussions = $this->discussionModel->get(
            null,
            self::SPHINX_DEFAULT_LIMIT,
            ['d.DiscussionID' => $ids]
        )->resultArray();
        $normalized = $this->normalizeForumRecords($discussions, $dtype);
        return $normalized;
    }

    /**
     * Get records from commentModel model
     *
     * @param array $ids
     * @param int $dtype
     * @return array
     */
    public function getComments(array $ids, int $dtype): array {
        $comments = $this->commentModel->getWhere(
            [
                'CommentID' => $ids,
                'joinDiscussions' => ['Name' => 'Name']
            ]
        )->resultArray();
        $normalized = $this->normalizeForumRecords($comments, $dtype);
        return $normalized;
    }

    /**
     * Apply filters for knowledge queries.
     *
     * @param SphinxAdapter $sphinxClient
     * @param array $query
     *
     * @return SphinxAdapter
     */
    public function applySearchFilters(SphinxClient $sphinxClient, array $query = []) {
        $this->query = $query;
        if ($query['knowledgeBaseID'] ?? []) {
            $knowledgeCategoryIDs = $this->getKnowledgeCategories();
            $this->prepareKnowledgeCategoryFilter($knowledgeCategoryIDs);
        }

        if ($query['knowledgeCategoryID'] ?? []) {
            $this->prepareKnowledgeCategoryFilter($this->query['knowledgeCategoryIDs']);
        }

        if (!$this->session->checkPermission('Garden.Settings.Manage')) {
            $filterIDs = $this->filterKnowledgeBases();
            $sphinxClient->setFilter('knowledgeCategoryID', $filterIDs);
        } else {
            if (!is_null($this->knowledgeCategories)) {
                $sphinxClient->setFilter('knowledgeCategoryID', $this->knowledgeCategories);
            }
        }

        if ($this->query['locale'] ?? []) {
            $sphinxClient->setFilterString('locale', $this->query['locale']);
        }

        if (isset($this->query['siteSectionGroup'])) {
            $sphinxClient->setFilterString('siteSectionGroup', $this->query['siteSectionGroup']);
        }

        if ($this->query['featured'] ?? false) {
            $sphinxClient->setFilter('featured', [1]);
            $sphinxClient->setSortMode(SphinxAdapter::SORT_ATTR_DESC, 'dateFeatured');
        }

        return $sphinxClient;
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
            "locale:s?" => "The locale articles are published in",
            "siteSectionGroup:s?" => "The site-section-group articles are associated to",
            "featured:b?" => "Search for featured articles only. Default: false",
            "global:b?" => "Global search flag. Default: false",
            "sort:s?" => [
                "description" => "Sort option to order search results.",
                "enum" => [
                    "name",
                    "-name",
                    "dateInserted",
                    "-dateInserted",
                    "dateFeatured",
                    "-dateFeatured",
                ]
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => self::SPHINX_DEFAULT_LIMIT,
                'minimum' => 1,
                'maximum' => 100,
            ],
        ];
    }

    /**
     * Get all the knowledge-categories for query
     *
     * @return array
     */
    protected function getKnowledgeCategories(): array {
        $knowledgeUniversalContent = $this->knowledgeUniversalSourceModel->get(
            [
                "targetKnowledgeBaseID" => $this->query['knowledgeBaseID'],
            ]
        );
        if ($knowledgeUniversalContent) {
            $knowledgeBaseIDs = array_column($knowledgeUniversalContent, "sourceKnowledgeBaseID");
        }
        $knowledgeBaseIDs[] = $this->query['knowledgeBaseID'];
        $knowledgeCategories = array_column(
            $this->knowledgeCategoryModel->get(
                ['knowledgeBaseID' => $knowledgeBaseIDs],
                ['select' => ['knowledgeCategoryID']]
            ),
            'knowledgeCategoryID'
        );

        return $knowledgeCategories;
    }

    /**
     * @return array
     */
    private function filterKnowledgeBases(): array {
        $kbs = $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission([]);
        $knowledgeCategories = array_column(
            $this->knowledgeCategoryModel->get(
                ['knowledgeBaseID' => $kbs['knowledgeBaseID']],
                ['select' => ['knowledgeCategoryID']]
            ),
            'knowledgeCategoryID'
        );
        if (empty($this->knowledgeCategories)) {
            $filterIDs = $knowledgeCategories;
        } else {
            $filterIDs = array_intersect($this->knowledgeCategories, $knowledgeCategories);
        }

        return $filterIDs;
    }

    /**
     * Get the article status Filters.
     *
     * @param array $articleIndexes
     * @return array
     */
    protected function getStatusFilters(array $articleIndexes): array {
        if (array_search(ArticleModel::STATUS_DELETED, $this->query['statuses'])) {
            $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        };
        $articleIndexes[] = self::TYPE_ARTICLE_DELETED;
        $statuses = array_map(
            function ($status) {
                return array_search($status, self::ARTICLE_STATUSES);
            },
            $this->query['statuses']
        );

        return [$articleIndexes, $statuses];
    }
}
