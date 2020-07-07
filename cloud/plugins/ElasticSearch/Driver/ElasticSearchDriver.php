<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cloud\ElasticSearch\Driver;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Cloud\ElasticSearch\Http\ElasticHttpClient;
use Vanilla\Search\AbstractSearchDriver;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResults;

/**
 * Elasticsearch search driver.
 */
class ElasticSearchDriver extends AbstractSearchDriver {

    const MAX_RESULTS = 1000;

    /** @var SearchRecordTypeProviderInterface */
    private $searchTypeRecordProvider;


    /** @var ConfigurationInterface $config */
    private $config;

    /** @var ElasticHttpClient $elastic */
    private $elastic;

    /**
     * DI.
     *
     * @param SearchRecordTypeProviderInterface $searchRecordProvider
     */
    public function __construct(
        SearchRecordTypeProviderInterface $searchRecordProvider,
        ConfigurationInterface $config,
        ElasticHttpClient $elastic
    ) {
        $this->searchTypeRecordProvider = $searchRecordProvider;
        $this->config = $config;
        $this->elastic  = $elastic;
    }

    /**
     * Perform a search.
     *
     * @param array $queryData The query to search for.
     * @param SearchOptions $options Options for the query.
     *
     * @return SearchResults
     */
    public function search(array $queryData, SearchOptions $options): SearchResults {
        $query = new ElasticSearchQuery($this->getSearchTypes(), $queryData);
        $records = [];
        $indexes = $query->getIndexes();
        if(!empty($indexes)) {
            $search = $this->elastic->searchDocuments(
                $indexes,
                $query->getPayload()
            )->getBody();

            $results = $search['result']['hits']['hits'] ?? [];

            foreach ($results as $result) {
                $records[] = $query->prepareResultItem($result);
            }
        }

        $search = $this->convertRecordsToResultItems($records);
        return new SearchResults(
            $search,
            count($search),
            $options->getOffset(),
            $options->getLimit()
        );
    }

}
