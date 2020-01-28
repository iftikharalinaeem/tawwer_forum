<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Mocks;

use Garden\Events\ResourceEvent;

/**
 * Mock discussion event for testing purposes.
 */
class MockDiscussionEvent extends ResourceEvent {

    /**
     * {@inheritDoc}
     */
    public function getType(): string {
        return "discussion";
    }
}
