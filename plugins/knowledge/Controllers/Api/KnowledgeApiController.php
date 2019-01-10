<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\SphinxTrait;
use Garden\Web\Exception\ClientException;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\Breadcrumb;
use DiscussionModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use \CommentModel;

/**
 * Endpoint for the Knowledge resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;

    const SPHINX_DEFAULT_LIMIT = 100;

    const TYPE_ARTICLE = 5;
    const TYPE_DISCUSSION = 0;
    const TYPE_POLL = 2;

    const FORMAT_RICH = 'Rich';

    const ARTICLE_STATUSES = [
        1 => ArticleModel::STATUS_PUBLISHED,
        2 => ArticleModel::STATUS_DELETED,
        3 => ArticleModel::STATUS_UNDELETED
    ];

    const RECORD_TYPES = [
        0 => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
        ],
        1 => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
        ],
        2 => [
            'model' => 'discussion',
            'recordType' => 'discussion',
            'recordID' => 'DiscussionID',
            'offset' => 1,
            'multiplier' => 10,
            'getRecordsFunction' => 'getDiscussions',
            'sphinxIndexName' => ['Discussion', 'Discussion_Delta'],
        ],
        100 => [
            'model' => 'comment',
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
        ],
        101 => [
            'model' => 'comment',
            'recordType' => 'comment',
            'recordID' => 'DiscussionID',
            'offset' => 2,
            'multiplier' => 10,
            'getRecordsFunction' => 'getComments',
            'sphinxIndexName' => ['Comment', 'Comment_Delta'],
        ],
        5 => [
            'model' => 'articleRevisionModel',
            'recordType' => 'article',
            'recordID' => 'articleID',
            'offset' => 5,
            'multiplier' => 10,
            'getRecordsFunction' => 'getArticles',
            'sphinxIndexName' => ['KnowledgeArticle', 'KnowledgeArticle_Delta'],
        ],
    ];


    /** @var Schema */
    private $searchResultSchema;

    /** @var array */
    private $results = [];

    /** @var array */
    private $query = [];

    /**
     * Knowledge API controller constructor.
     *
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleModel $articleModel
     * @param UserModel $userModel
     * @param knowledgeCategoryModel $knowledgeCategoryModel
     * @param DiscussionModel $discussionModels
     * @param CommentModel $commentModel
     * @param \CategoryCollection $categoryCollection
     */
    public function __construct(
        ArticleRevisionModel $articleRevisionModel,
        ArticleModel $articleModel,
        \UserModel $userModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        DiscussionModel $discussionModel,
        \CommentModel $commentModel,
        \CategoryCollection $categoryCollection
    ) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->categoryCollectionModel = $categoryCollection;

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
                "knowledgeCategory?" => $this->categoryFragmentSchema(),
                "forumCategory?" => $this->forumCategoryFragmentSchema(),
            ],
            "searchResultSchema"
        );
    }

    /**
     * Get category breadcrumbs fragment schema.
     *
     * @return Schema
     */
    public function categoryFragmentSchema(): Schema {
        return $this->schema([
            'knowledgeCategoryID:i' => 'Knowledge category ID.',
            'breadcrumbs:a' => Schema::parse([
                "name:s" => "Breadcrumb element name.",
                "url:s" => "Breadcrumb element url.",
            ]),

        ], 'CategoryBreadcrumbsFragment');
    }

    /**
     * Get category breadcrumbs fragment schema.
     *
     * @return Schema
     */
    public function forumCategoryFragmentSchema(): Schema {
        return $this->schema([
            'CategoryID:i' => 'Forum category ID.',
            'breadcrumbs:a' => Schema::parse([
                "name:s" => "Breadcrumb element name.",
                "url:s" => "Breadcrumb element url.",
            ]),
        ], 'ForumCategoryBreadcrumbsFragment');
    }

    /**
     * Search endpoint controller. Ex: /api/v2/knowledge/search
     *
     * @param array $query
     * @return array
     */
    public function get_search(array $query = []): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema($this->defaultSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");

        $out = $this->schema([":a" => $this->searchResultSchema()], "out");

        $this->query = $in->validate($query);

        $searchResults = $this->sphinxSearch();
//die(print_r($searchResults));
        $results = $this->getNormalizedData($searchResults);

        $result = $out->validate($results);
        return $result;
    }

    /**
     * Prepare query for Sphinx search and gets Sphinx search results.
     *
     * @return array
     */
    protected function sphinxSearch(): array {
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits(0, self::SPHINX_DEFAULT_LIMIT);
        $query = $this->query;
        if (isset($query['knowledgeCategoryID'])) {
            $sphinx->setFilter('knowledgeCategoryID', [$query['knowledgeCategoryID']]);
        }
        $sphinxQuery = '';

        if (($query['global'] ?? false)) {
            $sphinxIndexes = $this->getIndexes();
            if (isset($query['insertUserIDs'])) {
                $sphinx->setFilter('insertUserID', $query['insertUserIDs']);
            }
            if (isset($query['name']) && !empty(trim($query['name']))) {
                $sphinxQuery .= '@name (' . $sphinx->escapeString($query['name']) . ')*';
            }
            if (isset($query['body']) && !empty(trim($query['body']))) {
                $sphinxQuery .= ' @body (' . $sphinx->escapeString($query['body']) . ')*';
            }
            if (isset($query['all']) && !empty(trim($query['all']))) {
                $sphinxQuery .= ' @(name,body) (' . $sphinx->escapeString($query['all']) . ')*';
            }
        } else {
            $sphinxIndexes = $this->getIndexes([self::TYPE_ARTICLE]);
            if (isset($query['statuses'])) {
                $statuses = array_map(
                    function ($status) {
                        return array_search($status, self::ARTICLE_STATUSES);
                    },
                    $query['statuses']
                );
                $sphinx->setFilter('status', $statuses);
            } else {
                $sphinx->setFilter('status', [array_search(ArticleModel::STATUS_PUBLISHED, self::ARTICLE_STATUSES)]);
            }

            if (isset($query['insertUserIDs'])) {
                $sphinx->setFilter('insertUserID', $query['insertUserIDs']);
            }
            if (isset($query['updateUserIDs'])) {
                $sphinx->setFilter('updateUserID', $query['updateUserIDs']);
            }
            if (isset($query['dateUpdated'])) {
                $range = DateFilterSphinxSchema::dateFilterRange($query['dateUpdated']);
                $range['startDate'] = $range['startDate'] ?? (new \DateTime())->setDate(1970, 1, 1)->setTime(0, 0, 0);
                $range['endDate'] = $range['endDate'] ?? (new \DateTime())->setDate(2100, 12, 31)->setTime(0, 0, 0);
                $sphinx->setFilterRange('dateUpdated', $range['startDate']->getTimestamp(), $range['endDate']->getTimestamp());
            }


            if (isset($query['name']) && !empty(trim($query['name']))) {
                $sphinxQuery .= '@name (' . $sphinx->escapeString($query['name']) . ')*';
            }
            if (isset($query['body']) && !empty(trim($query['body']))) {
                $sphinxQuery .= ' @body (' . $sphinx->escapeString($query['body']) . ')*';
            }
            if (isset($query['all']) && !empty(trim($query['all']))) {
                $sphinxQuery .= ' @(name,body) (' . $sphinx->escapeString($query['all']) . ')*';
            }
        }

        if ($sphinxRes = $sphinx->query($sphinxQuery, $sphinxIndexes)) {
            return $sphinxRes;
        } else {
            $errorMessage = $sphinx->getLastError();
            if (empty($errorMessage)) {
                $errorMessage = $sphinx->getLastWarning();
            }
            throw new ClientException($errorMessage);
        }
    }

    /**
     * Get all full sphinx index names needed for current search
     *
     * @param array $typesRequired
     * @return string
     */
    protected function getIndexes(array $typesRequired = []):string {
        $sphinxIndexes = [];
        $all = empty($typesRequired);
        foreach (self::RECORD_TYPES as $key => $sphinxTypes) {
            if ($all || in_array($key, $typesRequired)) {
                $idxFullNames = [];
                foreach ($sphinxTypes['sphinxIndexName'] as $idx) {
                    $idxFullNames[] = $this->sphinxIndexName($idx);
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

        if (in_array('category', $expand)) {
            $this->results['kbCategories'] = $this->getCategoriesData();
            $this->results['categories'] = $this->getForumCategoriesData();
            $this->results['discussions'] = $this->getForumDiscussionsData();
        }

        if (in_array('user', $expand)) {
            $this->results['users'] = $this->getUsersData();
        }

        if (($searchResults['total'] ?? 0) > 0) {
            $ids = [];
            foreach ($searchResults['matches'] as $guid => $record) {
                $type = self::RECORD_TYPES[$record['attrs']['dtype']];
                $ids[$record['attrs']['dtype']][] = ($guid - $type['offset']) / $type['multiplier'];
            };
            $results = [];
            foreach ($ids as $dtype => $recordIds) {

                array_push($results, ...$this->{self::RECORD_TYPES[$dtype]['getRecordsFunction']}($recordIds, $dtype, $expand));

            }
        }
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
            $article["recordID"] = $article[$type['recordID']];
            $article["recordType"] = self::RECORD_TYPES[self::TYPE_ARTICLE]['recordType'];
            $article["body"] = htmlspecialchars_decode(strip_tags($article["bodyRendered"]), ENT_QUOTES);
            $article["url"] = $this->articleModel->url($article);
            $article["status"] = self::ARTICLE_STATUSES[$article["status"]];

            $guid = $article['articleRevisionID'] * $type['multiplier'] + $type['offset'];
            $article = array_merge($article, $this->results['matches'][$guid]['attrs']);
            if (in_array('category', $expand)) {
                $article["knowledgeCategory"] = $this->results['kbCategories'][$article['categoryid']];
            }
            if (in_array('user', $expand)) {
                if (isset($this->results['users'][$article['updateuserid']])) {
                    $article["updateUser"] = $this->results['users'][$article['updateuserid']];
                } elseif (isset($this->results['users'][$article['insertuserid']])) {
                    $article["insertUser"] = $this->results['users'][$article['insertuserid']];
                }
            }
        }
        return $result;
    }

    /**
     * Get records from discussionModel model
     *
     * @param array $iDs
     * @param int $type
     * @param array $expand
     * @return array
     */
    public function getDiscussions(array $iDs, int $type, array $expand = []): array {

        $result = $this->discussionModel->get( null, '',
            ['d.DiscussionID' => $iDs]
        )->resultArray();


        $type = self::RECORD_TYPES[$type];
        foreach ($result as &$discussion) {

            $discussion["body"] = $this->format($discussion['Body'], $discussion['Format']);
            $discussion["recordID"] = $discussion[$type['recordID']];
            $discussion["guid"] = $discussion[$type['recordID']] * $type['multiplier'] + $type['offset'];
            $discussion["recordType"] = $type['recordType'];
            $discussion['url'] = \Gdn::request()->url('/discussion/'.urlencode($discussion['DiscussionID']).'/'.urlencode($discussion['Name']), true);
            if (in_array('category', $expand)) {
                $discussion["forumCategory"] = $this->results['categories'][$discussion['CategoryID']];
            }
            if (in_array('user', $expand)) {
                if (isset($this->results['users'][$discussion['UpdateUserID']])) {
                    $discussion["updateUser"] = $this->results['users'][$discussion['UpdateUserID']];
                } elseif (isset($this->results['users'][$discussion['InsertUserID']])) {
                    $discussion["insertUser"] = $this->results['users'][$discussion['InsertUserID']];
                }
            }
        }
        return $result;
    }

    public static function format(string $body,string $format, bool $text = true): string {
        if ($format === self::FORMAT_RICH) {
            $body = \Gdn_Format::rich($body);
        } else {
            $body = \Gdn_Format::to($body,$format);
        }
        if ($text) {
            $body = strip_tags($body);
        }
        return $body;
    }
    /**
     * Get records from commentModel model
     *
     * @param array $iDs
     * @param int $type
     * @param array $expand
     * @return array
     */
    public function getComments(array $iDs, int $type, array $expand = []): array {
        $result = $this->commentModel->getWhere(
            ['CommentID' => $iDs]
        )->resultArray();
        //if($type > 99 ) $t = $type;
        $type = self::RECORD_TYPES[$type];

        foreach ($result as &$comment) {
            //print_r($comment);
            //if ($comment['Format'] === 'Rich') die(\Gdn_Format::rich($comment['Body']));
            $comment["body"] =  $this->format($comment['Body'], $comment['Format']);
            $comment["recordID"] = $comment[$type['recordID']];
            $discussion = $this->results['discussions'][$comment['DiscussionID']];
            $comment["name"] = $discussion['Name'];
            $comment["discussionID"] = $comment['DiscussionID'];
            $comment["guid"] = $comment[$type['recordID']] * $type['multiplier'] + $type['offset'];
            $comment["recordType"] = $type['recordType'];
            $comment['url'] = \Gdn::request()->url('/discussion/'.urlencode($comment['DiscussionID']).'/'.urlencode($discussion['Name']), true);
            if (in_array('category', $expand)) {
                $cat = ($this->results['categories'][$discussion['CategoryID']] ?? false);
                if ($cat) {
                    //print_r($cat)
                    $comment["forumCategory"] = $cat;
                } else {
                    ;
                };
            }
            if (in_array('user', $expand)) {
                if (isset($this->results['users'][$comment['UpdateUserID']])) {
                    $comment["updateUser"] = $this->results['users'][$comment['UpdateUserID']];
                } elseif (isset($this->results['users'][$comment['InsertUserID']])) {
                    $comment["insertUser"] = $this->results['users'][$comment['InsertUserID']];
                }
            }
        }
        //die();

        return $result;
    }

    /**
     * Check if need to expand user fragment and return users data.
     *
     * @return array
     */
    protected function getUsersData(): array {
        $userResults = [];
        if (in_array('user', $this->query['expand'])) {
            $users = [];
            foreach ($this->results['matches'] as $key => $article) {
                if ($article['attrs']['updateuserid'] ?? false) {
                    $users[$article['attrs']['updateuserid']] = true;
                } else {
                    $users[$article['attrs']['insertuserid']] = true;
                }
            };
            $userResults = $this->userModel->getIDs(array_keys($users));
            foreach ($userResults as $id => &$user) {
                $user['photoUrl'] = $user['Photo'] ?? \UserModel::getDefaultAvatarUrl($user);
            }
        }
        return $userResults;
    }

    /**
     * Check if need to expand category and return categories data.
     *
     * @return array
     */
    protected function getCategoriesData(): array {
        $categoryResults = [];

        $categories = [];
        foreach ($this->results['matches'] as $key => $article) {
            if ($article['attrs']['dtype'] === self::TYPE_ARTICLE) {
                $categories[$article['attrs']['categoryid']] = true;
            }
        };

        foreach ($categories as $categoryID => $drop) {
            $categoryResults[$categoryID] = [
                'knowledgeCategoryID' => $categoryID,
                'breadcrumbs' => array_map(
                    function (Breadcrumb $breadcrumb) {
                        return $breadcrumb->asArray();
                    },
                    array_values($this->knowledgeCategoryModel->buildBreadcrumbs($categoryID))
                )
            ];
        }

        return $categoryResults;
    }

    /**
     * Check if need to expand category and return categories data.
     *
     * @return array
     */
    protected function getForumCategoriesData(): array {
        $categoryResults = [];

        $categories = [];
       // die(print_r($this->results['matches']));
        foreach ($this->results['matches'] as $key => $article) {
            if ($article['attrs']['dtype'] !== self::TYPE_ARTICLE) {
                $categories[$article['attrs']['categoryid']] = true;
            }
        };
        //die(print_r($this->results['matches']));

        foreach ($categories as $categoryID => $drop) {
            $ancestors = $this->categoryCollectionModel->getAncestors($categoryID);
            $breadcrumbs = [];
            foreach ($ancestors as $category) {
                $breadcrumbs[] = (new Breadcrumb(
                    $category["Name"],
                    \Gdn::request()->url('/categories/'.rawurlencode($category['UrlCode']), true)
                ))->asArray();
            }
            $categoryResults[$categoryID] = [
                'CategoryID' => $categoryID,
                'breadcrumbs' => $breadcrumbs,
            ];
        }
        return $categoryResults;
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
            ['DiscussionID' => array_keys($discussions)]
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
    protected function defaultSchema() {
        return [
            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
            "knowledgeCategoryID:i?" => "Knowledge category ID to filter results.",
            "insertUserIDs:a?" => "Array of insertUserIDs (authors of article) to filter results.",
            "updateUserIDs:a?" => "Array of updateUserIDs (last editors of an article) to filter results.",
            "expand:a?" => [
                "description" => "Expand data for: user, category.",
                'items' => [
                    'enum' => ["user", "category"],
                    'type' => 'string'
                ]
            ],
            'dateUpdated?' => new DateFilterSphinxSchema([
                'description' => 'Filter by date when the article was updated.',
            ]),
            "statuses:a?" => "Article statuses array to filter results.",
            "name:s?" => "Keywords to search against article name.",
            "body:s?" => "Keywords to search against article body.",
            "all:s?" => "Keywords to search against article name or body.",
            "global:b?" => "Global search flag. Default: false",
        ];
    }
}
