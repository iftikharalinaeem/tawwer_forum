<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Http\HttpClient;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Contracts\Search\ElasticServiceClient;

/**
 * Elasticsearch search driver.
 */
class ElasticSearchDriver extends AbstractSearchDriver {

    const MAX_RESULTS = 1000;

    /** @var SearchRecordTypeProviderInterface */
    private $searchTypeRecordProvider;


    /** @var ConfigurationInterface $config */
    private $config;

    /** @var ElasticServiceClient $elastic */
    private $elastic;

    /**
     * DI.
     *
     * @param SearchRecordTypeProviderInterface $searchRecordProvider
     */
    public function __construct(
        SearchRecordTypeProviderInterface $searchRecordProvider,
        ConfigurationInterface $config,
        ElasticServiceClient $elastic
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

        $searchPayload = $query->getPayload();

        $search = $this->query($searchPayload);

        $search = $this->convertRecordsToResultItems($search);
        return new SearchResults(
            $search,
            count($search),
            $options->getOffset(),
            $options->getLimit()
        );
    }

    public function query(array $payload): array {
        $response = $this->elastic->search($payload);
        return $response;
    }
}
