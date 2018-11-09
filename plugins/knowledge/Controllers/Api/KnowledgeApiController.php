<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\SphinxTrait;
use Vanilla\DateFilterSphinxSchema;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\Breadcrumb;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Endpoint for the Knowledge resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;

    const SPHINX_DEFAULT_LIMIT = 100;

    const ARTICLE_STATUSES = [
        1 => ArticleModel::STATUS_PUBLISHED,
        2 => ArticleModel::STATUS_DELETED,
        3 => ArticleModel::STATUS_UNDELETED
    ];

    /** @var Schema */
    private $searchResultSchema;

    /**
     * Knowledge API controller constructor.
     *
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleModel $articleModel
     * @param UserModel $userModel
     * @param knowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(
        ArticleRevisionModel $articleRevisionModel,
        ArticleModel $articleModel,
        \UserModel $userModel,
        KnowledgeCategoryModel $knowledgeCategoryModel
    ) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
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
                "updateUserID" => ["type" => "integer"],
                "recordID" => ["type" => "integer"],
                "dateInserted" => ["type" => "datetime"],
                "dateUpdated" => ["type" => "datetime"],
                "knowledgeCategoryID?"=> ["type" => "integer"],
                "status" => ["type" => "string"],
                "recordType" => [
                    "enum" => ["article", "knowledgeCategory"],
                    "type" => "string",
                ],
                "updateUser?" => $this->getUserFragmentSchema(),
                "knowledgeCategory?" => $this->categoryFragmentSchema()
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

        $query = $in->validate($query);

        $searchResults = $this->sphinxSearch($query);

        $results = $this->getNormalizedData($searchResults, $query['expand'] ?? []);

        $result = $out->validate($results);
        return $result;
    }

    /**
     * Prepare query for Sphinx search and gets Sphinx search results
     *
     * @param array $query GET query parameters array
     * @return array
     */
    protected function sphinxSearch(array $query): array {
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits(0, self::SPHINX_DEFAULT_LIMIT);

        if (isset($query['knowledgeCategoryID'])) {
            $sphinx->setFilter('knowledgeCategoryID', [$query['knowledgeCategoryID']]);
        }
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
        $sphinxQuery = '';

        if (isset($query['name']) && !empty(trim($query['name']))) {
            $sphinxQuery .= '@name (' . $sphinx->escapeString($query['name']) . ')*';
        }
        if (isset($query['body']) && !empty(trim($query['body']))) {
            $sphinxQuery .= ' @bodyRendered (' . $sphinx->escapeString($query['body']) . ')*';
        }
        if (isset($query['all']) && !empty(trim($query['all']))) {
            $sphinxQuery .= '@(name,bodyRendered) (' . $sphinx->escapeString($query['all']) . ')*';
        }
        return $sphinx->query($sphinxQuery, $this->sphinxIndexName('KnowledgeArticle'));
    }

    /**
     * Get articles data from articleRevisionsModel and normalize records for output
     *
     * @param array $searchResults Result set returned by Sphinx search
     * @param array $expand List of properties need to provide extra details.
     *        Ex ['category','user']
     * @return array
     */
    protected function getNormalizedData(array $searchResults, array $expand = []): array {
        $results = [];
        if (($searchResults['total'] ?? 0) > 0) {
            $results = $this->articleRevisionModel->get(
                ['articleRevisionID' => array_keys($searchResults['matches']),
                    'status' => ArticleModel::STATUS_PUBLISHED]
            );
        }
        $userResults = $this->getUsersData($searchResults, $expand);

        $categoryResults = $this->getCategoriesData($searchResults, $expand);

        foreach ($results as &$article) {
            $article = array_merge($article, $searchResults['matches'][$article['articleRevisionID']]['attrs']);

            $article = $this->normalizeOutput($article, $userResults, $categoryResults);
        }
        return $results;
    }

    /**
     * Check if need to expand user fragment and return users data.
     *
     * @param array $searchResults Sphinx search results array
     * @param array $expand Query param: expand
     * @return array
     */
    protected function getUsersData(array $searchResults, array $expand): array {
        $userResults = [];
        if (in_array('user', $expand)) {
            $users = [];
            foreach ($searchResults['matches'] as $key => $article) {
                $users[$article['attrs']['updateuserid']] = true;
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
     * @param array $searchResults Sphinx search results array
     * @param array $expand Query param: expand
     * @return array
     */
    protected function getCategoriesData(array $searchResults, array $expand): array {
        $categoryResults = [];
        if (in_array('category', $expand)) {
            $categories = [];
            foreach ($searchResults['matches'] as $key => $article) {
                $categories[$article['attrs']['knowledgecategoryid']] = true;
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
        }
        return $categoryResults;
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
        ];
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $row
     * @param array $users Array of userID => [user fields]
     * @param array $categories Array of knowldegCategoryID => [category fields]
     * @return array
     */
    private function normalizeOutput(array $row, array $users, array $categories): array {
        $row["recordID"] = $row["articleID"];
        $row["recordType"] = "article";
        $row["body"] = strip_tags($row["bodyRendered"]);
        $row["url"] = $this->articleModel->url($row);
        if (isset($users[$row['updateuserid']])) {
            $row["updateUser"] = $users[$row['updateuserid']];
        }
        if (isset($categories[$row['knowledgecategoryid']])) {
            $row["knowledgeCategory"] = $categories[$row['knowledgecategoryid']];
        }

        $row["status"] = self::ARTICLE_STATUSES[$row["status"]];

        return $row;
    }
}
