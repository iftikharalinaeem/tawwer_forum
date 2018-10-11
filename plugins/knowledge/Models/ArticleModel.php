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
class ArticleModel extends \Vanilla\Models\Model {
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
     * Add an article.
     *
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        $set["insertUserID"] = $set["updateUserID"] = $this->session->UserID;
        $set["dateInserted"] = $set["dateUpdated"] = new DateTimeImmutable("now");

        $result = parent::insert($set);
        return $result;
    }

    /**
     * Update existing articles.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @throws Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        $set["updateUserID"] = $this->session->UserID;
        $set["dateUpdated"] = new DateTimeImmutable("now");
        return parent::update($set, $where);
    }
}
