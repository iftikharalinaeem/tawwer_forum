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
use Vanilla\Scheduler\Job\LocalJobInterface;
use VanillaTests\InternalClient;

/**
 * Local job for with access to elasticsearch and an internal API client.
 */
abstract class AbstractLocalElasticJob implements LocalJobInterface {

    /** @var AbstractElasticHttpClient */
    protected $elasticClient;

    /** @var HttpClient */
    protected $vanillaClient;

    /**
     * Local job for updating individual requests in elasticsearch.
     *
     * @param AbstractElasticHttpClient $elasticClient
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $config
     */
    public function __construct(AbstractElasticHttpClient $elasticClient, InternalClient $internalClient, ConfigurationInterface $config) {
        $this->elasticClient = $elasticClient;

        // Make an internal http client.
        $internalClient->setBaseUrl('');
        $internalClient->setUserID($config->get('Garden.SystemUserID'));
        $internalClient->setThrowExceptions(true);
        $this->vanillaClient = $internalClient;
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