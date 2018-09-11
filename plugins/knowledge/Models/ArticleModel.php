<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Models;

use DateTimeImmutable;
use Gdn_Session;

/**
 * A model for managing articles.
 */
class ArticleModel extends \Vanilla\Models\Model {

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
     * @inheritDoc
     */
    public function insert(array $set) {
        $set["insertUserID"] = $set["updateUserID"] = $this->session->UserID;
        $set["dateInserted"] = $set["dateUpdated"] = new DateTimeImmutable("now");

        $result = parent::insert($set);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(array $set, array $where): bool {
        $set["updateUserID"] = $this->session->UserID;
        $set["dateUpdated"] = new DateTimeImmutable("now");
        return parent::update($set, $where);
    }
}
