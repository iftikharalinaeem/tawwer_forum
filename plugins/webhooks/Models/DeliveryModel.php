<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Models;

use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;
use Vanilla\Webhooks\Processors\NormalizeDataProcessor;

/**
 * Provides data management capabilities for webhook delivery information.
 */
class DeliveryModel extends PipelineModel {

    /**
     * WebhookDeliveryModel constructor.
     */
    public function __construct() {
        parent::__construct("webhookDelivery");

        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor
            ->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $normalizeProcessor = new NormalizeDataProcessor();
        $normalizeProcessor
            ->addSerializedField("request")
            ->addSerializedField("response");
        $this->addPipelineProcessor($normalizeProcessor);
    }
}
