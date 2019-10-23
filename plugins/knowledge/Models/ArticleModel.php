<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * A model for managing articles.
 */
class ArticleModel extends \Vanilla\Models\PipelineModel {
    /**
     * Record type is the key we can use as a foreign reference
     * to differentiate records of other types: article, discussion, category, etc
     *
     * @var string
     *
     */
    const RECORD_TYPE = 'article';

    /** Published status value. Default status. */
    const STATUS_PUBLISHED = "published";

    /** Deleted status value. */
    const STATUS_DELETED = "deleted";

    /** Restored status value. */
    const STATUS_UNDELETED = "undeleted";

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 30;

    /** @var Gdn_Session */
    private $session;

    /** @var KnowledgeBaseModel */
    private $kbModel;

    /**
     * ArticleModel constructor.
     *
     * @param Gdn_Session $session
     * @param KnowledgeBaseModel $kbModel
     */
    public function __construct(Gdn_Session $session, KnowledgeBaseModel $kbModel) {
        parent::__construct("article");
        $this->session = $session;
        $this->kbModel = $kbModel;

        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Configure a Garden Schema instance for read operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured read schema.
     */
    protected function configureReadSchema(Schema $schema): Schema {
        $schema = parent::configureReadSchema($schema);

        // Add optional revision fields to accommodate join operations.
        $schema = $schema->merge(Schema::parse([
            "knowledgeBaseID?" => [
                "type" => "integer",
            ],
            "articleRevisionID?" => [
                "allowNull" => true,
                "type" => "integer",
            ],
            "name?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "format?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "body?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "bodyRendered?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "outline?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "excerpt?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "locale?" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "translationStatus?" => [
                "allowNull" => true,
                "type" => "string",
            ],
        ]));
        return $schema;
    }

    /**
     * Get resource rows from a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     * @throws ValidationException If a row fails to validate against the schema.
     */
    public function get(array $where = [], array $options = []): array {
        $options["limit"] = $options["limit"] ?? self::LIMIT_DEFAULT;
        return parent::get($where, $options);
    }

    /**
     * Get a single article row by its ID.
     *
     * @param int $articleID
     * @return array
     * @throws ValidationException If the result fails schema validation.
     * @throws NoResultsException If the article could not be found.
     */
    public function getID(int $articleID): array {
        return $this->selectSingle(["articleID" => $articleID]);
    }

    /**
     * Get a single article row by its ID.
     *
     * @param int $articleID
     * @param bool $includeTranslations
     * @return array
     * @throws NoResultsException If the article could not be found.
     */
    public function getIDWithRevision(int $articleID, bool $includeTranslations = false): array {
        $limit = (!$includeTranslations) ?  ["limit" => 1] : ["limit" => ArticleRevisionModel::DEFAULT_LIMIT];
        $resultSet = $this->getWithRevision(["a.ArticleID" => $articleID], $limit);
        if (empty($resultSet)) {
            throw new NoResultsException("An article with that ID could not be found.");
        }

        $row = (!$includeTranslations) ? reset($resultSet) : $resultSet;
        return $row;
    }

    /**
     * Get article rows, merged with fields from the current revision.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     *    - includeBody (bool): Should revision body fields be included? Defaults to true.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     */
    public function getWithRevision(array $where = [], array $options = []): array {
        $databaseOperation = new Operation();
        $databaseOperation->setType(Operation::TYPE_SELECT);
        $databaseOperation->setCaller($this);
        $databaseOperation->setWhere($where);
        $databaseOperation->setOptions($options);

        $rows = $this->pipeline->process($databaseOperation, function (Operation $databaseOperation) {
            $options = $databaseOperation->getOptions();
            $where = $databaseOperation->getWhere();

            $orderFields = $options["orderFields"] ?? "";
            $orderDirection = $options["orderDirection"] ?? "asc";
            $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
            $offset = $options["offset"] ?? 0;
            $includeBody = $options["includeBody"] ?? true;

            $sql = $this->sql()
                ->select("a.*, c.knowledgeBaseID")
                ->select("ar.articleRevisionID")
                ->select("ar.name")
                ->select("ar.locale")
                ->select("ar.translationStatus")
                ->from($this->getTable() . " as a")
                ->join("articleRevision ar", "a.articleID = ar.articleID and ar.status = \"" . self::STATUS_PUBLISHED . "\"", "left")
                ->join("knowledgeCategory c", "a.knowledgeCategoryID = c.knowledgeCategoryID", "left")
                ->limit($limit, $offset);

            if ($includeBody) {
                $sql->select("ar.format")
                    ->select("ar.body")
                    ->select("ar.excerpt")
                    ->select("ar.bodyRendered")
                    ->select("ar.outline");
            }
            if ($orderFields) {
                $sql->orderBy($orderFields, $orderDirection);
            }
            if ($where) {
                $sql->where($where);
            }

            $result = $sql->get()->resultArray();
            return $result;
        });

        $schema = Schema::parse([":a" => $this->getReadSchema()]);
        $result = $schema->validate($rows);
        return $result;
    }

    /**
     * Get articles with a published revision in a particular category.
     *
     * @param int $knowledgeCategoryID
     * @param array $options
     * @return array
     */
    public function getPublishedByCategory(int $knowledgeCategoryID, array $options = []): array {
        $orderFields = $options["orderFields"] ?? "";
        $orderDirection = $options["orderDirection"] ?? "asc";
        $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
        $offset = $options["offset"] ?? 0;
        $page = $offset / $limit;

        $result = $this->sql()
            ->select("a.*")
            ->join("articleRevision ar", "ar.status = \"published\" and a.articleID = ar.articleID")
            ->where(["a.knowledgeCategoryID" => $knowledgeCategoryID])
            ->get($this->getTable() . " a", $orderFields, $orderDirection, $limit, $page)
            ->resultArray();
        return $result;
    }

    /**
     * Return extended article records joined with article revisions
     * Note: it only returns articles with published revisions.
     *       if there is no any - will not return those articles
     *
     * @param array $where
     * @param array $options
     * @param array $pseudoFields
     *
     * @return array
     */
    public function getExtended(array $where = [], array $options = [], array $pseudoFields = []): array {
        if (($options['only-translated'] ?? false)
        || empty($options['arl.locale'])) {
            $selectColumns = ['a.*, ar.name, ar.locale, c.knowledgeBaseID'];
        } else {
            $selectColumns = [
                'a.*',
                ['arl.name, ar.name', 'COALESCE', 'name'],
                ['arl.locale, ar.locale', 'COALESCE', 'locale'],
                'c.knowledgeBaseID'
            ];
        }

        $orderFields = $options["orderFields"] ?? "";
        $orderDirection = $options["orderDirection"] ?? "asc";
        $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
        if ($limit !== false) {
            $offset = $options["offset"] ?: 0;
            $page = $offset / $limit;
        } else {
            $page = false;
        }

        $sql = $this->sql()
            ->from('article a');
        foreach ($selectColumns as $selectColumn) {
            if (is_array($selectColumn)) {
                $sql->select($selectColumn[0], $selectColumn[1], $selectColumn[2]);
            } else {
                $sql->select($selectColumn);
            }
        }
        $sql->join("articleRevision ar", 'ar.status = "'.self::STATUS_PUBLISHED.'" AND a.articleID = ar.articleID')
            ->join("knowledgeCategory c", "a.knowledgeCategoryID = c.knowledgeCategoryID", "left");
        if (!empty($options['arl.locale'])) {
            $sql->leftJoin(
                'articleRevision arl',
                'arl.status = "'.self::STATUS_PUBLISHED.'" 
                    AND ar.articleID = arl.articleID 
                    AND arl.locale = "'.$options['arl.locale'].'" '
            );
        }
        foreach ($pseudoFields as $field => $val) {
            $sql->select('"' . $val . '" as ' . $field);
        }
        $sql->where($where);
        $result = $sql->get('', $orderFields, $orderDirection, $limit, $page)
            ->resultArray();
        return $result;
    }

    /**
     * Return all possible statuses for article record/item
     *
     * @return array
     */
    public static function getAllStatuses(): array {
        return [
            self::STATUS_UNDELETED,
            self::STATUS_DELETED,
            self::STATUS_PUBLISHED,
        ];
    }

    /**
     * Given a list of knowledge category IDs, get the top X articles in each.
     *
     * @param array $knowledgeCategoryIDs
     * @param string $orderField
     * @param string $orderDirection
     * @param int $limit
     */
    public function getTopPerCategory(array $knowledgeCategoryIDs, string $orderField, string $orderDirection, int $limit): array {
        $result = [];

        foreach ($knowledgeCategoryIDs as $knowledgeCategoryID) {
            $rows = $this->getExtended(
                [
                    "a.knowledgeCategoryID" => $knowledgeCategoryID,
                    "a.status" => self::STATUS_PUBLISHED,
                ],
                [
                    "limit" => $limit,
                    "orderFields" => $orderField,
                    "orderDirection" => $orderDirection,
                ]
            );
            $result = array_merge($result, $rows);
        }

        return $result;
    }

    /**
     * Generate a URL to the provided article row with revision fields.
     *
     * @param array $article An article row, joined with fields from a revision. A standard article row will not work.
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $article, bool $withDomain = true): string {
        $name = $article["name"] ?? null;
        $articleID = $article["articleID"] ?? null;

        if (!$name || !$articleID) {
            throw new \Exception('Invalid article row.');
        }

        $slug = \Gdn_Format::url("{$articleID}-{$name}");
        $siteSectionSlug = $this->kbModel->getSiteSectionSlug($article['knowledgeBaseID'], $article['locale']);
        $result = \Gdn::request()->getSimpleUrl($siteSectionSlug . "kb/articles/" . $slug);
        return $result;
    }

    /**
     * Generate a URL slug for an article row .
     *
     * @param array $article An array with just 2 filed required: name, articleID.
     *
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function getSlug(array $article): string {
        $name = $article["name"] ?? null;
        $articleID = $article["articleID"] ?? null;

        if (!$name || !$articleID) {
            throw new \Exception('Invalid article row.');
        }

        $slug = \Gdn_Format::url("{$articleID}-{$name}");
        return $slug;
    }
}
