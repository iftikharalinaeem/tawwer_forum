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
        $count = $expandedResource['crawl']['count'];
        $min = $expandedResource['crawl']['min'];
        $max = $expandedResource['crawl']['max'];

        $continue = true;

        // Add pagination and limit to original request
        $separator = strpos($url, '?') === false ? '?' : '&';
        $url = "{$url}{$separator}{$parameter}={$min}..{$max}&page=1&limit=".self::API_LIMIT;

        while ($continue) {
            $response = $this->vanillaClient->get($url);
            $responseCode = $response->getStatusCode();
            if ($responseCode !== 200) {
                $msg = "Couldn't get records, received a {$responseCode} response code.";
                throw new Exception($this, $msg);
            }
            $records = $response->getBody();

            // There is currently a problem where the <Link> response header of vanilla sometimes contains a "next" page even though there is no more records to be shown
            if (empty($records)) {
                break;
            }

            $records = $this->prepareRecordsForMS($type, $parameter, $records);
            $this->indexRecords($records);

            // Check the response header we got from vanilla and see if we need to make a subsequent call
            $url = $this->subsequentRequest($response);

            if (empty($url)) {
                $continue = false;
            }
        }
    }

    /**
     * Takes an array of records of the same type and adds what the microservice is expecting to properly index the records
     *
     * @param string $type
     * @param string $field
     * @param array $records
     * @return array
     */
    protected function prepareRecordsForMS(string $type, string $field, array $records): array {

        // Resource type prepended by {{idx: and appended with }}
        $formattedRecords['indexAlias'] = "{{idx:$type}}";
        // Record unique identifier
        $formattedRecords['documentIdField'] = $field;
        // Array of the records we are indexing
        $formattedRecords['documents'] = $records;

        return $formattedRecords;
    }

    /**
     * Calls the microservice to index the records
     *
     * @param array $records
     * @throws Exception If we the request to the MS failed.
     */
    protected function indexRecords(array $records) {
        $response = $this->elasticClient->bulkIndexDocuments($records);

        $responseCode = $response->getStatusCode();
        if ($responseCode !== 201) {
            $msg = "Error indexing records through the microservice, received a {$responseCode} response code.";
            throw new Exception($this, $msg);
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
