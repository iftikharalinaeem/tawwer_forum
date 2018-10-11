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

    /** @var Gdn_Session */
    private $session;

    /**
     * ArticleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct('article');
        $this->session = $session;
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
     * @inheritdoc
     */
    public function insert(array $set) {
        $set["insertUserID"] = $set["updateUserID"] = $this->session->UserID;
        $set["dateInserted"] = $set["dateUpdated"] = new DateTimeImmutable("now");

        $result = parent::insert($set);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function update(array $set, array $where): bool {
        $set["updateUserID"] = $this->session->UserID;
        $set["dateUpdated"] = new DateTimeImmutable("now");
        return parent::update($set, $where);
    }
}
