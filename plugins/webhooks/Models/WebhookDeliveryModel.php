<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Models;

use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;

/**
 * Provides data management capabilities for webhook delivery information.
 */
class WebhookDeliveryModel extends PipelineModel {

    /** Default limit on number of deliveries that should be returned. */
    public const LIMIT_DEFAULT = 10;

    /**
     * WebhookDeliveryModel constructor.
     */
    public function __construct() {
        parent::__construct("webhookDelivery");

        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor
            ->setInsertFields(["dateInserted"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
    }
}
