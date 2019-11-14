<?php
/**
 * Handles rules data.
 * @author Patrick Desjardins <patrick.d@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class RuleModel
 */
class RuleModel extends \Vanilla\Models\PipelineModel {

    /** @var Gdn_Session  */
    private $session;

    /**
     * RuleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct('Rule');
        $this->session = $session;

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["DateInserted"])->setUpdateFields(["DateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["InsertUserID"])
            ->setUpdateFields(["UpdateUserID"])
        ;
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get all rules.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function get(array $where = [], array $options = []): array {
        return parent::get($where, $options);
    }

    /**
     * Get a rule by unique identifier.
     *
     * @param int $ruleID
     * @return array
     */
    public function getID(int $ruleID) {
        return $this->selectSingle(['RuleID' => $ruleID]);
    }

    /**
     * Create a rule.
     *
     * @param array $set
     * @return mixed
     */
    public function insert(array $set) {
        return parent::insert($set);
    }

    /**
     * Update existing rules.
     *
     * @param array $set
     * @param array $where
     * @return bool
     */
    public function update(array $set, array $where):bool {
        return parent::update($set, $where);
    }
}
