<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Container\Container;
use Garden\Web\Data;
use Vanilla\Cloud\ElasticSearch\Http\AbstractElasticHttpClient;
use Vanilla\Cloud\ElasticSearch\Http\AbstractElasticHttpConfig;
use Vanilla\Cloud\ElasticSearch\Http\DevElasticHttpClient;
use Vanilla\Cloud\ElasticSearch\Http\DevElasticHttpConfig;
use Vanilla\Dashboard\Controllers\API\ResourcesApiController;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Plugin for elastic search.
 */
class ElasticSearchPlugin extends \Gdn_Plugin {

    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * DI.
     *
     * @param SchedulerInterface $scheduler
     */
    public function __construct(SchedulerInterface $scheduler) {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    /**
     * @param Container $dic
     */
    public function container_init(Container $dic) {
        if (!$dic->hasRule(AbstractElasticHttpClient::class)) {
            // No elasticsearch client has been added.
            // Let's setup the development one.
            $dic
                ->rule(AbstractElasticHttpClient::class)
                ->setClass(DevElasticHttpClient::class);
        }
    }

    /**
     * Trigger a full crawl of the site contents.
     *
     * @param ResourcesApiController $resourcesApi
     * @param \Gdn_Request $request
     *
     * @return Data
     */
    public function resourcesApiController_post_indexElastic(
        ResourcesApiController $resourcesApi,
        \Gdn_Request $request
    ): Data {
        $resourcesApi->permission('Garden.Settings.Manage');

        $slip = $this->scheduler->addJob(ResourceHandlerJob::class, [
            'url' => $request->getSimpleUrl('/api/v2/resources'),
            'devMode' => true
        ], JobPriority::low(), 0);

        $slipID = $slip->getId();
        return new Data(['slipID' => $slipID, 'extendedStatus' => $slip->getExtendedStatus()]);
    }
}
