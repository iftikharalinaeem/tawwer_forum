<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchService;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Class SearchApiController
 */
class SearchApiController extends AbstractApiController {

    const FEATURE_USE_SEARCH_SERVICE = 'useSearchService';

    /** Default limit on the number of rows returned in a page. */
    const LIMIT_DEFAULT = 30;

    /** Maximum number of items that can be returned in a page. */
    const LIMIT_MAXIMUM = 100;

    /** @var Schema */
    private $searchResultSchema;

    /** @var CommentModel */
    private $commentModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var SearchModel */
    private $searchModel;

    /** @var UserModel */
    private $userModel;

    /** @var SearchRecordTypeProviderInterface */
    private $searchRecordTypeProvider;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var SearchService */
    private $searchService;

    /**
     * SearchApiController constructor.
     *
     * @inheritdoc
     */
    public function __construct(
        CommentModel $commentModel,
        DiscussionModel $discussionModel,
        SearchModel $searchModel,
        UserModel $userModel,
        SearchService $searchService,
        SearchRecordTypeProviderInterface $searchRecordTypeProvider,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->searchModel = $searchModel;
        $this->userModel = $userModel;
        $this->searchService = $searchService;
        $this->searchRecordTypeProvider =$searchRecordTypeProvider;
        $this->breadcrumbModel = $breadcrumbModel;
    }

    /**
     * Search results schema record.
     *
     * @return Schema
     */
    public function fullSchema(): Schema {
        if (!$this->searchResultSchema) {
            $typeProviders = $this->searchRecordTypeProvider->getAll();
            $recordTypes = [];
            $types = [];
            /** @var SearchRecordTypeInterface $provider */
            foreach ($typeProviders as $provider) {
                $recordTypes[] = $provider::TYPE;
                $types[] = $provider->getApiTypeKey();
            }
            $this->searchResultSchema = $this->schema([
                'recordID:i' => 'The identifier of the record.',
                'recordType:s' => [
                    'enum' => $recordTypes,
                    'description' => 'The main type of record.',
                ],
                'type:s' => [
                    'enum' => $types,
                    'description' => 'Sub-type of the discussion.',
                ],
                'discussionID:i?' => 'The id of the discussion.',
                'commentID:i?' => 'The id of the comment.',
                'categoryID:i?' => 'The category containing the record.',
                'name:s' => 'The title of the record. A comment would be "RE: {DiscussionTitle}".',
                'url:s' => 'The url for the record',
                'body:s?' => 'The content of the record.',
                'excerpt:s?' => 'excerpt of record.',
                'score:i' => 'Score of the record.',
                'insertUserID:i' => 'The user that created the record.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the record was created.',
                'updateUserID:i|n' => 'The user that updated the record.',
                'dateUpdated:dt|n' => 'When the user was updated.',
                'status:s?',
                'image:o?' => [
                    'url:s',
                    'alt:s',
                ],
                "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
            ], 'SearchResult');
        }

        return $this->searchResultSchema;
    }

    /**
     * List search results. Currently has a feature flag check to go between new variations of the service and old variations.
     *
     * @param array $query
     *
     * @return Data
     */
    public function index(array $query): Data {
        if (FeatureFlagHelper::featureEnabled(self::FEATURE_USE_SEARCH_SERVICE)) {
            return $this->newIndex($query);
        } else {
            return $this->oldIndex($query);
        }
    }

