<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\SphinxTrait;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

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
     */
    public function __construct(
        ArticleRevisionModel $articleRevisionModel,
        ArticleModel $articleModel
    ) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
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
                "recordID" => ["type" => "integer"],
                "dateInserted" => ["type" => "datetime"],
                "dateUpdated" => ["type" => "datetime"],
                "knowledgeCategoryID?"=> ["type" => "integer"],
                "status" => ["type" => "string"],
                "recordType" => [
                    "enum" => ["article", "knowledgeCategory"],
                    "type" => "string",
                ]
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
    public function get_search(array $query = []): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema($this->defaultSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");

        $out = $this->schema([":a" => $this->searchResultSchema()], "out");

        $query = $in->validate($query);

        $searchResults = $this->sphinxSearch($query);

        $results = $this->getNormalizedData($searchResults);

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
        $sphinx->setMatchMode(SPH_MATCH_EXTENDED);
        //$sphinx->setSelect(' id, status');

        if (isset($query['knowledgeCategoryID'])) {
            $sphinx->setFilter('knowledgeCategoryID', [$query['knowledgeCategoryID']]);
        }
        if (isset($query['status'])) {
            $statuses = array_map(
                function ($status) {
                    return array_search($status, self::ARTICLE_STATUSES);
                },
                $query['status']
            );
            $sphinx->setFilter('status', $statuses);
        }
        if (isset($query['insertUserID'])) {
            $sphinx->setFilter('insertUserID', $query['insertUserID']);
        }
        if (isset($query['dateUpdated'])) {
            $range = DateFilterSchema::dateFilterRange($query['dateUpdated']);
            $sphinx->setFilterRange('dateUpdated', $range['startDate']->getTimestamp(), $range['endDate']->getTimestamp(), $range['exclude']);
        }
        $sphinxQuery = '';
        if (isset($query['name']) && !empty(trim($query['name']))) {
            $sphinxQuery .= '@name (' . $sphinx->escapeString($query['name']) . ')*';
        }
        if (isset($query['body']) && !empty(trim($query['body']))) {
            $sphinxQuery .= '@bodyRendered (' . $sphinx->escapeString($query['body']) . ')*';
        }
        return $sphinx->query($sphinxQuery, $this->sphinxIndexName('KnowledgeArticle'));
    }

    /**
     * Get articles data from articleRevisionsModel and normalize records for output
     *
     * @param array $searchResults Result set returned by Sphinx search
     * @return array
     */
    protected function getNormalizedData(array $searchResults): array {
        $results = [];
        if (($searchResults['total'] ?? 0) > 0) {
            $results = $this->articleRevisionModel->get(
                ['articleRevisionID' => array_keys($searchResults['matches']),
                    'status' => ArticleModel::STATUS_PUBLISHED]
            );
        }

        foreach ($results as &$article) {
            $article = array_merge($article, $searchResults['matches'][$article['articleRevisionID']]['attrs']);
            $article = $this->normalizeOutput($article);
        }
        return $results;
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
            "insertUserID:a?" => "User ID (author of article) to filter results.",
            'dateUpdated?' => new DateFilterSchema([
                'description' => 'Filter by date when the article was updated.',
            ]),
            "status:a?" => "Article statuses array to filter results.",
            "name:s?" => "Keywords to search against article name.",
            "body:s?" => "Keywords to search against article body.",
        ];
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $row
     * @return array
     */
    private function normalizeOutput(array $row): array {
        $row["recordID"] = $row["articleID"];
        $row["recordType"] = "article";
        $row["body"] = strip_tags($row["bodyRendered"]);
        $row["url"] = $this->articleModel->url($row);
        $row["status"] = self::ARTICLE_STATUSES[$row["status"]];

        return $row;
    }
}
