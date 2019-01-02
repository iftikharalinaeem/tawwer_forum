<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;

/**
 * Class SearchApiController
 */
class SearchApiController extends AbstractApiController {

    /** @var Schema */
    private $searchResultSchema;

    /** @var CommentModel */
    private $commentModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var SearchModel */
    private $searchModel;

    /**
     * SearchApiController constructor.
     *
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @param SearchModel $searchModel
     */
    public function __construct(CommentModel $commentModel, DiscussionModel $discussionModel, SearchModel $searchModel) {
        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->searchModel = $searchModel;
    }

    /**
     * Search results schema record.
     *
     * @return Schema
     */
    public function fullSchema(): Schema {
        if (!$this->searchResultSchema) {
            $this->searchResultSchema = $this->schema([
                'recordID:i' => 'The identifier of the record.',
                'recordType:s' => [
                    'enum' => ['discussion', 'comment'],
                    'description' => 'The main type of record.',
                ],
                'type:s' => [
                    'enum' => ['discussion', 'comment'],
                    'description' => 'Sub-type of the discussion.',
                ],
                'discussionID:i?' => 'The id of the discussion.',
                'commentID:i?' => 'The id of the comment.',
                'categoryID:i?' => 'The category containing the record.',
                'name:s' => 'The title of the record. A comment would be "RE: {DiscussionTitle}".',
                'body:s' => 'The content of the record.',
                'score:i' => 'Score of the record.',
                'insertUserID:i' => 'The user that created the record.',
                'dateInserted:dt' => 'When the record was created.',
                'updateUserID:i|n' => 'The user that updated the record.',
                'dateUpdated:dt|n' => 'When the user was updated.',
            ], 'SearchResult');
        }

        return $this->searchResultSchema;
    }

    /**
     * List search results.
     *
     * @throws ServerException
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data {
        $this->permission();

        // Custom validator
        $validator = function ($data, ValidationField $field) {
            $schemaDefinition = $field->getField()['properties'];
            foreach ($data as $name => $value) {
                if (!empty($value) &&
                    (isset($schemaDefinition[$name]['x-search-scope']) || isset($schemaDefinition[$name]['x-search-filter']))
                ) {
                    return true;
                }
            }

            $field->addError('missingField', [
                'messageCode' => 'You need to specify either a scope or a filter.',
                'required' => true,
            ]);

            return false;
        };

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
                'followedCategories:b?' => [
                    'default' => false,
                    'description' => 'Set the scope of the search to followed categories only.',
                    'x-search-scope' => true,
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
                    'description' => 'Filter the record by when it was inserted.',
                    'x-search-filter' => true,
                    'x-filter' => [
                        'field' => 'DateInserted',
                        'processor' => [DateFilterSchema::class, 'dateFilterField'],
                    ],
                ]),
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
                'page:i?' => [
                    'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => 30,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ], ['SearchIndex', 'in'])
            ->addValidator('', $validator)
            ->setDescription('Search for records matching specific criteria.');
        $out = $this->schema([':a' => $fullSchema], 'out');

        $query = $in->validate($query);
        if (isset($query['dateInserted'])) {
            $query['dateFilters'] = ApiUtils::queryToFilters($in, ['dateInserted' => $query['dateInserted']]);
        }
        $search = $this->normalizeSearch($query);

        // Paging
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if ($this->searchModel instanceof SphinxSearchModel) {
            $data = $this->searchModel->advancedSearch($search, $offset, $limit, 'api')['SearchResults'] ?? [];
            if (!$data) {
                $sphinx = $this->searchModel->sphinxClient();
                if ($sphinx->getLastError()) {
                    throw new ServerException($sphinx->getLastError(), 500);
                }
            }
        } else {
            $data = AdvancedSearchPlugin::devancedSearch($this->searchModel, $search, $offset, $limit, 'api');
        }

        $data = $this->preNormalizeOutputs($data);
        $data = array_map([$this, 'normalizeOutput'], $data);

        $result = $out->validate($data);

        return new Data($result);
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
            'dateFilters' => 'date-filters',
            'insertUserNames' => 'author',
            'insertUserIDs' => 'users',
            'tags' => 'tags',
            'tagOperator' => 'tags-op',
        ];
        $recordTypeMap = Search::types();

        $result = [
            'types' => [],
        ];

        if (!array_key_exists('recordTypes', $query)) {
            $query['recordTypes'] = $this->fullSchema()->getField('properties.recordType.enum');
        }
        if (!array_key_exists('types', $query)) {
            $query['types'] = $this->fullSchema()->getField('properties.type.enum');
        }

        $types = $query['recordTypes'];
        $subTypes = $query['types'];
        foreach ($types as $type) {
            if (isset($recordTypeMap[$type])) {
                foreach ($subTypes as $subType) {
                    if ($subType === 'discussion') {
                        $subType = 'd';
                    } else if ($subType === 'comment') {
                        $subType = 'c';
                    // Until we rework sphinx. See TODO LINK PR.
                    } else if ($type === 'comment' && $subType === 'question') {
                        $subType = 'answer';
                    }
                    if (isset($recordTypeMap[$type][$subType])) {
                        $result['types'][$type][] = $subType;
                    }
                }
                if (empty($result['types'][$type])) {
                    $result['types'][$type] = array_keys($recordTypeMap[$type]);
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
     * @return array
     */
    public function normalizeOutput(array $searchRecord): array {
        $schemaRecord = [
            'recordID' => $searchRecord['PrimaryID'],
            'recordType' => null,
            'type' => null,
            'categoryID' => $searchRecord['CategoryID'],
            'name' => $searchRecord['Title'],
            'body' => Gdn_Format::to($searchRecord['Summary'], $searchRecord['Format']),
            'score' => $searchRecord['Score'] ?? 0,
            'insertUserID' => $searchRecord['UserID'],
            'dateInserted' => $searchRecord['DateInserted'],
            'updateUserID' => $searchRecord['UpdateUserID'] ?? null,
            'dateUpdated' => $searchRecord['DateUpdated'] ?? null,
        ];

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
            } else if ($record['RecordType'] === 'Comment') {
                $commentIDs[] = $record['PrimaryID'];
            }
        }

        $discussions = [];
        if ($discussionsIDs) {
            $discussions = $this->discussionModel->getWhere(['DiscussionID' => $discussionsIDs])->resultArray();
            $discussions = Gdn_DataSet::index($discussions, 'DiscussionID');
        }

        $comments = [];
        if ($commentIDs) {
            $comments = $this->commentModel->getWhere(['CommentID' => $commentIDs])->resultArray();
            $comments = Gdn_DataSet::index($comments, 'CommentID');
        }

        if ($comments || $discussions) {
            foreach ($records as &$record) {
                if ($record['RecordType'] === 'Discussion') {
                    $record['UpdateUserID'] = $discussions[$record['PrimaryID']]['UpdateUserID'] ?? null;
                    $record['DateUpdated'] = $discussions[$record['PrimaryID']]['DateUpdated'] ?? null;
                } else if ($record['RecordType'] === 'Comment') {
                    $record['DiscussionID'] = $record['DiscussionID'] ?? $comments[$record['PrimaryID']]['DiscussionID'] ?? null;
                    $record['UpdateUserID'] = $comments[$record['PrimaryID']]['UpdateUserID'] ?? null;
                    $record['DateUpdated'] = $comments[$record['PrimaryID']]['DateUpdated'] ?? null;
                }
            }
        }

        $options = [];
        $result = $this->getEventManager()->fireFilter('searchApiController_preNormalizeOutputs', $records, $this, $options);

        return $result;
    }
}
