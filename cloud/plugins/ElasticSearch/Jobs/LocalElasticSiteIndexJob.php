<?php
/**
 * @author Francis Caisse <francisc.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch;

use Garden\Http\HttpResponse;
use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use \Exception;
use \Throwable;
use Vanilla\Web\Pagination\ApiPaginationIterator;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Local job for handling deleting of records from elasticsearch.
 */
class LocalElasticSiteIndexJob extends AbstractLocalElasticJob {
    /**
     * @var $message
     */
    protected $message;

    /**
     * @var $resourceApiUrl
     */
    protected $resourceApiUrl;

    /**
     * @const API_LIMIT
     */
    protected const API_LIMIT = 100;

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        try {
            // Get the resource list from the forum
            $resources = $this->getResources($this->resourceApiUrl);
            foreach ($resources as $resource) {
                // Expand each of the resource and index them
                $expandedResource = $this->getResource($resource);
                $this->indexResource($expandedResource);
            }
        } catch (Throwable $t) {
            logException($t);
            return JobExecutionStatus::error();
        }

        return JobExecutionStatus::complete();
    }

    /**
     * @param array $message
     */
    public function setMessage(array $message) {
        $schema = Schema::parse([
            'resourceApiUrl:s',
        ]);

        $message = $schema->validate($message);

        $this->resourceApiUrl = $message['resourceApiUrl'];
    }

    /**
     * Get resources from the api based on the received URL
     *
     * @param string $url
     * @return array
     * @throws Exception If we can't get the resources from vanilla.
     */
    protected function getResources(string $url): array {
        $response = $this->vanillaClient->get($url);
        $responseCode = $response->getStatusCode();
        if ($responseCode !== 200) {
            $msg = "Couldn't get resources, received a {$responseCode} response code.";
            throw new Exception($this, $msg);
        }
        return $response->getBody();
    }

    /**
     * Fetch vanilla resource using the expand=crawl parameter and value
     *
     * @param array $resource
     * @return array
     * @throws Exception If we can't get the resource from vanilla.
     */
    protected function getResource(array $resource): array {
        $response = $this->vanillaClient->get("{$resource['url']}?expand=crawl");

        $responseCode = $response->getStatusCode();
        if ($responseCode !== 200) {
            $msg = "Couldn't get resources from Vanilla, received a {$responseCode} response code.";
            throw new Exception($this, $msg);
        }

        return $response->getBody();
    }

    /**
     * Crawl the expanded resource and index it
     *
     * @param array $expandedResource
     * @throws Exception If we can't get the records from Vanilla's API.
     */
    protected function indexResource(array $expandedResource) {
        // resource info
        $type = $expandedResource['recordType'];
        $url = $expandedResource['crawl']['url'];
        $parameter = $expandedResource['crawl']['parameter'];
        $min = $expandedResource['crawl']['min'];
        $max = $expandedResource['crawl']['max'];

        // Add pagination and limit to original request
        $separator = strpos($url, '?') === false ? '?' : '&';
        $url = "{$url}{$separator}{$parameter}={$min}..{$max}&page=1&limit=".self::API_LIMIT;

        $emptyPageCount = 0;

        $iterator = new ApiPaginationIterator($this->vanillaClient, $url);
        foreach ($iterator as $records) {
            if (empty($records)) {
                if ($emptyPageCount > 5) {
                    trigger_error("Local elastic indexer encounter 5+ empty pages in a row. This may indicate a bug", E_USER_WARNING);
                }
                $emptyPageCount++;
                continue;
            }

            $this->elasticClient->indexDocuments($type, $parameter, $records);
        }
    }

    /**
     * Check if we need to make a subsequent request
     * If we do it returns the URL
     * If we don't it returns an empty string
     *
     * @param HttpResponse $response
     * @return string
     * @throws Exception If the response didn't include a Link header.
     */
    protected function subsequentRequest(HttpResponse $response): string {
        $linkHeader = $response->getHeader('Link');
        if (empty($linkHeader)) {
            throw new Exception($this, "Missing `Link` response header");
        }

        $result = WebLinking::parseLinkHeaders($linkHeader);

        return $result['next'] ?? '';
    }
}
