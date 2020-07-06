<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Http\HttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Cloud\ElasticSearch\Http\AbstractElasticHttpClient;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalApiJob;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Http\InternalClient;

/**
 * Local job for with access to elasticsearch and an internal API client.
 */
abstract class AbstractLocalElasticJob extends LocalApiJob {

    /** @var AbstractElasticHttpClient */
    protected $elasticClient;

    /**
     * DI.
     *
     * @param InternalClient $internalClient
     * @param AbstractElasticHttpClient $elasticClient
     */
    public function setDependencies(InternalClient $internalClient, AbstractElasticHttpClient $elasticClient = null) {
        parent::setDependencies($internalClient);
        $this->elasticClient = $elasticClient;
    }

    /**
     * @inheritdoc
     */
    public function setPriority(JobPriority $priority) {
        // Unused.
    }

    /**
     * @inheritdoc
     */
    public function setDelay(int $seconds) {
        // Unused.
    }
}
