<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Jobs;

use Vanilla\HostedJob\Job\AbstractHostedJob;

/**
 * Class HostedJob
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class HostedJob extends AbstractHostedJob {

    /**
     * @return string
     */
    public function getJobType(): string {
        return 'HostedQueue\Addons\KnowledgePorterJob\KnowledgePorterJob';
    }
}
