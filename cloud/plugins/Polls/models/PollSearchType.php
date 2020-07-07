<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Polls\Models;

use Vanilla\Forum\Search\DiscussionSearchType;

/**
 * Class PollSearchType
 *
 * @package Vanilla\Polls\Models
 */
class PollSearchType extends DiscussionSearchType {
    
    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'poll';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'discussion';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'poll';
    }
}
