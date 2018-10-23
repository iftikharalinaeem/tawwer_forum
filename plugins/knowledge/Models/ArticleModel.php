<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use DateTimeImmutable;
use Garden\Schema\ValidationException;
use Gdn_Session;
use Exception;

/**
 * A model for managing articles.
 */
class ArticleModel extends \Vanilla\Models\PipelineModel {

    const STATUS_PUBLISHED = 'published'; //default state
    const STATUS_DELETED = 'deleted';
    const STATUS_UNDELETED = 'undeleted';

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 30;

    /** @var Gdn_Session */
    private $session;

    /**
     * ArticleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("article");
        $this->session = $session;

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
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
     * @throws Exception If the article could not be found.
     */
    public function getID(int $articleID): array {
        $resultSet = $this->get(["ArticleID" => $articleID], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new Exception("An article with that ID could not be found.");
        }
        $row = reset($resultSet);
        return $row;
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
     *
     * @param array $where
     * @param array $options
     * @param array $pseudoFields
     *
     * @return array
     */
    public function getOutline(array $where = [], array $options = [], array $pseudoFields = []): array {
        $orderFields = $options["orderFields"] ?? "";
        $orderDirection = $options["orderDirection"] ?? "asc";
        $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
        $offset = $options["offset"] ?? 0;
        $page = $offset / $limit;

        $sql = $this->sql()
            ->from('article a')
            ->select('a.*, ar.name, ar.body, ar.bodyRendered')
            ->leftJoin("articleRevision ar", "a.articleRevisionID = ar.articleRevisionID and a.articleID = ar.articleID");
        foreach ($pseudoFields as $field => $val) {
            $sql->select('"'.$val.'" as '.$field);
        }
        foreach ($where as $field => $op) {
            if (is_array($op) && array_key_exists('in', $op)) {
                $sql->whereIn($field, $op['in']);
            } else {
                $sql->where([$field => $op]);
            }
        }
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
            self::STATUS_PUBLISHED
        ];
    }

    /**
     * Generate a URL to the provided article.
     *
     * @param array $article
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $article, bool $withDomain = true): string {
        $name = $article["name"] ?? 'article';
        $articleID = $article["articleID"] ?? null;

        if (!$name || !$articleID) {
            throw new \Exception("Invalid article row. ".json_encode($article));
        }

        $slug = \Gdn_Format::url("{$articleID}-{$name}");
        $result = \Gdn::request()->url("/kb/articles/".$slug, $withDomain);
        return $result;
    }
}
