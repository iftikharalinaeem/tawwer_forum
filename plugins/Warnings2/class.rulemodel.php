<?php
/**
 * Simple model to handle rules data.
 *
 * @author Patrick Desjardins <patrick.d@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use \Vanilla\Database\Operation\CurrentDateFieldProcessor;
use \Vanilla\Database\Operation\CurrentUserFieldProcessor;
use \Vanilla\Models\PipelineModel;

/**
 * Class RuleModel
 */
class RuleModel extends PipelineModel {

    /**
     * RuleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct('Rule');

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["DateInserted"])->setUpdateFields(["DateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->setInsertFields(["InsertUserID"])->setUpdateFields(["UpdateUserID"]);
        $this->addPipelineProcessor($userProcessor);
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
}
