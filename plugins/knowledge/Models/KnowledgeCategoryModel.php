<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use DateTimeImmutable;
use Gdn_Session;

/**
 * A model for managing knowledge categories.
 */
class KnowledgeCategoryModel extends \Vanilla\Models\PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * KnowledgeCategoryModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("knowledgeCategory");
        $this->session = $session;
    }

    /**
     * Delete knowledge categories.
     *
     * @param array $where
     * @param int $limit
     */
    public function delete(array $where, int $limit = 1) {
        $this->sql()->delete(
            $this->getTable(),
            $where,
            $limit
        );
    }

    /**
     * Add a knowledge category.
     *
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        $set["insertUserID"] = $set["updateUserID"] = $this->session->UserID;
        $set["dateInserted"] = $set["dateUpdated"] = new DateTimeImmutable("now");

        $result = parent::insert($set);
        return $result;
    }

    /**
     * Update existing knowledge categories.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        $set["updateUserID"] = $this->session->UserID;
        $set["dateUpdated"] = new DateTimeImmutable("now");
        return parent::update($set, $where);
    }
}
