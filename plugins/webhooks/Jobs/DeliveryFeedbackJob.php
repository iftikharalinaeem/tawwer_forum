<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Vanilla\HostedJob\Job\HostedFeedbackInterface;

/**
 * Record an event delivery attempt to a webhook from the remote queue.
 */
class DeliveryFeedbackJob extends LogDeliveryJob implements HostedFeedbackInterface {

    /**
     * {@inheritDoc}
     */
    public function execute(array $body = []) {
        $this->setMessage($body);
        $this->run();
    }
}
