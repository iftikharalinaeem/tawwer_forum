<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\ValidationException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;
use Webhooks\Processors\EncodeDecode;
use Webhooks\Processors\NormalizeInput;

/**
 * Class WebhookModel
 */
class WebhookModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * WebhookModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct('webhook');
        $this->session = $session;
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $normalizeProcessor = new NormalizeInput();
        $encodeDecodeProcessor = new EncodeDecode();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor($normalizeProcessor);
        $this->addPipelineProcessor($encodeDecodeProcessor);
        $userProcessor = new Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get a single webhook by ID.
     *
     * @param int $webhookID Unique ID of the webhook.
     * @return array SQL result.
     * @throws ValidationException If the result fails schema validation.
     * @throws NoResultsException If the webhook could not be found.
     */
    public function getID(int $webhookID): array {
        return $this->selectSingle(["webhookID" => $webhookID]);
    }
}
