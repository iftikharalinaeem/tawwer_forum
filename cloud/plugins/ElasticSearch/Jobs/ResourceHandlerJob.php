<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Vanilla\HostedJob\Job\AbstractHostedJob;

/**
 * Class ElasticSearchIndexerRemote.
 *
 * @author Francis Caisse <francis.caisse@vanillaforums.com>
 */
class ResourceHandlerJob extends AbstractHostedJob {
    /**
     * @return string
     */
    public function getJobType(): string {
        return "\HostedQueue\Addons\ElasticSearchResourceHandler\ElasticSearchResourceHandlerJob";
    }
}
