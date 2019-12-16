<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

use Garden\Schema\ValidationException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;
use Vanilla\Webhooks\Processors\EncodeDecode;
use Vanilla\Webhooks\Processors\NormalizeInput;

/**
 * Class WebhookModel
 */
class WebhookModel extends PipelineModel {

    /**
     * WebhookModel constructor.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        parent::__construct('webhook');
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $normalizeProcessor = new NormalizeInput();
        $encodeDecodeProcessor = new EncodeDecode();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor($normalizeProcessor);
        $this->addPipelineProcessor($encodeDecodeProcessor);
        $userProcessor = new Operation\CurrentUserFieldProcessor($session);
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
