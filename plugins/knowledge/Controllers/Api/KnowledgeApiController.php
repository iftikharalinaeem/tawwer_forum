<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\SphinxTrait;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

/**
 * Endpoint for the Knowledge resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;

    const SPHINX_DEFAULT_LIMIT = 100;

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
                "bodyPlainText?"  => ["type" => "string"],
                "bodyRendered?"  => ["type" => "string"],
                "url" => ["type" => "string"],
                "recordID" => ["type" => "integer"],
                "knowledgeCategoryID?"=> ["type" => "integer"],
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
        $sphinx->SetLimits(0, self::SPHINX_DEFAULT_LIMIT);
        $sphinx->setMatchMode(SPH_MATCH_EXTENDED);

        if (isset($query['knowledgeCategoryID'])) {
            $sphinx->setFilter('knowledgeCategoryID', [$query['knowledgeCategoryID']]);
        }
        $sphinxQuery = '';
        if (isset($query['name']) && !empty(trim($query['name']))) {
            $sphinxQuery .= '@name ('.$sphinx->EscapeString($query['name']).')*';
        }
        if (isset($query['body']) && !empty(trim($query['body']))) {
            $sphinxQuery .= '@body ('.$sphinx->EscapeString($query['body']).')*';
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
            "name:s?" => "Keywords to search against article name.",
            "body:s?" => "Keywords to search against article body.",
        ];
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    private function normalizeOutput(array $row): array {
        $row["recordID"] = $row["articleID"];
        $row["recordType"] = "article";
        $row["bodyPlainText"] = strip_tags($row["bodyRendered"]);
        $row["url"] = $this->articleModel->url($row);

        return $row;
    }
}