    /**
     * New implementation of search index. Uses the search service.
     *
     * @param array $query
     *
     * @return Data
     */
    private function newIndex(array $query): Data {
        $in = $this
            ->searchService
            ->buildQuerySchema()
            ->merge($this->getCommonIndexSchema())
        ;

        $query = $in->validate($query);
        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $searchResults = $this->searchService->search($query, new SearchOptions($offset, $limit));

        // Expand associated rows.
        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ['insertUser' => 'insertUserID'])
        );

        $recordCount = $searchResults->getResultCount();

        return new Data(
            $searchResults,
            [
                'paging' => ApiUtils::numberedPagerInfo($recordCount, '/api/v2/search', $query, $in),
            ]
        );
    }

    /**
     * List search results.
     *
     * @param array $query
     *
     * @return Data
     */
    private function oldIndex(array $query): Data {
        $this->permission();

        $fullSchema = $this->fullSchema();
        $in = $this->schema(
            [
                'recordTypes:a?' => [
                    'items' => [
                        'type' => 'string',
                        'enum' => $fullSchema->getField('properties.recordType.enum'),
                    ],
                    'style' => 'form',
                    'description' => 'Restrict the search to the specified main type(s) of records.',
                ],
                'types:a?' => [
                    'items' => [
                        'type' => 'string',
                        'enum' => $fullSchema->getField('properties.type.enum'),
                    ],
                    'style' => 'form',
                    'description' => 'Restrict the search to the specified type(s) of records.',
                ],
                'discussionID:i?' => [
                    'description' => 'Set the scope of the search to the comments of a discussion. Incompatible with recordType and type.',
                    'x-search-scope' => true,
                ],
                'categoryID:i?' => [
                    'description' => 'Set the scope of the search to a specific category.',
                    'x-search-scope' => true,
                ],
                "knowledgeBaseID:i?" => [
                    'description' => 'Unique ID of a knowledge base. Results will be relative to this value.',
                    'x-search-scope' => true,
                ],
                'knowledgeCategoryIDs:a?' => [
                    'description' => 'Set the scope of the search to a specific category.',
                    'x-search-scope' => true,
                ],
                'followedCategories:b?' => [
                    'default' => false,
                    'description' => 'Set the scope of the search to followed categories only.',
                    'x-search-scope' => true,
                ],
                "featured:b?" => [
                    'description' => "Search for featured articles only. Default: false",
                    'x-search-filter' => true,
                ],
                'includeChildCategories:b?' => [
                    'default' => false,
                    'description' => 'Search the specified category\'s subtree. Works with categoryID',
                ],
                'includeArchivedCategories:b?' => [
                    'default' => false,
                    'description' => 'Allow search in archived categories.',
                ],
                'query:s?' => [
                    'description' => 'Filter the records using the supplied terms.',
                    'x-search-filter' => true,
                ],
                'name:s?' => [
                    'description' => 'Filter the records by matching part of their name.',
                    'x-search-filter' => true,
                ],
                'insertUserNames:a?' => [
                    'items' => ['type' => 'string'],
                    'style' => 'form',
                    'description' => 'Filter the records by inserted user names.',
                    'x-search-filter' => true,
                ],
                'insertUserIDs:a?' => [
                    'items' => ['type' => 'integer'],
                    'style' => 'form',
                    'description' => 'Filter the records by inserted userIDs.',
                    'x-search-filter' => true,
                ],
                'dateInserted?' => new DateFilterSchema([
                    'description' => 'Filter by date when a record was inserted',
                    'x-search-filter' => true,
                    'x-filter' => [
                        'field' => 'DateInserted',
                        'processor' => [DateFilterSchema::class, 'dateFilterField'],
                    ],
                ]),
                'statuses:a?' => [
                    'items' => ['type' => 'string'],
                    'description' => 'Article statuses array to filter results.',
                    'x-search-filter' => true,
                ],
                "locale:s?" => [
                    'description' => 'The locale articles are published in.',
                    'x-search-scope' => true,
                ],
                'siteSectionGroup:s?' => [
                    'description' => 'The site-section-group articles are associated to',
                    'x-search-scope' => true,
                ],
                'tags:a?' => [
                    'items' => ['type' => 'string'],
                    'style' => 'form',
                    'description' => 'Filter discussions by matching tags.',
                    'x-search-filter' => true,
                ],
                'tagOperator:s?' => [
                    'default' => 'or',
                    'description' => 'Tags search condition.',
                    'enum' => ['and', 'or'],
                ],
                "sort:s?" => [
                    "description" => "Sort option to order search results.",
                    "enum" => [
                        "relevance",
                        "name",
                        "-name",
                        "dateInserted",
                        "-dateInserted",
                        "dateFeatured",
                        "-dateFeatured",
                    ],
                ],
            ],
            ['SearchIndex', 'in']
        )
            ->merge($this->getCommonIndexSchema())
            ->setDescription('Search for records matching specific criteria.');
        $out = $this->schema([':a' => $fullSchema], 'out');

        $query = $in->validate($query);
        $search = $this->normalizeSearch($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);


        $data = [];
        $usePagerNumberInfo = true;
        if ($this->searchModel instanceof SphinxSearchModel) {
            $data = $this->searchModel->advancedSearch($search, $offset, $limit, 'api') ?? [];
            $searchResults = $data['SearchResults'] ?? [];
            if (!$searchResults) {
                $sphinx = $this->searchModel->sphinxClient();
                if ($sphinx->getLastError()) {
                    throw new ServerException($sphinx->getLastError(), 500);
                }
            }
        } else {
            $usePagerNumberInfo = false;
            if ($query['dateInserted'] ?? false) {
                $search['date-filters'] = ApiUtils::queryToFilters($in, ['dateInserted' => $query['dateInserted']]);
            }
            $searchResults = AdvancedSearchPlugin::devancedSearch($this->searchModel, $search, $offset, $limit, 'api');
            $data['RecordCount'] = count($searchResults);
        }

        $searchResults = $this->preNormalizeOutputs($searchResults);

        // Expand associated rows.
        $this->userModel->expandUsers(
            $searchResults,
            $this->resolveExpandFields($query, ['insertUser' => 'UserID'])
        );

        $searchResults = array_map(function ($record) use ($query) {
            $expandParams = [
                'breadcrumbs' => $this->isExpandField('breadcrumbs', $query['expand']),
                'excerpt' => $this->isExpandField('excerpt', $query['expand']),
                'image' => $this->isExpandField('image', $query['expand']),
            ];

            $expandBody = $query['expandBody'] ?? false;
            $searchTerm = $query['query'] ?? '';

            return $this->normalizeOutput(
                $record,
                $expandBody,
                $expandParams,
                $searchTerm
            );
        }, $searchResults);


        $result = $out->validate($searchResults);
        $recordCount = $data['RecordCount'] ?? 0;
        $paging = $usePagerNumberInfo ?
            [
                'paging' => ApiUtils::numberedPagerInfo($recordCount, '/api/v2/search', $query, $in),
            ] :
            [
                'paging' => ApiUtils::morePagerInfo($recordCount, '/api/v2/search', $query, $in),
            ];

        return new Data(
            $result,
            $paging
        );
    }

    /**
     * Get common schema for the search indexes.
     *
     * @return Schema
     */
    private function getCommonIndexSchema(): Schema {
        return Schema::parse([

            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => self::LIMIT_DEFAULT,
                'minimum' => 1,
                'maximum' => self::LIMIT_MAXIMUM,
            ],
            'expandBody:b?' => [
                'default' => true,
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'breadcrumbs', 'image', 'excerpt']),
        ]);
    }

    /**
     * Normalize endpoint search parameters to model search parameters.
     *
     * @param array $query
     * @return array
     */
    public function normalizeSearch(array $query): array {
        $paramMap = [
            'discussionID' => 'discussionid',
            'categoryID' => 'cat',
            'followedCategories' => 'followedcats',
            'includeChildCategories' => 'subcats',
            'includeArchivedCategories' => 'archived',
            'query' => 'search',
            'name' => 'title',
            'dateInserted' => 'date-filters',
            'insertUserNames' => 'author',
            'insertUserIDs' => 'users',
            'tags' => 'tags',
            'tagOperator' => 'tags-op',
            'knowledgeBaseID' => 'knowledgebaseid',
            'locale' => 'locale',
            'statuses' => 'statuses',
            'featured' => 'featured',
            'siteSectionGroup' => 'siteSectionGroup',
        ];
        $recordTypes = $this->searchRecordTypeProvider->getAll();

        $result = [
            'types' => [],
        ];

        if (!array_key_exists('recordTypes', $query)) {
            $query['recordTypes'] = $this->fullSchema()->getField('properties.recordType.enum');
        }
        if (!array_key_exists('types', $query)) {
            $query['types'] = $this->fullSchema()->getField('properties.type.enum');
        }

        $queryRecordTypes = $query['recordTypes'];
        $queryTypes = $query['types'] ?? [];
        foreach ($queryRecordTypes as $queryRecordType) {
            /** @var SearchRecordTypeInterface $recordType */
            foreach ($recordTypes as $recordType) {
                if ($queryRecordType === $recordType->getKey()) {
                    if (empty($queryTypes)) {
                        $result['types'][] = $recordType;
                    } elseif (in_array($recordType->getApiTypeKey(), $queryTypes)) {
                        $result['types'][] = $recordType;
                    }
                }
            }
        }

        foreach ($paramMap as $from => $to) {
            if (array_key_exists($from, $query)) {
                $value = $query[$from];

                if ($from === 'insertUserIDs') {
                    $value = array_map(function($value) {
                        return ['UserID' => trim($value)];
                    }, $value);
                } else if ($from === 'insertUserNames') {
                    // A string is expected later on.
                    $value = implode(',', $value);
                }

                $result[$to] = $value;
            }
        }
        return $result;
    }

    /**
     * Normalize a group database record to match the schema definition.
     *
     * @param array $searchRecord
     * @param bool $includeBody Whether or not to include the body in the output.
     * @param array $expandParams
     * @param string $searchTerm
     * @return array
     */
    public function normalizeOutput(
        array $searchRecord,
        bool $includeBody = true,
        array $expandParams = [],
        string $searchTerm = ''
    ): array {
        $schemaRecord = [
            'recordID' => $searchRecord['PrimaryID'],
            'url' => $searchRecord['Url'],
            'recordType' => null,
            'type' => null,
            'categoryID' => $searchRecord['CategoryID'],
            'knowledgeCategoryID' => $searchRecord['knowledgeCategoryID'] ?? null,
            'name' => $searchRecord['Title'],
            'score' => $searchRecord['Score'] ?? 0,
            'insertUserID' => $searchRecord['UserID'],
            'dateInserted' => $searchRecord['DateInserted'],
            'updateUserID' => $searchRecord['UpdateUserID'] ?? null,
            'dateUpdated' => $searchRecord['DateUpdated'] ?? null,
            'status' => $searchRecord['status'] ?? null,
        ];

        if ($includeBody) {
            $schemaRecord['body'] = Gdn::formatService()->renderHTML($searchRecord['Summary'], $searchRecord['Format']);
        }

        $lcfRecordType = lcfirst($searchRecord['RecordType'] ?? '');
        if (in_array($lcfRecordType, $this->fullSchema()->getField('properties.recordType.enum'))) {
            $schemaRecord['recordType'] = $lcfRecordType;
        }

        $lcfType = lcfirst($searchRecord['Type'] ?? '');
        if (in_array($lcfType, $this->fullSchema()->getField('properties.type.enum'))) {
            $schemaRecord['type'] = $lcfType;
        }

        if ($schemaRecord['recordType'] === 'discussion') {
            $schemaRecord['discussionID'] = $schemaRecord['recordID'];
            $schemaRecord['type'] = $schemaRecord['type'] ?? 'discussion';
        } else if ($schemaRecord['recordType'] === 'comment') {
            $schemaRecord['discussionID'] = $searchRecord['DiscussionID'];
            $schemaRecord['commentID'] = $schemaRecord['recordID'];
            $schemaRecord['type'] = $schemaRecord['type'] ?? 'comment';
        }

        if (isset($searchRecord['User'])) {
            $schemaRecord['insertUser'] = $searchRecord['User'];
        }

        $includeBreadcrumbs = $expandParams['breadcrumbs'] ?? false;
        if ($includeBreadcrumbs) {
            if (isset($schemaRecord['categoryID']) && $schemaRecord['categoryID'] !== 0) {
                $schemaRecord['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($schemaRecord['categoryID']));
            } elseif (isset($schemaRecord['knowledgeCategoryID'])) {
                $schemaRecord['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($schemaRecord['knowledgeCategoryID']));
            }
        }

        $includeExcept = $expandParams['excerpt'] ?? false;
        $excerpt = $searchRecord['excerpt'] ?? false;

        if ($includeExcept && $excerpt) {
            $schemaRecord['excerpt'] = $searchRecord['excerpt'];
        }

        if (!$excerpt) {
            $schemaRecord['excerpt'] = searchExcerpt(
                Gdn::formatService()->renderPlainText($searchRecord['Summary'], $searchRecord['Format']),
                $searchTerm
            );
        }

        $includeFirstImage = $expandParams['image'] ?? false;
        if ($includeFirstImage && isset($searchRecord['Summary'])) {
            $images = Gdn::formatService()->parseImages($searchRecord['Summary'], $searchRecord['Format']);

            if ($images && count($images) >= 1) {
                $schemaRecord['image'] = $images[0];
            }
        }

        $result = $this->getEventManager()->fireFilter('searchApiController_normalizeOutput', $schemaRecord, $this, $searchRecord, []);

        return $result;
    }

    /**
     * Allow to fill missing records information needed in normalization.
     *
     * @param array[] $records
     * @return array[]
     */
    public function preNormalizeOutputs(array $records): array {
        $discussionsIDs = [];
        $commentIDs = [];

        foreach ($records as $record) {
            if ($record['RecordType'] === 'Discussion') {
                $discussionsIDs[] = $record['PrimaryID'];
            } elseif ($record['RecordType'] === 'Comment') {
                $commentIDs[] = $record['PrimaryID'];
            }
        }

        $discussions = [];
        if ($discussionsIDs) {
            $discussions = $this->discussionModel->getWhere(
                ['DiscussionID' => $discussionsIDs],
                "",
                "",
                count($discussionsIDs)
            )->resultArray();
            $discussions = Gdn_DataSet::index($discussions, 'DiscussionID');
        }

        $comments = [];
        if ($commentIDs) {
            $comments = $this->commentModel->getWhere(
                ["CommentID" => $commentIDs],
                "",
                "asc",
                count($commentIDs)
            )->resultArray();
            $comments = Gdn_DataSet::index($comments, 'CommentID');
        }

        if ($comments || $discussions) {
            foreach ($records as &$record) {
                if ($record['RecordType'] === 'Discussion') {
                    $record['UpdateUserID'] = $discussions[$record['PrimaryID']]['UpdateUserID'] ?? null;
                    $record['DateUpdated'] = $discussions[$record['PrimaryID']]['DateUpdated'] ?? null;
                    $record['Url'] = discussionUrl($discussions[$record['PrimaryID']]);
                } elseif ($record['RecordType'] === 'Comment') {
                    $record['DiscussionID'] = $record['DiscussionID'] ?? $comments[$record['PrimaryID']]['DiscussionID'] ?? null;
                    $record['UpdateUserID'] = $comments[$record['PrimaryID']]['UpdateUserID'] ?? null;
                    $record['Url'] = commentUrl($comments[$record['PrimaryID']]);
                }
            }
        }

        $options = [];
        $result = $this->getEventManager()->fireFilter('searchApiController_preNormalizeOutputs', $records, $this, $options);

        return $result;
    }
}
